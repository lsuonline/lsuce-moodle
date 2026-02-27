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
 * Modal spinner: show a spinner in a container and hide it when a promise settles.
 * Use for modals when calling web services so the spinner is only visible during the request.
 *
 * @module     report_content2fix/modal_spinner
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const VISIBLE_CLASS = 'content2fix-bulk-loading-visible';
const HIDDEN_CLASS = 'content2fix-bulk-loading-hidden';
const LOADING_TEXT_SELECTOR = '[data-region="loading-text"]';

/**
 * Show the spinner in the container (element with the given id).
 *
 * @param {string} containerId Id of the element that contains or is the spinner
 * @param {string} [loadingText] Optional text to set on [data-region="loading-text"] inside the container
 */
export function show(containerId, loadingText = '') {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }
    container.classList.remove(HIDDEN_CLASS);
    container.classList.add(VISIBLE_CLASS);
    if (loadingText) {
        const textEl = container.querySelector(LOADING_TEXT_SELECTOR);
        if (textEl) {
            textEl.textContent = loadingText;
        }
    }
}

/**
 * Hide the spinner in the container.
 *
 * @param {string} containerId Id of the element that contains or is the spinner
 */
export function hide(containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }
    container.classList.add(HIDDEN_CLASS);
    container.classList.remove(VISIBLE_CLASS);
}

/**
 * Show the spinner, run the given promise, and hide the spinner when it settles (fulfill or reject).
 * The returned promise resolves or rejects with the same value as the original.
 * Wraps in Promise.resolve so that jQuery promises (e.g. from core/ajax) get .finally().
 *
 * @param {string} containerId Id of the element that contains or is the spinner
 * @param {Promise|Thenable} promise The web service (or any) promise to chain
 * @param {Object} [options]
 * @param {string} [options.loadingText] Optional text to show next to the spinner
 * @returns {Promise} Promise that settles like the given promise
 */
export function wrapPromise(containerId, promise, options = {}) {
    const loadingText = options.loadingText || '';
    show(containerId, loadingText);
    const nativePromise = Promise.resolve(promise);
    return nativePromise.finally(() => {
        hide(containerId);
    });
}
