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

use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for local_ejicert.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * On a learner's course certificate page, adds "Download PDF" beside mod_coursecertificate's
     * "View certificate" button and shows the EJI Certificate ID.
     *
     * Read-only: it looks up the learner's existing issue and never issues or changes anything.
     * Teachers' report view and pages without an issue are left as they are.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $USER, $DB;

        if ($PAGE->pagetype !== 'mod-coursecertificate-view' || !$PAGE->cm || !isloggedin() || isguestuser()) {
            return;
        }
        $templateid = (int)$DB->get_field('coursecertificate', 'template', ['id' => $PAGE->cm->instance]);
        if (!$templateid) {
            return;
        }
        $issue = \mod_coursecertificate\helper::get_user_certificate($USER->id, $PAGE->course->id, $templateid);
        if (!$issue) {
            return;
        }
        $certid = $DB->get_field('local_ejicert_ids', 'certid', ['issueid' => $issue->id]);
        $template = \tool_certificate\template::instance($templateid);
        $download = $template->get_issue_file_url($issue);
        $download->param('forcedownload', 1);

        $html = \html_writer::link($download, get_string('downloadpdf', 'local_ejicert'),
            ['class' => 'btn btn-outline-primary ms-2', 'data-ejicert' => 'download']);
        if ($certid) {
            $html .= \html_writer::div(get_string('certid', 'local_ejicert') . ': ' .
                \html_writer::tag('strong', s($certid)), 'mt-2 text-muted small', ['data-ejicert' => 'certid']);
        }
        // Moves the snippet next to the plugin's own "View certificate" button.
        $hook->add_html(\html_writer::div($html, 'd-none', ['id' => 'ejicert-extra']) . \html_writer::script("
            (function() {
                var extra = document.getElementById('ejicert-extra');
                var target = document.querySelector('[data-region=\"mod_coursecertificate_view\"] .mdl-align');
                if (!extra || !target) { return; }
                while (extra.firstChild) { target.appendChild(extra.firstChild); }
                extra.remove();
            })();"));
    }
}
