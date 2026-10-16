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
 * Settings for local_ejimedia.
 *
 * @package    local_ejimedia
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_ejimedia', get_string('pluginname', 'local_ejimedia'));

    $settings->add(new admin_setting_configcheckbox(
        'local_ejimedia/enabled',
        get_string('enabled', 'local_ejimedia'),
        get_string('enabled_desc', 'local_ejimedia'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_ejimedia/maxwidth',
        get_string('maxwidth', 'local_ejimedia'),
        get_string('maxwidth_desc', 'local_ejimedia'),
        720,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
