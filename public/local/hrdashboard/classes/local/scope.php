<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_hrdashboard\local;

use context;
use context_coursecat;
use context_system;

/**
 * What an HR user is allowed to see.
 *
 * A scope is a set of course categories. It is the intersection of
 *   - the categories in which the user holds local/hrdashboard:viewhr
 *     (system context = every category; a category context = that category
 *     and, through context inheritance, its sub-categories), and
 *   - the administrator's "Course category scope" setting (empty = all).
 *
 * Every HR query in the plugin starts from scope::course_ids(), so a course
 * outside the scope - and a learner enrolled only in such courses - is never
 * read from the database, let alone displayed.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scope {

    /** @var int[] Category ids (including descendants) the scope covers. */
    protected array $categoryids;

    /** @var bool True when viewhr is held at system level. */
    protected bool $systemwide;

    /** @var int[]|null Memoised course ids. */
    protected ?array $courseids = null;

    /** @var int[]|null Memoised employee ids. */
    protected ?array $employeeids = null;

    /**
     * Constructor.
     *
     * @param int[] $categoryids
     * @param bool $systemwide
     */
    protected function __construct(array $categoryids, bool $systemwide) {
        $this->categoryids = array_values(array_unique(array_map('intval', $categoryids)));
        sort($this->categoryids);
        $this->systemwide = $systemwide;
    }

    /**
     * Resolve the HR scope of a user, or null when the user has no HR access at all.
     *
     * @param int $userid 0 = current user
     * @return scope|null
     */
    public static function for_user(int $userid = 0): ?scope {
        global $USER;
        $userid = $userid ?: (int) $USER->id;
        if (!$userid || isguestuser($userid)) {
            return null;
        }

        $all = self::all_categories();
        $configured = self::expand_with_descendants(config::categoryscope(), $all);

        if (has_capability('local/hrdashboard:viewhr', context_system::instance(), $userid)) {
            $allowed = array_keys($all);
            $cats = $configured === null ? $allowed : array_intersect($allowed, $configured);
            return new self($cats, true);
        }

        $granted = [];
        foreach ($all as $catid => $cat) {
            if (has_capability('local/hrdashboard:viewhr', context_coursecat::instance($catid), $userid)) {
                $granted[] = $catid;
            }
        }
        if (empty($granted)) {
            return null;
        }
        $cats = $configured === null ? $granted : array_intersect($granted, $configured);
        if (empty($cats)) {
            return null;
        }
        return new self($cats, false);
    }

    /**
     * The site-wide scope (used by the scheduled task and by administrators).
     *
     * @return scope
     */
    public static function all(): scope {
        $all = self::all_categories();
        $configured = self::expand_with_descendants(config::categoryscope(), $all);
        $cats = $configured === null ? array_keys($all) : array_intersect(array_keys($all), $configured);
        return new self($cats, true);
    }

    /**
     * Whether a user may open the HR dashboard at all.
     *
     * @param int $userid
     * @return bool
     */
    public static function user_can_view_hr(int $userid = 0): bool {
        return self::for_user($userid) !== null;
    }

    /**
     * Whether the HR user may export.
     *
     * @param int $userid
     * @return bool
     */
    public function can_export(int $userid = 0): bool {
        global $USER;
        $userid = $userid ?: (int) $USER->id;
        if (has_capability('local/hrdashboard:export', context_system::instance(), $userid)) {
            return true;
        }
        foreach ($this->categoryids as $catid) {
            if (has_capability('local/hrdashboard:export', context_coursecat::instance($catid), $userid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The context to use for require_capability() and $PAGE->set_context().
     *
     * @return context
     */
    public function context(): context {
        if ($this->systemwide || empty($this->categoryids)) {
            return context_system::instance();
        }
        return context_coursecat::instance(reset($this->categoryids));
    }

    /** @return bool */
    public function is_systemwide(): bool {
        return $this->systemwide;
    }

    /** @return int[] */
    public function category_ids(): array {
        return $this->categoryids;
    }

    /**
     * Cache key: same categories + same settings = same matrix.
     *
     * @return string
     */
    public function signature(): string {
        return md5(implode(',', $this->categoryids) . '|' . config::signature());
    }

    /**
     * Ids of the monitored courses: in scope, completion enabled, visible
     * unless the administrator chose to include hidden courses.
     *
     * @return int[]
     */
    public function course_ids(): array {
        global $DB;
        if ($this->courseids !== null) {
            return $this->courseids;
        }
        if (empty($this->categoryids)) {
            return $this->courseids = [];
        }
        [$insql, $params] = $DB->get_in_or_equal($this->categoryids, SQL_PARAMS_NAMED, 'cat');
        $sql = "SELECT c.id
                  FROM {course} c
                 WHERE c.category $insql
                   AND c.id <> :siteid
                   AND c.enablecompletion = 1";
        $params['siteid'] = SITEID;
        if (!config::includehidden()) {
            $sql .= " AND c.visible = 1";
        }
        return $this->courseids = array_map('intval', array_keys($DB->get_records_sql($sql, $params)));
    }

    /**
     * @param int $courseid
     * @return bool
     */
    public function contains_course(int $courseid): bool {
        return in_array($courseid, $this->course_ids(), true);
    }

    /**
     * SQL fragment selecting the employee population (alias "u" for {user}).
     *
     * Site administrators, the guest user, deleted and unconfirmed accounts and
     * accounts that cannot log in (nologin / webservice auth) are never employees.
     *
     * @return array [string $where, array $params]
     */
    public function employee_where(): array {
        global $DB, $CFG;

        $where = "u.deleted = 0 AND u.confirmed = 1 AND u.id <> :guestid AND u.auth <> 'nologin' AND u.auth <> 'webservice'";
        $params = ['guestid' => (int) $CFG->siteguest];

        $admins = array_filter(array_map('intval', explode(',', (string) $CFG->siteadmins)));
        if (!empty($admins)) {
            [$notin, $aparams] = $DB->get_in_or_equal($admins, SQL_PARAMS_NAMED, 'adm', false);
            $where .= " AND u.id $notin";
            $params += $aparams;
        }

        switch (config::employeesource()) {
            case config::SOURCE_COHORT:
                $cohortid = (int) $DB->get_field('cohort', 'id', ['idnumber' => config::employeecohort()]);
                $where .= " AND u.id IN (SELECT cm.userid FROM {cohort_members} cm WHERE cm.cohortid = :cohortid)";
                $params['cohortid'] = $cohortid; // 0 when missing: an empty population, never "everyone".
                break;

            case config::SOURCE_ENROLLED:
                $courseids = $this->course_ids();
                if (empty($courseids)) {
                    $where .= " AND 1 = 0";
                    break;
                }
                [$insql, $cparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'ec');
                $where .= " AND u.id IN (SELECT ue.userid
                                           FROM {user_enrolments} ue
                                           JOIN {enrol} e ON e.id = ue.enrolid
                                          WHERE e.courseid $insql)";
                $params += $cparams;
                break;

            case config::SOURCE_ALL:
            default:
                break;
        }

        return [$where, $params];
    }

    /**
     * Whether a user belongs to this scope: part of the employee population
     * or enrolled in a monitored course.
     *
     * @param int $userid
     * @return bool
     */
    public function contains_user(int $userid): bool {
        global $DB;
        [$where, $params] = $this->employee_where();
        $params['uid'] = $userid;
        if ($DB->record_exists_sql("SELECT u.id FROM {user} u WHERE u.id = :uid AND $where", $params)) {
            return true;
        }
        $courseids = $this->course_ids();
        if (empty($courseids)) {
            return false;
        }
        [$insql, $cparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
        $cparams['uid'] = $userid;
        return $DB->record_exists_sql(
            "SELECT ue.id FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid
              WHERE ue.userid = :uid AND e.courseid $insql", $cparams);
    }

    /**
     * All categories, id => (id, parent, path).
     *
     * @return array
     */
    protected static function all_categories(): array {
        global $DB;
        return $DB->get_records('course_categories', null, 'sortorder', 'id, parent, path');
    }

    /**
     * Expand a list of category ids with every descendant.
     *
     * @param int[] $ids
     * @param array $all id => category
     * @return int[]|null null when $ids is empty (= no restriction)
     */
    protected static function expand_with_descendants(array $ids, array $all): ?array {
        if (empty($ids)) {
            return null;
        }
        $out = [];
        foreach ($all as $cat) {
            foreach ($ids as $id) {
                if ((int) $cat->id === (int) $id || strpos($cat->path . '/', '/' . $id . '/') !== false) {
                    $out[] = (int) $cat->id;
                    break;
                }
            }
        }
        return $out;
    }
}
