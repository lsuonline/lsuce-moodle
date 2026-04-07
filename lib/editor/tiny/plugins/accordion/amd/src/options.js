// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Options for accordion attribute editing.
 *
 * @module      tiny_accordion/options
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const allowInlineStyleOption = getPluginOptionName(pluginName, 'allowinlinestyle');
const classPrefixAllowlistOption = getPluginOptionName(pluginName, 'classprefixallowlist');

/**
 * Register editor options (values come from PHP plugin configuration).
 *
 * @param {import('tinymce').Editor} editor
 */
export const register = (editor) => {
    const registerOption = editor.options.register;

    registerOption(allowInlineStyleOption, {
        processor: 'boolean',
        default: false,
    });

    registerOption(classPrefixAllowlistOption, {
        processor: 'string',
        default: '',
    });
};

/**
 * Whether inline style fields are enabled for authors.
 *
 * @param {import('tinymce').Editor} editor
 * @returns {boolean}
 */
export const getAllowInlineStyle = (editor) => editor.options.get(allowInlineStyleOption);

/**
 * Comma-separated class prefixes; empty means any class token is allowed.
 *
 * @param {import('tinymce').Editor} editor
 * @returns {string}
 */
export const getClassPrefixAllowlist = (editor) => editor.options.get(classPrefixAllowlistOption) || '';
