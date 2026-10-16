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

namespace local_ejimedia;

use core_course\hook\before_course_viewed;

/**
 * Hook callbacks for local_ejimedia.
 *
 * @package    local_ejimedia
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Attach inline media players to the course page activities.
     *
     * This fires from course/view.php before the course format renders, which is
     * the last point at which cm_info objects can still be enriched.
     *
     * @param before_course_viewed $hook the hook carrying the course being viewed
     */
    public static function before_course_viewed(before_course_viewed $hook): void {
        inline_player::inject_into_course($hook->course);
    }
}
