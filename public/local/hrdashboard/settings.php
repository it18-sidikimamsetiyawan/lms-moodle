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
 * Admin settings for local_hrdashboard.
 *
 * Every business rule the dashboard applies is configured here; nothing is
 * hard-coded in the plugin.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_hrdashboard', get_string('pluginname', 'local_hrdashboard'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        global $DB;
        $s = fn(string $key) => get_string($key, 'local_hrdashboard');
        $d = fn(string $key) => get_string($key . '_desc', 'local_hrdashboard');

        // Scope.
        $settings->add(new admin_setting_heading('local_hrdashboard/scopeheading', $s('settings:scope'), ''));

        $categories = core_course_category::make_categories_list('moodle/category:viewcourselist');
        $settings->add(new admin_setting_configmultiselect('local_hrdashboard/categoryscope',
            $s('categoryscope'), $d('categoryscope'), [], $categories));

        $settings->add(new admin_setting_configselect('local_hrdashboard/employeesource',
            $s('employeesource'), $d('employeesource'), 'all', [
                'all' => $s('employeesource:all'),
                'enrolled' => $s('employeesource:enrolled'),
                'cohort' => $s('employeesource:cohort'),
            ]));

        $cohorts = ['' => $s('none')];
        foreach ($DB->get_records('cohort', ['contextid' => context_system::instance()->id], 'name', 'id, name, idnumber') as $c) {
            if ($c->idnumber !== '') {
                $cohorts[$c->idnumber] = format_string($c->name) . ' (' . $c->idnumber . ')';
            }
        }
        $settings->add(new admin_setting_configselect('local_hrdashboard/employeecohort',
            $s('employeecohort'), $d('employeecohort'), '', $cohorts));

        $settings->add(new admin_setting_configcheckbox('local_hrdashboard/includehidden',
            $s('includehidden'), $d('includehidden'), 0));

        // Employee attributes.
        $settings->add(new admin_setting_heading('local_hrdashboard/attrheading', $s('settings:attributes'),
            $s('settings:attributes_desc')));

        $sources = ['' => $s('none'), 'department' => $s('source:department'), 'institution' => $s('source:institution')];
        foreach ($DB->get_records('user_info_field', null, 'sortorder', 'id, shortname, name, datatype') as $f) {
            if (in_array($f->datatype, ['text', 'menu'], true)) {
                $sources['profile_' . $f->shortname] = $s('source:profile') . ': ' . format_string($f->name)
                    . ' (' . $f->shortname . ')';
            }
        }
        $settings->add(new admin_setting_configselect('local_hrdashboard/departmentfield',
            $s('attr:department'), $d('attrfield'), 'department', $sources));
        $settings->add(new admin_setting_configselect('local_hrdashboard/divisionfield',
            $s('attr:division'), $d('attrfield'), '', $sources));
        $settings->add(new admin_setting_configselect('local_hrdashboard/positionfield',
            $s('attr:position'), $d('attrfield'), '', $sources));

        // At-risk thresholds.
        $settings->add(new admin_setting_heading('local_hrdashboard/riskheading', $s('settings:risk'),
            $s('settings:risk_desc')));
        $settings->add(new admin_setting_configtext('local_hrdashboard/inactivedays',
            $s('inactivedays'), $d('inactivedays'), 14, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/nologindays',
            $s('nologindays'), $d('nologindays'), 14, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/notstarteddays',
            $s('notstarteddays'), $d('notstarteddays'), 14, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/lowprogress',
            $s('lowprogress'), $d('lowprogress'), 50, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/deadlinedays',
            $s('deadlinedays'), $d('deadlinedays'), 7, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/pendingthreshold',
            $s('pendingthreshold'), $d('pendingthreshold'), 0, PARAM_INT));

        // Performance and display.
        $settings->add(new admin_setting_heading('local_hrdashboard/perfheading', $s('settings:performance'), ''));
        $settings->add(new admin_setting_configtext('local_hrdashboard/cachettl',
            $s('cachettl'), $d('cachettl'), 15, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/trendweeks',
            $s('trendweeks'), $d('trendweeks'), 12, PARAM_INT));
        $settings->add(new admin_setting_configtext('local_hrdashboard/timelinedays',
            $s('timelinedays'), $d('timelinedays'), 30, PARAM_INT));

        $settings->add(new admin_setting_description('local_hrdashboard/links', '',
            get_string('settings:links', 'local_hrdashboard', (object) [
                'hr' => (new moodle_url('/local/hrdashboard/index.php'))->out(),
                'my' => (new moodle_url('/local/hrdashboard/my.php'))->out(),
                'roles' => (new moodle_url('/admin/roles/manage.php'))->out(),
            ])));
    }
}
