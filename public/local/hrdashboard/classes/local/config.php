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
 * Typed access to every plugin setting, with the defaults in one place.
 *
 * No business rule (threshold, scope, field name) lives anywhere else in the
 * plugin: everything is read from here so an administrator can change it in
 * Site administration > Plugins > Local plugins > HR Learning Dashboard.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {

    /** @var string Plugin component. */
    const COMPONENT = 'local_hrdashboard';

    /** @var string Employee population: every confirmed, non-deleted, non-admin user. */
    const SOURCE_ALL = 'all';
    /** @var string Employee population: users enrolled in a course within scope. */
    const SOURCE_ENROLLED = 'enrolled';
    /** @var string Employee population: members of a cohort. */
    const SOURCE_COHORT = 'cohort';

    /**
     * Integer setting with default.
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    public static function int(string $name, int $default): int {
        $value = get_config(self::COMPONENT, $name);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return max(0, (int) $value);
    }

    /**
     * String setting with default.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function str(string $name, string $default = ''): string {
        $value = get_config(self::COMPONENT, $name);
        if ($value === false || $value === null) {
            return $default;
        }
        return trim((string) $value);
    }

    /** @return int Days without course activity before an unfinished enrolment is "inactive". 0 = off. */
    public static function inactivedays(): int {
        return self::int('inactivedays', 14);
    }

    /** @return int Days without a site login before a learner with unfinished work is flagged. 0 = off. */
    public static function nologindays(): int {
        return self::int('nologindays', 14);
    }

    /** @return int Days after enrolment before "not started" becomes a risk. 0 = off. */
    public static function notstarteddays(): int {
        return self::int('notstarteddays', 14);
    }

    /** @return int Progress percentage below which a learner close to a deadline is at risk. 0 = off. */
    public static function lowprogress(): int {
        return self::int('lowprogress', 50);
    }

    /** @return int Window (days) before a due date in which low progress is a risk. */
    public static function deadlinedays(): int {
        return self::int('deadlinedays', 7);
    }

    /** @return int Pending-activity count that is a risk on its own. 0 = off. */
    public static function pendingthreshold(): int {
        return self::int('pendingthreshold', 0);
    }

    /** @return int Cache lifetime in seconds. 0 = rebuild on every request. */
    public static function cachettl(): int {
        return self::int('cachettl', 15) * MINSECS;
    }

    /** @return int Days of history shown in activity timelines. */
    public static function timelinedays(): int {
        return max(1, self::int('timelinedays', 30));
    }

    /** @return int Weeks of history in the completion trend chart. */
    public static function trendweeks(): int {
        return max(4, self::int('trendweeks', 12));
    }

    /** @return bool Whether hidden courses are monitored. */
    public static function includehidden(): bool {
        return self::int('includehidden', 0) === 1;
    }

    /** @return int[] Category ids limiting the monitored courses. Empty = all. */
    public static function categoryscope(): array {
        $raw = self::str('categoryscope');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }

    /** @return string One of the SOURCE_* constants. */
    public static function employeesource(): string {
        $value = self::str('employeesource', self::SOURCE_ALL);
        return in_array($value, [self::SOURCE_ALL, self::SOURCE_ENROLLED, self::SOURCE_COHORT], true)
            ? $value : self::SOURCE_ALL;
    }

    /** @return string Cohort idnumber for SOURCE_COHORT. */
    public static function employeecohort(): string {
        return self::str('employeecohort');
    }

    /**
     * Where an employee attribute comes from.
     *
     * Values: '' (not configured), 'department' or 'institution' (core user
     * fields) or 'profile_<shortname>' (custom profile field).
     *
     * @param string $attribute department|division|position
     * @return string
     */
    public static function attribute_source(string $attribute): string {
        $defaults = ['department' => 'department', 'division' => '', 'position' => ''];
        return self::str($attribute . 'field', $defaults[$attribute] ?? '');
    }

    /**
     * The configured attributes, in display order.
     *
     * @return array attribute => source (only configured ones)
     */
    public static function attributes(): array {
        $out = [];
        foreach (['department', 'division', 'position'] as $attribute) {
            $source = self::attribute_source($attribute);
            if ($source !== '') {
                $out[$attribute] = $source;
            }
        }
        return $out;
    }

    /**
     * A fingerprint of every setting that changes the content of the cached matrix.
     *
     * @return string
     */
    public static function signature(): string {
        return md5(json_encode([
            self::categoryscope(),
            self::employeesource(),
            self::employeecohort(),
            self::attributes(),
            self::includehidden(),
            self::trendweeks(),
        ]));
    }
}
