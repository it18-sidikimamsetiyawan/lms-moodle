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
use local_hrdashboard\local\config;
use local_hrdashboard\local\employee;

/**
 * The personal "My Learning" dashboard.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_dashboard implements renderable, templatable {

    /** @var int */
    protected int $userid;
    /** @var url|null */
    protected ?url $hrurl;

    /**
     * Constructor.
     *
     * @param int $userid
     * @param url|null $hrurl Link to the HR dashboard when the user may open it.
     */
    public function __construct(int $userid, ?url $hrurl) {
        $this->userid = $userid;
        $this->hrurl = $hrurl;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $USER;
        $now = time();
        $d = (new employee($this->userid))->build();
        $k = $d['kpis'];
        $s = fn(string $key, $a = null) => get_string($key, 'local_hrdashboard', $a);

        // Nearest pending due date per course (for the course cards).
        $duebycourse = [];
        foreach ($d['pending'] as $p) {
            if ($p['due'] && (!isset($duebycourse[$p['courseid']]) || $p['due'] < $duebycourse[$p['courseid']])) {
                $duebycourse[$p['courseid']] = $p['due'];
            }
        }

        $courses = [];
        foreach ($d['courses'] as $c) {
            if ($c['state'] === 'completed') {
                continue; // "Continue learning" shows what is left; completed courses stay in the detail list.
            }
            $due = $duebycourse[$c['id']] ?? ($c['enddate'] ?: 0);
            $duerel = $due ? helper::due_relative($due, $now) : null;
            $courses[] = [
                'fullname' => $c['fullname'],
                'category' => $c['category'] ?? $c['shortname'],
                'viewurl' => $c['viewurl'],
                'detailurl' => $c['detailurl'],
                'image' => $c['image'],
                'progress' => helper::progress($c['progress']),
                'hasactivities' => $c['tracked'] > 0,
                'done' => $c['done'],
                'tracked' => $c['tracked'],
                'lastactivity' => $c['lastactivity'] ? $c['lastactivity']['name'] : helper::EMPTY,
                'nextactivity' => $c['nextactivity'] ? $c['nextactivity']['name'] : helper::EMPTY,
                'hasdue' => $duerel !== null,
                'duelabel' => $duerel ? $duerel['label'] : '',
                'duecls' => $duerel ? $duerel['cls'] : '',
                'score' => $c['scorepercent'] !== null ? helper::percent($c['scorepercent'], 1) : $c['score'],
                'status' => helper::status($c['state']),
                'buttonlabel' => $c['state'] === 'notstarted' ? $s('start') : $s('continuelearning'),
                'buttonurl' => $c['nextactivity'] ? $c['nextactivity']['url'] : $c['viewurl'],
                'sort' => $c['lastaccess'],
            ];
        }
        usort($courses, fn($a, $b) => $b['sort'] <=> $a['sort']);

        $pending = [];
        foreach ($d['pending'] as $p) {
            $duerel = helper::due_relative($p['due'], $now);
            $pending[] = array_merge($p, [
                'duelabel' => $duerel['label'],
                'duestatus' => $duerel['status'] === 'none' ? 'incomplete' : $duerel['status'],
                'isoverdue' => $duerel['status'] === 'overdue',
                'actionlabel' => $s($p['action']),
            ]);
        }

        $timeline = [];
        foreach ($d['timeline'] as $group) {
            $entries = [];
            foreach ($group['entries'] as $e) {
                $entries[] = [
                    'timestr' => userdate($e['time'], get_string('strftimetime', 'langconfig')),
                    'text' => $e['text'],
                    'sub' => $e['sub'] ?? '',
                    'url' => $e['url'],
                    'type' => $e['type'],
                ];
            }
            $timeline[] = ['label' => $group['label'], 'entries' => $entries];
        }

        $done = $k['completedactivities'];
        $pend = $k['pending'];
        $total = $done + $pend;
        $overall = $k['overallprogress'];

        $stats = [
            helper::stat($s('kpi:mycourses'), $k['courses'], 'default', null, (new url('/my/courses.php'))->out(false)),
            helper::stat($s('kpi:completedcourses'), $k['completed']),
            helper::stat($s('kpi:inprogresscourses'), $k['inprogress'], 'default',
                $k['notstarted'] ? $s('notstartedsummary', $k['notstarted']) : null),
            helper::stat($s('kpi:avgscore'), $k['avgscore'] === null ? null : format_float($k['avgscore'], 1, true, true),
                'default', null, null, '%'),
        ];

        return [
            'greeting' => helper::greeting($USER->firstname, $now),
            'subtitle' => $s('subtitle:my'),
            'hrurl' => $this->hrurl ? $this->hrurl->out(false) : false,
            'hero' => [
                'hasprogress' => $overall !== null,
                'progress' => $overall ?? 0,
                'done' => $done,
                'total' => $total,
                'completedcourses' => $k['completed'],
                'totalcourses' => $k['courses'],
                'pending' => $pend,
            ],
            'stats' => ['stats' => $stats],
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'allcompleted' => empty($courses) && $k['courses'] > 0,
            'pending' => $pending,
            'haspending' => !empty($pending),
            'split' => ['done' => $done, 'pending' => $pend, 'total' => $total,
                'donepct' => $total ? (int) round($done / $total * 100) : 0],
            'streak' => $d['streak'],
            'streakstr' => $s('streakdays', $d['streak']),
            'streaktext' => $d['streak'] ? $s('streak:text') : $s('streak:none'),
            'timeline' => $timeline,
            'hastimeline' => !empty($timeline),
            'timelinedays' => config::timelinedays(),
        ];
    }
}
