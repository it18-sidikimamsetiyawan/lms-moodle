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
 * Setup / rollback helper for local_hrdashboard.
 *
 * Creates the "HR Learning Admin" role (shortname hrlearning) holding only the
 * plugin's HR capabilities, assigns or unassigns it, and optionally makes
 * "My Learning" the default landing page. Everything here can also be done in
 * the UI (Site administration > Users > Permissions > Define roles).
 *
 *   php local/hrdashboard/cli/setup.php --role                 create/update the role
 *   php local/hrdashboard/cli/setup.php --assign=hr.admin      assign at system level (all categories)
 *   php local/hrdashboard/cli/setup.php --assign=hr.ops --category=LEADDEV   assign on one category (idnumber or id)
 *   php local/hrdashboard/cli/setup.php --unassign=hr.admin
 *   php local/hrdashboard/cli/setup.php --homepage=on|off      default home page -> /local/hrdashboard/my.php
 *   php local/hrdashboard/cli/setup.php --delete-role          rollback: remove the role
 *   php local/hrdashboard/cli/setup.php --warm                 build the statistics cache now
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/accesslib.php');

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'role' => false,
    'assign' => '',
    'unassign' => '',
    'category' => '',
    'homepage' => '',
    'delete-role' => false,
    'warm' => false,
], ['h' => 'help']);

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || !array_filter(array_diff_key($options, ['help' => 1, 'category' => 1]))) {
    cli_writeln(file_get_contents(__FILE__, false, null, 0, 1900));
    exit(0);
}

const HRD_ROLE = 'hrlearning';
$systemcontext = context_system::instance();

if ($options['role']) {
    $roleid = (int) $DB->get_field('role', 'id', ['shortname' => HRD_ROLE]);
    if (!$roleid) {
        $roleid = create_role('HR Learning Admin', HRD_ROLE,
            'Monitors employee learning progress through the HR Learning Dashboard. Holds no course editing rights.');
        cli_writeln("Created role '" . HRD_ROLE . "' (id $roleid).");
    } else {
        cli_writeln("Role '" . HRD_ROLE . "' already exists (id $roleid).");
    }
    set_role_contextlevels($roleid, [CONTEXT_SYSTEM, CONTEXT_COURSECAT]);
    foreach (['local/hrdashboard:viewhr', 'local/hrdashboard:export', 'local/hrdashboard:viewown'] as $cap) {
        assign_capability($cap, CAP_ALLOW, $roleid, $systemcontext->id, true);
    }
    // Lets HR see learner names in the dashboard pages that link to core profiles.
    assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $systemcontext->id, true);
    $systemcontext->mark_dirty();
    cli_writeln('Capabilities set: viewhr, export, viewown, user:viewdetails. Assignable at system and category level.');
}

if ($options['assign'] !== '' || $options['unassign'] !== '') {
    $username = $options['assign'] !== '' ? $options['assign'] : $options['unassign'];
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', MUST_EXIST);
    $roleid = (int) $DB->get_field('role', 'id', ['shortname' => HRD_ROLE], MUST_EXIST);

    $context = $systemcontext;
    if ($options['category'] !== '') {
        $cat = is_numeric($options['category'])
            ? $DB->get_record('course_categories', ['id' => (int) $options['category']], '*', MUST_EXIST)
            : $DB->get_record('course_categories', ['idnumber' => $options['category']], '*', MUST_EXIST);
        $context = context_coursecat::instance($cat->id);
    }
    if ($options['assign'] !== '') {
        role_assign($roleid, $user->id, $context->id);
        cli_writeln("Assigned '" . HRD_ROLE . "' to $username in " . $context->get_context_name() . '.');
    } else {
        role_unassign($roleid, $user->id, $context->id);
        cli_writeln("Unassigned '" . HRD_ROLE . "' from $username in " . $context->get_context_name() . '.');
    }
}

if ($options['homepage'] !== '') {
    if ($options['homepage'] === 'on') {
        set_config('defaulthomepage', '/local/hrdashboard/my.php');
        cli_writeln("defaulthomepage set to /local/hrdashboard/my.php (My Learning is the landing page).");
    } else {
        set_config('defaulthomepage', HOMEPAGE_MY);
        cli_writeln('defaulthomepage restored to Dashboard (/my/).');
    }
}

if ($options['delete-role']) {
    $roleid = (int) $DB->get_field('role', 'id', ['shortname' => HRD_ROLE]);
    if ($roleid) {
        delete_role($roleid);
        cli_writeln("Deleted role '" . HRD_ROLE . "'.");
    } else {
        cli_writeln('Role not found; nothing to delete.');
    }
}

if ($options['warm']) {
    $start = microtime(true);
    $data = \local_hrdashboard\local\stats::get(\local_hrdashboard\local\scope::all(), true);
    cli_writeln(sprintf('Statistics built in %.2fs: %d courses, %d employees, %d enrolments.',
        microtime(true) - $start, count($data['courses']), count($data['users']), count($data['rows'])));
}

exit(0);
