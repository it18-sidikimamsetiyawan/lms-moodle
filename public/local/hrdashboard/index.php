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
 * HR Learning Dashboard.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_hrdashboard\local\filters;
use local_hrdashboard\local\scope;
use local_hrdashboard\local\stats;
use local_hrdashboard\output\hr_dashboard;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$scope = scope::for_user();
if ($scope === null) {
    throw new required_capability_exception(context_system::instance(), 'local/hrdashboard:viewhr', 'nopermissions', '');
}

$filters = filters::from_request();
$url = $filters->url('/local/hrdashboard/index.php');

$PAGE->set_url($url);
$PAGE->set_context($scope->context());
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('hrd-page');
$PAGE->set_title(get_string('hrdashboardtitle', 'local_hrdashboard'));
$PAGE->set_heading(get_string('hrdashboardtitle', 'local_hrdashboard'));
$PAGE->navbar->add(get_string('hrdashboard', 'local_hrdashboard'), $url);
$PAGE->requires->js_call_amd('local_hrdashboard/dashboard', 'init');

if (optional_param('refresh', 0, PARAM_BOOL)) {
    require_sesskey();
    stats::purge();
    redirect($url, get_string('refreshed', 'local_hrdashboard'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$data = stats::get($scope);

echo $OUTPUT->header();
echo $OUTPUT->render(new hr_dashboard($scope, $data, $filters));
echo $OUTPUT->footer();
