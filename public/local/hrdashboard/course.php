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
 * HR drill-down: one course.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_hrdashboard\local\filters;
use local_hrdashboard\local\scope;
use local_hrdashboard\local\stats;
use local_hrdashboard\output\course_detail;

$courseid = required_param('id', PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$scope = scope::for_user();
if ($scope === null) {
    throw new required_capability_exception(context_system::instance(), 'local/hrdashboard:viewhr', 'nopermissions', '');
}
if (!$scope->contains_course($courseid)) {
    throw new moodle_exception('notinscope', 'local_hrdashboard');
}

$filters = filters::from_request();
$url = $filters->url('/local/hrdashboard/course.php', ['id' => $courseid, 'status' => $status]);
$data = stats::get($scope);
if (!isset($data['courses'][$courseid])) {
    throw new moodle_exception('notinscope', 'local_hrdashboard');
}
$course = $data['courses'][$courseid];

$PAGE->set_url($url);
$PAGE->set_context($scope->context());
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('hrd-page');
$PAGE->set_title(get_string('coursedetail', 'local_hrdashboard') . ': ' . $course['fullname']);
$PAGE->set_heading($course['fullname']);
$PAGE->navbar->add(get_string('hrdashboard', 'local_hrdashboard'), $filters->url('/local/hrdashboard/index.php'));
$PAGE->navbar->add($course['fullname'], $url);
$PAGE->requires->js_call_amd('local_hrdashboard/dashboard', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render(new course_detail($scope, $data, $filters, $courseid, $status));
echo $OUTPUT->footer();
