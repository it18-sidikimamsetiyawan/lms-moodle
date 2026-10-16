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

namespace local_hrdashboard;

use core\hook\navigation\primary_extend;
use core\url;
use local_hrdashboard\local\scope;

/**
 * Hook callbacks for local_hrdashboard.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Adds "My Learning" and, for HR staff, "HR Dashboard" to the primary navigation.
     *
     * Each entry is gated on the same capability that guards the page itself.
     *
     * @param primary_extend $hook
     */
    public static function primary_extend(primary_extend $hook): void {
        if (!isloggedin() || isguestuser()) {
            return;
        }
        $view = $hook->get_primaryview();

        if (has_capability('local/hrdashboard:viewown', \context_system::instance())) {
            $view->add(
                get_string('mylearning', 'local_hrdashboard'),
                new url('/local/hrdashboard/my.php'),
                \navigation_node::TYPE_CUSTOM,
                null,
                'hrdashboard_my'
            );
        }

        if (scope::user_can_view_hr()) {
            $view->add(
                get_string('hrdashboard', 'local_hrdashboard'),
                new url('/local/hrdashboard/index.php'),
                \navigation_node::TYPE_CUSTOM,
                null,
                'hrdashboard_hr'
            );
        }
    }
}
