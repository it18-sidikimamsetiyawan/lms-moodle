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
 * EJI ONE login layout.
 *
 * Used by the login pagelayout, which also covers the MFA, password-reset and
 * signup flows. The form itself is rendered by core from /login/index.php -
 * this only supplies the surrounding panel and branding. No authentication
 * logic is touched.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes(['eji-login']);

// Site-configured auth instructions replace the EJI welcome panel entirely,
// matching Boost's behaviour - an admin who has written instructions meant
// them to be read.
$leftinstructions = !empty($CFG->auth_instructions)
    ? format_text($CFG->auth_instructions, FORMAT_MOODLE, ['context' => context_system::instance()])
    : null;

$sitename = format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]);
$sitefullname = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), 'escape' => false]);

$ejibrand = trim((string) get_config('theme_eji', 'sidebarbrand'));
if ($ejibrand === '') {
    $ejibrand = $sitename;
}

// Tagline under the brand. Falls back to the site's full name rather than to
// invented marketing copy.
$ejitagline = trim((string) get_config('theme_eji', 'portaltagline'));
if ($ejitagline === '') {
    $ejitagline = $sitefullname;
}

$templatecontext = [
    'sitename' => $sitename,
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'leftinstructions' => $leftinstructions,
    'ejibrand' => $ejibrand,
    'ejitagline' => $ejitagline,
];

echo $OUTPUT->render_from_template('theme_eji/login', $templatecontext);
