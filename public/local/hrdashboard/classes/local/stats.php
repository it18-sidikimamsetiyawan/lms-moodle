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

use cache;

require_once(__DIR__ . '/../../../../lib/completionlib.php');

/**
 * Builds the learning matrix (employee x course) for one HR scope.
 *
 * One build is a fixed number of set-based SQL queries (never one query per
 * user or per course) followed by PHP aggregation. The result is a plain
 * array so it can be stored in MUC; every HR page then filters and sums in
 * memory. Definitions deliberately mirror Moodle core:
 *
 *   tracked activity  = course module with completion tracking, visible,
 *                       not being deleted (as completion_info::get_activities
 *                       filtered by is_visible_on_course_page);
 *   completed activity= completionstate COMPLETE or COMPLETE_PASS
 *                       (as completion_info::count_modules_completed);
 *   tracked learner   = active enrolment + a role listed in $CFG->gradebookroles
 *                       (as completion_info::is_tracked_user);
 *   completed course  = course_completions.timecompleted, or every tracked
 *                       activity completed when the course defines no criteria;
 *   score             = course total grade as a percentage of its range.
 *
 * Known approximation: per-user group/grouping restrictions on activities are
 * not evaluated here (core's progress bar does), so a learner excluded from an
 * activity by group may show a slightly lower percentage than on the course
 * page. The drill-down pages use the core API and are exact.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stats {

    /**
     * Cached matrix for a scope, rebuilt when older than the configured TTL.
     *
     * @param scope $scope
     * @param bool $force Rebuild regardless of cache.
     * @return array
     */
    public static function get(scope $scope, bool $force = false): array {
        $ttl = config::cachettl();
        $cache = cache::make('local_hrdashboard', 'stats');
        $key = $scope->signature();

        if (!$force && $ttl > 0) {
            $data = $cache->get($key);
            if (is_array($data) && !empty($data['generated']) && $data['generated'] > time() - $ttl) {
                return $data;
            }
        }

        $data = self::build($scope);
        if ($ttl > 0) {
            $cache->set($key, $data);
        }
        return $data;
    }

    /**
     * Drop every cached matrix.
     */
    public static function purge(): void {
        cache::make('local_hrdashboard', 'stats')->purge();
    }

    /**
     * Build the matrix from the database.
     *
     * @param scope $scope
     * @return array {generated, courses, users, rows, trend, recent}
     */
    public static function build(scope $scope): array {
        global $DB, $CFG;

        $now = time();
        $data = [
            'generated' => $now,
            'courses' => [],
            'users' => [],
            'rows' => [],
            'trend' => [],
            'recent' => [],
        ];

        $courseids = $scope->course_ids();
        $users = self::load_users($scope);
        $data['users'] = $users;
        if (empty($courseids)) {
            return $data;
        }
        [$cin, $cparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');

        // 1. Courses.
        $courses = $DB->get_records_sql(
            "SELECT c.id, c.fullname, c.shortname, c.category, c.visible, c.startdate, c.enddate, cc.name AS categoryname
               FROM {course} c
               JOIN {course_categories} cc ON cc.id = c.category
              WHERE c.id $cin
           ORDER BY c.sortorder", $cparams);
        foreach ($courses as $course) {
            $ctx = \context_course::instance($course->id);
            $data['courses'][(int) $course->id] = [
                'id' => (int) $course->id,
                'fullname' => format_string($course->fullname, true, ['context' => $ctx]),
                'shortname' => format_string($course->shortname, true, ['context' => $ctx]),
                'category' => (int) $course->category,
                'categoryname' => format_string($course->categoryname, true, ['context' => \context_coursecat::instance($course->category)]),
                'visible' => (int) $course->visible,
                'startdate' => (int) $course->startdate,
                'enddate' => (int) $course->enddate,
                'tracked' => 0,
                'cms' => [],
            ];
        }

        // 2. Tracked course modules, their names, order and due dates.
        $cms = $DB->get_records_sql(
            "SELECT cm.id, cm.course, cm.instance, cm.completionexpected, cm.section, m.name AS modname
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course $cin AND cm.completion > 0 AND cm.visible = 1 AND cm.deletioninprogress = 0", $cparams);
        $names = self::load_names($cms);
        $order = self::load_order($cin, $cparams);
        $due = duedates::for_cms($cms);
        $duecms = [];
        foreach ($cms as $cm) {
            $courseid = (int) $cm->course;
            if (!isset($data['courses'][$courseid])) {
                continue;
            }
            $data['courses'][$courseid]['cms'][(int) $cm->id] = [
                'id' => (int) $cm->id,
                'name' => $names[$cm->modname][(int) $cm->instance] ?? get_string('modulename', $cm->modname),
                'modname' => $cm->modname,
                'due' => $due[(int) $cm->id] ?? 0,
                'order' => $order[(int) $cm->id] ?? PHP_INT_MAX,
            ];
            $data['courses'][$courseid]['tracked']++;
            if (!empty($due[(int) $cm->id])) {
                $duecms[(int) $cm->id] = $courseid;
            }
        }
        foreach ($data['courses'] as &$course) {
            uasort($course['cms'], fn($a, $b) => $a['order'] <=> $b['order']);
        }
        unset($course);

        // 3. Active, tracked enrolments.
        $roleids = array_filter(array_map('intval', explode(',', (string) $CFG->gradebookroles)));
        $rolesql = '';
        $rparams = [];
        if (!empty($roleids)) {
            [$rin, $rparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
            $rolesql = "AND EXISTS (SELECT 1
                                      FROM {role_assignments} ra
                                      JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :courselevel
                                     WHERE ctx.instanceid = e.courseid AND ra.userid = ue.userid AND ra.roleid $rin)";
            $rparams['courselevel'] = CONTEXT_COURSE;
        }
        $enrolments = $DB->get_recordset_sql(
            "SELECT ue.userid, e.courseid, MIN(ue.timecreated) AS timeenrolled
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid $cin AND ue.status = :active AND e.status = :enabled $rolesql
           GROUP BY ue.userid, e.courseid",
            $cparams + $rparams + ['active' => ENROL_USER_ACTIVE, 'enabled' => ENROL_INSTANCE_ENABLED]);
        $rows = [];
        foreach ($enrolments as $e) {
            $uid = (int) $e->userid;
            $cid = (int) $e->courseid;
            if (!isset($users[$uid]) || !isset($data['courses'][$cid])) {
                continue;
            }
            $rows["$uid:$cid"] = [
                'userid' => $uid,
                'courseid' => $cid,
                'timeenrolled' => (int) $e->timeenrolled,
                'tracked' => $data['courses'][$cid]['tracked'],
                'done' => 0,
                'progress' => null,
                'completed' => false,
                'timecompleted' => 0,
                'score' => null,
                'lastaccess' => 0,
                'lastcompletion' => 0,
                'lastactivity' => 0,
                'pending' => 0,
                'overdue' => 0,
                'nextdue' => 0,
                'status' => 'notstarted',
                'risk' => [],
            ];
        }
        $enrolments->close();

        $states = [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS];
        [$sin, $sparams] = $DB->get_in_or_equal($states, SQL_PARAMS_NAMED, 's');

        // 4. Completed activities per enrolment.
        $completions = $DB->get_recordset_sql(
            "SELECT cmc.userid, cm.course, COUNT(1) AS done, MAX(cmc.timemodified) AS lastcompletion
               FROM {course_modules_completion} cmc
               JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
              WHERE cm.course $cin AND cm.completion > 0 AND cm.visible = 1 AND cm.deletioninprogress = 0
                    AND cmc.completionstate $sin
           GROUP BY cmc.userid, cm.course", $cparams + $sparams);
        foreach ($completions as $c) {
            $key = (int) $c->userid . ':' . (int) $c->course;
            if (isset($rows[$key])) {
                $rows[$key]['done'] = (int) $c->done;
                $rows[$key]['lastcompletion'] = (int) $c->lastcompletion;
            }
        }
        $completions->close();

        // 5. Completion state of activities that have a due date (for overdue / next due).
        $duedone = [];
        if (!empty($duecms)) {
            [$din, $dparams] = $DB->get_in_or_equal(array_keys($duecms), SQL_PARAMS_NAMED, 'd');
            $rs = $DB->get_recordset_sql(
                "SELECT cmc.id, cmc.userid, cmc.coursemoduleid
                   FROM {course_modules_completion} cmc
                  WHERE cmc.coursemoduleid $din AND cmc.completionstate $sin", $dparams + $sparams);
            foreach ($rs as $r) {
                $duedone[(int) $r->userid][(int) $r->coursemoduleid] = true;
            }
            $rs->close();
        }

        // 6. Course completions.
        $cc = $DB->get_recordset_sql(
            "SELECT id, userid, course, timecompleted FROM {course_completions}
              WHERE course $cin AND timecompleted > 0", $cparams);
        foreach ($cc as $c) {
            $key = (int) $c->userid . ':' . (int) $c->course;
            if (isset($rows[$key])) {
                $rows[$key]['completed'] = true;
                $rows[$key]['timecompleted'] = (int) $c->timecompleted;
            }
        }
        $cc->close();

        // 7. Course total grades.
        $grades = $DB->get_recordset_sql(
            "SELECT gg.id, gg.userid, gi.courseid, gg.finalgrade, gi.grademax, gi.grademin
               FROM {grade_items} gi
               JOIN {grade_grades} gg ON gg.itemid = gi.id
              WHERE gi.itemtype = 'course' AND gi.courseid $cin AND gg.finalgrade IS NOT NULL", $cparams);
        foreach ($grades as $g) {
            $key = (int) $g->userid . ':' . (int) $g->courseid;
            $range = (float) $g->grademax - (float) $g->grademin;
            if (isset($rows[$key]) && $range > 0) {
                $rows[$key]['score'] = round(((float) $g->finalgrade - (float) $g->grademin) / $range * 100, 1);
            }
        }
        $grades->close();

        // 8. Last access per course.
        $access = $DB->get_recordset_sql(
            "SELECT id, userid, courseid, timeaccess FROM {user_lastaccess} WHERE courseid $cin", $cparams);
        foreach ($access as $a) {
            $key = (int) $a->userid . ':' . (int) $a->courseid;
            if (isset($rows[$key])) {
                $rows[$key]['lastaccess'] = (int) $a->timeaccess;
            }
        }
        $access->close();

        // 9. Derive per-row figures, status and risk.
        foreach ($rows as &$row) {
            $course = $data['courses'][$row['courseid']];
            $user = $users[$row['userid']];
            if ($row['tracked'] > 0) {
                $row['done'] = min($row['done'], $row['tracked']);
                $row['progress'] = (int) round($row['done'] / $row['tracked'] * 100);
                if (!$row['completed'] && $row['done'] >= $row['tracked']) {
                    $row['completed'] = true;
                    $row['timecompleted'] = $row['lastcompletion'];
                }
            }
            if ($row['completed']) {
                $row['progress'] = 100;
            }
            $row['pending'] = max(0, $row['tracked'] - $row['done']);
            $row['lastactivity'] = max($row['lastaccess'], $row['lastcompletion']);

            foreach ($course['cms'] as $cm) {
                if (empty($cm['due']) || !empty($duedone[$row['userid']][$cm['id']])) {
                    continue;
                }
                if ($cm['due'] < $now) {
                    $row['overdue']++;
                } else if (!$row['nextdue'] || $cm['due'] < $row['nextdue']) {
                    $row['nextdue'] = $cm['due'];
                }
            }
            if ($row['completed']) {
                $row['overdue'] = 0;
                $row['nextdue'] = 0;
            }

            $row['risk'] = risk::evaluate($row, $user, $course, $now);
            $row['status'] = risk::status($row, $row['risk']);
        }
        unset($row);
        $data['rows'] = $rows;

        // 10. Completion trend (per week) and most recent completions.
        $data['trend'] = self::load_trend($cin, $cparams, $sin, $sparams, $now);
        $data['recent'] = self::load_recent($cin, $cparams, $sin, $sparams, $users, $data['courses']);

        return $data;
    }

    /**
     * The employee population with name fields and configured attributes.
     *
     * @param scope $scope
     * @return array userid => user array
     */
    protected static function load_users(scope $scope): array {
        global $DB;

        [$where, $params] = $scope->employee_where();
        $namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;

        $joins = '';
        $selects = '';
        $attributes = config::attributes();
        $i = 0;
        foreach ($attributes as $attribute => $source) {
            if ($source === 'department' || $source === 'institution') {
                $selects .= ", u.$source AS attr_$attribute";
            } else if (strpos($source, 'profile_') === 0) {
                $shortname = substr($source, 8);
                $fieldid = (int) $DB->get_field('user_info_field', 'id', ['shortname' => $shortname]);
                $i++;
                $joins .= " LEFT JOIN {user_info_data} d$i ON d$i.userid = u.id AND d$i.fieldid = :fieldid$i";
                $params["fieldid$i"] = $fieldid;
                $selects .= ", d$i.data AS attr_$attribute";
            }
        }

        $rs = $DB->get_recordset_sql(
            "SELECT u.id, $namefields, u.lastaccess, u.suspended, u.timecreated, u.department, u.institution $selects
               FROM {user} u $joins
              WHERE $where", $params);
        $users = [];
        foreach ($rs as $u) {
            $user = [
                'id' => (int) $u->id,
                'fullname' => fullname($u),
                'lastaccess' => (int) $u->lastaccess,
                'suspended' => (int) $u->suspended,
                'timecreated' => (int) $u->timecreated,
            ];
            foreach ($attributes as $attribute => $source) {
                $prop = 'attr_' . $attribute;
                $user[$attribute] = trim((string) ($u->$prop ?? ''));
            }
            $users[(int) $u->id] = $user;
        }
        $rs->close();
        return $users;
    }

    /**
     * Activity names, grouped by module type (one query per installed type).
     *
     * @param array $cms
     * @return array modname => [instance => name]
     */
    protected static function load_names(array $cms): array {
        global $DB;
        $bymod = [];
        foreach ($cms as $cm) {
            $bymod[$cm->modname][] = (int) $cm->instance;
        }
        $names = [];
        foreach ($bymod as $modname => $instances) {
            if (!\core_component::get_component_directory('mod_' . $modname)) {
                continue;
            }
            [$in, $params] = $DB->get_in_or_equal($instances, SQL_PARAMS_NAMED);
            $records = $DB->get_records_select_menu($modname, "id $in", $params, '', 'id, name');
            foreach ($records as $id => $name) {
                $names[$modname][(int) $id] = format_string($name, true);
            }
        }
        return $names;
    }

    /**
     * Position of every course module on its course page.
     *
     * @param string $cin
     * @param array $cparams
     * @return array cmid => sortable integer
     */
    protected static function load_order(string $cin, array $cparams): array {
        global $DB;
        $order = [];
        $sections = $DB->get_records_sql(
            "SELECT id, course, section, sequence FROM {course_sections} WHERE course $cin ORDER BY course, section", $cparams);
        foreach ($sections as $section) {
            $pos = 0;
            foreach (explode(',', (string) $section->sequence) as $cmid) {
                if ($cmid !== '') {
                    $order[(int) $cmid] = ((int) $section->section) * 10000 + $pos++;
                }
            }
        }
        return $order;
    }

    /**
     * Activities and courses completed per week over the configured window.
     *
     * @param string $cin
     * @param array $cparams
     * @param string $sin
     * @param array $sparams
     * @param int $now
     * @return array List of ['start' => ts, 'activities' => n, 'courses' => n]
     */
    protected static function load_trend(string $cin, array $cparams, string $sin, array $sparams, int $now): array {
        global $DB;

        $weeks = config::trendweeks();
        $info = usergetdate($now);
        $monday = usergetmidnight($now) - (($info['wday'] + 6) % 7) * DAYSECS;
        $base = $monday - ($weeks - 1) * WEEKSECS;

        $trend = [];
        for ($w = 0; $w < $weeks; $w++) {
            $trend[$w] = ['start' => $base + $w * WEEKSECS, 'activities' => 0, 'courses' => 0];
        }

        // Moodle requires every named placeholder to be unique, hence base1/base2/base3.
        $rs = $DB->get_recordset_sql(
            "SELECT FLOOR((cmc.timemodified - :base1) / " . WEEKSECS . ") AS wk, COUNT(1) AS n
               FROM {course_modules_completion} cmc
               JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
              WHERE cm.course $cin AND cmc.completionstate $sin AND cmc.timemodified >= :base2
           GROUP BY FLOOR((cmc.timemodified - :base3) / " . WEEKSECS . ")",
            $cparams + $sparams + ['base1' => $base, 'base2' => $base, 'base3' => $base]);
        foreach ($rs as $r) {
            if (isset($trend[(int) $r->wk])) {
                $trend[(int) $r->wk]['activities'] = (int) $r->n;
            }
        }
        $rs->close();

        $rs = $DB->get_recordset_sql(
            "SELECT FLOOR((cc.timecompleted - :base1) / " . WEEKSECS . ") AS wk, COUNT(1) AS n
               FROM {course_completions} cc
              WHERE cc.course $cin AND cc.timecompleted >= :base2
           GROUP BY FLOOR((cc.timecompleted - :base3) / " . WEEKSECS . ")",
            $cparams + ['base1' => $base, 'base2' => $base, 'base3' => $base]);
        foreach ($rs as $r) {
            if (isset($trend[(int) $r->wk])) {
                $trend[(int) $r->wk]['courses'] = (int) $r->n;
            }
        }
        $rs->close();

        return array_values($trend);
    }

    /**
     * The most recent activity completions inside the scope.
     *
     * @param string $cin
     * @param array $cparams
     * @param string $sin
     * @param array $sparams
     * @param array $users
     * @param array $courses
     * @return array
     */
    protected static function load_recent(string $cin, array $cparams, string $sin, array $sparams,
            array $users, array $courses): array {
        global $DB;

        $limit = 20;
        $rs = $DB->get_recordset_sql(
            "SELECT cmc.id, cmc.userid, cm.course, cm.id AS cmid, cmc.timemodified
               FROM {course_modules_completion} cmc
               JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
              WHERE cm.course $cin AND cmc.completionstate $sin AND cm.visible = 1 AND cm.deletioninprogress = 0
           ORDER BY cmc.timemodified DESC", $cparams + $sparams, 0, $limit * 3);
        $recent = [];
        foreach ($rs as $r) {
            $uid = (int) $r->userid;
            $cid = (int) $r->course;
            if (!isset($users[$uid]) || !isset($courses[$cid]['cms'][(int) $r->cmid])) {
                continue;
            }
            $recent[] = [
                'userid' => $uid,
                'courseid' => $cid,
                'cmid' => (int) $r->cmid,
                'time' => (int) $r->timemodified,
            ];
            if (count($recent) >= $limit) {
                break;
            }
        }
        $rs->close();
        return $recent;
    }
}
