// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
import {getAllowInlineStyle, getClassPrefixAllowlist} from 'tiny_accordion/options';
import {component} from 'tiny_accordion/common';

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

/**
 * Read class and style from an element (attributes may be absent).
 *
 * @param {import('tinymce').Editor} editor
 * @param {Element} el
 * @returns {{class: string, style: string}}
 */
const readAttrs = (editor, el) => ({
    class: editor.dom.getAttrib(el, 'class') || '',
    style: editor.dom.getAttrib(el, 'style') || '',
});

/**
 * Set or remove class and style on an element.
 *
 * @param {import('tinymce').Editor} editor
 * @param {Element} el
 * @param {string} className
 * @param {string} style
 * @param {boolean} allowStyle
 */
const applyAttrs = (editor, el, className, style, allowStyle) => {
    if (className.trim()) {
        editor.dom.setAttrib(el, 'class', className.trim());
    } else {
        editor.dom.setAttrib(el, 'class', null);
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
        },
    });

    const $root = await modal.getRoot();
    const root = $root[0];

    const submit = (e) => {
        const submitBtn = e.target.closest('[data-action="save"]');
        if (!submitBtn) {
            return;
        }
        e.preventDefault();

        const dClass = root.querySelector('.tiny_accordion_details_class')?.value ?? '';
        let dStyle = root.querySelector('.tiny_accordion_details_style')?.value ?? '';
        const sClass = root.querySelector('.tiny_accordion_summary_class')?.value ?? '';
        let sStyle = root.querySelector('.tiny_accordion_summary_style')?.value ?? '';

        if (!allowStyle) {
            dStyle = '';
            sStyle = '';
        }

        const dClassResult = filterClasses(dClass, prefixRaw);
        const sClassResult = filterClasses(sClass, prefixRaw);
        if (!dClassResult.ok || !sClassResult.ok) {
            const msg = dClassResult.message || sClassResult.message || 'Invalid classes';
            Notification.alert('', msg);
            return;
        }

        editor.undoManager.transact(() => {
            applyAttrs(editor, details, dClassResult.value, dStyle, allowStyle);
            applyAttrs(editor, summary, sClassResult.value, sStyle, allowStyle);
        });

        editor.nodeChanged();
        modal.destroy();
    };

    root.addEventListener('click', submit);
};
