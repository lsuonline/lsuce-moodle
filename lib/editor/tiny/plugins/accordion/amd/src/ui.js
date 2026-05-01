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
 * UI: modal and DOM updates for accordion class/style.
 *
 * @module      tiny_accordion/ui
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import AccordionAttributesModal from 'tiny_accordion/modal';
import Notification from 'core/notification';
import {getString} from 'core/str';
import {getAllowInlineStyle, getClassPrefixAllowlist, getStylePresets} from 'tiny_accordion/options';
import {component} from 'tiny_accordion/common';

/** @type {string} Sentinel value for the "Custom…" option. */
const CUSTOM_VALUE = '__custom__';

/**
 * Find the TinyMCE accordion details element for the current selection.
 *
 * @param {import('tinymce').Editor} editor
 * @returns {HTMLElement|null}
 */
export const getAccordionDetails = (editor) => {
    const node = editor.selection.getNode();
    return editor.dom.getParent(node, 'details.mce-accordion', editor.getBody());
};

/**
 * Parse comma-separated prefix list into trimmed non-empty strings.
 *
 * @param {string} raw
 * @returns {string[]}
 */
const parsePrefixList = (raw) => raw.split(',')
    .map((p) => p.trim())
    .filter(Boolean);

/**
 * Validate class tokens against an optional prefix allow-list.
 *
 * @param {string} classStr
 * @param {string} prefixListRaw
 * @returns {{ok: boolean, message?: string, value: string}}
 */
export const filterClasses = (classStr, prefixListRaw) => {
    const prefixes = parsePrefixList(prefixListRaw);
    if (prefixes.length === 0) {
        return {ok: true, value: classStr.trim()};
    }
    const tokens = classStr.trim().split(/\s+/).filter(Boolean);
    const rejected = tokens.filter((t) => !prefixes.some((p) => t.startsWith(p)));
    if (rejected.length) {
        return {
            ok: false,
            message: `These classes are not allowed (required prefix): ${rejected.join(', ')}`,
            value: tokens.filter((t) => prefixes.some((p) => t.startsWith(p))).join(' '),
        };
    }
    return {ok: true, value: tokens.join(' ')};
};

/** @type {string} Sentinel class TinyMCE's accordion plugin uses to identify accordion elements. */
const MCE_ACCORDION_CLASS = 'mce-accordion';

/**
 * Read class and style from an element (attributes may be absent).
 * Strips the internal mce-accordion sentinel class — that class is managed by
 * TinyMCE and should not be exposed to authors in the modal.
 *
 * @param {import('tinymce').Editor} editor
 * @param {Element} el
 * @returns {{class: string, style: string}}
 */
const readAttrs = (editor, el) => {
    const rawClass = editor.dom.getAttrib(el, 'class') || '';
    const authorClass = rawClass.split(/\s+/).filter((t) => t && t !== MCE_ACCORDION_CLASS).join(' ');
    return {
        'class': authorClass,
        style: editor.dom.getAttrib(el, 'style') || '',
    };
};

/**
 * Set or remove class and style on an element.
 * When writing to a details element, always preserves the mce-accordion sentinel
 * class so TinyMCE can continue to identify it as an accordion.
 *
 * @param {import('tinymce').Editor} editor
 * @param {Element} el
 * @param {string} className
 * @param {string} style
 * @param {boolean} allowStyle
 */
const applyAttrs = (editor, el, className, style, allowStyle) => {
    const isDetails = el.nodeName.toLowerCase() === 'details';
    const tokens = className.trim().split(/\s+/).filter(Boolean);
    if (isDetails && !tokens.includes(MCE_ACCORDION_CLASS)) {
        tokens.unshift(MCE_ACCORDION_CLASS);
    }
    const finalClass = tokens.join(' ');
    if (finalClass) {
        editor.dom.setAttrib(el, 'class', finalClass);
    } else {
        editor.dom.setAttrib(el, 'class', isDetails ? MCE_ACCORDION_CLASS : null);
    }
    if (allowStyle) {
        if (style.trim()) {
            editor.dom.setAttrib(el, 'style', style.trim());
        } else {
            editor.dom.setAttrib(el, 'style', null);
        }
    }
};

/**
 * Find the preset option whose data attributes match both class values.
 *
 * Returns the matching option element, or null if no preset matches.
 *
 * @param {HTMLSelectElement} select
 * @param {string} detailsClass
 * @param {string} summaryClass
 * @returns {HTMLOptionElement|null}
 */
const findMatchingPresetOption = (select, detailsClass, summaryClass) => {
    for (const option of select.options) {
        if (option.value === '' || option.value === CUSTOM_VALUE) {
            continue;
        }
        if (
            option.dataset.detailsclass === detailsClass &&
            option.dataset.summaryclass === summaryClass
        ) {
            return option;
        }
    }
    return null;
};

/**
 * Show or hide the custom-class/style rows based on the select value.
 *
 * @param {HTMLElement} root       Modal root element.
 * @param {boolean}     isCustom   Whether the custom option is selected.
 * @returns {void}
 */
const toggleCustomRows = (root, isCustom) => {
    root.querySelectorAll('.tiny_accordion_custom_details, .tiny_accordion_custom_summary')
        .forEach((el) => el.classList.toggle('d-none', !isCustom));
};

/**
 * Wire up the preset select so choosing a preset populates the hidden class/style
 * inputs and shows/hides the free-text custom rows.
 *
 * When no presets are configured the select elements won't exist in the DOM,
 * so this function is a no-op in that case.
 *
 * @param {HTMLElement}  root          Modal root element.
 * @param {string}       detailsClass  Current class on <details>.
 * @param {string}       summaryClass  Current class on <summary>.
 * @param {boolean}      allowStyle    Whether inline style is enabled.
 * @returns {void}
 */
const initPresetSelects = (root, detailsClass, summaryClass, allowStyle) => {
    const detailsSelect = root.querySelector('.tiny_accordion_details_preset');
    if (!detailsSelect) {
        return;
    }

    // Determine initial selection from current DOM values.
    const matchingOption = findMatchingPresetOption(detailsSelect, detailsClass, summaryClass);
    if (matchingOption) {
        detailsSelect.value = matchingOption.value;
        toggleCustomRows(root, false);
    } else if (detailsClass || summaryClass) {
        detailsSelect.value = CUSTOM_VALUE;
        toggleCustomRows(root, true);
    } else {
        detailsSelect.value = '';
        toggleCustomRows(root, false);
    }

    detailsSelect.addEventListener('change', () => {
        const selected = detailsSelect.options[detailsSelect.selectedIndex];
        const isCustom = selected.value === CUSTOM_VALUE;
        const isNone = selected.value === '';

        toggleCustomRows(root, isCustom);

        if (!isCustom && !isNone) {
            // Populate hidden inputs from the preset data-* attributes.
            const dClassInput = root.querySelector('.tiny_accordion_details_class');
            const sClassInput = root.querySelector('.tiny_accordion_summary_class');
            const dStyleInput = root.querySelector('.tiny_accordion_details_style');
            const sStyleInput = root.querySelector('.tiny_accordion_summary_style');

            if (dClassInput) {
                dClassInput.value = selected.dataset.detailsclass ?? '';
            }
            if (sClassInput) {
                sClassInput.value = selected.dataset.summaryclass ?? '';
            }
            if (allowStyle) {
                if (dStyleInput) {
                    dStyleInput.value = selected.dataset.detailsstyle ?? '';
                }
                if (sStyleInput) {
                    sStyleInput.value = selected.dataset.summarystyle ?? '';
                }
            }
        } else if (isNone) {
            // Clear the hidden inputs.
            const dClassInput = root.querySelector('.tiny_accordion_details_class');
            const sClassInput = root.querySelector('.tiny_accordion_summary_class');
            const dStyleInput = root.querySelector('.tiny_accordion_details_style');
            const sStyleInput = root.querySelector('.tiny_accordion_summary_style');

            if (dClassInput) {
                dClassInput.value = '';
            }
            if (sClassInput) {
                sClassInput.value = '';
            }
            if (allowStyle) {
                if (dStyleInput) {
                    dStyleInput.value = '';
                }
                if (sStyleInput) {
                    sStyleInput.value = '';
                }
            }
        }
    });
};

/**
 * Resolve the final class value to apply from the current modal state.
 *
 * When a preset is selected (not "Custom…"), returns the preset's class value
 * directly — filterClasses is NOT called (preset values are admin-vetted).
 * Only "Custom…" free-text goes through filterClasses.
 *
 * @param {HTMLElement} root         Modal root element.
 * @param {string}      classSelector CSS selector for the text input (e.g. '.tiny_accordion_details_class').
 * @param {string}      prefixRaw    Prefix allow-list raw string.
 * @param {boolean}     hasPresets   Whether the preset select is present.
 * @returns {{ok: boolean, value: string, message?: string}}
 */
const resolveClass = (root, classSelector, prefixRaw, hasPresets) => {
    if (hasPresets) {
        const select = root.querySelector('.tiny_accordion_details_preset');
        const selectedValue = select?.value ?? '';
        if (selectedValue !== CUSTOM_VALUE) {
            // Preset selected (or none): value comes from the hidden input populated by initPresetSelects.
            const input = root.querySelector(classSelector);
            return {ok: true, value: (input?.value ?? '').trim()};
        }
    }
    // Custom or no presets: validate through filterClasses.
    const input = root.querySelector(classSelector);
    return filterClasses(input?.value ?? '', prefixRaw);
};

/**
 * Resolve the final style value to apply from the current modal state.
 *
 * Returns empty string when allowStyle is false (unconditionally).
 *
 * @param {HTMLElement} root         Modal root element.
 * @param {string}      styleSelector CSS selector for the text input.
 * @param {boolean}     allowStyle   Whether inline styles are permitted.
 * @returns {string}
 */
const resolveStyle = (root, styleSelector, allowStyle) => {
    if (!allowStyle) {
        return '';
    }
    return (root.querySelector(styleSelector)?.value ?? '').trim();
};

/**
 * Open the attributes modal and apply changes to the accordion.
 *
 * @param {import('tinymce').Editor} editor
 * @returns {Promise<void>}
 */
export const handleAttributesAction = async(editor) => {
    const details = getAccordionDetails(editor);
    if (!details) {
        const msg = await getString('accordionneedselection', component);
        await Notification.alert('', msg);
        return;
    }

    const summary = details.querySelector('summary');
    if (!summary) {
        const msg = await getString('accordionnosummary', component);
        await Notification.alert('', msg);
        return;
    }

    const allowStyle = getAllowInlineStyle(editor);
    const prefixRaw = getClassPrefixAllowlist(editor);
    const presets = getStylePresets(editor);
    const hasPresets = presets.length > 0;
    const elementid = editor.id;

    const detailsAttrs = readAttrs(editor, details);
    const summaryAttrs = readAttrs(editor, summary);

    const modal = await AccordionAttributesModal.create({
        templateContext: {
            elementid,
            uniqid: `accattr_${Math.random().toString(36).slice(2)}`,
            detailsclass: detailsAttrs.class,
            detailsstyle: allowStyle ? detailsAttrs.style : '',
            summaryclass: summaryAttrs.class,
            summarystyle: allowStyle ? summaryAttrs.style : '',
            allowinlinestyle: allowStyle,
            haspresets: hasPresets,
            stylepresets: presets,
        },
    });

    const $root = await modal.getRoot();
    const root = $root[0];

    if (hasPresets) {
        initPresetSelects(root, detailsAttrs.class, summaryAttrs.class, allowStyle);
    }

    const submit = (e) => {
        const submitBtn = e.target.closest('[data-action="save"]');
        if (!submitBtn) {
            return;
        }
        e.preventDefault();

        const dClassResult = resolveClass(root, '.tiny_accordion_details_class', prefixRaw, hasPresets);
        const sClassResult = resolveClass(root, '.tiny_accordion_summary_class', prefixRaw, hasPresets);

        if (!dClassResult.ok || !sClassResult.ok) {
            const msg = dClassResult.message || sClassResult.message || 'Invalid classes';
            Notification.alert('', msg);
            return;
        }

        const dStyle = resolveStyle(root, '.tiny_accordion_details_style', allowStyle);
        const sStyle = resolveStyle(root, '.tiny_accordion_summary_style', allowStyle);

        editor.undoManager.transact(() => {
            applyAttrs(editor, details, dClassResult.value, dStyle, allowStyle);
            applyAttrs(editor, summary, sClassResult.value, sStyle, allowStyle);
        });

        editor.nodeChanged();
        modal.destroy();
    };

    root.addEventListener('click', submit);
};
