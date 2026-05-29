/**
 * Render a Bootstrap 5 nav-tabs bar across block_backadel admin pages.
 *
 * Adapted from theme_kenai/settings-handler-lazy. Self-aborts when the
 * current page is not a block_backadel admin page.
 *
 * @module     block_backadel/admin-tabs-lazy
 * @copyright  2026 Louisiana State University
 * @author     David Castro <davidcastro00@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import Templates from 'core/templates';
import Notification from 'core/notification';

const renderTabs = (links, strings, validPlaces) => {
    const activeClasses = [];
    let isValid = false;

    for (let i = 0; i < validPlaces.length; i++) {
        if ($(validPlaces[i]).length > 0) {
            isValid = true;
            activeClasses[i] = 'active';
        } else {
            activeClasses[i] = '';
        }
    }

    if (!isValid) {
        return;
    }

    const nav = [];
    for (let i = 0; i < links.length; i++) {
        nav.push({
            link: links[i],
            string: strings[i],
            activeClass: activeClasses[i],
        });
    }

    Templates.render('block_backadel/admin_tabs', {nav: nav}).then((html) => {
        const $anchor = $('#region-main .secondary-navigation').first();
        if ($anchor.length) {
            $anchor.after(html);
        } else {
            $('#region-main').prepend(html);
        }
        return null;
    }).catch(Notification.exception);
};

export const init = (links, strings, validPlaces) => {
    renderTabs(links, strings, validPlaces);
};
