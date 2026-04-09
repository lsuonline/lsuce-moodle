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
 * Tiny phonetic UI.
 *
 * @module      tiny_phonetic/ui
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import PhoneticModal from 'tiny_phonetic/modal';
import {getLibraries} from 'tiny_phonetic/options';
import Selectors from 'tiny_phonetic/selectors';

/**
 * Decode an HTML entity string into a Unicode character.
 *
 * @param {string} str  e.g. "&#112;"
 * @returns {string}
 */
const decodeEntity = (str) => {
    const el = document.createElement('span');
    el.innerHTML = str;
    return el.textContent;
};

/**
 * Append a symbol to the textarea at the current cursor position and update preview.
 *
 * @param {Element} root  Modal root element
 * @param {Element} item  The clicked button
 */
const selectLibraryItem = (root, item) => {
    const symbol = decodeEntity(item.dataset.symbol);
    const textarea = root.querySelector(Selectors.elements.textArea);
    const tabPane = item.closest('.tab-pane');
    const baseInput = tabPane?.querySelector(Selectors.elements.baseSymbol);
    const base = baseInput?.value ?? '';
    const insertSymbol = (baseInput && base) ? base + symbol : symbol;

    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? start;
    textarea.value = textarea.value.slice(0, start) + insertSymbol + textarea.value.slice(end);
    const newCursor = start + insertSymbol.length;
    textarea.selectionStart = textarea.selectionEnd = newCursor;
    textarea.focus();

    const previewEl = root.querySelector(Selectors.elements.previewChar);
    if (previewEl) {
        previewEl.textContent = insertSymbol;
    }
};

/**
 * Insert the textarea content into the editor at cursor position.
 *
 * @param {Element} root
 * @param {import('tinymce').Editor} editor
 */
const insertPhonetic = (root, editor) => {
    const value = root.querySelector(Selectors.elements.textArea)?.value ?? '';
    if (value) {
        editor.insertContent(value);
    }
};

/**
 * Keyboard navigation between symbol buttons within a toolbar.
 *
 * @param {KeyboardEvent} e
 */
const groupNavigation = (e) => {
    e.preventDefault();
    const current = e.target.closest(Selectors.elements.libraryItem);
    const parent = current.parentNode;
    const buttons = Array.from(parent.querySelectorAll(Selectors.elements.libraryItem));
    const direction = e.keyCode !== 37 ? 1 : -1;
    let index = buttons.indexOf(current);
    if (index < 0) {
        index = 0;
    }
    index += direction;
    if (index < 0) {
        index = buttons.length - 1;
    } else if (index >= buttons.length) {
        index = 0;
    }
    buttons[index].focus();
};

/**
 * Display the phonetic symbol picker dialogue.
 *
 * @param {import('tinymce').Editor} editor
 */
const displayDialogue = async(editor) => {
    const libraries = getLibraries(editor);
    const modal = await PhoneticModal.create({
        templateContext: {
            elementid: editor.id,
            libraries,
            currentvalue: '',
        },
    });

    const $root = await modal.getRoot();
    const root = $root[0];

    root.addEventListener('click', (e) => {
        const libraryItem = e.target.closest('.tiny_phonetic_btn');
        const submitAction = e.target.closest(Selectors.actions.submit);
        if (libraryItem) {
            e.preventDefault();
            selectLibraryItem(root, libraryItem);
        }
        if (submitAction) {
            e.preventDefault();
            insertPhonetic(root, editor);
            modal.destroy();
        }
    });

    root.addEventListener('mouseenter', (e) => {
        const libraryItem = e.target.closest('.tiny_phonetic_btn');
        if (libraryItem) {
            const previewEl = root.querySelector(Selectors.elements.previewChar);
            if (previewEl) {
                previewEl.textContent = decodeEntity(libraryItem.dataset.symbol);
            }
        }
    }, true);

    root.addEventListener('keydown', (e) => {
        const libraryItem = e.target.closest('.tiny_phonetic_btn');
        if (libraryItem && (e.keyCode === 37 || e.keyCode === 39)) {
            groupNavigation(e);
        }
    });
};

/**
 * Handle the toolbar/menu action.
 *
 * @param {import('tinymce').Editor} editor
 */
export const handleAction = async(editor) => {
    await displayDialogue(editor);
};
