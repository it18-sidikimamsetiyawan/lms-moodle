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

/**
 * Sums over a (filtered) matrix: KPIs, per-course, per-user and per-attribute summaries.
 *
 * Pure functions over arrays; no database access.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aggregate {

    /**
     * Dashboard KPIs.
     *
     * @param array $data Filtered matrix.
     * @param int $now
     * @return array
     */
    public static function kpis(array $data, int $now): array {
        $users = $data['users'];
        $rows = $data['rows'];

        $withactivity = [];
        $enrolled = [];
        $atrisk = [];
        $completed = 0;
        $progresssum = 0;
        $progressn = 0;
        $scoresum = 0;
        $scoren = 0;
        $overdue = 0;
        foreach ($rows as $r) {
            $enrolled[$r['userid']] = true;
            if ($r['done'] || $r['lastactivity'] || $r['completed']) {
                $withactivity[$r['userid']] = true;
            }
            if ($r['completed']) {
                $completed++;
            }
            if ($r['progress'] !== null) {
                $progresssum += $r['progress'];
                $progressn++;
            }
            if ($r['score'] !== null) {
                $scoresum += $r['score'];
                $scoren++;
            }
            $overdue += $r['overdue'];
            if (!empty($r['risk'])) {
                $atrisk[$r['userid']] = true;
            }
        }

        $activewindow = config::nologindays() ?: 30;
        $active = 0;
        foreach ($users as $u) {
            if (!$u['suspended'] && $u['lastaccess'] >= $now - $activewindow * DAYSECS) {
                $active++;
            }
        }

        return [
            'totalemployees' => count($users),
            'activeemployees' => $active,
            'activewindow' => $activewindow,
            'withactivity' => count($withactivity),
            'noactivity' => max(0, count($users) - count($withactivity)),
            'enrolled' => count($enrolled),
            'enrolments' => count($rows),
            'completedenrolments' => $completed,
            'completionrate' => count($rows) ? (int) round($completed / count($rows) * 100) : null,
            'avgprogress' => $progressn ? (int) round($progresssum / $progressn) : null,
            'avgscore' => $scoren ? round($scoresum / $scoren, 1) : null,
            'overdue' => $overdue,
            'atrisk' => count($atrisk),
        ];
    }

    /**
     * Per-course summary (statuses are mutually exclusive and sum to "enrolled").
     *
     * @param array $data
     * @return array courseid => summary
     */
    public static function courses(array $data): array {
        $out = [];
        foreach ($data['courses'] as $cid => $c) {
            $out[$cid] = [
                'id' => $cid,
                'fullname' => $c['fullname'],
                'shortname' => $c['shortname'],
                'categoryname' => $c['categoryname'],
                'visible' => $c['visible'],
                'tracked' => $c['tracked'],
                'enrolled' => 0,
                'notstarted' => 0,
                'inprogress' => 0,
                'atrisk' => 0,
                'overdue' => 0,
                'completed' => 0,
                'progresssum' => 0,
                'progressn' => 0,
                'scoresum' => 0,
                'scoren' => 0,
            ];
        }
        foreach ($data['rows'] as $r) {
            if (!isset($out[$r['courseid']])) {
                continue;
            }
            $s = &$out[$r['courseid']];
            $s['enrolled']++;
            $s[$r['status']]++;
            if ($r['progress'] !== null) {
                $s['progresssum'] += $r['progress'];
                $s['progressn']++;
            }
            if ($r['score'] !== null) {
                $s['scoresum'] += $r['score'];
                $s['scoren']++;
            }
            unset($s);
        }
        foreach ($out as &$s) {
            $s['completionrate'] = $s['enrolled'] ? (int) round($s['completed'] / $s['enrolled'] * 100) : null;
            $s['avgprogress'] = $s['progressn'] ? (int) round($s['progresssum'] / $s['progressn']) : null;
            $s['avgscore'] = $s['scoren'] ? round($s['scoresum'] / $s['scoren'], 1) : null;
        }
        unset($s);
        return $out;
    }

    /**
     * Per-user summary across their enrolments.
     *
     * @param array $data
     * @return array userid => summary
     */
    public static function users(array $data): array {
        $out = [];
        foreach ($data['rows'] as $r) {
            $uid = $r['userid'];
            if (!isset($out[$uid])) {
                $out[$uid] = $data['users'][$uid] + [
                    'enrolled' => 0,
                    'completed' => 0,
                    'inprogress' => 0,
                    'notstarted' => 0,
                    'atrisk' => 0,
                    'overdue' => 0,
                    'progresssum' => 0,
                    'progressn' => 0,
                    'scoresum' => 0,
                    'scoren' => 0,
                    'lastactivity' => 0,
                    'pending' => 0,
                    'risk' => [],
                    'riskcourses' => [],
                ];
            }
            $s = &$out[$uid];
            $s['enrolled']++;
            $s[$r['status']]++;
            if ($r['progress'] !== null) {
                $s['progresssum'] += $r['progress'];
                $s['progressn']++;
            }
            if ($r['score'] !== null) {
                $s['scoresum'] += $r['score'];
                $s['scoren']++;
            }
            $s['lastactivity'] = max($s['lastactivity'], $r['lastactivity']);
            $s['pending'] += $r['pending'];
            if (!empty($r['risk'])) {
                $s['riskcourses'][$r['courseid']] = $r['risk'];
                foreach ($r['risk'] as $reason) {
                    $s['risk'][$reason['key']] = $reason;
                }
            }
            unset($s);
        }
        foreach ($out as &$s) {
            $s['avgprogress'] = $s['progressn'] ? (int) round($s['progresssum'] / $s['progressn']) : null;
            $s['avgscore'] = $s['scoren'] ? round($s['scoresum'] / $s['scoren'], 1) : null;
        }
        unset($s);
        return $out;
    }

    /**
     * Enrolment status distribution.
     *
     * @param array $data
     * @return array status => count
     */
    public static function status_distribution(array $data): array {
        $out = array_fill_keys(risk::STATUSES, 0);
        foreach ($data['rows'] as $r) {
            $out[$r['status']]++;
        }
        return $out;
    }

    /**
     * Average progress and completion rate per value of an employee attribute.
     *
     * @param array $data
     * @param string $attribute department|division|position
     * @return array value => ['employees' => n, 'enrolments' => n, 'avgprogress' => int|null, 'completionrate' => int|null]
     */
    public static function by_attribute(array $data, string $attribute): array {
        $out = [];
        foreach ($data['rows'] as $r) {
            $u = $data['users'][$r['userid']];
            $value = ($u[$attribute] ?? '') !== '' ? $u[$attribute] : get_string('unspecified', 'local_hrdashboard');
            if (!isset($out[$value])) {
                $out[$value] = ['employees' => [], 'enrolments' => 0, 'completed' => 0, 'progresssum' => 0, 'progressn' => 0];
            }
            $out[$value]['employees'][$r['userid']] = true;
            $out[$value]['enrolments']++;
            if ($r['completed']) {
                $out[$value]['completed']++;
            }
            if ($r['progress'] !== null) {
                $out[$value]['progresssum'] += $r['progress'];
                $out[$value]['progressn']++;
            }
        }
        foreach ($out as &$s) {
            $s['employees'] = count($s['employees']);
            $s['avgprogress'] = $s['progressn'] ? (int) round($s['progresssum'] / $s['progressn']) : null;
            $s['completionrate'] = $s['enrolments'] ? (int) round($s['completed'] / $s['enrolments'] * 100) : null;
        }
        unset($s);
        \core_collator::ksort($out);
        return $out;
    }
}
