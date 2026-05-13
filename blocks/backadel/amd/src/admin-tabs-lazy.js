/**
 * Render a Bootstrap 4 nav-tabs bar across local_dbfix admin pages.
 *
 * Adapted from theme_kenai/settings-handler-lazy. Self-aborts when the
 * current page is not a local_dbfix admin page.
 *
 * @module     local_dbfix/admin-tabs-lazy
 * @copyright  2026 Pivot Point International
 * @author     David Castro <davidcastro00@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import Templates from 'core/templates';

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
    }).catch((error) => {
        window.console.error(error);
    });
};

export const init = (links, strings, validPlaces) => {
    renderTabs(links, strings, validPlaces);
};
