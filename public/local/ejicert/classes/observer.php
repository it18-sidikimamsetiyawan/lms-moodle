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

/**
 * Event observer for local_ejicert.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Adds the EJI Certificate ID, completion date and optional score to a new issue.
     *
     * Failures are reported but never block issuing: a learner who completed the
     * course still gets the certificate, and cli/backfill.php can repair it later.
     *
     * @param \tool_certificate\event\certificate_issued $event
     */
    public static function certificate_issued(\tool_certificate\event\certificate_issued $event): void {
        global $DB;
        try {
            if ($issue = $DB->get_record('tool_certificate_issues', ['id' => $event->objectid])) {
                issuer::enrich($issue);
            }
        } catch (\Throwable $e) {
            debugging('local_ejicert: could not enrich issue ' . $event->objectid . ': ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }
}
