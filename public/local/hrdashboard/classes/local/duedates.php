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

namespace local_hrdashboard\local;

/**
 * Resolves the due date of a course module.
 *
 * Precedence:
 *   1. course_modules.completionexpected ("Expect completed on") - the
 *      explicit expectation an HR/course author sets on any activity type;
 *   2. the module's own closing field (assign due date, quiz close, ...).
 *
 * Only modules actually installed on the site are queried; the field list is
 * taken from each module's own install.xml and can be extended here.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duedates {

    /** @var string[] modname => closing-date column. */
    const FIELDS = [
        'assign' => 'duedate',
        'quiz' => 'timeclose',
        'lesson' => 'deadline',
        'feedback' => 'timeclose',
        'scorm' => 'timeclose',
        'workshop' => 'submissionend',
        'choice' => 'timeclose',
        'data' => 'timeavailableto',
        'forum' => 'duedate',
    ];

    /**
     * Due timestamps for a set of course modules.
     *
     * @param array $cms Objects/arrays with keys id, modname, instance, completionexpected.
     * @return array cmid => timestamp (0 = no due date)
     */
    public static function for_cms(array $cms): array {
        global $DB;

        $out = [];
        $bymod = [];
        foreach ($cms as $cm) {
            $cm = (object) $cm;
            $out[(int) $cm->id] = (int) ($cm->completionexpected ?? 0);
            if (empty($out[(int) $cm->id]) && isset(self::FIELDS[$cm->modname])) {
                $bymod[$cm->modname][(int) $cm->instance] = (int) $cm->id;
            }
        }

        foreach ($bymod as $modname => $instances) {
            if (!\core_component::get_component_directory('mod_' . $modname)) {
                continue;
            }
            $field = self::FIELDS[$modname];
            [$insql, $params] = $DB->get_in_or_equal(array_keys($instances), SQL_PARAMS_NAMED);
            $records = $DB->get_records_select_menu($modname, "id $insql", $params, '', "id, $field");
            foreach ($records as $instanceid => $due) {
                $out[$instances[(int) $instanceid]] = (int) $due;
            }
        }

        return $out;
    }
}
