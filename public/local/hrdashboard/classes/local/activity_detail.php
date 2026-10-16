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
use stdClass;

/**
 * Activity-level detail for one learner in one course - the last drill-down step.
 *
 * Uses the core completion API (so it matches the course page exactly) and one
 * query for the learner's activity grades.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_detail {

    /**
     * Activities with completion state, due date and grade.
     *
     * @param int $userid
     * @param stdClass $course Course record.
     * @param bool $respecthidden Hide grades the gradebook hides from the learner (true for self-view).
     * @return array {enabled: bool, tracked: int, done: int, progress: int|null, activities: array}
     */
    public static function for_user_course(int $userid, stdClass $course, bool $respecthidden): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $completion = new completion_info($course);
        $out = ['enabled' => $completion->is_enabled(), 'tracked' => 0, 'done' => 0, 'progress' => null, 'activities' => []];
        if (!$out['enabled']) {
            return $out;
        }

        $now = time();
        $modinfo = get_fast_modinfo($course, $userid);
        $tracked = $completion->get_user_activities_with_completion($userid);

        // Grades of this learner for every graded activity, one query.
        $grades = [];
        $rs = $DB->get_recordset_sql(
            "SELECT gi.id, gi.itemmodule, gi.iteminstance, gi.gradetype, gi.grademax, gi.grademin, gi.scaleid, gi.decimals,
                    gi.hidden AS itemhidden, gg.finalgrade, gg.hidden AS gradehidden
               FROM {grade_items} gi
          LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
              WHERE gi.courseid = :courseid AND gi.itemtype = 'mod' AND gi.itemnumber = 0",
            ['userid' => $userid, 'courseid' => $course->id]);
        foreach ($rs as $g) {
            $grades[$g->itemmodule . ':' . $g->iteminstance] = $g;
        }
        $rs->close();

        $cmlist = [];
        foreach (array_keys($tracked) as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            $cmlist[] = (object) ['id' => $cm->id, 'modname' => $cm->modname, 'instance' => $cm->instance,
                'completionexpected' => $cm->completionexpected];
        }
        $due = duedates::for_cms($cmlist);

        foreach ($modinfo->get_cms() as $cm) {
            if (!isset($tracked[$cm->id])) {
                continue;
            }
            $data = $completion->get_data($cm, true, $userid);
            $state = (int) $data->completionstate;
            $complete = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true);
            $out['tracked']++;
            if ($complete) {
                $out['done']++;
            }

            $gradestr = null;
            $gradepercent = null;
            $key = $cm->modname . ':' . $cm->instance;
            if (isset($grades[$key]) && $grades[$key]->finalgrade !== null) {
                $g = $grades[$key];
                $hidden = !empty($g->itemhidden) || !empty($g->gradehidden);
                if (!($respecthidden && $hidden)) {
                    $item = new \grade_item((array) $g + ['id' => $g->id], false);
                    $gradestr = grade_format_gradevalue($g->finalgrade, $item, true, GRADE_DISPLAY_TYPE_REAL, $g->decimals);
                    $range = (float) $g->grademax - (float) $g->grademin;
                    if ($range > 0 && (int) $g->gradetype === GRADE_TYPE_VALUE) {
                        $gradepercent = round(((float) $g->finalgrade - (float) $g->grademin) / $range * 100);
                    }
                } else {
                    $gradestr = get_string('gradehidden', 'local_hrdashboard');
                }
            }

            $duets = $due[$cm->id] ?? 0;
            $out['activities'][] = [
                'id' => (int) $cm->id,
                'name' => $cm->get_formatted_name(),
                'modname' => $cm->modname,
                'modnamestr' => $cm->get_module_type_name(),
                'icon' => $cm->get_icon_url()->out(false),
                'url' => $cm->url ? $cm->url->out(false) : null,
                'section' => $cm->get_section_info()->name ?: get_section_name($course, $cm->get_section_info()),
                'due' => $duets,
                'state' => self::state_key($state),
                'complete' => $complete,
                'overdue' => !$complete && $duets && $duets < $now,
                'timecompleted' => $complete ? (int) $data->timemodified : 0,
                'viewed' => !empty($data->viewed),
                'grade' => $gradestr,
                'gradepercent' => $gradepercent,
            ];
        }

        if ($out['tracked']) {
            $out['progress'] = $completion->is_course_complete($userid) ? 100
                : (int) round($out['done'] / $out['tracked'] * 100);
        }
        return $out;
    }

    /**
     * Template-friendly completion state key.
     *
     * @param int $state
     * @return string complete|pass|fail|incomplete
     */
    public static function state_key(int $state): string {
        switch ($state) {
            case COMPLETION_COMPLETE_PASS:
                return 'pass';
            case COMPLETION_COMPLETE_FAIL:
            case COMPLETION_COMPLETE_FAIL_HIDDEN:
                return 'fail';
            case COMPLETION_COMPLETE:
                return 'complete';
            default:
                return 'incomplete';
        }
    }
}
