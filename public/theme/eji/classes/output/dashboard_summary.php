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

namespace theme_eji\output;

use completion_info;
use context_course;
use core\output\renderer_base;
use core\url;
use core_completion\progress;

require_once(__DIR__ . '/../../../../lib/completionlib.php');

/**
 * The welcome banner, KPI row and "Continue learning" card on the dashboard.
 *
 * Every number here is derived from Moodle's own APIs for the user who is
 * logged in - enrolments, completion and last-access. Nothing is hardcoded and
 * nothing is estimated: where Moodle genuinely does not know a value, this
 * class says so rather than inventing one.
 *
 * How a course is classified:
 *
 *   completed   completion_info::is_course_complete() is true.
 *   in progress not complete AND (completion reports progress above zero, OR
 *               the user has opened the course at least once).
 *   not started neither of the above.
 *
 * The last-access fallback exists because completion tracking is optional per
 * course. Without it, a course with tracking switched off could never leave
 * "not started" no matter how much of it the learner had done.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_summary {

    /** @var \stdClass The user to summarise. */
    protected $user;

    /** @var array<int,string> Course id => state, memoised by state_for(). */
    protected $statecache = [];

    /** @var array<int,float|null> Course id => completion percentage, memoised. */
    protected $progresscache = [];

    /** @var array<int,int>|null Course id => last access time, loaded once. */
    protected $lastaccesscache = null;

    /**
     * Constructor.
     *
     * @param \stdClass|null $user Defaults to the current user.
     */
    public function __construct($user = null) {
        global $USER;
        $this->user = $user ?? $USER;
    }

    /**
     * Export for the dashboard template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $courses = $this->get_courses();
        $stats = $this->classify($courses);

        return [
            'welcome' => get_string('welcomeback', 'theme_eji', $this->user->firstname),
            'hascourses' => !empty($courses),
            'kpis' => [
                [
                    'label' => get_string('kpi:courses', 'theme_eji'),
                    'value' => count($courses),
                    'icon' => $output->pix_icon('i/course', '', 'core'),
                    'tone' => 'default',
                    'url' => (new url('/my/courses.php'))->out(false),
                ],
                [
                    'label' => get_string('kpi:inprogress', 'theme_eji'),
                    'value' => $stats['inprogress'],
                    'icon' => $output->pix_icon('i/duration', '', 'core'),
                    'tone' => 'info',
                    'url' => (new url('/my/courses.php'))->out(false),
                ],
                [
                    'label' => get_string('kpi:completed', 'theme_eji'),
                    'value' => $stats['completed'],
                    'icon' => $output->pix_icon('i/checkedcircle', '', 'core'),
                    'tone' => 'success',
                    'url' => (new url('/my/courses.php'))->out(false),
                ],
                [
                    'label' => get_string('kpi:notstarted', 'theme_eji'),
                    'value' => $stats['notstarted'],
                    'icon' => $output->pix_icon('i/competencies', '', 'core'),
                    'tone' => 'muted',
                    'url' => (new url('/my/courses.php'))->out(false),
                ],
            ],
            'continue' => $this->get_continue_card($output, $courses),
        ];
    }

    /**
     * Courses the user is enrolled in and allowed to see.
     *
     * enrol_get_my_courses() already filters by enrolment status and course
     * visibility, so no extra guard is needed here.
     *
     * @return array
     */
    protected function get_courses(): array {
        $courses = enrol_get_my_courses(['id', 'fullname', 'shortname', 'visible', 'enablecompletion'], 'fullname ASC');
        // Drop the front page if it ever appears.
        unset($courses[SITEID]);
        return $courses;
    }

    /**
     * Count courses by state.
     *
     * @param array $courses
     * @return array
     */
    protected function classify(array $courses): array {
        $stats = ['inprogress' => 0, 'completed' => 0, 'notstarted' => 0];

        foreach ($courses as $course) {
            $state = $this->state_for($course);
            $stats[$state]++;
        }

        return $stats;
    }

    /**
     * Work out one course's state for this user.
     *
     * Memoised: the KPI counts and the "Continue learning" card both need the
     * state of every course, and each miss costs a completion_info plus its
     * queries. Without this the whole set is evaluated twice on every
     * dashboard load.
     *
     * @param \stdClass $course
     * @return string One of completed|inprogress|notstarted.
     */
    protected function state_for($course): string {
        if (isset($this->statecache[$course->id])) {
            return $this->statecache[$course->id];
        }

        $completion = new completion_info($course);

        if ($completion->is_enabled() && $completion->is_course_complete($this->user->id)) {
            return $this->statecache[$course->id] = 'completed';
        }

        $percentage = $this->progress_for($course);
        if ($percentage !== null && $percentage > 0) {
            return $this->statecache[$course->id] = 'inprogress';
        }

        // Completion may be off for this course. Falling back to "has the
        // learner ever opened it" keeps the count honest instead of parking
        // every untracked course under "not started".
        return $this->statecache[$course->id] = $this->last_access($course->id) ? 'inprogress' : 'notstarted';
    }

    /**
     * Completion percentage, or null when the course does not track it.
     *
     * @param \stdClass $course
     * @return float|null
     */
    protected function progress_for($course): ?float {
        if (array_key_exists($course->id, $this->progresscache)) {
            return $this->progresscache[$course->id];
        }
        if (empty($course->enablecompletion)) {
            return $this->progresscache[$course->id] = null;
        }
        $percentage = progress::get_course_progress_percentage($course, $this->user->id);
        return $this->progresscache[$course->id] = ($percentage === null ? null : (float) $percentage);
    }

    /**
     * Timestamp of the user's last visit to a course, or 0.
     *
     * @param int $courseid
     * @return int
     */
    protected function last_access(int $courseid): int {
        global $DB;

        // Per instance, not static: a static cache would be keyed by nothing
        // and would hand one user's last-access times to another if two
        // summaries were ever built in the same request.
        if ($this->lastaccesscache === null) {
            $this->lastaccesscache = $DB->get_records_menu(
                'user_lastaccess',
                ['userid' => $this->user->id],
                '',
                'courseid, timeaccess'
            );
        }

        return (int) ($this->lastaccesscache[$courseid] ?? 0);
    }

    /**
     * The "Continue learning" card: the course opened most recently that is
     * not yet complete.
     *
     * Returns false when there is nothing sensible to continue, so the
     * template can leave the section out entirely rather than show an empty
     * card.
     *
     * @param renderer_base $output
     * @param array $courses
     * @return array|false
     */
    protected function get_continue_card(renderer_base $output, array $courses) {
        $best = null;
        $bestaccess = 0;

        foreach ($courses as $course) {
            if ($this->state_for($course) === 'completed') {
                continue;
            }
            $access = $this->last_access($course->id);
            if ($best === null || $access > $bestaccess) {
                $best = $course;
                $bestaccess = $access;
            }
        }

        if ($best === null) {
            return false;
        }

        $context = context_course::instance($best->id);
        $percentage = $this->progress_for($best);
        $hasprogress = $percentage !== null;

        return [
            'fullname' => format_string($best->fullname, true, ['context' => $context, 'escape' => false]),
            'shortname' => format_string($best->shortname, true, ['context' => $context, 'escape' => false]),
            'viewurl' => (new url('/course/view.php', ['id' => $best->id]))->out(false),
            'hasprogress' => $hasprogress,
            'progress' => $hasprogress ? (int) round($percentage) : 0,
            'activitysummary' => $this->activity_summary($best),
            'image' => \core_course\external\course_summary_exporter::get_course_image($best),
        ];
    }

    /**
     * "N of M activities completed", or an empty string when the course does
     * not track completion and the number would be a guess.
     *
     * @param \stdClass $course
     * @return string
     */
    protected function activity_summary($course): string {
        $completion = new completion_info($course);
        if (!$completion->is_enabled()) {
            return '';
        }

        $activities = $completion->get_activities();
        if (empty($activities)) {
            return '';
        }

        $done = 0;
        foreach ($activities as $activity) {
            $data = $completion->get_data($activity, true, $this->user->id);
            if (in_array((int) $data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
                $done++;
            }
        }

        return get_string('activitiescompleted', 'completion', (object) [
            'complete' => $done,
            'total' => count($activities),
        ]);
    }
}
