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
 * Options for the tiny_phonetic plugin.
 *
 * @module      tiny_phonetic/options
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const librariesOption = getPluginOptionName(pluginName, 'libraries');

/**
 * Register editor options (values come from PHP plugin configuration).
 *
 * @param {import('tinymce').Editor} editor
 */
export const register = (editor) => {
    editor.options.register(librariesOption, {
        processor: 'array',
        default: [],
    });
};

/**
 * Get the configured symbol libraries.
 *
 * @param {import('tinymce').Editor} editor
 * @returns {Array}
 */
export const getLibraries = (editor) => editor.options.get(librariesOption) ?? [];
