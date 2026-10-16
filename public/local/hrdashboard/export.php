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
 * Employee Learning Progress Report export (CSV / Excel / PDF via core dataformats).
 *
 * Honours the same filters and HR scope as the pages.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_hrdashboard\local\config;
use local_hrdashboard\local\filters;
use local_hrdashboard\local\risk;
use local_hrdashboard\local\scope;
use local_hrdashboard\local\stats;
use local_hrdashboard\output\employees_page;
use local_hrdashboard\output\helper;

$format = required_param('format', PARAM_ALPHA);

require_login(null, false);
if (isguestuser()) {
    throw new require_login_exception('Guests are not allowed here.');
}

$scope = scope::for_user();
if ($scope === null) {
    throw new required_capability_exception(context_system::instance(), 'local/hrdashboard:viewhr', 'nopermissions', '');
}
if (!$scope->can_export()) {
    throw new required_capability_exception($scope->context(), 'local/hrdashboard:export', 'nopermissions', '');
}
if (!\core_component::get_component_directory('dataformat_' . $format)) {
    throw new moodle_exception('invaliddataformat', 'error');
}

$PAGE->set_url(new \core\url('/local/hrdashboard/export.php'));
$PAGE->set_context($scope->context());

$filters = filters::from_request();
$data = stats::get($scope);
$rows = employees_page::rows($data, $filters);

$columns = ['employee' => get_string('col:employee', 'local_hrdashboard')];
foreach (config::attributes() as $attribute => $source) {
    $columns[$attribute] = get_string('attr:' . $attribute, 'local_hrdashboard');
}
$columns += [
    'course' => get_string('col:course', 'local_hrdashboard'),
    'category' => get_string('col:category', 'local_hrdashboard'),
    'enrolledon' => get_string('col:enrolledon', 'local_hrdashboard'),
    'progress' => get_string('col:progress', 'local_hrdashboard') . ' (%)',
    'completed' => get_string('col:completed', 'local_hrdashboard'),
    'completedon' => get_string('col:completedon', 'local_hrdashboard'),
    'score' => get_string('col:score', 'local_hrdashboard') . ' (%)',
    'lastactivity' => get_string('col:lastactivity', 'local_hrdashboard'),
    'status' => get_string('col:status', 'local_hrdashboard'),
    'reasons' => get_string('col:reasons', 'local_hrdashboard'),
];

$iterator = (function() use ($rows) {
    foreach ($rows as $item) {
        $u = $item['user'];
        $c = $item['course'];
        $r = $item['row'];
        $out = ['employee' => $u['fullname']];
        foreach (array_keys(config::attributes()) as $attribute) {
            $out[$attribute] = $u[$attribute] ?? '';
        }
        $out += [
            'course' => $c['fullname'],
            'category' => $c['categoryname'],
            'enrolledon' => $r['timeenrolled'] ? userdate($r['timeenrolled'], '%Y-%m-%d') : '',
            'progress' => $r['progress'] ?? '',
            'completed' => $r['completed'] ? get_string('yes') : get_string('no'),
            'completedon' => $r['timecompleted'] ? userdate($r['timecompleted'], '%Y-%m-%d') : '',
            'score' => $r['score'] ?? '',
            'lastactivity' => $r['lastactivity'] ? userdate($r['lastactivity'], '%Y-%m-%d %H:%M') : '',
            'status' => risk::status_text($r['status']),
            'reasons' => implode('; ', helper::reasons($r['risk'])),
        ];
        yield $out;
    }
})();

$filename = get_string('exportfilename', 'local_hrdashboard') . '-' . userdate(time(), '%Y%m%d-%H%M');
\core\dataformat::download_data($filename, $format, $columns, $iterator);
