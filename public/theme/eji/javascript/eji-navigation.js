/**
 * EJI ONE theme JavaScript.
 *
 * Loaded through $THEME->javascripts_footer, which means plain script - no AMD
 * module, and therefore no npm/grunt build step for this theme.
 *
 * Deliberately small. The sidebar reuses Boost's drawer component
 * (theme_boost/drawers), which already provides opening and closing, the
 * backdrop on small screens, aria-hidden management, closing the other drawers
 * when one opens, and persistence of the open state as a user preference.
 *
 * Two things Boost's drawer does NOT do are added here:
 *
 *   1. Keeping `eji-nav-open` on <body> in step with the drawer, because the
 *      layout offset (--eji-nav-offset) is driven by that class.
 *   2. Closing the sidebar on Escape while it is an overlay. Boost registers
 *      no keydown handler at all; on a panel that covers the page behind a
 *      backdrop, Escape is the behaviour people expect.
 *
 * @package   theme_eji
 * @copyright 2026 PT. Eka Jaya Internasional
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    var SIDEBAR_ID = 'theme_boost-drawers-primary';
    var OPEN_CLASS = 'eji-nav-open';

    // Matches the lg breakpoint used by _navigation.scss and, near enough, the
    // 991px that Moodle's core/pagehelpers isSmall() uses. Above this the
    // sidebar is a permanent column and Escape must leave it alone.
    var OVERLAY_MAX_WIDTH = 991;

    /**
     * Mirror the drawer's state onto <body>.
     *
     * The layout offset is driven by this class, so it has to follow the
     * drawer rather than be set independently - otherwise the content column
     * and the sidebar can disagree about how much space the sidebar uses.
     *
     * @param {HTMLElement} drawer
     */
    function syncBodyClass(drawer) {
        document.body.classList.toggle(OPEN_CLASS, drawer.classList.contains('show'));
    }

    /**
     * Watch the drawer for class changes.
     *
     * Boost toggles `show` from its own code and also fires drawer events, but
     * those events are only reachable from an AMD module. Observing the class
     * covers both that path and any direct manipulation, and costs nothing
     * while the drawer is idle.
     *
     * @param {HTMLElement} drawer
     */
    function observe(drawer) {
        syncBodyClass(drawer);

        if (typeof MutationObserver !== 'function') {
            return;
        }

        new MutationObserver(function() {
            syncBodyClass(drawer);
        }).observe(drawer, {attributes: true, attributeFilter: ['class']});
    }

    /**
     * Close the sidebar on Escape, but only while it is an overlay.
     *
     * The close is routed through Boost's own close button rather than by
     * removing the class directly, so that Boost still updates aria-hidden,
     * hides the backdrop and restores focus exactly as it would for a click.
     *
     * @param {HTMLElement} drawer
     */
    function closeOnEscape(drawer) {
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape' && e.key !== 'Esc') {
                return;
            }
            if (!drawer.classList.contains('show')) {
                return;
            }
            if (window.innerWidth > OVERLAY_MAX_WIDTH) {
                // A permanent column, not an overlay - leave it open.
                return;
            }

            var closeButton = drawer.querySelector('[data-action="closedrawer"]');
            if (closeButton) {
                closeButton.click();
            }
        });
    }

    function init() {
        var drawer = document.getElementById(SIDEBAR_ID);
        if (!drawer) {
            // Layouts without a sidebar (login, embedded, maintenance).
            return;
        }
        observe(drawer);
        closeOnEscape(drawer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
