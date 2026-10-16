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

namespace local_hrdashboard\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\url;
use local_hrdashboard\local\activity_detail;

require_once(__DIR__ . '/../../../../lib/completionlib.php');
require_once(__DIR__ . '/../../../../lib/gradelib.php');
require_once(__DIR__ . '/../../../../grade/querylib.php');

/**
 * One learner's courses and, for a selected course, the activity-level detail.
 *
 * HR mode: the course list and statuses come from the HR matrix (so they match
 * the dashboard). Self mode: the list is built from the learner's own
 * enrolments with the core completion API and no risk evaluation.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_detail implements renderable, templatable {

    /** @var \stdClass */
    protected \stdClass $user;
    /** @var \stdClass|null */
    protected ?\stdClass $course;
    /** @var bool */
    protected bool $isself;
    /** @var array|null HR matrix (null in self mode). */
    protected ?array $data;
    /** @var url */
    protected url $backurl;

    /**
     * Constructor.
     *
     * @param \stdClass $user Target user record.
     * @param \stdClass|null $course Selected course record or null.
     * @param bool $isself
     * @param array|null $data HR matrix or null.
     * @param url $backurl
     */
    public function __construct(\stdClass $user, ?\stdClass $course, bool $isself, ?array $data, url $backurl) {
        $this->user = $user;
        $this->course = $course;
        $this->isself = $isself;
        $this->data = $data;
        $this->backurl = $backurl;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $userid = (int) $this->user->id;
        $courses = $this->data !== null ? $this->courses_from_matrix() : $this->courses_live();
        $selectedid = $this->course ? (int) $this->course->id : 0;
        $completed = 0;
        $progresssum = 0;
        $progressn = 0;
        foreach ($courses as &$c) {
            $c['active'] = $c['id'] === $selectedid;
            $c['url'] = (new url('/local/hrdashboard/user.php', ['id' => $userid, 'courseid' => $c['id']]))->out(false);
            if ($c['status']['status'] === 'completed') {
                $completed++;
            }
            if ($c['progress']['hasprogress']) {
                $progresssum += $c['progress']['progress'];
                $progressn++;
            }
        }
        unset($c);

        if ($this->data !== null && isset($this->data['users'][$userid])) {
            $attrs = helper::attributes_list($this->data['users'][$userid]);
        } else {
            $attrs = helper::attributes_list($this->own_attributes());
        }

        $summary = [
            ['label' => get_string('col:courses', 'local_hrdashboard'), 'value' => count($courses)],
            ['label' => get_string('kpi:completed', 'local_hrdashboard'), 'value' => $completed],
            ['label' => get_string('kpi:avgprogress', 'local_hrdashboard'),
                'value' => $progressn ? (int) round($progresssum / $progressn) . '%' : helper::EMPTY],
        ];

        $selected = false;
        if ($this->course) {
            $detail = activity_detail::for_user_course($userid, $this->course, $this->isself);
            $activities = [];
            foreach ($detail['activities'] as $a) {
                $activities[] = [
                    'name' => $a['name'],
                    'modnamestr' => $a['modnamestr'],
                    'icon' => $a['icon'],
                    'url' => $a['url'],
                    'section' => $a['section'],
                    'due' => helper::due($a['due']),
                    'hasdue' => (bool) $a['due'],
                    'overdue' => $a['overdue'],
                    'status' => ['status' => $a['state'], 'label' => get_string('state:' . $a['state'], 'local_hrdashboard')],
                    'timecompleted' => $a['timecompleted'] ? helper::datetime($a['timecompleted']) : helper::EMPTY,
                    'timecompletedset' => (bool) $a['timecompleted'],
                    'grade' => $a['grade'] ?? helper::EMPTY,
                ];
            }
            $selected = [
                'fullname' => format_string($this->course->fullname, true, ['context' => \context_course::instance($this->course->id)]),
                'viewurl' => (new url('/course/view.php', ['id' => $this->course->id]))->out(false),
                'enabled' => $detail['enabled'],
                'progress' => helper::progress($detail['progress']),
                'done' => $detail['done'],
                'tracked' => $detail['tracked'],
                'activities' => $activities,
                'hasactivities' => !empty($activities),
            ];
        }

        return [
            'user' => [
                'fullname' => fullname($this->user),
                'initials' => helper::initials(fullname($this->user)),
                'attrs' => $attrs,
                'lastlogin' => helper::datetime((int) $this->user->lastaccess),
            ],
            'summary' => $summary,
            'backurl' => $this->backurl->out(false),
            'backlabel' => $this->isself ? get_string('mylearning', 'local_hrdashboard')
                : get_string('backtodashboard', 'local_hrdashboard'),
            'isself' => $this->isself,
            'courses' => array_values($courses),
            'hascourses' => !empty($courses),
            'selected' => $selected,
        ];
    }

    /**
     * Course rows from the HR matrix.
     *
     * @return array
     */
    protected function courses_from_matrix(): array {
        $userid = (int) $this->user->id;
        $out = [];
        foreach ($this->data['rows'] as $r) {
            if ($r['userid'] !== $userid) {
                continue;
            }
            $c = $this->data['courses'][$r['courseid']];
            $out[] = [
                'id' => $c['id'],
                'fullname' => $c['fullname'],
                'shortname' => $c['shortname'],
                'progress' => helper::progress($r['progress']),
                'progresspct' => $r['progress'] ?? -1,
                'pending' => $r['pending'],
                'score' => helper::percent($r['score'], 1),
                'lastactivity' => helper::datetime($r['lastactivity']),
                'lastsort' => $r['lastactivity'],
                'status' => helper::status($r['status']),
                'reasons' => helper::reasons($r['risk']),
                'hasreasons' => !empty($r['risk']),
            ];
        }
        \core_collator::asort_array_of_arrays_by_key($out, 'fullname');
        return $out;
    }

    /**
     * Course rows for the learner's own view (core API, no risk rules).
     *
     * @return array
     */
    protected function courses_live(): array {
        global $DB;
        $userid = (int) $this->user->id;
        $courses = enrol_get_users_courses($userid, true, ['enablecompletion', 'enddate']);
        unset($courses[SITEID]);
        $lastaccess = $DB->get_records_menu('user_lastaccess', ['userid' => $userid], '', 'courseid, timeaccess');
        $out = [];
        foreach ($courses as $course) {
            if (!$course->visible) {
                continue;
            }
            $detail = activity_detail::for_user_course($userid, $course, true);
            $completed = $detail['progress'] === 100;
            $status = $completed ? 'completed'
                : (($detail['done'] || !empty($lastaccess[$course->id])) ? 'inprogress' : 'notstarted');
            $grades = grade_get_course_grades($course->id, $userid);
            $score = helper::EMPTY;
            if (!empty($grades->grades[$userid]) && $grades->grades[$userid]->grade !== null && empty($grades->grades[$userid]->hidden)) {
                $score = $grades->grades[$userid]->str_grade;
            }
            $out[] = [
                'id' => (int) $course->id,
                'fullname' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
                'shortname' => format_string($course->shortname, true, ['context' => \context_course::instance($course->id)]),
                'progress' => helper::progress($detail['progress']),
                'progresspct' => $detail['progress'] ?? -1,
                'pending' => max(0, $detail['tracked'] - $detail['done']),
                'score' => $score,
                'lastactivity' => helper::datetime((int) ($lastaccess[$course->id] ?? 0)),
                'lastsort' => (int) ($lastaccess[$course->id] ?? 0),
                'status' => helper::status($status),
                'reasons' => [],
                'hasreasons' => false,
            ];
        }
        return $out;
    }

    /**
     * Attribute values of the learner for the self view.
     *
     * @return array
     */
    protected function own_attributes(): array {
        global $DB;
        $out = [];
        foreach (\local_hrdashboard\local\config::attributes() as $attribute => $source) {
            if ($source === 'department' || $source === 'institution') {
                $out[$attribute] = (string) ($this->user->$source ?? '');
            } else if (strpos($source, 'profile_') === 0) {
                $out[$attribute] = (string) $DB->get_field_sql(
                    "SELECT d.data FROM {user_info_data} d JOIN {user_info_field} f ON f.id = d.fieldid
                      WHERE d.userid = :userid AND f.shortname = :shortname",
                    ['userid' => $this->user->id, 'shortname' => substr($source, 8)]);
            }
        }
        return $out;
    }
}
