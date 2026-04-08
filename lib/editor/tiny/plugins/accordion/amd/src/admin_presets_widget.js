// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin widget for the accordion style presets setting.
 *
 * Handles all interactivity on the admin settings page: adding preset rows,
 * deleting rows, and keeping the hidden JSON input in sync. Uses native DOM
 * only — no jQuery, no define()/require().
 *
 * Loaded via $PAGE->requires->js_call_amd('tiny_accordion/admin_presets_widget', 'init')
 * inside settings.php.
 *
 * @module      tiny_accordion/admin_presets_widget
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @type {string[]} Field names that each preset row contains. */
const PRESET_FIELDS = ['label', 'detailsclass', 'detailsstyle', 'summaryclass', 'summarystyle'];

/**
 * Serialise all visible preset rows from a tbody into a JSON string and write
 * it to the hidden input.
 *
 * @param {HTMLTableSectionElement} tbody   The <tbody data-presets-tbody> element.
 * @param {HTMLInputElement}        hidden  The <input data-presets-json> element.
 * @returns {void}
 */
const serialize = (tbody, hidden) => {
    const rows = [...tbody.querySelectorAll('tr:not([data-template-row])')];
    const data = rows.map((tr) => Object.fromEntries(
        PRESET_FIELDS.map((field) => [
            field,
            tr.querySelector(`[data-field="${field}"]`)?.value ?? '',
        ])
    ));
    hidden.value = JSON.stringify(data);
};

/**
 * Wire up a single [data-widget="accordion-style-presets"] container.
 *
 * @param {HTMLElement} container  The widget root element.
 * @returns {void}
 */
const initContainer = (container) => {
    const tbody = container.querySelector('[data-presets-tbody]');
    const addBtn = container.querySelector('[data-action="add-row"]');
    const hidden = container.querySelector('[data-presets-json]');
    const templateRow = container.querySelector('[data-template-row]');

    if (!tbody || !addBtn || !hidden || !templateRow) {
        return;
    }

    let rowCounter = tbody.querySelectorAll('tr:not([data-template-row])').length;

    /** @returns {void} */
    const doSerialize = () => serialize(tbody, hidden);

    addBtn.addEventListener('click', () => {
        const newRow = templateRow.cloneNode(true);
        newRow.removeAttribute('data-template-row');
        newRow.removeAttribute('aria-hidden');
        newRow.style.display = '';
        // Replace placeholder index token in any future name/id attributes.
        newRow.innerHTML = newRow.innerHTML.replaceAll('__idx__', String(rowCounter));
        rowCounter++;
        tbody.appendChild(newRow);
        doSerialize();
    });

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="delete-row"]');
        if (btn) {
            btn.closest('tr')?.remove();
            doSerialize();
        }
    });

    tbody.addEventListener('input', doSerialize);

    container.closest('form')?.addEventListener('submit', doSerialize);

    // Initialise hidden field from existing rows on page load.
    doSerialize();
};

/**
 * Initialise all accordion style preset widgets on the page.
 *
 * Called automatically by Moodle's AMD bootstrapper after the DOM is ready.
 *
 * @returns {void}
 */
export const init = () => {
    document
        .querySelectorAll('[data-widget="accordion-style-presets"]')
        .forEach(initContainer);
};
