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
 * Settings for the EJI ONE theme.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingeji', get_string('configtitle', 'theme_eji'));

    // ------------------------------------------------------------------
    // General.
    // ------------------------------------------------------------------
    $page = new admin_settingpage('theme_eji_general', get_string('generalsettings', 'theme_eji'));

    // Preset. Boost's two presets are offered, plus any uploaded by an admin.
    $name = 'theme_eji/preset';
    $title = get_string('preset', 'theme_eji');
    $description = get_string('preset_desc', 'theme_eji');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_eji', 'preset', 0, 'itemid, filepath, filename', false);
    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'eji');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Additional preset files.
    $name = 'theme_eji/presetfiles';
    $title = get_string('presetfiles', 'theme_eji');
    $description = get_string('presetfiles_desc', 'theme_eji');
    $setting = new admin_setting_configstoredfile(
        $name,
        $title,
        $description,
        'preset',
        0,
        ['maxfiles' => 20, 'accepted_types' => ['.scss']]
    );
    $page->add($setting);

    $settings->add($page);

    // ------------------------------------------------------------------
    // Branding.
    // ------------------------------------------------------------------
    $page = new admin_settingpage('theme_eji_brand', get_string('brandsettings', 'theme_eji'));

    // Accent colour. Defaults to the EJI accent token, see scss/eji/_tokens.scss.
    $name = 'theme_eji/brandcolor';
    $title = get_string('brandcolor', 'theme_eji');
    $description = get_string('brandcolor_desc', 'theme_eji');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar brand name.
    $name = 'theme_eji/sidebarbrand';
    $title = get_string('sidebarbrand', 'theme_eji');
    $description = get_string('sidebarbrand_desc', 'theme_eji');
    $setting = new admin_setting_configtext($name, $title, $description, 'EJI', PARAM_TEXT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login tagline.
    $name = 'theme_eji/portaltagline';
    $title = get_string('portaltagline', 'theme_eji');
    $description = get_string('portaltagline_desc', 'theme_eji');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Background image.
    $name = 'theme_eji/backgroundimage';
    $title = get_string('backgroundimage', 'theme_eji');
    $description = get_string('backgroundimage_desc', 'theme_eji');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login background image.
    $name = 'theme_eji/loginbackgroundimage';
    $title = get_string('loginbackgroundimage', 'theme_eji');
    $description = get_string('loginbackgroundimage_desc', 'theme_eji');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbackgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    // ------------------------------------------------------------------
    // Advanced.
    // ------------------------------------------------------------------
    $page = new admin_settingpage('theme_eji_advanced', get_string('advancedsettings', 'theme_eji'));

    // Raw initial SCSS - injected after the EJI tokens, so it can override them.
    $name = 'theme_eji/scsspre';
    $title = get_string('rawscsspre', 'theme_eji');
    $description = get_string('rawscsspre_desc', 'theme_eji');
    $setting = new admin_setting_scsscode($name, $title, $description, '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS - injected at the very end.
    $name = 'theme_eji/scss';
    $title = get_string('rawscss', 'theme_eji');
    $description = get_string('rawscss_desc', 'theme_eji');
    $setting = new admin_setting_scsscode($name, $title, $description, '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
