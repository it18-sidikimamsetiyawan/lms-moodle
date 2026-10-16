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

namespace theme_eji\output;

use context_system;
use core\output\renderer_base;
use core\url;
use stdClass;

/**
 * Builds the grouped navigation shown in the EJI ONE sidebar.
 *
 * Two rules govern everything in this class:
 *
 * 1. Every entry points at a route that genuinely exists in this Moodle. No
 *    item is invented to match a mockup: if Moodle has no page for it, it is
 *    not in the menu. That is why there is no "Certificates" or "Progress"
 *    entry - this site has no certificate module installed, and Moodle has no
 *    standalone progress page.
 *
 * 2. Every entry is gated on the same condition that guards the page itself -
 *    login state, a site setting, or a capability. A user never sees a link
 *    they would be bounced from.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sidebar_navigation {

    /** @var \moodle_page The page being rendered. */
    protected $page;

    /**
     * Constructor.
     *
     * @param \moodle_page $page
     */
    public function __construct($page) {
        $this->page = $page;
    }

    /**
     * Export the grouped navigation for the sidebar template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $sections = [];

        foreach ($this->build_sections() as $section) {
            $items = [];
            foreach ($section['items'] as $item) {
                if (empty($item['show'])) {
                    continue;
                }
                $items[] = $this->export_item($item, $output);
            }
            if (empty($items)) {
                // Never render an empty group heading.
                continue;
            }
            $sections[] = [
                'heading' => $section['heading'],
                'hasheading' => $section['heading'] !== null,
                'count' => count($items),
                'items' => $items,
            ];
        }

        return $sections;
    }

    /**
     * Turn one item definition into template context.
     *
     * @param array $item
     * @param renderer_base $output
     * @return array
     */
    protected function export_item(array $item, renderer_base $output): array {
        $url = $item['url'] instanceof url ? $item['url'] : new url($item['url']);

        return [
            'key' => $item['key'],
            'text' => $item['text'],
            'url' => $url->out(false),
            'icon' => $output->pix_icon($item['icon'], '', 'core', ['class' => 'eji-nav-item__ico']),
            'isactive' => $this->is_active($item['key']),
        ];
    }

    /**
     * The section and item definitions.
     *
     * @return array
     */
    protected function build_sections(): array {
        global $CFG, $USER;

        $loggedin = isloggedin() && !isguestuser();
        $guestorbetter = isloggedin();

        return [
            // ----------------------------------------------------------
            // Main.
            // ----------------------------------------------------------
            [
                'heading' => null,
                'items' => [
                    [
                        'key' => 'home',
                        'text' => get_string('home'),
                        'url' => new url('/', ['redirect' => 0]),
                        'icon' => 'i/home',
                        'show' => true,
                    ],
                    [
                        'key' => 'dashboard',
                        'text' => get_string('myhome'),
                        'url' => new url('/my/'),
                        'icon' => 'i/dashboard',
                        // Mirrors the condition core uses to expose /my/ at all.
                        'show' => $loggedin && !empty($CFG->enabledashboard),
                    ],
                ],
            ],

            // ----------------------------------------------------------
            // Learning.
            // ----------------------------------------------------------
            [
                'heading' => get_string('navsection:learning', 'theme_eji'),
                'items' => [
                    [
                        'key' => 'hrdashboard_my',
                        'text' => $this->hrdashboard_string('mylearning'),
                        'url' => new url('/local/hrdashboard/my.php'),
                        'icon' => 'i/progressbar',
                        'show' => $loggedin && $this->hrdashboard_installed()
                            && has_capability('local/hrdashboard:viewown', context_system::instance()),
                    ],
                    [
                        'key' => 'mycourses',
                        'text' => get_string('mycourses'),
                        'url' => new url('/my/courses.php'),
                        'icon' => 'i/course',
                        'show' => $loggedin,
                    ],
                    [
                        'key' => 'catalog',
                        // Course listing honours course visibility on its own.
                        'text' => get_string('fulllistofcourses'),
                        'url' => new url('/course/index.php'),
                        'icon' => 'i/courseevent',
                        'show' => $guestorbetter || empty($CFG->forcelogin),
                    ],
                ],
            ],

            // ----------------------------------------------------------
            // My learning.
            // ----------------------------------------------------------
            [
                'heading' => get_string('navsection:mylearning', 'theme_eji'),
                'items' => [
                    [
                        'key' => 'grades',
                        'text' => get_string('grades'),
                        'url' => new url('/grade/report/overview/index.php', ['userid' => $USER->id ?? 0]),
                        'icon' => 'i/grades',
                        'show' => $loggedin && $this->grade_overview_available(),
                    ],
                    [
                        'key' => 'badges',
                        'text' => get_string('badges', 'badges'),
                        'url' => new url('/badges/mybadges.php'),
                        'icon' => 'i/badge',
                        'show' => $loggedin && !empty($CFG->enablebadges),
                    ],
                ],
            ],

            // ----------------------------------------------------------
            // Tools.
            // ----------------------------------------------------------
            [
                'heading' => get_string('navsection:tools', 'theme_eji'),
                'items' => [
                    [
                        'key' => 'calendar',
                        'text' => get_string('calendar', 'calendar'),
                        'url' => new url('/calendar/view.php', ['view' => 'month']),
                        'icon' => 'i/calendar',
                        'show' => $guestorbetter,
                    ],
                    [
                        'key' => 'messages',
                        'text' => get_string('messages', 'message'),
                        'url' => new url('/message/index.php'),
                        'icon' => 't/message',
                        'show' => $loggedin && !empty($CFG->messaging),
                    ],
                ],
            ],

            // ----------------------------------------------------------
            // Profile.
            // ----------------------------------------------------------
            [
                'heading' => get_string('navsection:profile', 'theme_eji'),
                'items' => [
                    [
                        'key' => 'profile',
                        'text' => get_string('profile'),
                        'url' => new url('/user/profile.php'),
                        'icon' => 'i/user',
                        'show' => $loggedin,
                    ],
                    [
                        'key' => 'preferences',
                        'text' => get_string('preferences'),
                        'url' => new url('/user/preferences.php'),
                        'icon' => 'i/settings',
                        'show' => $loggedin,
                    ],
                ],
            ],

            // ----------------------------------------------------------
            // Monitoring (local_hrdashboard). Shown only when the plugin is
            // installed and the user holds an HR scope.
            // ----------------------------------------------------------
            [
                'heading' => $this->hrdashboard_string('hrdashboard'),
                'items' => [
                    [
                        'key' => 'hrdashboard_hr',
                        'text' => $this->hrdashboard_string('hrdashboardtitle'),
                        'url' => new url('/local/hrdashboard/index.php'),
                        'icon' => 'i/report',
                        'show' => $loggedin && $this->hrdashboard_installed()
                            && \local_hrdashboard\local\scope::user_can_view_hr(),
                    ],
                    [
                        'key' => 'hrdashboard_employees',
                        'text' => $this->hrdashboard_string('employeeprogress'),
                        'url' => new url('/local/hrdashboard/employees.php'),
                        'icon' => 'i/users',
                        'show' => $loggedin && $this->hrdashboard_installed()
                            && \local_hrdashboard\local\scope::user_can_view_hr(),
                    ],
                ],
            ],

            // ----------------------------------------------------------
            // Administration. Capability-gated, exactly like core's own
            // Site administration entry.
            // ----------------------------------------------------------
            [
                'heading' => get_string('navsection:administration', 'theme_eji'),
                'items' => [
                    [
                        'key' => 'siteadmin',
                        'text' => get_string('administrationsite'),
                        'url' => new url('/admin/search.php'),
                        'icon' => 'i/settings',
                        'show' => $this->can_see_site_administration(),
                    ],
                    [
                        'key' => 'participants',
                        'text' => get_string('users'),
                        'url' => new url('/admin/user.php'),
                        'icon' => 'i/users',
                        'show' => has_capability('moodle/user:update', context_system::instance()),
                    ],
                ],
            ],
        ];
    }

    /**
     * Whether local_hrdashboard is installed (directory present and upgraded).
     *
     * @return bool
     */
    protected function hrdashboard_installed(): bool {
        static $installed = null;
        if ($installed === null) {
            $installed = \core_component::get_component_directory('local_hrdashboard') !== null
                && get_config('local_hrdashboard', 'version') !== false;
        }
        return $installed;
    }

    /**
     * A local_hrdashboard string, safe to call before the plugin exists.
     *
     * @param string $key
     * @return string
     */
    protected function hrdashboard_string(string $key): string {
        return $this->hrdashboard_installed() ? get_string($key, 'local_hrdashboard') : '';
    }

    /**
     * Whether the current user may reach Site administration.
     *
     * Deliberately uses the same test core uses, rather than a hardcoded
     * "is admin" check, so custom roles with partial site configuration
     * rights still get the link.
     *
     * @return bool
     */
    protected function can_see_site_administration(): bool {
        $context = context_system::instance();
        return has_capability('moodle/site:config', $context)
            || has_any_capability([
                'moodle/site:configview',
                'moodle/user:update',
                'moodle/role:manage',
                'moodle/backup:backupcourse',
            ], $context);
    }

    /**
     * Whether the grader overview report is available to this user.
     *
     * @return bool
     */
    protected function grade_overview_available(): bool {
        global $CFG;

        if (empty($CFG->gradebookroles)) {
            return false;
        }
        // The overview report ships with core but can be disabled by an admin.
        $disabled = explode(',', (string) get_config('moodle', 'gradereport_disabled'));
        return !in_array('overview', $disabled, true);
    }

    /**
     * Whether a menu key corresponds to the page currently being viewed.
     *
     * Matching is done on the script path rather than on the full URL, so that
     * query strings (course ids, filters, paging) do not break highlighting.
     *
     * @param string $key
     * @return bool
     */
    protected function is_active(string $key): bool {
        global $CFG;

        $path = $this->page->url->get_path();
        // Strip any subdirectory install prefix so the comparisons below hold.
        $prefix = parse_url($CFG->wwwroot, PHP_URL_PATH);
        if (!empty($prefix) && strpos($path, $prefix) === 0) {
            $path = substr($path, strlen($prefix));
        }
        $path = '/' . ltrim($path, '/');

        $matchers = [
            'home' => ['/index.php', '/'],
            'dashboard' => ['/my/index.php', '/my/'],
            'mycourses' => ['/my/courses.php'],
            // Deliberately not /course/view.php: being inside a course does
            // not mean the catalogue is the current page, and highlighting it
            // there would tell the user something untrue about where they are.
            'catalog' => ['/course/index.php', '/course/search.php'],
            'grades' => ['/grade/report/overview/index.php'],
            'badges' => ['/badges/mybadges.php'],
            'calendar' => ['/calendar/view.php'],
            'messages' => ['/message/index.php'],
            'profile' => ['/user/profile.php', '/user/edit.php'],
            'preferences' => ['/user/preferences.php'],
            'hrdashboard_my' => ['/local/hrdashboard/my.php'],
            'hrdashboard_hr' => ['/local/hrdashboard/index.php', '/local/hrdashboard/course.php'],
            'hrdashboard_employees' => ['/local/hrdashboard/employees.php'],
            'siteadmin' => ['/admin/search.php', '/admin/settings.php', '/admin/category.php'],
            'participants' => ['/admin/user.php'],
        ];

        return in_array($path, $matchers[$key] ?? [], true);
    }
}
