/**
 * Modal listing catalogue instructors (username / full name).
 *
 * @module     block_backadel/instructors_modal
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import Notification from 'core/notification';
import Pending from 'core/pending';

/**
 * Escape text for safe insertion into HTML.
 *
 * @param {string} value Raw text.
 * @returns {string} Escaped HTML.
 */
const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
};

/**
 * Build HTML body for instructor rows.
 *
 * @param {Array<{username: string, fullname: string}>} rows Instructor rows from PHP.
 * @param {object} strings Localised labels.
 * @returns {string} HTML fragment.
 */
const buildBodyHtml = (rows, strings) => {
    if (!rows.length) {
        return `<p class="text-muted">${strings.none}</p>`;
    }
    const head = `<thead><tr><th scope="col">${strings.colUsername}</th>` +
        `<th scope="col">${strings.colFullname}</th></tr></thead>`;
    const body = rows.map((r) => `<tr><td>${escapeHtml(r.username)}</td><td>${escapeHtml(r.fullname)}</td></tr>`).join('');
    return `<div class="block_backadel-modal-body"><table class="table table-sm">${head}<tbody>${body}</tbody></table></div>`;
};

/**
 * Parse instructors payload from a trigger button.
 *
 * @param {string} raw JSON from data-instructors.
 * @returns {Array<{username: string, fullname: string}>}
 */
const parseRows = (raw) => {
    if (!raw) {
        return [];
    }
    try {
        const data = JSON.parse(raw);
        if (!Array.isArray(data)) {
            return [];
        }
        return data.filter((item) => item && typeof item.username === 'string');
    } catch {
        return [];
    }
};

/**
 * Display instructors modal.
 *
 * @param {HTMLElement} btn Trigger element.
 * @param {object} strings Localised strings from PHP.
 */
const showInstructorsModal = async(btn, strings) => {
    const pendingPromise = new Pending('block_backadel/instructors_modal:show');

    try {
        const rows = parseRows(btn.dataset.instructors || '');
        const bodyHtml = buildBodyHtml(rows, strings);

        const modal = await Modal.create({
            title: strings.modalTitle,
            body: bodyHtml,
            show: true,
            large: true,
            removeOnClose: true,
            returnElement: btn,
        });

        modal.getModal().addClass('block_backadel-modal-dialog');
    } catch (error) {
        Notification.exception(error);
    } finally {
        pendingPromise.resolve();
    }
};

/**
 * Initialise delegated click handlers.
 *
 * @param {object} strings Lang strings supplied from PHP (keys: modalTitle, colUsername, colFullname, none).
 */
export const init = (strings) => {
    try {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="show-instructors"]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            showInstructorsModal(btn, strings);
        });
    } catch (err) {
        window.console.error('block_backadel/instructors_modal init failed:', err);
    }
};
