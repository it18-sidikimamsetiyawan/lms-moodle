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
use local_hrdashboard\local\aggregate;
use local_hrdashboard\local\filters;
use local_hrdashboard\local\risk;
use local_hrdashboard\local\scope;

require_once(__DIR__ . '/../../../../lib/completionlib.php');

/**
 * HR drill-down: one course.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_detail implements renderable, templatable {

    /** @var scope */
    protected scope $scope;
    /** @var array */
    protected array $data;
    /** @var filters */
    protected filters $filters;
    /** @var int */
    protected int $courseid;
    /** @var string */
    protected string $status;

    /**
     * Constructor.
     *
     * @param scope $scope
     * @param array $data
     * @param filters $filters
     * @param int $courseid
     * @param string $status Status tab ('' = all)
     */
    public function __construct(scope $scope, array $data, filters $filters, int $courseid, string $status) {
        $this->scope = $scope;
        $this->data = $data;
        $this->filters = $filters;
        $this->courseid = $courseid;
        $this->status = in_array($status, risk::STATUSES, true) ? $status : '';
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        $course = $this->data['courses'][$this->courseid];
        $f = clone $this->filters;
        $f->course = $this->courseid;
        $f->status = '';
        $filtered = $f->apply($this->data);
        $rows = array_values($filtered['rows']);
        $summary = aggregate::courses($filtered)[$this->courseid];
        $s = fn(string $k, $a = null) => get_string($k, 'local_hrdashboard', $a);

        $needsattention = $summary['atrisk'] + $summary['overdue'];
        $tiles = [
            helper::kpi($output, $s('col:enrolled'), $summary['enrolled'], 'default', 'i/users',
                $s('activitiessummary', $course['tracked'])),
            helper::kpi($output, $s('kpi:completionrate'), $summary['completionrate'], 'primary', 'i/checkedcircle',
                $s('kpi:completedof', (object) ['completed' => $summary['completed'], 'total' => $summary['enrolled']]), null, '%'),
            helper::kpi($output, $s('kpi:avgprogress'), $summary['avgprogress'], 'default', 'i/progressbar',
                $summary['avgscore'] === null ? null : $s('kpi:avgscore') . ': ' . format_float($summary['avgscore'], 1, true, true) . '%',
                null, '%'),
            helper::kpi($output, $s('col:atrisk') . ' / ' . $s('col:overdue'), $summary['atrisk'] . ' / ' . $summary['overdue'],
                $needsattention ? 'warning' : 'default', 'i/warning'),
        ];

        // Per-activity completion among the listed learners.
        $activities = [];
        $userids = array_values(array_unique(array_map(fn($r) => $r['userid'], $rows)));
        $percm = [];
        if (!empty($userids) && !empty($course['cms'])) {
            [$uin, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
            [$cin, $cparams] = $DB->get_in_or_equal(array_keys($course['cms']), SQL_PARAMS_NAMED, 'cm');
            [$sin, $sparams] = $DB->get_in_or_equal([COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], SQL_PARAMS_NAMED, 's');
            $percm = $DB->get_records_sql_menu(
                "SELECT coursemoduleid, COUNT(1) FROM {course_modules_completion}
                  WHERE coursemoduleid $cin AND userid $uin AND completionstate $sin GROUP BY coursemoduleid",
                $uparams + $cparams + $sparams);
        }
        $enrolled = count($userids);
        foreach ($course['cms'] as $cm) {
            $done = (int) ($percm[$cm['id']] ?? 0);
            $pct = $enrolled ? (int) round($done / $enrolled * 100) : null;
            $activities[] = [
                'name' => $cm['name'],
                'modnamestr' => get_string('modulename', $cm['modname']),
                'icon' => $output->image_url('monologo', $cm['modname'])->out(false),
                'due' => helper::due($cm['due']),
                'completed' => $done,
                'enrolled' => $enrolled,
                'completionrate' => helper::progress($pct),
                'completionpct' => $pct ?? -1,
            ];
        }

        // Status tabs.
        $counts = ['' => count($rows)] + array_fill_keys(risk::STATUSES, 0);
        foreach ($rows as $r) {
            $counts[$r['status']]++;
        }
        $tabs = [];
        foreach ($counts as $status => $count) {
            $tabs[] = [
                'label' => $status === '' ? $s('filter:all') : risk::status_text($status),
                'url' => $this->filters->url('/local/hrdashboard/course.php', ['id' => $this->courseid, 'status' => $status])->out(false),
                'count' => $count,
                'active' => $status === $this->status,
            ];
        }

        // Employee rows.
        $employees = [];
        foreach ($rows as $r) {
            if ($this->status !== '' && $r['status'] !== $this->status) {
                continue;
            }
            $u = $filtered['users'][$r['userid']];
            $employees[] = [
                'url' => (new url('/local/hrdashboard/user.php', ['id' => $u['id'], 'courseid' => $this->courseid]))->out(false),
                'fullname' => $u['fullname'],
                'initials' => helper::initials($u['fullname']),
                'attrs' => helper::attributes_line($u),
                'progress' => helper::progress($r['progress']),
                'progresspct' => $r['progress'] ?? -1,
                'pending' => $r['pending'],
                'score' => helper::percent($r['score'], 1),
                'scoresort' => $r['score'] ?? -1,
                'lastactivity' => helper::datetime($r['lastactivity']),
                'lastsort' => $r['lastactivity'],
                'status' => helper::status($r['status']),
                'reasons' => helper::reasons($r['risk']),
                'hasreasons' => !empty($r['risk']),
                'sortstatus' => array_search($r['status'], ['overdue', 'atrisk', 'inprogress', 'notstarted', 'completed'], true),
            ];
        }
        usort($employees, fn($a, $b) => ($a['sortstatus'] <=> $b['sortstatus']) ?: strcoll($a['fullname'], $b['fullname']));

        $exportbase = $f->url('/local/hrdashboard/export.php');

        return [
            'course' => [
                'fullname' => $course['fullname'],
                'shortname' => $course['shortname'],
                'categoryname' => $course['categoryname'],
                'viewurl' => (new url('/course/view.php', ['id' => $this->courseid]))->out(false),
                'enddate' => $course['enddate'] ? helper::date($course['enddate']) : '',
            ],
            'backurl' => $this->filters->url('/local/hrdashboard/index.php')->out(false),
            'generated' => userdate($this->data['generated'], get_string('strftimedatetimeshort', 'langconfig')),
            'kpis' => ['kpis' => $tiles],
            'activities' => $activities,
            'hasactivities' => !empty($activities),
            'tabs' => $tabs,
            'employees' => $employees,
            'hasemployees' => !empty($employees),
            'employeecount' => count($employees),
            'canexport' => $this->scope->can_export(),
            'exports' => helper::export_links($exportbase),
        ];
    }
}
