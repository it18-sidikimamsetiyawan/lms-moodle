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
 * Audits courses for certificate readiness and adds the standard EJI certificate.
 *
 * Default is a DRY RUN that changes nothing. With --execute, each READY course gets:
 *   - one new section at the end named "Certificate";
 *   - one "Course certificate" activity using the EJI Standard Certificate template,
 *     restricted by "course completed", activity completion tracking OFF (so
 *     course progress percentages are not affected), cm idnumber "ejicert-standard".
 * Nothing else in the course is touched. Courses without course completion criteria
 * are reported as NOT READY and never modified: completion rules are a course
 * design decision, not something this tool invents.
 *
 * --rollback removes only what --execute created (cm idnumber "ejicert-standard" and
 * the "Certificate" section if it is then empty). Issued certificates are kept.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

[$options] = cli_get_params(
    ['execute' => false, 'rollback' => false, 'courses' => '', 'help' => false],
    ['h' => 'help']
);
if ($options['help']) {
    echo "Usage: php local/ejicert/cli/setup_courses.php [--courses=10,12] [--execute | --rollback]\n" .
         "Without --execute/--rollback this is a dry run and changes nothing.\n";
    exit(0);
}

const LOCAL_EJICERT_IDNUMBER = 'ejicert-standard';
const LOCAL_EJICERT_SECTION = 'Certificate';

\core\session\manager::set_user(get_admin());

$template = \tool_certificate\template::find_by_name('EJI Standard Certificate');
if (!$template) {
    cli_error('Template "EJI Standard Certificate" not found. Run local/ejicert/cli/create_template.php first.');
}
$mode = $options['rollback'] ? 'ROLLBACK' : ($options['execute'] ? 'EXECUTE' : 'DRY RUN');

$where = 'id <> :site';
$params = ['site' => SITEID];
if ($options['courses'] !== '') {
    $ids = array_filter(array_map('intval', explode(',', $options['courses'])));
    [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
    $where .= " AND id $insql";
    $params += $inparams;
}
$courses = $DB->get_records_select('course', $where, $params, 'id');

$logdir = make_writable_directory($CFG->dataroot . '/local_ejicert');
$logfile = $logdir . '/setup-' . date('Ymd-His') . '-' . strtolower(str_replace(' ', '', $mode)) . '.csv';
$log = fopen($logfile, 'w');
fputcsv($log, ['courseid', 'shortname', 'fullname', 'completion', 'score', 'readiness', 'action', 'cmid', 'notes']);

/**
 * Describes a course's certificate readiness without changing it.
 *
 * @param stdClass $course
 * @param int $templateid
 * @return array
 */
function local_ejicert_inspect(stdClass $course, int $templateid): array {
    global $DB;
    $modinfo = get_fast_modinfo($course);
    $counts = [];
    $tracked = [];
    $existing = null;
    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->deletioninprogress) {
            continue;
        }
        if ($cm->modname === 'coursecertificate') {
            $tpl = $DB->get_field('coursecertificate', 'template', ['id' => $cm->instance]);
            if ((int)$tpl === $templateid) {
                $existing = $cm;
            }
            continue;
        }
        $counts[$cm->modname] = ($counts[$cm->modname] ?? 0) + 1;
        if ($cm->completion) {
            $tracked[] = $cm->modname;
        }
    }
    $criteria = $DB->get_records('course_completion_criteria', ['course' => $course->id]);
    $broken = 0;
    foreach ($criteria as $cr) {
        if ($cr->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY && !isset($modinfo->cms[$cr->moduleinstance])) {
            $broken++;
        }
    }
    $courseitem = \grade_item::fetch_course_item($course->id);
    $graded = $DB->count_records_select('grade_items', "courseid = ? AND itemtype = 'mod' AND gradetype = ?",
        [$course->id, GRADE_TYPE_VALUE]);
    $score = $graded > 0 && $courseitem && (float)$courseitem->grademax > 0 && !$courseitem->is_hidden();

    $completion = ($course->enablecompletion && $criteria) ? 'Configured' : 'Not configured';
    $notes = [];
    if ($existing) {
        $readiness = 'ALREADY CONFIGURED';
        $notes[] = "cm {$existing->id}";
    } else if ($course->format === 'singleactivity') {
        $readiness = 'MANUAL REVIEW';
        $notes[] = 'Single activity format cannot hold a second (certificate) activity';
    } else if ($completion !== 'Configured') {
        $readiness = 'NOT READY';
        $notes[] = 'Course completion criteria must be configured before certificate eligibility can be determined';
    } else if ($broken) {
        $readiness = 'MANUAL REVIEW';
        $notes[] = "$broken completion criteria point at missing activities";
    } else {
        $readiness = 'READY';
        $notes[] = count($criteria) . ' completion criteria';
    }
    ksort($counts);
    $acts = implode(' ', array_map(fn($k, $v) => "$k:$v", array_keys($counts), $counts));
    return [
        'completion' => $completion,
        'score' => $score ? 'Yes' : 'No',
        'readiness' => $readiness,
        'existing' => $existing,
        'activities' => $acts,
        'tracked' => count($tracked),
        'notes' => implode('; ', $notes),
    ];
}

/**
 * Adds the certificate section + activity to one course.
 *
 * @param stdClass $course
 * @param int $templateid
 * @return int new cm id
 */
function local_ejicert_add_certificate(stdClass $course, int $templateid): int {
    global $DB;
    $moduleid = $DB->get_field('modules', 'id', ['name' => 'coursecertificate'], MUST_EXIST);
    $section = course_create_section($course);
    course_update_section($course, $section, ['name' => LOCAL_EJICERT_SECTION]);

    $availability = json_encode([
        'op' => '&',
        'c' => [['type' => 'coursecompleted', 'id' => true]],
        'showc' => [true],
    ]);
    $moduleinfo = (object)[
        'modulename' => 'coursecertificate',
        'module' => $moduleid,
        'course' => $course->id,
        'section' => $section->section,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'name' => 'Course Certificate',
        'introeditor' => [
            'text' => '<p>Your EJI certificate becomes available here once you have completed this course. ' .
                'Open it to view and download the PDF.</p>',
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ],
        'showdescription' => 1,
        'template' => $templateid,
        'automaticsend' => 0,
        'expirydatetype' => 0,
        'expirydateoffset' => 0,
        'availability' => $availability,
        'completion' => COMPLETION_TRACKING_NONE,
        'cmidnumber' => LOCAL_EJICERT_IDNUMBER,
        'groupmode' => 0,
        'groupingid' => 0,
    ];
    try {
        $result = create_module($moduleinfo);
    } catch (\Throwable $e) {
        // Leave the course exactly as it was: drop the section we just added.
        course_delete_section($course, $section, true);
        throw $e;
    }
    return (int)$result->coursemodule;
}

/**
 * Removes what local_ejicert_add_certificate() created.
 *
 * @param stdClass $course
 * @return array [removed cm ids, removed section numbers]
 */
function local_ejicert_rollback(stdClass $course): array {
    global $DB;
    $removed = [];
    $sections = [];
    $cms = $DB->get_records('course_modules', ['course' => $course->id, 'idnumber' => LOCAL_EJICERT_IDNUMBER]);
    foreach ($cms as $cm) {
        $sectionid = $cm->section;
        course_delete_module($cm->id);
        $removed[] = $cm->id;
        $section = $DB->get_record('course_sections', ['id' => $sectionid]);
        if ($section && $section->name === LOCAL_EJICERT_SECTION) {
            $left = $DB->count_records_select('course_modules', 'section = ? AND deletioninprogress = 0', [$sectionid]);
            if (!$left) {
                course_delete_section($course, $section, true);
                $sections[] = $section->section;
            }
        }
    }
    return [$removed, $sections];
}

$summary = ['scanned' => 0, 'READY' => 0, 'ALREADY CONFIGURED' => 0, 'NOT READY' => 0, 'MANUAL REVIEW' => 0,
    'modified' => 0, 'unchanged' => 0];
$rows = [];
foreach ($courses as $course) {
    $summary['scanned']++;
    $info = local_ejicert_inspect($course, $template->get_id());
    $summary[$info['readiness']]++;
    $action = 'none';
    $cmid = $info['existing'] ? $info['existing']->id : '';

    if ($mode === 'ROLLBACK') {
        [$removedcms, $removedsections] = local_ejicert_rollback($course);
        if ($removedcms) {
            $action = 'removed cm ' . implode(',', $removedcms) .
                ($removedsections ? '; removed section ' . implode(',', $removedsections) : '');
            $summary['modified']++;
        } else {
            $summary['unchanged']++;
        }
    } else if ($info['readiness'] === 'READY') {
        if ($mode === 'EXECUTE') {
            $cmid = local_ejicert_add_certificate($course, $template->get_id());
            $action = 'added certificate';
        } else {
            $action = 'would add certificate';
        }
        $summary['modified']++;
    } else {
        $summary['unchanged']++;
    }

    $rows[] = [$course->id, $course->shortname, $course->fullname, $info['completion'], $info['score'],
        $info['readiness'], $action, $cmid, $info['notes'] . ($info['activities'] ? " | activities {$info['activities']}" : '')];
    fputcsv($log, end($rows));
}
fclose($log);

cli_writeln("Certificate migration - $mode");
cli_writeln(str_repeat('=', 60));
cli_writeln("Template            : EJI Standard Certificate (id {$template->get_id()})");
cli_writeln("Courses scanned     : {$summary['scanned']}");
cli_writeln("Ready               : {$summary['READY']}");
cli_writeln("Already configured  : {$summary['ALREADY CONFIGURED']}");
cli_writeln("Need completion     : {$summary['NOT READY']}");
cli_writeln("Need manual review  : {$summary['MANUAL REVIEW']}");
cli_writeln(($mode === 'DRY RUN' ? 'Will modify         : ' : 'Modified            : ') . $summary['modified']);
cli_writeln(($mode === 'DRY RUN' ? 'Will not modify     : ' : 'Not modified        : ') . $summary['unchanged']);
cli_writeln('');
foreach ($rows as $r) {
    cli_writeln(sprintf('%-3s | %-28s | %-14s | score %-3s | %-18s | %-22s | %s',
        $r[0], core_text::substr($r[2], 0, 28), $r[3], $r[4], $r[5], $r[6] . ($r[7] ? " ($r[7])" : ''), $r[8]));
}
cli_writeln('');
cli_writeln("Log: $logfile");
