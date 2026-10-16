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
 * One learner: courses and activity-level completion detail.
 *
 * Own data: needs local/hrdashboard:viewown.
 * Another user's data: needs HR scope that contains that user.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core\url;
use local_hrdashboard\local\filters;
use local_hrdashboard\local\scope;
use local_hrdashboard\local\stats;
use local_hrdashboard\output\user_detail;

$userid = optional_param('id', 0, PARAM_INT) ?: (int) $USER->id;
$courseid = optional_param('courseid', 0, PARAM_INT);

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$isself = $userid === (int) $USER->id;
$data = null;
$filters = filters::from_request();

if ($isself) {
    require_capability('local/hrdashboard:viewown', context_system::instance());
    $context = context_system::instance();
    $backurl = new url('/local/hrdashboard/my.php');
} else {
    $scope = scope::for_user();
    if ($scope === null) {
        throw new required_capability_exception(context_system::instance(), 'local/hrdashboard:viewhr', 'nopermissions', '');
    }
    if (!$scope->contains_user($userid)) {
        throw new moodle_exception('notinscope', 'local_hrdashboard');
    }
    $context = $scope->context();
    $data = stats::get($scope);
    $backurl = $filters->url('/local/hrdashboard/index.php');
}

$user = core_user::get_user($userid, '*', MUST_EXIST);
if ($user->deleted) {
    throw new moodle_exception('invaliduser');
}

$course = null;
if ($courseid) {
    $course = get_course($courseid);
    if ($course->id == SITEID || !is_enrolled(context_course::instance($course->id), $user)) {
        throw new moodle_exception('notinscope', 'local_hrdashboard');
    }
    if (!$isself && !$scope->contains_course((int) $course->id)) {
        throw new moodle_exception('notinscope', 'local_hrdashboard');
    }
}

$url = new url('/local/hrdashboard/user.php', ['id' => $userid] + ($courseid ? ['courseid' => $courseid] : []));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('hrd-page');
$PAGE->set_title(($isself ? get_string('mylearning', 'local_hrdashboard') : get_string('employeedetail', 'local_hrdashboard'))
    . ': ' . fullname($user));
$PAGE->set_heading(fullname($user));
$PAGE->requires->js_call_amd('local_hrdashboard/dashboard', 'init');
if ($isself) {
    $PAGE->navbar->add(get_string('mylearning', 'local_hrdashboard'), $backurl);
} else {
    $PAGE->navbar->add(get_string('hrdashboard', 'local_hrdashboard'), $backurl);
}
$PAGE->navbar->add(fullname($user), new url('/local/hrdashboard/user.php', ['id' => $userid]));
if ($course) {
    $PAGE->navbar->add(format_string($course->fullname, true, ['context' => context_course::instance($course->id)]), $url);
}

echo $OUTPUT->header();
echo $OUTPUT->render(new user_detail($user, $course, $isself, $data, $backurl));
echo $OUTPUT->footer();
