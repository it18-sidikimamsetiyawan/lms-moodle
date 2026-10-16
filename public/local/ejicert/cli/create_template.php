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
 * Creates the "EJI Standard Certificate" tool_certificate template (A4 landscape).
 *
 * Idempotent: an existing template with the same name is left alone unless
 * --rebuild is given, which replaces its elements in place (same template id,
 * so every course activity keeps pointing at it; issued PDFs are not touched).
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(['rebuild' => false, 'help' => false], ['h' => 'help']);
if ($options['help']) {
    echo "Usage: php local/ejicert/cli/create_template.php [--rebuild]\n";
    exit(0);
}

const LOCAL_EJICERT_TEMPLATE = 'EJI Standard Certificate';

\core\session\manager::set_user(get_admin());
\local_ejicert\issuer::ensure_fields();

use tool_certificate\element_helper as eh;

$template = \tool_certificate\template::find_by_name(LOCAL_EJICERT_TEMPLATE);
if ($template && !$options['rebuild']) {
    cli_writeln("Template already exists (id {$template->get_id()}). Use --rebuild to replace its elements.");
    exit(0);
}
if (!$template) {
    $template = \tool_certificate\template::create((object)[
        'name' => LOCAL_EJICERT_TEMPLATE,
        'contextid' => context_system::instance()->id,
        'shared' => 1,
    ]);
    cli_writeln("Created template id {$template->get_id()}");
} else {
    foreach ($template->get_pages() as $page) {
        $page->delete();
    }
    $template = \tool_certificate\template::instance($template->get_id());
    cli_writeln("Rebuilding template id {$template->get_id()}");
}

$page = $template->new_page();
$page->save((object)['width' => 297, 'height' => 210, 'leftmargin' => 0, 'rightmargin' => 0]);
$pageid = $page->get_id();

$navy = '#152568';
$blue = '#2a44a8';
$ink = '#334155';
$mute = '#64748b';

/**
 * Adds one element to the page.
 *
 * @param int $pageid
 * @param string $type element plugin
 * @param array $data element properties + form data
 */
function local_ejicert_add(int $pageid, string $type, array $data): void {
    $el = \tool_certificate\element::instance(0, (object)['element' => $type, 'pageid' => $pageid]);
    $el->save_form_data((object)$data);
}

// 1. Background (frame, corner accents, logo, seal, rules) - see tools/gen_certificate_background.js.
$fs = get_file_storage();
$usercontext = context_user::instance(get_admin()->id);
$draftid = file_get_unused_draft_itemid();
$fs->create_file_from_pathname([
    'contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft',
    'itemid' => $draftid, 'filepath' => '/', 'filename' => 'eji-certificate-bg.png',
], __DIR__ . '/../assets/eji-certificate-bg.png');
local_ejicert_add($pageid, 'image', [
    'name' => 'Background', 'image' => $draftid, 'isbackground' => 1,
    'width' => 0, 'height' => 0, 'posx' => 0, 'posy' => 0, 'refpoint' => eh::CUSTOMCERT_REF_POINT_TOPLEFT,
]);

$center = ['posx' => 149, 'refpoint' => eh::CUSTOMCERT_REF_POINT_TOPCENTER];
$left = ['refpoint' => eh::CUSTOMCERT_REF_POINT_TOPLEFT];

// 2. Title block.
local_ejicert_add($pageid, 'text', $center + ['name' => 'Title', 'posy' => 49, 'width' => 220,
    'font' => 'freesansb', 'fontsize' => 40, 'colour' => $navy,
    'text' => '<span style="letter-spacing:2.2mm">CERTIFICATE</span>']);
local_ejicert_add($pageid, 'text', $center + ['name' => 'Subtitle', 'posy' => 65, 'width' => 220,
    'font' => 'freesans', 'fontsize' => 13, 'colour' => $blue,
    'text' => '<span style="letter-spacing:1.6mm">OF ACHIEVEMENT</span>']);
local_ejicert_add($pageid, 'text', $center + ['name' => 'Presented to', 'posy' => 81, 'width' => 220,
    'font' => 'freesans', 'fontsize' => 9, 'colour' => $mute,
    'text' => '<span style="letter-spacing:0.8mm">THIS CERTIFICATE IS PROUDLY PRESENTED TO</span>']);

// 3. Recipient, course, optional score.
local_ejicert_add($pageid, 'userfield', $center + ['name' => 'Student full name', 'posy' => 92, 'width' => 200,
    'font' => 'freeserifb', 'fontsize' => 30, 'colour' => $navy, 'userfield' => 'fullname']);
local_ejicert_add($pageid, 'text', $center + ['name' => 'Completing', 'posy' => 115, 'width' => 200,
    'font' => 'freeserifi', 'fontsize' => 12, 'colour' => $ink, 'text' => 'for successfully completing']);
local_ejicert_add($pageid, 'program', $center + ['name' => 'Course name', 'posy' => 123, 'width' => 220,
    'font' => 'freesansb', 'fontsize' => 18, 'colour' => $navy, 'display' => 'coursefullname']);
local_ejicert_add($pageid, 'program', $center + ['name' => 'Score (optional)', 'posy' => 140, 'width' => 200,
    'font' => 'freesans', 'fontsize' => 11, 'colour' => $blue, 'display' => \local_ejicert\issuer::FIELD_SCORE]);

// 4. Footer left: QR + completion date + certificate ID.
local_ejicert_add($pageid, 'code', $left + ['name' => 'QR verification', 'posx' => 28, 'posy' => 160, 'width' => 24,
    'display' => 4]);
local_ejicert_add($pageid, 'text', $left + ['name' => 'Completion date label', 'posx' => 57, 'posy' => 161, 'width' => 70,
    'font' => 'freesans', 'fontsize' => 7, 'colour' => $mute,
    'text' => '<span style="letter-spacing:0.5mm">COMPLETION DATE</span>']);
local_ejicert_add($pageid, 'program', $left + ['name' => 'Completion date', 'posx' => 57, 'posy' => 165, 'width' => 70,
    'font' => 'freesansb', 'fontsize' => 11, 'colour' => $navy, 'display' => \local_ejicert\issuer::FIELD_DATE]);
local_ejicert_add($pageid, 'text', $left + ['name' => 'Certificate ID label', 'posx' => 57, 'posy' => 173, 'width' => 70,
    'font' => 'freesans', 'fontsize' => 7, 'colour' => $mute,
    'text' => '<span style="letter-spacing:0.5mm">CERTIFICATE ID</span>']);
local_ejicert_add($pageid, 'program', $left + ['name' => 'Certificate ID', 'posx' => 57, 'posy' => 177, 'width' => 70,
    'font' => 'freesansb', 'fontsize' => 11, 'colour' => $navy, 'display' => \local_ejicert\issuer::FIELD_CERTID]);
local_ejicert_add($pageid, 'text', $left + ['name' => 'Verify hint', 'posx' => 28, 'posy' => 186, 'width' => 100,
    'font' => 'freesans', 'fontsize' => 7, 'colour' => $mute, 'text' => 'Scan the QR code to verify this certificate']);

// 5. Footer right: issuer signature block (line is in the background at y=178).
local_ejicert_add($pageid, 'text', ['name' => 'Signatory', 'posx' => 222, 'posy' => 180, 'width' => 70,
    'refpoint' => eh::CUSTOMCERT_REF_POINT_TOPCENTER, 'font' => 'freesansb', 'fontsize' => 10, 'colour' => $navy,
    'text' => 'Learning &amp; Development']);
local_ejicert_add($pageid, 'text', ['name' => 'Issuer', 'posx' => 222, 'posy' => 185, 'width' => 70,
    'refpoint' => eh::CUSTOMCERT_REF_POINT_TOPCENTER, 'font' => 'freesans', 'fontsize' => 9, 'colour' => $mute,
    'text' => 'Eka Jaya Internasional']);

cli_writeln('Template "' . LOCAL_EJICERT_TEMPLATE . '" ready: id ' . $template->get_id() . ', page ' . $pageid . ', ' .
    $DB->count_records('tool_certificate_elements', ['pageid' => $pageid]) . ' elements.');
