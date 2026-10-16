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

namespace local_ejicert;

use tool_certificate\customfield\issue_handler;

/**
 * Adds the EJI-specific data to a tool_certificate issue.
 *
 * Eligibility is NOT decided here: mod_coursecertificate only issues when the
 * activity is available, and the activity is restricted by "course completed".
 * This class only enriches an issue that already exists:
 *
 *  - ejicertid          permanent EJI Certificate ID (EJI-CERT-YYYY-NNNNNN);
 *  - ejicompletiondate  course completion date in long form;
 *  - ejiscore           "Final Score: 85/100 (85%)" or '' when the course has no score.
 *
 * tool_certificate's own random `code` is left untouched: it stays the key used by
 * the QR code and the built-in verification page.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issuer {

    /** Issue custom field holding the EJI Certificate ID. */
    const FIELD_CERTID = 'ejicertid';
    /** Issue custom field holding the long completion date. */
    const FIELD_DATE = 'ejicompletiondate';
    /** Issue custom field holding the optional score line. */
    const FIELD_SCORE = 'ejiscore';

    /**
     * Registers the three issue fields with tool_certificate (idempotent).
     */
    public static function ensure_fields(): void {
        $handler = issue_handler::create();
        $handler->ensure_field_exists(self::FIELD_CERTID, 'text',
            get_string('field_certid', 'local_ejicert'), true, 'EJI-CERT-2026-000123');
        $handler->ensure_field_exists(self::FIELD_DATE, 'text',
            get_string('field_completiondate', 'local_ejicert'), true, 'October 2, 2026');
        $handler->ensure_field_exists(self::FIELD_SCORE, 'text',
            get_string('field_score', 'local_ejicert'), true, 'Final Score: 85/100 (85%)');
    }

    /**
     * Enriches one issue. Safe to call more than once: an existing ID is never changed.
     *
     * @param \stdClass $issue tool_certificate_issues record
     * @return string the EJI Certificate ID
     */
    public static function enrich(\stdClass $issue): string {
        $certid = self::assign_certid($issue);
        $data = [
            self::FIELD_CERTID => $certid,
            self::FIELD_DATE => self::completion_date($issue),
            self::FIELD_SCORE => self::score_line($issue),
        ];
        issue_handler::create()->save_additional_data($issue, $data);
        return $certid;
    }

    /**
     * Returns the issue's EJI Certificate ID, allocating the next running number if it has none.
     *
     * @param \stdClass $issue
     * @return string
     */
    public static function assign_certid(\stdClass $issue): string {
        global $DB;

        if ($existing = $DB->get_field('local_ejicert_ids', 'certid', ['issueid' => $issue->id])) {
            return $existing;
        }

        $year = (int)(new \DateTime('@' . $issue->timecreated))
            ->setTimezone(\core_date::get_server_timezone_object())->format('Y');
        $prefix = get_config('local_ejicert', 'idprefix') ?: 'EJI-CERT';

        $lockfactory = \core\lock\lock_config::get_lock_factory('local_ejicert');
        if (!$lock = $lockfactory->get_lock('certid_' . $year, 10)) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            $seq = (int)$DB->get_field_sql('SELECT MAX(seq) FROM {local_ejicert_ids} WHERE year = ?', [$year]) + 1;
            $certid = sprintf('%s-%d-%06d', $prefix, $year, $seq);
            $DB->insert_record('local_ejicert_ids', (object)[
                'issueid' => $issue->id,
                'certid' => $certid,
                'year' => $year,
                'seq' => $seq,
                'timecreated' => time(),
            ]);
        } finally {
            $lock->release();
        }
        return $certid;
    }

    /**
     * Course completion date ("October 2, 2026"). Falls back to the issue date for
     * certificates that are not tied to a course.
     *
     * @param \stdClass $issue
     * @return string
     */
    public static function completion_date(\stdClass $issue): string {
        global $DB;
        $time = 0;
        if (!empty($issue->courseid)) {
            $time = (int)$DB->get_field('course_completions', 'timecompleted',
                ['course' => $issue->courseid, 'userid' => $issue->userid]);
        }
        $time = $time ?: (int)$issue->timecreated;
        return (new \DateTime('@' . $time))->setTimezone(\core_date::get_server_timezone_object())->format('F j, Y');
    }

    /**
     * The optional score line. Empty when the course has no usable score, so the
     * certificate shows nothing rather than an invented "0/100".
     *
     * Source: gradebook course total, scaled to 100. Empty when:
     *  - score display is disabled in the plugin settings;
     *  - the issue has no course;
     *  - the course has no graded activity (course total max is 0);
     *  - the course total is hidden from learners;
     *  - the learner has no final grade.
     *
     * @param \stdClass $issue
     * @return string
     */
    public static function score_line(\stdClass $issue): string {
        $pct = self::score_percent((int)($issue->courseid ?? 0), (int)$issue->userid);
        if ($pct === null) {
            return '';
        }
        $label = get_config('local_ejicert', 'scorelabel') ?: 'Final Score';
        return sprintf('%s: %d/100 (%d%%)', $label, $pct, $pct);
    }

    /**
     * Course total as a whole-number percentage, or null when there is no score to show.
     *
     * @param int $courseid
     * @param int $userid
     * @return int|null
     */
    public static function score_percent(int $courseid, int $userid): ?int {
        global $CFG;
        if (!$courseid || !get_config('local_ejicert', 'showscore')) {
            return null;
        }
        require_once($CFG->libdir . '/gradelib.php');

        $item = \grade_item::fetch_course_item($courseid);
        if (!$item || $item->is_hidden() || $item->gradetype != GRADE_TYPE_VALUE) {
            return null;
        }
        $range = (float)$item->grademax - (float)$item->grademin;
        if ($range <= 0) {
            return null;
        }
        $grade = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $userid]);
        if (!$grade || $grade->finalgrade === null || $grade->is_hidden()) {
            return null;
        }
        $pct = ((float)$grade->finalgrade - (float)$item->grademin) / $range * 100;
        return (int)round(max(0, min(100, $pct)));
    }

    /**
     * Finds an issue by EJI Certificate ID.
     *
     * @param string $certid
     * @return \stdClass|null issue joined with template name, or null
     */
    public static function find_by_certid(string $certid): ?\stdClass {
        global $DB;
        $sql = "SELECT ci.*, e.certid, t.name AS templatename
                  FROM {local_ejicert_ids} e
                  JOIN {tool_certificate_issues} ci ON ci.id = e.issueid
                  JOIN {tool_certificate_templates} t ON t.id = ci.templateid
                 WHERE e.certid = ?";
        return $DB->get_record_sql($sql, [strtoupper(trim($certid))]) ?: null;
    }
}
