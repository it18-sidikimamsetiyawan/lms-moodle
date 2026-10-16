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
 * EJI ONE theme functions.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * SCSS injected before everything else.
 *
 * This is where the EJI design tokens land. Boost's preset imports the Moodle
 * Design System tokens (all declared with !default) and then uses them to set
 * the Bootstrap variables, so anything we assign here wins for the whole
 * compile: Bootstrap, every Moodle component, and our own partials alike.
 *
 * Order matters and is deliberate:
 *   1. our tokens   - so they beat the !default declarations further down;
 *   2. brandcolor   - so an admin can still override the accent from the UI;
 *   3. scsspre      - so an admin's raw SCSS is the very last word.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_eji_get_pre_scss($theme) {
    global $CFG;

    $scss = '';

    // 1. EJI design tokens: fonts, colours, spacing, radius, shadows.
    $scss .= file_get_contents($CFG->dirroot . '/theme/eji/scss/pre.scss');

    // 2. Let the brandcolor setting still work, the way Boost does it.
    $configurable = [
        // Config key => [variableName, ...].
        'brandcolor' => ['primary', 'mds-bg-interactive-primary-default'],
    ];
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        foreach ((array) $targets as $target) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }
    }

    // Behat needs to know it is running, same as Boost.
    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\$behatsite: true;\n";
    }

    // 3. Admin-supplied raw initial SCSS always has the final say.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Returns the main SCSS content.
 *
 * We reuse Boost's presets verbatim rather than forking them. Everything this
 * theme changes is expressed as token overrides (pre) and component styles
 * (post), which is what keeps the theme upgradable.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_eji_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();
    $context = context_system::instance();

    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_eji', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Safety fallback - new installs etc.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    }

    return $scss;
}

/**
 * SCSS injected after Bootstrap and all of Moodle's own component styles.
 *
 * This is where EJI components that have no Moodle equivalent live: the
 * persistent sidebar, the dashboard KPI row, the course cards, the status
 * badges. Anything that could instead have been achieved with a token override
 * belongs in pre.scss, not here.
 *
 * Note that Moodle runs the PARENT theme's extra-SCSS callback before this one
 * (theme_config::get_extra_scss_code walks parent_configs first), so Boost's
 * own callback still fires and still injects its default login background plus
 * an "AI-generated image" watermark. Not calling it from here is not enough to
 * suppress that. Because our SCSS is appended afterwards it wins on order, and
 * scss/eji/_login.scss explicitly resets both - see the note there.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_eji_get_extra_scss($theme) {
    global $CFG;

    $content = file_get_contents($CFG->dirroot . '/theme/eji/scss/post.scss');

    // Site-wide background image, if one was uploaded.
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');
    if (!empty($imageurl)) {
        $content .= '@media (min-width: 768px) {';
        $content .= '  body { background-image: url("' . $imageurl . '"); background-size: cover; }';
        $content .= '}';
    }

    // Login panel background. Unlike Boost we have a designed fallback (a flat
    // EJI primary gradient defined in _login.scss), so there is no default
    // photograph and no watermark.
    $loginbackgroundimageurl = $theme->setting_file_url('loginbackgroundimage', 'loginbackgroundimage');
    if (!empty($loginbackgroundimageurl)) {
        $content .= 'body.pagelayout-login #page .login-layout-left {';
        $content .= '  background-image: url("' . $loginbackgroundimageurl . '");';
        $content .= '  background-size: cover; background-position: center;';
        $content .= '}';
    }

    // Admin-supplied raw SCSS last.
    if (!empty($theme->settings->scss)) {
        $content .= "\n" . $theme->settings->scss;
    }

    return $content;
}

/**
 * Serves the files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_eji_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    $allowed = ['logo', 'logocompact', 'backgroundimage', 'loginbackgroundimage', 'preset'];
    if ($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, $allowed, true)) {
        $theme = theme_config::load('eji');
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    send_file_not_found();
}

/**
 * User preferences this theme is allowed to read and write.
 *
 * Mirrors Boost's drawer preferences and adds the EJI sidebar, so that a user's
 * collapsed/expanded choice survives page loads the same way Boost's do.
 *
 * @return array[]
 */
function theme_eji_user_preferences(): array {
    return [
        'drawer-open-block' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => false,
            'permissioncallback' => [core_user::class, 'is_current_user'],
        ],
        'drawer-open-index' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => true,
            'permissioncallback' => [core_user::class, 'is_current_user'],
        ],
        'drawer-open-nav' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => true,
            'permissioncallback' => [core_user::class, 'is_current_user'],
        ],
    ];
}
