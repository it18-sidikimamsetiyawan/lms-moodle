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

use core_course\cm_info;
use core_media_manager;
use html_writer;
use moodle_url;
use stdClass;
use stored_file;

/**
 * Renders file resources that hold a video as a player on the course page itself.
 *
 * Moodle already knows how to build a player: core_media_manager picks the best
 * enabled media plugin (VideoJS on this site) and returns markup with the full
 * control bar. What it does not do by itself is put that player on
 * course/view.php - a File resource there is only ever a link to
 * mod/resource/view.php.
 *
 * The course format reads the extra content of an activity card from
 * cm_info::get_formatted_content(), which is whatever cm_info::set_content()
 * last stored. Setting it from the before_course_viewed hook - after modinfo is
 * built, before the format renders - puts the player inside the existing
 * activity card without touching core, the theme, or the activity itself.
 *
 * @package    local_ejimedia
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inline_player {

    /** @var string[] Module types this plugin knows how to turn into an inline player. */
    private const SUPPORTED_MODS = ['resource'];

    /**
     * Give every eligible activity of a course an inline player.
     *
     * @param stdClass $course the course being viewed
     */
    public static function inject_into_course(stdClass $course): void {
        global $CFG, $DB, $PAGE;

        if (!get_config('local_ejimedia', 'enabled')) {
            return;
        }

        require_once($CFG->libdir . '/filelib.php');

        $candidates = [];
        foreach (get_fast_modinfo($course)->get_cms() as $cm) {
            if (self::is_candidate($cm)) {
                $candidates[] = $cm;
            }
        }
        if (!$candidates) {
            return;
        }

        // The revision is the itemid segment of the pluginfile URL. mod_resource
        // bumps it whenever the file changes so that browsers drop their cache.
        $revisions = $DB->get_records_menu('resource', ['course' => $course->id], '', 'id, revision');

        // Passing $PAGE lets the media plugins register their JS and CSS. This is
        // why the work has to happen before $OUTPUT->header() is called.
        $mediamanager = core_media_manager::instance($PAGE);
        $maxwidth = (int) get_config('local_ejimedia', 'maxwidth');

        foreach ($candidates as $cm) {
            if (!$file = self::get_main_video_file($cm)) {
                continue;
            }

            $url = self::make_file_url($file, (int) ($revisions[$cm->instance] ?? 0));

            // Do not replace the link with a dead player when no enabled media
            // plugin can handle this file - the activity keeps working as before.
            if (!$mediamanager->can_embed_url($url)) {
                continue;
            }

            // Passing the width explicitly is what keeps the player from falling
            // back to the narrow site-wide default media size.
            $player = $mediamanager->embed_url($url, $cm->get_formatted_name(), $maxwidth, 0, [
                core_media_manager::OPTION_BLOCK => true,
            ]);
            if (trim($player) === '') {
                continue;
            }

            self::append_content($cm, self::wrap($player, $maxwidth, $cm->id));
        }
    }

    /**
     * Whether an activity is one this plugin should look at.
     *
     * @param cm_info $cm the activity
     * @return bool
     */
    private static function is_candidate(cm_info $cm): bool {
        return in_array($cm->modname, self::SUPPORTED_MODS, true)
            && $cm->uservisible
            && $cm->is_visible_on_course_page()
            && empty($cm->deletioninprogress);
    }

    /**
     * The main file of a File resource, but only when it is a video.
     *
     * @param cm_info $cm the activity
     * @return stored_file|null null when the resource holds something else
     */
    private static function get_main_video_file(cm_info $cm): ?stored_file {
        $fs = get_file_storage();

        // Same ordering mod/resource/view.php uses: the main file sorts first.
        $files = $fs->get_area_files(
            $cm->context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false
        );
        if (!$files) {
            return null;
        }

        $file = reset($files);

        return str_starts_with((string) $file->get_mimetype(), 'video/') ? $file : null;
    }

    /**
     * Build the pluginfile URL for a resource file.
     *
     * Going through make_pluginfile_url keeps the path, the slasharguments
     * setting and the access check in mod_resource's hands - nothing about the
     * location of the file is assumed here.
     *
     * @param stored_file $file the file to serve
     * @param int $revision the resource revision, used as the itemid segment
     * @return moodle_url
     */
    private static function make_file_url(stored_file $file, int $revision): moodle_url {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            'mod_resource',
            'content',
            $revision,
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    /**
     * Wrap the player so the stylesheet can size and frame it.
     *
     * @param string $player markup returned by the media manager
     * @param int $maxwidth widest the player may get on a large screen, in pixels
     * @param int $cmid the activity this player belongs to
     * @return string
     */
    private static function wrap(string $player, int $maxwidth, int $cmid): string {
        $attributes = ['data-region' => 'ejimedia-player', 'data-cmid' => $cmid];
        if ($maxwidth > 0) {
            $attributes['style'] = '--ejimedia-maxwidth: ' . $maxwidth . 'px;';
        }

        return html_writer::div($player, 'local-ejimedia-player', $attributes);
    }

    /**
     * Add the player below whatever the activity card already shows.
     *
     * Reading the current content first means an activity with "Display
     * description on course page" enabled keeps its description, with the player
     * underneath rather than instead of it.
     *
     * @param cm_info $cm the activity
     * @param string $html the wrapped player
     */
    private static function append_content(cm_info $cm, string $html): void {
        $existing = $cm->get_formatted_content(['overflowdiv' => true, 'noclean' => true]);

        // The description came back already formatted and the player is trusted
        // markup we just built, so flag the result as formatted to stop
        // format_text() running over it a second time.
        $cm->set_content($existing . $html, true);
    }
}
