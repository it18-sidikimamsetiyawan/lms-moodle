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
use local_hrdashboard\local\filters;
use local_hrdashboard\local\scope;

/**
 * HR employee progress table (one row per enrolment) with paging and export.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class employees_page implements renderable, templatable {

    /** @var int Rows per page. */
    const PERPAGE = 25;

    /** @var string[] Display order of statuses. */
    const ORDER = ['overdue', 'atrisk', 'inprogress', 'notstarted', 'completed'];

    /** @var scope */
    protected scope $scope;
    /** @var array */
    protected array $data;
    /** @var filters */
    protected filters $filters;
    /** @var int */
    protected int $page;

    /**
     * Constructor.
     *
     * @param scope $scope
     * @param array $data
     * @param filters $filters
     * @param int $page
     */
    public function __construct(scope $scope, array $data, filters $filters, int $page) {
        $this->scope = $scope;
        $this->data = $data;
        $this->filters = $filters;
        $this->page = max(0, $page);
    }

    /**
     * Sorted enrolment rows (plain data) for the current filters. Shared with export.
     *
     * @param array $data Unfiltered matrix.
     * @param filters $filters
     * @return array List of ['user' => array, 'course' => array, 'row' => array]
     */
    public static function rows(array $data, filters $filters): array {
        $filtered = $filters->apply($data);
        $rows = [];
        foreach ($filtered['rows'] as $r) {
            $rows[] = [
                'user' => $filtered['users'][$r['userid']],
                'course' => $filtered['courses'][$r['courseid']],
                'row' => $r,
            ];
        }
        usort($rows, function($a, $b) {
            $sa = array_search($a['row']['status'], self::ORDER, true);
            $sb = array_search($b['row']['status'], self::ORDER, true);
            return ($sa <=> $sb)
                ?: strcoll($a['user']['fullname'], $b['user']['fullname'])
                ?: strcoll($a['course']['fullname'], $b['course']['fullname']);
        });
        return $rows;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $all = self::rows($this->data, $this->filters);
        $total = count($all);
        $slice = array_slice($all, $this->page * self::PERPAGE, self::PERPAGE);

        $rows = [];
        foreach ($slice as $item) {
            $u = $item['user'];
            $c = $item['course'];
            $r = $item['row'];
            $rows[] = [
                'userurl' => (new url('/local/hrdashboard/user.php', ['id' => $u['id'], 'courseid' => $c['id']]))->out(false),
                'fullname' => $u['fullname'],
                'initials' => helper::initials($u['fullname']),
                'attrs' => helper::attributes_line($u),
                'courseurl' => $this->filters->url('/local/hrdashboard/course.php', ['id' => $c['id']])->out(false),
                'coursename' => $c['fullname'],
                'progress' => helper::progress($r['progress']),
                'progresspct' => $r['progress'] ?? -1,
                'completed' => $r['completed'] ? helper::date($r['timecompleted']) : helper::EMPTY,
                'completedsort' => $r['completed'] ? $r['timecompleted'] : 0,
                'score' => helper::percent($r['score'], 1),
                'scoresort' => $r['score'] ?? -1,
                'lastactivity' => helper::datetime($r['lastactivity']),
                'lastsort' => $r['lastactivity'],
                'status' => helper::status($r['status']),
                'reasons' => helper::reasons($r['risk']),
                'hasreasons' => !empty($r['risk']),
            ];
        }

        $baseurl = $this->filters->url('/local/hrdashboard/employees.php');
        $dashboard = new hr_dashboard($this->scope, $this->data, $this->filters);
        $from = $total ? $this->page * self::PERPAGE + 1 : 0;
        $to = min($total, ($this->page + 1) * self::PERPAGE);

        return [
            'backurl' => $this->filters->url('/local/hrdashboard/index.php')->out(false),
            'generated' => userdate($this->data['generated'], get_string('strftimedatetimeshort', 'langconfig')),
            'filters' => $dashboard->filters_context('/local/hrdashboard/employees.php', true, true, true),
            'canexport' => $this->scope->can_export(),
            'exports' => helper::export_links($this->filters->url('/local/hrdashboard/export.php')),
            'rows' => $rows,
            'hasrows' => !empty($rows),
            'total' => $total,
            'showing' => get_string('showing', 'local_hrdashboard', (object) ['from' => $from, 'to' => $to, 'total' => $total]),
            'pagingbar' => $output->paging_bar($total, $this->page, self::PERPAGE, $baseurl),
        ];
    }
}
