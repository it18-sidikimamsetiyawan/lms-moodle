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

use core\url;

/**
 * HR filters: parsed from the request, applied to the cached matrix in memory.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filters {

    /** @var int */
    public int $category = 0;
    /** @var int */
    public int $course = 0;
    /** @var int */
    public int $user = 0;
    /** @var string */
    public string $department = '';
    /** @var string */
    public string $division = '';
    /** @var string */
    public string $position = '';
    /** @var string */
    public string $status = '';
    /** @var string */
    public string $search = '';
    /** @var int Unix timestamp (start of day) or 0. */
    public int $from = 0;
    /** @var int Unix timestamp (end of day) or 0. */
    public int $to = 0;

    /**
     * Read the filters from the current request.
     *
     * @return filters
     */
    public static function from_request(): filters {
        $f = new self();
        $f->category = optional_param('category', 0, PARAM_INT);
        $f->course = optional_param('course', 0, PARAM_INT);
        $f->user = optional_param('user', 0, PARAM_INT);
        $f->department = trim(optional_param('department', '', PARAM_TEXT));
        $f->division = trim(optional_param('division', '', PARAM_TEXT));
        $f->position = trim(optional_param('position', '', PARAM_TEXT));
        $status = optional_param('status', '', PARAM_ALPHA);
        $f->status = in_array($status, risk::STATUSES, true) ? $status : '';
        $f->search = trim(optional_param('search', '', PARAM_TEXT));
        $f->from = self::parse_date(optional_param('from', '', PARAM_RAW_TRIMMED), false);
        $f->to = self::parse_date(optional_param('to', '', PARAM_RAW_TRIMMED), true);
        return $f;
    }

    /**
     * Parse a YYYY-MM-DD value from the date inputs.
     *
     * @param string $value
     * @param bool $endofday
     * @return int
     */
    protected static function parse_date(string $value, bool $endofday): int {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return 0;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (!checkdate($m, $d, $y)) {
            return 0;
        }
        $ts = make_timestamp($y, $m, $d);
        return $endofday ? $ts + DAYSECS - 1 : $ts;
    }

    /**
     * Whether anything is set.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->category || $this->course || $this->user || $this->department !== '' || $this->division !== ''
            || $this->position !== '' || $this->status !== '' || $this->search !== '' || $this->from || $this->to;
    }

    /**
     * Query parameters for links that must keep the filters.
     *
     * @return array
     */
    public function params(): array {
        $p = [];
        foreach (['category', 'course', 'user', 'department', 'division', 'position', 'status', 'search'] as $k) {
            if (!empty($this->$k)) {
                $p[$k] = $this->$k;
            }
        }
        if ($this->from) {
            $p['from'] = userdate($this->from, '%Y-%m-%d');
        }
        if ($this->to) {
            $p['to'] = userdate($this->to, '%Y-%m-%d');
        }
        return $p;
    }

    /**
     * URL with the current filters applied.
     *
     * @param string $path
     * @param array $extra
     * @return url
     */
    public function url(string $path, array $extra = []): url {
        return new url($path, $this->params() + $extra);
    }

    /**
     * Apply the filters to a matrix.
     *
     * Row filters (course, category, status, date range) remove enrolment rows.
     * User filters (attributes, search, user) remove users and their rows.
     * Course filters also hide courses from the course tables.
     *
     * @param array $data From stats::get()
     * @return array Same shape, filtered
     */
    public function apply(array $data): array {
        $courses = $data['courses'];
        $users = $data['users'];

        if ($this->category) {
            $courses = array_filter($courses, fn($c) => (int) $c['category'] === $this->category);
        }
        if ($this->course) {
            $courses = array_filter($courses, fn($c) => (int) $c['id'] === $this->course);
        }

        $search = \core_text::strtolower($this->search);
        $users = array_filter($users, function($u) use ($search) {
            if ($this->user && (int) $u['id'] !== $this->user) {
                return false;
            }
            foreach (['department', 'division', 'position'] as $attribute) {
                if ($this->$attribute !== '' && ($u[$attribute] ?? '') !== $this->$attribute) {
                    return false;
                }
            }
            if ($search !== '' && strpos(\core_text::strtolower($u['fullname']), $search) === false) {
                return false;
            }
            return true;
        });

        $rows = array_filter($data['rows'], function($r) use ($courses, $users) {
            if (!isset($courses[$r['courseid']]) || !isset($users[$r['userid']])) {
                return false;
            }
            if ($this->status !== '' && $r['status'] !== $this->status) {
                return false;
            }
            if ($this->from && $r['lastactivity'] < $this->from) {
                return false;
            }
            if ($this->to && ($r['lastactivity'] === 0 || $r['lastactivity'] > $this->to)) {
                return false;
            }
            return true;
        });

        $data['courses'] = $courses;
        $data['users'] = $users;
        $data['rows'] = $rows;
        $data['recent'] = array_values(array_filter($data['recent'],
            fn($e) => isset($courses[$e['courseid']]) && isset($users[$e['userid']])));
        return $data;
    }

    /**
     * Options for the filter form, derived from the unfiltered matrix.
     *
     * @param array $data
     * @return array
     */
    public function options(array $data): array {
        $categories = [];
        $courses = [];
        foreach ($data['courses'] as $c) {
            $categories[$c['category']] = $c['categoryname'];
            $courses[$c['id']] = $c['fullname'];
        }
        \core_collator::asort($categories);
        \core_collator::asort($courses);

        $attributes = [];
        foreach (config::attributes() as $attribute => $source) {
            $values = [];
            foreach ($data['users'] as $u) {
                if (($u[$attribute] ?? '') !== '') {
                    $values[$u[$attribute]] = $u[$attribute];
                }
            }
            if (!empty($values)) {
                \core_collator::asort($values);
                $attributes[$attribute] = $values;
            }
        }

        return [
            'categories' => $categories,
            'courses' => $courses,
            'attributes' => $attributes,
        ];
    }
}
