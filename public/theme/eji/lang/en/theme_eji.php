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
 * Language strings for the EJI ONE theme.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'EJI';
$string['choosereadme'] = 'EJI is a child theme of Boost that applies the EJI ONE design system: Poppins typography, the EJI colour tokens, a persistent left sidebar and a compact topbar. All Moodle functionality, permissions and activity behaviour are unchanged.';
$string['configtitle'] = 'EJI';
$string['privacy:metadata'] = 'The EJI theme does not store any personal data.';

// Settings pages.
$string['generalsettings'] = 'General';
$string['brandsettings'] = 'Branding';
$string['advancedsettings'] = 'Advanced';

// Preset.
$string['preset'] = 'Theme preset';
$string['preset_desc'] = 'Pick a preset to broadly change the look of the theme. EJI design tokens are applied on top of whichever preset is selected.';
$string['presetfiles'] = 'Additional theme preset files';
$string['presetfiles_desc'] = 'Preset files can be used to dramatically alter the appearance of the theme. See <a href="https://docs.moodle.org/dev/Boost_Presets">Boost presets</a> for information on creating and sharing your own preset files.';

// Brand.
$string['brandcolor'] = 'Accent colour';
$string['brandcolor_desc'] = 'The interactive accent colour used for buttons, links and focus rings. Leave empty to use the EJI accent (#1D4ED8).';
$string['sidebarbrand'] = 'Sidebar brand name';
$string['sidebarbrand_desc'] = 'Text shown next to the logo at the top of the sidebar. Leave empty to use the site short name.';
$string['portaltagline'] = 'Login tagline';
$string['portaltagline_desc'] = 'Short line shown under the brand on the login screen. Leave empty to use the site name.';
$string['backgroundimage'] = 'Background image';
$string['backgroundimage_desc'] = 'The image to display as a background of the site. The background image you upload here will override the background image in your theme preset files.';
$string['loginbackgroundimage'] = 'Login page background image';
$string['loginbackgroundimage_desc'] = 'The image to display as a background of the login page panel. If left empty the EJI primary gradient is used.';

// Raw SCSS.
$string['rawscss'] = 'Raw SCSS';
$string['rawscss_desc'] = 'Use this field to provide SCSS or CSS code which will be injected at the end of the style sheet.';
$string['rawscsspre'] = 'Raw initial SCSS';
$string['rawscsspre_desc'] = 'In this field you can provide initialising SCSS code, it will be injected before everything else. Most of the time you will use this setting to define variables.';

// Navigation.
$string['navsection:learning'] = 'Learning';
$string['navsection:mylearning'] = 'My learning';
$string['navsection:tools'] = 'Tools';
$string['navsection:profile'] = 'Profile';
$string['navsection:administration'] = 'Administration';
$string['navsection:main'] = 'Main menu';
$string['togglesidebar'] = 'Toggle navigation sidebar';
$string['closesidebar'] = 'Close navigation sidebar';
$string['sidebarnavlabel'] = 'Site navigation';
$string['searchnav'] = 'Search menu';
$string['searchnavplaceholder'] = 'Search menu';

// Dashboard.
$string['welcomeback'] = 'Welcome back, {$a}';
$string['welcome'] = 'Welcome, {$a}';
$string['kpi:courses'] = 'My courses';
$string['kpi:inprogress'] = 'In progress';
$string['kpi:completed'] = 'Completed';
$string['kpi:notstarted'] = 'Not started';
$string['continuelearning'] = 'Continue learning';
$string['continuelearningdesc'] = 'Pick up where you left off';
$string['continue'] = 'Continue';
$string['nocoursesyet'] = 'No courses yet';
$string['nocoursesyetdesc'] = 'Courses you are enrolled in will appear here.';

// Status badges.
$string['status:notstarted'] = 'Not started';
$string['status:inprogress'] = 'In progress';
$string['status:completed'] = 'Completed';
$string['status:locked'] = 'Locked';

$string['courseprogress'] = 'Course progress';

// Course catalogue (/course/index.php).
$string['catalog:courses'] = '{$a} courses';
$string['catalog:onecourse'] = '1 course';
$string['catalog:subcategories'] = '{$a} subcategories';
$string['catalog:onesubcategory'] = '1 subcategory';
$string['catalog:empty'] = 'No courses yet';
$string['catalog:emptydesc'] = 'Courses added to this category will appear here.';
$string['catalog:browseall'] = 'Browse all courses';
$string['catalog:viewcourse'] = 'View course';
