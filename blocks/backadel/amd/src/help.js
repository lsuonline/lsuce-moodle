/**
 * Reusable help modal module for Backadel.
 *
 * Listens for clicks on [data-action="show-help"] buttons, fetches the
 * corresponding markdown-rendered help content via AJAX, and displays
 * it in a Moodle modal.
 *
 * @module     block_backadel/help
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import {getHelpContent} from './repository';
import Notification from 'core/notification';
import Pending from 'core/pending';

const contentCache = new Map();

/**
 * Show a help modal for the given topic.
 *
 * @param {string} topic The help topic key.
 * @param {string} title The modal title.
 * @param {HTMLElement} returnElement Element to return focus to.
 */
const showHelp = async(topic, title, returnElement) => {
    const pendingPromise = new Pending(`block_backadel/help:${topic}`);

    try {
        let html = contentCache.get(topic);
        if (!html) {
            const result = await getHelpContent(topic);
            html = result.content;
            if (result.success) {
                contentCache.set(topic, html);
            }
        }

        const bodyHtml = `<div class="block_backadel-modal-body">${html}</div>`;

        const modal = await Modal.create({
            title,
            body: bodyHtml,
            show: true,
            large: true,
            removeOnClose: true,
            returnElement,
        });

        modal.getModal().addClass('block_backadel-modal-dialog');
    } catch (error) {
        Notification.exception(error);
    } finally {
        pendingPromise.resolve();
    }
};

/**
 * Initialise help icon listeners.
 *
 * Attaches a delegated click handler to the document so that help buttons
 * added dynamically (or in any page) are handled.
 */
export const init = () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="show-help"]');
        if (!btn) {
            return;
        }
        e.preventDefault();
        showHelp(btn.dataset.helpTopic, btn.dataset.helpTitle || '', btn);
    });
};
