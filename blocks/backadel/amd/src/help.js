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

/** Track the currently-open topic to prevent double-open. */
let activeModalTopic = null;

/**
 * Show a help modal for the given topic.
 *
 * Content is injected on the 'shown.bs.modal' event (after Bootstrap's
 * animation completes) to avoid layout flashes during the open transition.
 * A guard prevents re-opening a modal that is already visible.
 *
 * @param {string} topic The help topic key.
 * @param {string} title The modal title.
 * @param {HTMLElement} returnElement Element to return focus to.
 */
const showHelp = async(topic, title, returnElement) => {
    if (activeModalTopic === topic) {
        return;
    }

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

        // Create without show:true so we can inject content after animation.
        const modal = await Modal.create({
            title,
            large: true,
            removeOnClose: true,
            returnElement,
        });

        modal.getModal().addClass('block_backadel-modal-dialog');

        const rootEl = modal.getRoot()[0];
        activeModalTopic = topic;

        rootEl.addEventListener('shown.bs.modal', () => {
            modal.setBody(bodyHtml);
        }, {once: true});

        rootEl.addEventListener('hidden.bs.modal', () => {
            activeModalTopic = null;
        }, {once: true});

        modal.show();
    } catch (error) {
        activeModalTopic = null;
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
