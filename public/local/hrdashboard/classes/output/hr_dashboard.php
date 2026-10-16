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

use core\chart_bar;
use core\chart_line;
use core\chart_pie;
use core\chart_series;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\url;
use local_hrdashboard\local\aggregate;
use local_hrdashboard\local\config;
use local_hrdashboard\local\filters;
use local_hrdashboard\local\risk;
use local_hrdashboard\local\scope;

/**
 * The HR Learning Dashboard page.
 *
 * Presentation only: every figure comes from aggregate::* over the cached matrix.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hr_dashboard implements renderable, templatable {

    /** @var scope */
    protected scope $scope;
    /** @var array Unfiltered matrix. */
    protected array $data;
    /** @var filters */
    protected filters $filters;

    /**
     * Constructor.
     *
     * @param scope $scope
     * @param array $data stats::get() output
     * @param filters $filters
     */
    public function __construct(scope $scope, array $data, filters $filters) {
        $this->scope = $scope;
        $this->data = $data;
        $this->filters = $filters;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $now = time();
        $filtered = $this->filters->apply($this->data);
        $kpis = aggregate::kpis($filtered, $now);
        $courses = aggregate::courses($filtered);
        $users = aggregate::users($filtered);
        $base = '/local/hrdashboard/index.php';
        $employeesurl = $this->filters->url('/local/hrdashboard/employees.php');
        $s = fn(string $k, $a = null) => get_string($k, 'local_hrdashboard', $a);

        // Four primary KPIs: who, how active, how far, who needs attention.
        $tiles = [
            helper::kpi($output, $s('kpi:totalemployees'), $kpis['totalemployees'], 'default', 'i/users',
                $s('kpi:enrolled') . ': ' . $kpis['enrolled']),
            helper::kpi($output, $s('kpi:activeemployees'), $kpis['activeemployees'], 'success', 'i/user',
                $s('kpi:activewindow', $kpis['activewindow'])),
            helper::kpi($output, $s('kpi:completionrate'), $kpis['completionrate'], 'primary', 'i/checkedcircle',
                $s('kpi:completedof', (object) ['completed' => $kpis['completedenrolments'], 'total' => $kpis['enrolments']]),
                $this->filters->url('/local/hrdashboard/employees.php', ['status' => 'completed'])->out(false), '%'),
            helper::kpi($output, $s('kpi:atrisk'), $kpis['atrisk'], $kpis['atrisk'] ? 'warning' : 'default', 'i/warning',
                $kpis['overdue'] ? $s('kpi:overdue') . ': ' . $kpis['overdue'] : null,
                $this->filters->url('/local/hrdashboard/employees.php', ['status' => 'atrisk'])->out(false)),
        ];

        // Six secondary metrics in one strip.
        $stats = [
            helper::stat($s('kpi:withactivity'), $kpis['withactivity']),
            helper::stat($s('kpi:noactivity'), $kpis['noactivity']),
            helper::stat($s('kpi:enrolled'), $kpis['enrolled'], 'default', $s('kpi:enrolments', $kpis['enrolments']),
                $employeesurl->out(false)),
            helper::stat($s('kpi:avgprogress'), $kpis['avgprogress'], 'default', null, null, '%'),
            helper::stat($s('kpi:avgscore'), $kpis['avgscore'] === null ? null : format_float($kpis['avgscore'], 1, true, true),
                'default', null, null, '%'),
            helper::stat($s('kpi:overdue'), $kpis['overdue'], $kpis['overdue'] ? 'danger' : 'default', null,
                $this->filters->url('/local/hrdashboard/employees.php', ['status' => 'overdue'])->out(false)),
        ];

        // Course table.
        $courserows = [];
        foreach ($courses as $c) {
            $courserows[] = [
                'id' => $c['id'],
                'url' => $this->filters->url('/local/hrdashboard/course.php', ['id' => $c['id']])->out(false),
                'fullname' => $c['fullname'],
                'shortname' => $c['shortname'],
                'categoryname' => $c['categoryname'],
                'hidden' => empty($c['visible']),
                'enrolled' => $c['enrolled'],
                'completionrate' => helper::progress($c['completionrate']),
                'completionpct' => $c['completionrate'] ?? -1,
                'breakdown' => helper::breakdown($c),
                'atrisk' => $c['atrisk'],
                'overdue' => $c['overdue'],
            ];
        }
        usort($courserows, fn($a, $b) => ($b['enrolled'] <=> $a['enrolled']) ?: ($a['completionpct'] <=> $b['completionpct']));

        // At-risk employees: reasons shown directly, at most two plus a count.
        $atrisk = [];
        foreach ($users as $u) {
            if (empty($u['risk'])) {
                continue;
            }
            $reasons = helper::reasons(array_values($u['risk']));
            $atrisk[] = [
                'userid' => $u['id'],
                'url' => (new url('/local/hrdashboard/user.php', ['id' => $u['id']]))->out(false),
                'fullname' => $u['fullname'],
                'initials' => helper::initials($u['fullname']),
                'attrs' => helper::attributes_line($u),
                'courses' => count($u['riskcourses']),
                'reasons' => array_slice($reasons, 0, 2),
                'more' => count($reasons) > 2 ? count($reasons) - 2 : false,
                'lastactivity' => helper::datetime($u['lastactivity']),
                'sort' => $u['lastactivity'],
            ];
        }
        usort($atrisk, fn($a, $b) => ($b['courses'] <=> $a['courses']) ?: ($a['sort'] <=> $b['sort']));
        $atriskcount = count($atrisk);
        $atrisk = array_slice($atrisk, 0, 6);

        // Recent completions.
        $recent = [];
        foreach ($filtered['recent'] as $e) {
            $u = $filtered['users'][$e['userid']];
            $c = $filtered['courses'][$e['courseid']];
            $cm = $c['cms'][$e['cmid']] ?? null;
            if (!$cm) {
                continue;
            }
            $recent[] = [
                'time' => helper::datetime($e['time']),
                'timeshort' => userdate($e['time'], get_string('strftimedateshort', 'langconfig')),
                'text' => $u['fullname'] . ' · ' . $cm['name'],
                'sub' => $c['fullname'],
                'url' => (new url('/local/hrdashboard/user.php', ['id' => $e['userid'], 'courseid' => $e['courseid']]))->out(false),
            ];
            if (count($recent) >= 8) {
                break;
            }
        }

        $refresh = $this->filters->url($base, ['refresh' => 1, 'sesskey' => sesskey()]);
        $exportbase = $this->filters->url('/local/hrdashboard/export.php');

        return [
            'generated' => userdate($this->data['generated'], get_string('strftimedatetimeshort', 'langconfig')),
            'scopenote' => $this->scope->is_systemwide() ? ''
                : $s('scopelimited', count($this->scope->category_ids())),
            'refreshurl' => $refresh->out(false),
            'employeesurl' => $employeesurl->out(false),
            'canexport' => $this->scope->can_export(),
            'exports' => helper::export_links($exportbase),
            'filters' => $this->filters_context($base, true, true, true),
            'kpis' => ['kpis' => $tiles],
            'stats' => ['stats' => $stats],
            'charts' => $this->charts($output, $filtered, $courses),
            'hasdata' => !empty($filtered['rows']),
            'courses' => $courserows,
            'hascourses' => !empty($courserows),
            'coursecount' => count($courserows),
            'employeecount' => $kpis['enrolled'],
            'atrisk' => $atrisk,
            'hasatrisk' => !empty($atrisk),
            'atriskcount' => $atriskcount,
            'atriskurl' => $this->filters->url('/local/hrdashboard/employees.php', ['status' => 'atrisk'])->out(false),
            'recent' => $recent,
            'hasrecent' => !empty($recent),
            'title' => $s('empty:nodatatitle'),
            'text' => $s('empty:nodatatext'),
        ];
    }

    /**
     * Filter toolbar context (shared with the employees page).
     *
     * @param string $action
     * @param bool $showcourse
     * @param bool $showstatus
     * @param bool $showdates
     * @param array $hidden
     * @return array
     */
    public function filters_context(string $action, bool $showcourse, bool $showstatus, bool $showdates, array $hidden = []): array {
        $options = $this->filters->options($this->data);
        $f = $this->filters;
        $sel = fn(array $list, $current) => array_map(fn($value, $label) => [
            'value' => $value, 'label' => $label, 'selected' => (string) $value === (string) $current,
        ], array_keys($list), $list);

        $attributes = [];
        foreach ($options['attributes'] as $attribute => $values) {
            $attributes[] = [
                'name' => $attribute,
                'label' => get_string('attr:' . $attribute, 'local_hrdashboard'),
                'options' => $sel(array_combine($values, $values), $f->$attribute),
            ];
        }
        $statuses = [];
        foreach (risk::STATUSES as $status) {
            $statuses[$status] = risk::status_text($status);
        }

        // Active filter chips; each link drops exactly one filter.
        $chips = [];
        $chip = function(string $label, array $drop) use (&$chips, $action, $hidden, $f) {
            $params = $f->params() + $hidden;
            foreach ($drop as $key) {
                unset($params[$key]);
            }
            $chips[] = ['label' => $label, 'url' => (new url($action, $params))->out(false)];
        };
        if ($f->category && isset($options['categories'][$f->category])) {
            $chip(get_string('filter:category', 'local_hrdashboard') . ': ' . $options['categories'][$f->category], ['category']);
        }
        if ($f->course && isset($options['courses'][$f->course])) {
            $chip(get_string('filter:course', 'local_hrdashboard') . ': ' . $options['courses'][$f->course], ['course']);
        }
        foreach (array_keys($options['attributes']) as $attribute) {
            if ($f->$attribute !== '') {
                $chip(get_string('attr:' . $attribute, 'local_hrdashboard') . ': ' . $f->$attribute, [$attribute]);
            }
        }
        if ($f->status !== '') {
            $chip(get_string('filter:status', 'local_hrdashboard') . ': ' . risk::status_text($f->status), ['status']);
        }
        if ($f->search !== '') {
            $chip(get_string('filter:search', 'local_hrdashboard') . ': ' . $f->search, ['search']);
        }
        if ($f->from || $f->to) {
            $chip(get_string('filter:daterange', 'local_hrdashboard', (object) [
                'from' => $f->from ? helper::date($f->from) : '…', 'to' => $f->to ? helper::date($f->to) : '…']), ['from', 'to']);
        }

        $advancedcount = count(array_filter(array_keys($options['attributes']), fn($a) => $f->$a !== ''))
            + (($f->from || $f->to) ? 1 : 0);
        $hasadvanced = !empty($attributes) || $showdates;

        return [
            'action' => (new url($action))->out(false),
            'hidden' => array_map(fn($k, $v) => ['name' => $k, 'value' => $v], array_keys($hidden), $hidden),
            'search' => $f->search,
            'categories' => $sel($options['categories'], $f->category),
            'courses' => $sel($options['courses'], $f->course),
            'attributes' => $attributes,
            'statuses' => $sel($statuses, $f->status),
            'from' => $f->from ? userdate($f->from, '%Y-%m-%d') : '',
            'to' => $f->to ? userdate($f->to, '%Y-%m-%d') : '',
            'showcourse' => $showcourse,
            'showstatus' => $showstatus,
            'showdates' => $showdates,
            'hasadvanced' => $hasadvanced,
            'advancedopen' => $advancedcount > 0,
            'advancedcount' => $advancedcount,
            'chips' => $chips,
            'haschips' => !empty($chips),
            'reseturl' => (new url($action, $hidden))->out(false),
            'uid' => substr(md5($action), 0, 6),
        ];
    }

    /**
     * Rendered charts (core chart API, Chart.js).
     *
     * @param renderer_base $output
     * @param array $filtered
     * @param array $courses aggregate::courses()
     * @return array
     */
    protected function charts(renderer_base $output, array $filtered, array $courses): array {
        $charts = ['completion' => false, 'status' => false, 'attribute' => false, 'attributetitle' => '',
            'attributedesc' => '', 'trend' => false];
        if (empty($courses)) {
            return $charts;
        }
        $colours = helper::status_colours();

        // 1. Stacked completion per course (top 10 by enrolment).
        $top = array_filter($courses, fn($c) => $c['enrolled'] > 0);
        usort($top, fn($a, $b) => $b['enrolled'] <=> $a['enrolled']);
        $top = array_slice($top, 0, 10);
        if (!empty($top)) {
            $chart = new chart_bar();
            $chart->set_horizontal(true);
            $chart->set_stacked(true);
            $labels = array_map(fn($c) => \core_text::substr($c['shortname'] ?: $c['fullname'], 0, 22), $top);
            foreach (['completed', 'inprogress', 'atrisk', 'overdue', 'notstarted'] as $status) {
                $series = new chart_series(risk::status_text($status), array_map(fn($c) => $c[$status], $top));
                $series->set_color($colours[$status]);
                $chart->add_series($series);
            }
            $chart->set_labels(array_values($labels));
            $charts['completion'] = $output->render($chart);
        }

        // 2. Status distribution.
        $dist = aggregate::status_distribution($filtered);
        if (array_sum($dist) > 0) {
            $chart = new chart_pie();
            $chart->set_doughnut(true);
            $series = new chart_series(get_string('chart:statuslabel', 'local_hrdashboard'), array_values($dist));
            $series->set_colors(array_values(array_map(fn($st) => $colours[$st], array_keys($dist))));
            $chart->add_series($series);
            $chart->set_labels(array_map([risk::class, 'status_text'], array_keys($dist)));
            $charts['status'] = $output->render($chart);
        }

        // 3. Progress by the first configured attribute, only when values exist.
        $attributes = array_keys(config::attributes());
        if (!empty($attributes)) {
            $attribute = $attributes[0];
            $groups = aggregate::by_attribute($filtered, $attribute);
            $unspecified = get_string('unspecified', 'local_hrdashboard');
            $named = array_filter($groups, fn($v, $k) => $k !== $unspecified, ARRAY_FILTER_USE_BOTH);
            if (count($named) >= 1 && count($groups) > 1 || count($named) >= 2) {
                $chart = new chart_bar();
                $s1 = new chart_series(get_string('series:avgprogress', 'local_hrdashboard'),
                    array_values(array_map(fn($g) => $g['avgprogress'] ?? 0, $groups)));
                $s1->set_color('#2563eb');
                $s2 = new chart_series(get_string('series:completionrate', 'local_hrdashboard'),
                    array_values(array_map(fn($g) => $g['completionrate'] ?? 0, $groups)));
                $s2->set_color('#059669');
                $chart->add_series($s1);
                $chart->add_series($s2);
                $chart->set_labels(array_keys($groups));
                $chart->get_yaxis(0, true)->set_max(100);
                $label = get_string('attr:' . $attribute, 'local_hrdashboard');
                $charts['attribute'] = $output->render($chart);
                $charts['attributetitle'] = get_string('section:departmentprogress', 'local_hrdashboard', $label);
                $charts['attributedesc'] = get_string('chart:attributedesc', 'local_hrdashboard', \core_text::strtolower($label));
            }
        }

        // 4. Completion trend.
        if (!empty($this->data['trend'])) {
            $chart = new chart_line();
            $chart->set_smooth(true);
            $labels = array_map(fn($w) => userdate($w['start'], '%d/%m'), $this->data['trend']);
            $s1 = new chart_series(get_string('series:activities', 'local_hrdashboard'),
                array_map(fn($w) => $w['activities'], $this->data['trend']));
            $s1->set_color('#2563eb');
            $s2 = new chart_series(get_string('series:courses', 'local_hrdashboard'),
                array_map(fn($w) => $w['courses'], $this->data['trend']));
            $s2->set_color('#059669');
            $chart->add_series($s1);
            $chart->add_series($s2);
            $chart->set_labels($labels);
            $charts['trend'] = $output->render($chart);
        }

        return $charts;
    }
}
