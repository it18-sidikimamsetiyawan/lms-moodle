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

use completion_info;
use core_course\external\course_summary_exporter;
use core\url;

/**
 * Everything the personal "My Learning" dashboard shows for one learner.
 *
 * Computed live with the core completion API: one learner has a handful of
 * courses, so this is cheap and always exact. Only the learner's own data is
 * ever read.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class employee {

    /** @var int */
    protected int $userid;

    /** @var int */
    protected int $now;

    /** @var array courseid => cm_info[] cache. */
    protected array $modinfos = [];

    /**
     * Constructor.
     *
     * @param int $userid
     */
    public function __construct(int $userid) {
        $this->userid = $userid;
        $this->now = time();
    }

    /**
     * Build the dashboard data.
     *
     * @return array
     */
    public function build(): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        require_once($CFG->dirroot . '/course/lib.php');

        $courses = enrol_get_users_courses($this->userid, true,
            ['enablecompletion', 'enddate', 'startdate', 'visible', 'summary', 'summaryformat', 'cacherev', 'category']);
        unset($courses[SITEID]);

        $list = [];
        $pendingcms = [];
        $lastaccess = $this->last_access();
        $completedactivities = 0;

        foreach ($courses as $course) {
            if (!$course->visible) {
                continue;
            }
            $entry = [
                'id' => (int) $course->id,
                'fullname' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
                'shortname' => format_string($course->shortname, true, ['context' => \context_course::instance($course->id)]),
                'category' => $this->category_name((int) $course->category),
                'viewurl' => (new url('/course/view.php', ['id' => $course->id]))->out(false),
                'detailurl' => (new url('/local/hrdashboard/user.php', ['id' => $this->userid, 'courseid' => $course->id]))->out(false),
                'image' => course_summary_exporter::get_course_image($course),
                'tracked' => 0,
                'done' => 0,
                'progress' => null,
                'state' => 'notstarted',
                'lastaccess' => $lastaccess[$course->id] ?? 0,
                'lastactivity' => null,
                'nextactivity' => null,
                'score' => null,
                'scorepercent' => null,
                'enddate' => (int) $course->enddate,
            ];

            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                $modinfo = get_fast_modinfo($course, $this->userid);
                $this->modinfos[(int) $course->id] = $modinfo;
                $tracked = $completion->get_user_activities_with_completion($this->userid);
                $iscomplete = $completion->is_course_complete($this->userid);
                $lastcompleted = null;

                foreach ($modinfo->get_cms() as $cm) {
                    if (!isset($tracked[$cm->id])) {
                        continue;
                    }
                    $entry['tracked']++;
                    $data = $completion->get_data($cm, true, $this->userid);
                    $complete = in_array((int) $data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true);
                    if ($complete) {
                        $entry['done']++;
                        $completedactivities++;
                        if ($lastcompleted === null || $data->timemodified > $lastcompleted['time']) {
                            $lastcompleted = ['name' => $cm->get_formatted_name(), 'time' => (int) $data->timemodified,
                                'modnamestr' => $cm->get_module_type_name()];
                        }
                        continue;
                    }
                    if (!$cm->uservisible) {
                        continue;
                    }
                    if ($entry['nextactivity'] === null) {
                        $entry['nextactivity'] = [
                            'name' => $cm->get_formatted_name(),
                            'modnamestr' => $cm->get_module_type_name(),
                            'url' => $cm->url ? $cm->url->out(false) : $entry['viewurl'],
                        ];
                    }
                    $pendingcms[] = [
                        'cm' => $cm,
                        'courseid' => (int) $course->id,
                        'coursename' => $entry['fullname'],
                        'viewed' => !empty($data->viewed),
                    ];
                }

                $entry['lastactivity'] = $lastcompleted;
                if ($iscomplete || ($entry['tracked'] && $entry['done'] >= $entry['tracked'])) {
                    $entry['state'] = 'completed';
                    $entry['progress'] = 100;
                } else {
                    $entry['progress'] = $entry['tracked'] ? (int) round($entry['done'] / $entry['tracked'] * 100) : null;
                    $entry['state'] = ($entry['done'] || $entry['lastaccess']) ? 'inprogress' : 'notstarted';
                }
            } else {
                $entry['state'] = $entry['lastaccess'] ? 'inprogress' : 'notstarted';
            }

            $this->add_score($entry, $course);
            $list[] = $entry;
        }

        $pending = $this->pending($pendingcms);
        $timeline = $this->timeline();

        $kpis = $this->kpis($list, $pending);
        $kpis['completedactivities'] = $completedactivities;

        return [
            'courses' => $list,
            'pending' => $pending,
            'timeline' => $timeline['groups'],
            'streak' => $timeline['streak'],
            'kpis' => $kpis,
        ];
    }

    /**
     * Course total grade for the learner, respecting gradebook hiding.
     *
     * @param array $entry
     * @param \stdClass $course
     */
    protected function add_score(array &$entry, \stdClass $course): void {
        $grades = grade_get_course_grades($course->id, $this->userid);
        if (empty($grades->grades[$this->userid])) {
            return;
        }
        $g = $grades->grades[$this->userid];
        if ($g->grade === null || !empty($g->hidden)) {
            return;
        }
        $entry['score'] = $g->str_grade;
        $range = (float) $grades->grademax - (float) $grades->grademin;
        if ($range > 0) {
            $entry['scorepercent'] = round(((float) $g->grade - (float) $grades->grademin) / $range * 100, 1);
        }
    }

    /**
     * Pending activities across all courses, nearest due date first.
     *
     * @param array $pendingcms
     * @return array
     */
    protected function pending(array $pendingcms): array {
        $cmlist = [];
        foreach ($pendingcms as $p) {
            $cm = $p['cm'];
            $cmlist[] = (object) ['id' => $cm->id, 'modname' => $cm->modname, 'instance' => $cm->instance,
                'completionexpected' => $cm->completionexpected];
        }
        $due = duedates::for_cms($cmlist);

        $out = [];
        foreach ($pendingcms as $p) {
            $cm = $p['cm'];
            $duets = $due[$cm->id] ?? 0;
            $out[] = [
                'cmid' => (int) $cm->id,
                'name' => $cm->get_formatted_name(),
                'modnamestr' => $cm->get_module_type_name(),
                'icon' => $cm->get_icon_url()->out(false),
                'courseid' => $p['courseid'],
                'coursename' => $p['coursename'],
                'courseurl' => (new url('/course/view.php', ['id' => $p['courseid']]))->out(false),
                'url' => $cm->url ? $cm->url->out(false) : (new url('/course/view.php', ['id' => $p['courseid']]))->out(false),
                'due' => $duets,
                'status' => $duets && $duets < $this->now ? 'overdue' : 'pending',
                'action' => $p['viewed'] ? 'continue' : 'start',
            ];
        }
        usort($out, function($a, $b) {
            // Due dates first (earliest first), then everything without one in course order.
            if ($a['due'] && $b['due']) {
                return $a['due'] <=> $b['due'];
            }
            if ($a['due'] || $b['due']) {
                return $a['due'] ? -1 : 1;
            }
            return 0;
        });
        return $out;
    }

    /**
     * Recent learning events grouped by day, plus the current daily streak.
     *
     * @return array {groups: array, streak: int}
     */
    protected function timeline(): array {
        global $DB;

        $since = usergetmidnight($this->now) - config::timelinedays() * DAYSECS;
        $viewedlike = $DB->sql_like('l.eventname', ':viewed');
        $sql = "SELECT l.id, l.eventname, l.contextinstanceid, l.contextlevel, l.courseid, l.timecreated, l.other
                  FROM {logstore_standard_log} l
                 WHERE l.timecreated >= :since
                   AND ((l.userid = :u1 AND ($viewedlike OR l.eventname = :completed))
                        OR (l.relateduserid = :u2 AND l.eventname = :cmcompletion))
              ORDER BY l.timecreated DESC";
        $params = [
            'since' => $since,
            'u1' => $this->userid,
            'u2' => $this->userid,
            'viewed' => '%course_module_viewed',
            'completed' => '\\core\\event\\course_completed',
            'cmcompletion' => '\\core\\event\\course_module_completion_updated',
        ];
        $rows = $DB->get_records_sql($sql, $params, 0, 400);

        $entries = [];
        $days = [];
        $seenviews = [];
        foreach ($rows as $row) {
            $day = usergetmidnight($row->timecreated);
            $days[$day] = true;
            $courseid = (int) $row->courseid;

            if ($row->eventname === '\\core\\event\\course_completed') {
                $name = $this->course_name($courseid);
                if ($name === null) {
                    continue;
                }
                $entries[] = ['time' => (int) $row->timecreated, 'day' => $day, 'type' => 'coursecompleted',
                    'text' => get_string('timeline:coursecompleted', 'local_hrdashboard', $name),
                    'url' => (new url('/course/view.php', ['id' => $courseid]))->out(false)];
                continue;
            }

            if ((int) $row->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = $this->cm($courseid, (int) $row->contextinstanceid);
            if ($cm === null) {
                continue;
            }
            $a = (object) ['activity' => $cm->get_formatted_name(), 'type' => $cm->get_module_type_name(),
                'course' => $this->course_name($courseid)];

            if ($row->eventname === '\\core\\event\\course_module_completion_updated') {
                $other = @json_decode($row->other, true);
                if (!is_array($other)) {
                    $other = @unserialize($row->other);
                }
                $state = (int) ($other['completionstate'] ?? 0);
                if (!in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
                    continue;
                }
                $entries[] = ['time' => (int) $row->timecreated, 'day' => $day, 'type' => 'completed',
                    'text' => get_string('timeline:completed', 'local_hrdashboard', $a),
                    'sub' => $a->course,
                    'url' => $cm->url ? $cm->url->out(false) : null];
                continue;
            }

            // A view: one entry per activity per day.
            $key = $day . ':' . $cm->id;
            if (isset($seenviews[$key])) {
                continue;
            }
            $seenviews[$key] = true;
            $entries[] = ['time' => (int) $row->timecreated, 'day' => $day, 'type' => 'viewed',
                'text' => get_string('timeline:viewed', 'local_hrdashboard', $a),
                'sub' => $a->course,
                'url' => $cm->url ? $cm->url->out(false) : null];
        }

        // Group by day.
        $groups = [];
        $today = usergetmidnight($this->now);
        foreach ($entries as $e) {
            if (!isset($groups[$e['day']])) {
                if ($e['day'] === $today) {
                    $label = get_string('today');
                } else if ($e['day'] === $today - DAYSECS) {
                    $label = get_string('yesterday', 'local_hrdashboard');
                } else {
                    $label = userdate($e['day'], get_string('strftimedate', 'langconfig'));
                }
                $groups[$e['day']] = ['label' => $label, 'entries' => []];
            }
            if (count($groups[$e['day']]['entries']) < 8) {
                $groups[$e['day']]['entries'][] = $e;
            }
        }

        // Streak: consecutive days with activity ending today or yesterday.
        $streak = 0;
        $cursor = isset($days[$today]) ? $today : $today - DAYSECS;
        while (isset($days[$cursor])) {
            $streak++;
            $cursor -= DAYSECS;
        }

        return ['groups' => array_values($groups), 'streak' => $streak];
    }

    /**
     * KPI row.
     *
     * @param array $courses
     * @param array $pending
     * @return array
     */
    protected function kpis(array $courses, array $pending): array {
        $completed = $inprogress = $notstarted = 0;
        $progresssum = $progressn = 0;
        $scoresum = $scoren = 0;
        foreach ($courses as $c) {
            if ($c['state'] === 'completed') {
                $completed++;
            } else if ($c['state'] === 'inprogress') {
                $inprogress++;
            } else {
                $notstarted++;
            }
            if ($c['progress'] !== null) {
                $progresssum += $c['progress'];
                $progressn++;
            }
            if ($c['scorepercent'] !== null) {
                $scoresum += $c['scorepercent'];
                $scoren++;
            }
        }
        return [
            'courses' => count($courses),
            'completed' => $completed,
            'inprogress' => $inprogress,
            'notstarted' => $notstarted,
            'overallprogress' => $progressn ? (int) round($progresssum / $progressn) : null,
            'avgscore' => $scoren ? round($scoresum / $scoren, 1) : null,
            'pending' => count($pending),
            'overduecount' => count(array_filter($pending, fn($p) => $p['status'] === 'overdue')),
        ];
    }

    /**
     * Course last-access times for the learner.
     *
     * @return array courseid => timestamp
     */
    protected function last_access(): array {
        global $DB;
        return $DB->get_records_menu('user_lastaccess', ['userid' => $this->userid], '', 'courseid, timeaccess');
    }

    /**
     * A course module from the already loaded modinfo, or null.
     *
     * @param int $courseid
     * @param int $cmid
     * @return \cm_info|null
     */
    protected function cm(int $courseid, int $cmid): ?\cm_info {
        if (!isset($this->modinfos[$courseid])) {
            return null;
        }
        $cms = $this->modinfos[$courseid]->get_cms();
        return $cms[$cmid] ?? null;
    }

    /**
     * Formatted category name (memoised).
     *
     * @param int $categoryid
     * @return string
     */
    protected function category_name(int $categoryid): string {
        static $names = [];
        if (!isset($names[$categoryid])) {
            $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
            $names[$categoryid] = $category ? $category->get_formatted_name() : '';
        }
        return $names[$categoryid];
    }

    /**
     * Course name from loaded modinfo, or null when the learner is no longer enrolled.
     *
     * @param int $courseid
     * @return string|null
     */
    protected function course_name(int $courseid): ?string {
        if (!isset($this->modinfos[$courseid])) {
            return null;
        }
        $course = $this->modinfos[$courseid]->get_course();
        return format_string($course->fullname, true, ['context' => \context_course::instance($courseid)]);
    }
}
