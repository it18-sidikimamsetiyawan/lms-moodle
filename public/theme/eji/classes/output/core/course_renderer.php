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

namespace theme_eji\output\core;

use core_course_category;
use core_course_list_element;
use coursecat_helper;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/renderer.php');

/**
 * EJI ONE course catalogue renderer.
 *
 * Restyles /course/index.php (and the search results reached from its search
 * box) so it reads as a continuation of the dashboard: category cards, course
 * cards with an image, and an empty state.
 *
 * Presentation only. Which categories and courses appear, their order, paging,
 * visibility and permissions are all still decided by core - every method here
 * either calls the parent or reuses core's own helper methods for the data.
 * The markup hooks the category expander (moodle-course-categoryexpander)
 * depends on - .category, .info, .categoryname, .content, .coursebox,
 * .moreinfo and the data-* attributes - are kept exactly as core emits them.
 *
 * Every override falls straight through to core outside the catalogue pages,
 * so the site home and any other page listing courses are left untouched.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \core_course_renderer {

    /** @var string[] Page types that get the EJI catalogue treatment. */
    const CATALOGUE_PAGETYPES = ['course-index-category', 'course-search', 'course-category.ajax'];

    /** @var bool Whether the last coursecat_tree() call produced nothing. */
    protected $ejitreeempty = false;

    /**
     * Is the current page one of the catalogue pages?
     *
     * @return bool
     */
    protected function is_catalogue_page(): bool {
        return in_array($this->page->pagetype, self::CATALOGUE_PAGETYPES, true);
    }

    /**
     * Category page: core's output, plus an empty state when there is nothing to list.
     *
     * @param int|\stdClass|core_course_category $category
     * @return string
     */
    public function course_category($category) {
        $this->ejitreeempty = false;
        $output = parent::course_category($category);
        if (!$this->is_catalogue_page()) {
            return $output;
        }
        if ($this->ejitreeempty) {
            $output .= $this->catalogue_empty_state($category);
        }
        return html_writer::div($output, 'eji-catalog');
    }

    /**
     * The category tree, headed by a title and a count, laid out on one row with "Expand all".
     *
     * @param coursecat_helper $chelper
     * @param core_course_category $coursecat
     * @return string
     */
    protected function coursecat_tree(coursecat_helper $chelper, $coursecat) {
        $tree = parent::coursecat_tree($chelper, $coursecat);
        if (!$this->is_catalogue_page()) {
            return $tree;
        }
        $this->ejitreeempty = ($tree === '');
        if ($this->ejitreeempty) {
            return $tree;
        }

        // Core shows subcategories when there are any, courses otherwise (see course_category()).
        $browse = optional_param('browse', null, PARAM_ALPHA);
        $showcategories = $coursecat->get_children_count() && $browse !== 'courses';
        if ($showcategories) {
            $title = get_string('categories');
            $count = $coursecat->get_children_count();
        } else {
            $title = get_string('courses');
            $count = $coursecat->get_courses_count();
        }
        $head = html_writer::div(
            html_writer::tag('h2', $title, ['class' => 'eji-catalog__title']) .
                html_writer::span($count, 'eji-catalog__count'),
            'eji-catalog__head'
        );

        return html_writer::div($head . $tree, 'eji-catalog__section');
    }

    /**
     * One category in the tree.
     *
     * Mirrors core_course_renderer::coursecat_category() (Moodle 5.2.3). The
     * only additions are the icon tile and the course/subcategory count inside
     * .categoryname - inside it so that the whole row stays the expander's
     * click target, the same place core puts its own "(n)" count.
     *
     * @param coursecat_helper $chelper
     * @param core_course_category $coursecat
     * @param int $depth
     * @return string
     */
    protected function coursecat_category(coursecat_helper $chelper, $coursecat, $depth) {
        if (!$this->is_catalogue_page()) {
            return parent::coursecat_category($chelper, $coursecat, $depth);
        }

        $classes = ['category', 'eji-cat'];
        if (empty($coursecat->visible)) {
            $classes[] = 'dimmed_category';
        }
        if ($chelper->get_subcat_depth() > 0 && $depth >= $chelper->get_subcat_depth()) {
            $categorycontent = '';
            $classes[] = 'notloaded';
            if ($coursecat->get_children_count() ||
                    ($chelper->get_show_courses() >= self::COURSECAT_SHOW_COURSES_COLLAPSED && $coursecat->get_courses_count())) {
                $classes[] = 'with_children';
                $classes[] = 'collapsed';
            }
        } else {
            $categorycontent = $this->coursecat_category_content($chelper, $coursecat, $depth);
            $classes[] = 'loaded';
            if (!empty($categorycontent)) {
                $classes[] = 'with_children';
                $this->categoryexpandedonload = true;
            }
        }

        $this->coursecat_include_js();

        $content = html_writer::start_tag('div', [
            'class' => join(' ', $classes),
            'data-categoryid' => $coursecat->id,
            'data-depth' => $depth,
            'data-showcourses' => $chelper->get_show_courses(),
            'data-type' => self::COURSECAT_TYPE_CATEGORY,
        ]);

        $link = html_writer::link(
            new moodle_url('/course/index.php', ['categoryid' => $coursecat->id]),
            $coursecat->get_formatted_name(),
            ['class' => 'eji-cat__name']
        );
        $icon = html_writer::span($this->output->pix_icon('i/folder', ''), 'eji-cat__icon', ['aria-hidden' => 'true']);
        $text = html_writer::span($link . $this->category_meta($coursecat), 'eji-cat__text');

        $content .= html_writer::start_tag('div', ['class' => 'info']);
        $content .= html_writer::tag(($depth > 1) ? 'h4' : 'h3', $icon . $text, ['class' => 'categoryname aabtn']);
        $content .= html_writer::end_tag('div');

        $content .= html_writer::tag('div', $categorycontent, ['class' => 'content']);
        $content .= html_writer::end_tag('div');

        return $content;
    }

    /**
     * "3 courses · 1 subcategory" under a category name. Empty when both are zero.
     *
     * @param core_course_category $coursecat
     * @return string
     */
    protected function category_meta(core_course_category $coursecat): string {
        $parts = [];
        if ($n = $coursecat->get_courses_count()) {
            $parts[] = get_string($n == 1 ? 'catalog:onecourse' : 'catalog:courses', 'theme_eji', $n);
        }
        if ($n = $coursecat->get_children_count()) {
            $parts[] = get_string($n == 1 ? 'catalog:onesubcategory' : 'catalog:subcategories', 'theme_eji', $n);
        }
        if (!$parts) {
            $parts[] = get_string('catalog:empty', 'theme_eji');
        }
        return html_writer::span(implode(' · ', $parts), 'eji-cat__meta');
    }

    /**
     * One course. Expanded courses become a card with an image; collapsed ones stay core's row.
     *
     * The card keeps core's .coursebox, .info and .content and the data-*
     * attributes, and fills them from core's own helpers (course_name,
     * course_summary, course_contacts, ...). Only the image and the footer
     * link are added.
     *
     * @param coursecat_helper $chelper
     * @param core_course_list_element|\stdClass $course
     * @param string $additionalclasses
     * @return string
     */
    protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
        if (!$this->is_catalogue_page() || $chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            return parent::coursecat_coursebox($chelper, $course, $additionalclasses);
        }
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }
        if ($course instanceof \stdClass) {
            $course = new core_course_list_element($course);
        }
        $viewurl = new moodle_url('/course/view.php', ['id' => $course->id]);

        $content = html_writer::start_tag('div', [
            'class' => trim('coursebox eji-coursecard clearfix ' . $additionalclasses),
            'data-courseid' => $course->id,
            'data-type' => self::COURSECAT_TYPE_COURSE,
        ]);

        $content .= html_writer::link($viewurl, '', [
            'class' => 'eji-coursecard__media',
            'style' => 'background-image: url("' . $this->course_image_url($course) . '");',
            'tabindex' => '-1',
            'aria-hidden' => 'true',
        ]);

        $content .= html_writer::start_tag('div', ['class' => 'eji-coursecard__body']);
        $content .= html_writer::start_tag('div', ['class' => 'info']);
        $content .= $this->course_name($chelper, $course);
        $content .= $this->course_enrolment_icons($course);
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', ['class' => 'content']);
        $content .= $this->course_summary($chelper, $course);
        $content .= $this->course_contacts($course);
        $content .= $this->course_category_name($chelper, $course);
        $content .= $this->course_custom_fields($course);
        $content .= $this->course_overview_attachments($course);
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');

        $content .= html_writer::div(
            html_writer::link($viewurl, get_string('catalog:viewcourse', 'theme_eji') .
                $this->output->pix_icon('i/next', ''), ['class' => 'eji-coursecard__action']),
            'eji-coursecard__foot'
        );

        $content .= html_writer::end_tag('div');
        return $content;
    }

    /**
     * The course's first overview image, or the same generated pattern the dashboard cards use.
     *
     * @param core_course_list_element $course
     * @return string
     */
    protected function course_image_url(core_course_list_element $course): string {
        global $CFG;
        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename())->out(false);
            }
        }
        return $this->output->get_generated_image_for_id($course->id);
    }

    /**
     * Non-image overview files, rendered as core renders them. Images are shown as the card image instead.
     *
     * @param core_course_list_element $course
     * @return string
     */
    protected function course_overview_attachments(core_course_list_element $course): string {
        global $CFG;
        $content = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                continue;
            }
            $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                $file->get_filearea() . $file->get_filepath() . $file->get_filename(), true);
            $image = $this->output->pix_icon(file_file_icon($file), $file->get_filename(), 'moodle');
            $filename = html_writer::tag('span', $image, ['class' => 'fp-icon']) .
                html_writer::tag('span', $file->get_filename(), ['class' => 'fp-filename']);
            $content .= html_writer::tag('span', html_writer::link($url, $filename),
                ['class' => 'coursefile fp-filename-icon text-break']);
        }
        return $content;
    }

    /**
     * Shown in place of the tree when a category has neither courses nor subcategories.
     *
     * @param int|\stdClass|core_course_category $category
     * @return string
     */
    protected function catalogue_empty_state($category): string {
        $icon = html_writer::span($this->output->pix_icon('i/course', ''), 'eji-catalog-empty__icon', ['aria-hidden' => 'true']);
        $title = html_writer::tag('h2', get_string('nocoursesyet'), ['class' => 'eji-catalog-empty__title']);
        $desc = html_writer::tag('p', get_string('catalog:emptydesc', 'theme_eji'), ['class' => 'eji-catalog-empty__desc']);

        $action = '';
        if (!empty($category)) {
            $action = html_writer::link(new moodle_url('/course/index.php'), get_string('catalog:browseall', 'theme_eji'),
                ['class' => 'btn btn-primary']);
        }
        return html_writer::div($icon . $title . $desc . $action, 'eji-catalog-empty');
    }
}
