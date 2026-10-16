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
 * Personal "My Learning" dashboard. Shows only the logged-in user's own data.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core\url;
use local_hrdashboard\local\scope;
use local_hrdashboard\output\my_dashboard;

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}
require_capability('local/hrdashboard:viewown', context_system::instance());

$url = new url('/local/hrdashboard/my.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('report');
$PAGE->add_body_class('hrd-page');
$PAGE->set_title(get_string('mylearningtitle', 'local_hrdashboard'));
$PAGE->set_heading(get_string('mylearning', 'local_hrdashboard'));
$PAGE->navbar->add(get_string('mylearning', 'local_hrdashboard'), $url);
$PAGE->requires->js_call_amd('local_hrdashboard/dashboard', 'init');

$hrurl = scope::user_can_view_hr() ? new url('/local/hrdashboard/index.php') : null;

echo $OUTPUT->header();
echo $OUTPUT->render(new my_dashboard((int) $USER->id, $hrurl));
echo $OUTPUT->footer();
