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
 * Admin settings for local_ejicert.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_ejicert', get_string('pluginname', 'local_ejicert'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_ejicert/issuername',
        get_string('issuername', 'local_ejicert'), get_string('issuername_desc', 'local_ejicert'),
        'Eka Jaya Internasional', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_ejicert/idprefix',
        get_string('idprefix', 'local_ejicert'), get_string('idprefix_desc', 'local_ejicert'),
        'EJI-CERT', '/^[A-Z0-9][A-Z0-9-]{0,19}$/'));
    $settings->add(new admin_setting_configcheckbox('local_ejicert/showscore',
        get_string('showscore', 'local_ejicert'), get_string('showscore_desc', 'local_ejicert'), 1));
    $settings->add(new admin_setting_configtext('local_ejicert/scorelabel',
        get_string('scorelabel', 'local_ejicert'), get_string('scorelabel_desc', 'local_ejicert'),
        'Final Score', PARAM_TEXT));
    $settings->add(new admin_setting_description('local_ejicert/links', '',
        get_string('adminlinks', 'local_ejicert', (object)[
            'templates' => (new moodle_url('/admin/tool/certificate/manage_templates.php'))->out(),
            'verify' => (new moodle_url('/local/ejicert/verify.php'))->out(),
        ])));
}
