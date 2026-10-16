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
 * Status and "at risk" evaluation for one enrolment (user x course).
 *
 * Every threshold comes from config; a threshold of 0 switches that rule off.
 * The evaluator returns the *reasons*, not just a flag, so HR always sees why
 * somebody is listed.
 *
 * Status precedence: completed > overdue > atrisk > inprogress > notstarted.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class risk {

    /** @var string[] All status keys, in display order. */
    const STATUSES = ['notstarted', 'inprogress', 'atrisk', 'overdue', 'completed'];

    /**
     * Evaluate the risk reasons of one enrolment row.
     *
     * @param array $row Keys: tracked, done, progress, completed, lastactivity, timeenrolled, overdue, nextdue, pending.
     * @param array $user Keys: lastaccess, timecreated, suspended.
     * @param array $course Keys: enddate.
     * @param int $now
     * @return array List of ['key' => string, 'a' => mixed] reasons; empty = not at risk.
     */
    public static function evaluate(array $row, array $user, array $course, int $now): array {
        if (!empty($row['completed']) || !empty($user['suspended'])) {
            return [];
        }
        $reasons = [];
        $enddate = (int) ($course['enddate'] ?? 0);

        // 1. Overdue: an unfinished activity past its due date, or the course end date has passed.
        if (!empty($row['overdue'])) {
            $reasons[] = ['key' => 'overdue', 'a' => (int) $row['overdue']];
        } else if ($enddate && $enddate < $now) {
            $reasons[] = ['key' => 'courseended', 'a' => self::days($now - $enddate)];
        }

        // 2. Low progress close to a deadline.
        $lowprogress = config::lowprogress();
        $window = config::deadlinedays() * DAYSECS;
        $due = (int) ($row['nextdue'] ?? 0);
        if (!$due && $enddate > $now) {
            $due = $enddate;
        }
        if ($lowprogress > 0 && $due > $now && ($due - $now) <= $window
                && $row['progress'] !== null && (int) $row['progress'] < $lowprogress) {
            $reasons[] = ['key' => 'lowprogress', 'a' => (object) [
                'progress' => (int) $row['progress'],
                'days' => self::days($due - $now),
            ]];
        }

        // 3. Inactive in this course.
        $inactive = config::inactivedays();
        $last = (int) ($row['lastactivity'] ?? 0);
        if ($inactive > 0 && $last > 0 && ($now - $last) > $inactive * DAYSECS) {
            $reasons[] = ['key' => 'inactive', 'a' => self::days($now - $last)];
        }

        // 4. Enrolled a while ago and never started.
        $notstarted = config::notstarteddays();
        $enrolled = (int) ($row['timeenrolled'] ?? 0);
        if ($notstarted > 0 && $last === 0 && empty($row['done']) && $enrolled > 0
                && ($now - $enrolled) > $notstarted * DAYSECS) {
            $reasons[] = ['key' => 'notstarted', 'a' => self::days($now - $enrolled)];
        }

        // 5. No site login for a long time (user-level).
        $nologin = config::nologindays();
        $ref = (int) ($user['lastaccess'] ?? 0) ?: (int) ($user['timecreated'] ?? 0);
        if ($nologin > 0 && $ref > 0 && ($now - $ref) > $nologin * DAYSECS) {
            $reasons[] = ['key' => empty($user['lastaccess']) ? 'neverloggedin' : 'nologin', 'a' => self::days($now - $ref)];
        }

        // 6. Too many pending activities.
        $threshold = config::pendingthreshold();
        if ($threshold > 0 && (int) ($row['pending'] ?? 0) >= $threshold) {
            $reasons[] = ['key' => 'pending', 'a' => (int) $row['pending']];
        }

        return $reasons;
    }

    /**
     * Derive the status key of an enrolment row.
     *
     * @param array $row
     * @param array $reasons Output of evaluate().
     * @return string
     */
    public static function status(array $row, array $reasons): string {
        if (!empty($row['completed'])) {
            return 'completed';
        }
        foreach ($reasons as $reason) {
            if ($reason['key'] === 'overdue' || $reason['key'] === 'courseended') {
                return 'overdue';
            }
        }
        if (!empty($reasons)) {
            return 'atrisk';
        }
        if (!empty($row['done']) || !empty($row['lastactivity']) || (int) ($row['progress'] ?? 0) > 0) {
            return 'inprogress';
        }
        return 'notstarted';
    }

    /**
     * Human-readable reason.
     *
     * @param array $reason ['key' => ..., 'a' => ...]
     * @return string
     */
    public static function reason_text(array $reason): string {
        return get_string('risk:' . $reason['key'], 'local_hrdashboard', $reason['a']);
    }

    /**
     * Human-readable status.
     *
     * @param string $status
     * @return string
     */
    public static function status_text(string $status): string {
        return get_string('status:' . $status, 'local_hrdashboard');
    }

    /**
     * Whole days in a number of seconds (at least 1).
     *
     * @param int $seconds
     * @return int
     */
    protected static function days(int $seconds): int {
        return max(1, (int) floor($seconds / DAYSECS));
    }
}
