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
 * EJI ONE main layout.
 *
 * Serves 11 of the 16 pagelayouts (base, standard, course, coursecategory,
 * incourse, frontpage, admin, mycourses, mydashboard, mypublic, report).
 *
 * This is Boost's drawers.php with two additions and nothing removed:
 *   - an EJI sidebar navigation context ($ejinav, $ejisidebaropen);
 *   - a topbar page title.
 * Every variable Boost's template relies on is still built exactly as core
 * builds it, so course index, block drawers, secondary navigation, activity
 * headers and the edit switch all keep working untouched.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
    // The EJI sidebar is a navigation drawer. On >= 992px it is rendered as a
    // persistent column (see scss/eji/_navigation.scss); below that width
    // Boost's own drawer JS turns it back into an overlay with a backdrop.
    $ejisidebaropen = (get_user_preferences('drawer-open-nav', true) == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
    // Guests and the logged-out still get the sidebar, but closed by default:
    // there is very little in it for them and no preference to remember.
    $ejisidebaropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers', 'eji-layout'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}
if ($ejisidebaropen) {
    $extraclasses[] = 'eji-nav-open';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;

// EJI sidebar navigation. Permission-aware; see the class for the rules.
$ejinav = (new \theme_eji\output\sidebar_navigation($PAGE))->export_for_template($renderer);

// Brand shown at the top of the sidebar. Falls back to the site short name so
// the theme is usable on a site that has not configured the setting.
$sitename = format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]);
$ejibrand = trim((string) get_config('theme_eji', 'sidebarbrand'));
if ($ejibrand === '') {
    $ejibrand = $sitename;
}

// Short mark for the logo tile when no compact logo is uploaded.
//
// A brand like "EJI ONE" or "EJI LMS" reads best as its acronym first word
// ("EJI") rather than as initials ("EO"), so a short leading word wins.
// Otherwise fall back to initials, and for a single word to its first letters.
$ejibrandwords = preg_split('/\s+/', $ejibrand, -1, PREG_SPLIT_NO_EMPTY);
$ejifirstword = $ejibrandwords[0] ?? $ejibrand;
$ejifirstlen = core_text::strlen($ejifirstword);

if ($ejifirstlen >= 2 && $ejifirstlen <= 4) {
    $ejibrandmark = $ejifirstword;
} else if (count($ejibrandwords) > 1) {
    $ejibrandmark = '';
    foreach (array_slice($ejibrandwords, 0, 3) as $word) {
        $ejibrandmark .= core_text::substr($word, 0, 1);
    }
} else {
    $ejibrandmark = core_text::substr($ejibrand, 0, 3);
}
$ejibrandmark = core_text::strtoupper($ejibrandmark);

// Dashboard header: welcome line, KPI row and Continue learning. Only on the
// dashboard itself, and only for a real logged-in user - there is nothing to
// summarise for a guest, and the class would have no enrolments to read.
$ejidashboard = false;
if ($PAGE->pagelayout === 'mydashboard' && isloggedin() && !isguestuser()) {
    $ejidashboard = (new \theme_eji\output\dashboard_summary())->export_for_template($renderer);
}

// Topbar title. $PAGE->heading is what core shows as the page heading; inside a
// course it is the course name, which is what the EJI topbar wants too.
$topbartitle = $PAGE->heading !== '' ? $PAGE->heading : $sitename;

$templatecontext = [
    'sitename' => $sitename,
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    // EJI additions.
    'ejinav' => $ejinav,
    'ejisidebaropen' => $ejisidebaropen,
    'ejibrand' => $ejibrand,
    'ejibrandmark' => $ejibrandmark,
    'topbartitle' => $topbartitle,
    'ejidashboard' => $ejidashboard,
];

echo $OUTPUT->render_from_template('theme_eji/drawers', $templatecontext);
