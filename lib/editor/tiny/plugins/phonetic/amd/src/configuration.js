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
 * Tiny phonetic configuration.
 *
 * @module      tiny_phonetic/configuration
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {addToolbarButton, addMenubarItem} from 'editor_tiny/utils';
import {buttonName} from './common';

/**
 * Configure the TinyMCE instance with the phonetic toolbar button and menu item.
 *
 * @param {Object} instanceConfig The current editor instance configuration
 * @returns {Object}
 */
export const configure = (instanceConfig) => {
    return {
        toolbar: addToolbarButton(instanceConfig.toolbar, 'content', buttonName),
        menu: addMenubarItem(instanceConfig.menu, 'insert', buttonName),
    };
};
