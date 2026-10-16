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

/**
 * Strings for local_ejicert.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['adminlinks'] = 'Standard template: <a href="{$a->templates}">Manage certificate templates</a> · Public verification: <a href="{$a->verify}">{$a->verify}</a>';
$string['certid'] = 'Certificate ID';
$string['completiondate'] = 'Completion date';
$string['course'] = 'Course';
$string['downloadpdf'] = 'Download PDF';
$string['enterid'] = 'Enter the Certificate ID printed on the certificate, e.g. EJI-CERT-2026-000001.';
$string['field_certid'] = 'EJI Certificate ID';
$string['field_completiondate'] = 'EJI completion date (long)';
$string['field_score'] = 'EJI score line (optional)';
$string['idprefix'] = 'Certificate ID prefix';
$string['idprefix_desc'] = 'IDs are issued as PREFIX-YEAR-NNNNNN with a running number per year. Changing the prefix affects new certificates only; issued IDs never change.';
$string['issuedby'] = 'Issued by';
$string['issuedon'] = 'Issued on';
$string['issuername'] = 'Issuer name';
$string['issuername_desc'] = 'Shown on the verification page.';
$string['notfound'] = 'No certificate was found with this ID. Check the ID and try again, or contact HR / Learning & Development.';
$string['pluginname'] = 'EJI Certificate System';
$string['privacy:metadata'] = 'local_ejicert stores only a certificate number for each tool_certificate issue; all personal data is held and handled by tool_certificate.';
$string['score'] = 'Score';
$string['scorelabel'] = 'Score label';
$string['scorelabel_desc'] = 'Text before the score, e.g. "Final Score: 85/100 (85%)".';
$string['showscore'] = 'Show score when available';
$string['showscore_desc'] = 'Adds the gradebook course total (scaled to 100) to new certificates when the course has one. Courses without a score never show a score line.';
$string['status'] = 'Status';
$string['status_expired'] = 'EXPIRED';
$string['status_valid'] = 'VALID';
$string['student'] = 'Student';
$string['verify'] = 'Verify';
$string['verifyheading'] = 'Certificate verification';
$string['verifynotallowed'] = 'You are not allowed to verify certificates.';
