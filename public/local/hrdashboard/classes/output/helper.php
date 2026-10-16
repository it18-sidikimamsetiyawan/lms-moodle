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

use core\output\renderer_base;
use local_hrdashboard\local\config;
use local_hrdashboard\local\risk;

/**
 * Formatting helpers shared by the renderables (presentation only).
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var string Shown where Moodle genuinely has no value. */
    const EMPTY = '—';

    /**
     * KPI tile context.
     *
     * @param renderer_base $output
     * @param string $label
     * @param mixed $value null renders as a dash
     * @param string $tone default|primary|success|warning|danger
     * @param string $icon pix icon name
     * @param string|null $sub
     * @param string|null $url
     * @param string $suffix e.g. '%'
     * @return array
     */
    public static function kpi(renderer_base $output, string $label, $value, string $tone = 'default',
            string $icon = 'i/report', ?string $sub = null, ?string $url = null, string $suffix = ''): array {
        return [
            'label' => $label,
            'value' => $value === null ? self::EMPTY : $value . $suffix,
            'tone' => $tone,
            'icon' => $output->pix_icon($icon, '', 'core'),
            'sub' => $sub,
            'hassub' => $sub !== null && $sub !== '',
            'url' => $url,
            'hasurl' => !empty($url),
        ];
    }

    /**
     * Secondary stat context (strip item).
     *
     * @param string $label
     * @param mixed $value
     * @param string $tone
     * @param string|null $sub
     * @param string|null $url
     * @param string $suffix
     * @return array
     */
    public static function stat(string $label, $value, string $tone = 'default', ?string $sub = null,
            ?string $url = null, string $suffix = ''): array {
        return [
            'label' => $label,
            'value' => $value === null ? self::EMPTY : $value . $suffix,
            'tone' => $tone,
            'sub' => $sub,
            'hassub' => $sub !== null && $sub !== '',
            'url' => $url,
            'hasurl' => !empty($url),
        ];
    }

    /**
     * Status badge context.
     *
     * @param string $status
     * @return array
     */
    public static function status(string $status): array {
        return ['status' => $status, 'label' => risk::status_text($status)];
    }

    /**
     * Progress bar context.
     *
     * @param int|float|null $progress
     * @return array
     */
    public static function progress($progress): array {
        $value = $progress === null ? 0 : (int) round($progress);
        return [
            'hasprogress' => $progress !== null,
            'progress' => $value,
            'done' => $progress !== null && $value >= 100,
        ];
    }

    /**
     * Initials for an avatar ("Dewi Lestari" -> "DL").
     *
     * @param string $fullname
     * @return string
     */
    public static function initials(string $fullname): string {
        $words = preg_split('/\s+/', trim($fullname), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_values(array_filter($words, fn($w) => $w !== '-' && \core_text::strlen($w) > 0));
        if (empty($words)) {
            return '?';
        }
        $first = \core_text::substr($words[0], 0, 1);
        $second = count($words) > 1 ? \core_text::substr($words[count($words) - 1], 0, 1) : '';
        return \core_text::strtoupper($first . $second);
    }

    /**
     * Time-of-day greeting.
     *
     * @param string $firstname
     * @param int $now
     * @return string
     */
    public static function greeting(string $firstname, int $now): string {
        $hour = (int) usergetdate($now)['hours'];
        $key = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
        return get_string('greeting:' . $key, 'local_hrdashboard', $firstname);
    }

    /**
     * Relative due-date label with urgency.
     *
     * @param int $due
     * @param int $now
     * @return array {label, status: overdue|soon|pending|none, cls}
     */
    public static function due_relative(int $due, int $now): array {
        if (!$due) {
            return ['label' => get_string('due:none', 'local_hrdashboard'), 'status' => 'none', 'cls' => ''];
        }
        $days = (int) round((usergetmidnight($due) - usergetmidnight($now)) / DAYSECS);
        if ($days < 0) {
            return ['label' => get_string('due:overdueby', 'local_hrdashboard', -$days), 'status' => 'overdue', 'cls' => 'is-overdue'];
        }
        if ($days === 0) {
            return ['label' => get_string('due:today', 'local_hrdashboard'), 'status' => 'soon', 'cls' => 'is-due'];
        }
        if ($days === 1) {
            return ['label' => get_string('due:tomorrow', 'local_hrdashboard'), 'status' => 'soon', 'cls' => 'is-due'];
        }
        if ($days <= 7) {
            return ['label' => get_string('due:in', 'local_hrdashboard', $days), 'status' => 'pending', 'cls' => ''];
        }
        return ['label' => get_string('due:on', 'local_hrdashboard', self::date($due)), 'status' => 'pending', 'cls' => ''];
    }

    /**
     * Date (no time), or "Never".
     *
     * @param int $timestamp
     * @return string
     */
    public static function date(int $timestamp): string {
        return $timestamp ? userdate($timestamp, get_string('strftimedate', 'langconfig'))
            : get_string('never', 'local_hrdashboard');
    }

    /**
     * Date and time, or "Never".
     *
     * @param int $timestamp
     * @return string
     */
    public static function datetime(int $timestamp): string {
        return $timestamp ? userdate($timestamp, get_string('strftimedatetimeshort', 'langconfig'))
            : get_string('never', 'local_hrdashboard');
    }

    /**
     * Due date or "No due date".
     *
     * @param int $timestamp
     * @return string
     */
    public static function due(int $timestamp): string {
        return $timestamp ? userdate($timestamp, get_string('strftimedate', 'langconfig'))
            : get_string('nodue', 'local_hrdashboard');
    }

    /**
     * Percentage string or a dash.
     *
     * @param int|float|null $value
     * @param int $decimals
     * @return string
     */
    public static function percent($value, int $decimals = 0): string {
        return $value === null ? self::EMPTY : format_float((float) $value, $decimals, true, true) . '%';
    }

    /**
     * Risk reasons as text.
     *
     * @param array $reasons
     * @return string[]
     */
    public static function reasons(array $reasons): array {
        return array_values(array_map([risk::class, 'reason_text'], $reasons));
    }

    /**
     * Attribute values of a user joined for display ("HR · Jakarta").
     *
     * @param array $user
     * @return string
     */
    public static function attributes_line(array $user): string {
        $parts = [];
        foreach (array_keys(config::attributes()) as $attribute) {
            if (($user[$attribute] ?? '') !== '') {
                $parts[] = $user[$attribute];
            }
        }
        return implode(' · ', $parts);
    }

    /**
     * Attribute label/value pairs of a user.
     *
     * @param array $user
     * @return array
     */
    public static function attributes_list(array $user): array {
        $out = [];
        foreach (array_keys(config::attributes()) as $attribute) {
            $out[] = [
                'label' => get_string('attr:' . $attribute, 'local_hrdashboard'),
                'value' => ($user[$attribute] ?? '') !== '' ? $user[$attribute] : self::EMPTY,
            ];
        }
        return $out;
    }

    /**
     * Status colours for charts, in risk::STATUSES order. One hue per status,
     * matching the badge tokens.
     *
     * @return string[]
     */
    public static function status_colours(): array {
        return [
            'notstarted' => '#9ca3af',
            'inprogress' => '#2563eb',
            'atrisk' => '#d97706',
            'overdue' => '#dc2626',
            'completed' => '#059669',
        ];
    }

    /**
     * Status breakdown pills for a course summary (non-zero statuses only).
     *
     * @param array $summary aggregate::courses() row
     * @return array [{key, count, label}]
     */
    public static function breakdown(array $summary): array {
        $out = [];
        foreach (['completed', 'inprogress', 'atrisk', 'overdue', 'notstarted'] as $status) {
            if (!empty($summary[$status])) {
                $out[] = ['key' => $status, 'count' => $summary[$status],
                    'label' => get_string($status . 'summary', 'local_hrdashboard', $summary[$status])];
            }
        }
        return $out;
    }

    /**
     * Export links for a base URL.
     *
     * @param \core\url $base
     * @return array
     */
    public static function export_links(\core\url $base): array {
        $out = [];
        foreach (['csv', 'excel', 'pdf'] as $format) {
            if (!\core_component::get_component_directory('dataformat_' . $format)) {
                continue;
            }
            $url = new \core\url($base);
            $url->param('format', $format);
            $out[] = ['label' => get_string('export:' . $format, 'local_hrdashboard'), 'url' => $url->out(false)];
        }
        return $out;
    }
}
