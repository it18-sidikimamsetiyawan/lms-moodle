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

namespace local_hrdashboard\task;

use local_hrdashboard\local\scope;
use local_hrdashboard\local\stats;

/**
 * Pre-builds the site-wide HR matrix so HR pages open from a warm cache.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_stats extends \core\task\scheduled_task {

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:refreshstats', 'local_hrdashboard');
    }

    /**
     * Rebuild the matrix and store it.
     */
    public function execute(): void {
        $data = stats::get(scope::all(), true);
        mtrace(sprintf('local_hrdashboard: %d courses, %d employees, %d enrolments aggregated.',
            count($data['courses']), count($data['users']), count($data['rows'])));
    }
}
