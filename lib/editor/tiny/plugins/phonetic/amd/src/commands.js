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
 * Toolbar and menu registration for tiny_phonetic.
 *
 * @module      tiny_phonetic/commands
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import {buttonName, component, icon} from './common';
import {handleAction} from './ui';

// Inline SVG for the toolbar icon — a 5-bar audio waveform representing speech/
// phonetics. Path-based so it renders reliably regardless of font availability
// or bundler character encoding.
const iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' +
    '<rect x="11" y="2" width="2" height="20" rx="1" fill="currentColor"/>' +
    '<rect x="7" y="5" width="2" height="14" rx="1" fill="currentColor"/>' +
    '<rect x="15" y="5" width="2" height="14" rx="1" fill="currentColor"/>' +
    '<rect x="3" y="8" width="2" height="8" rx="1" fill="currentColor"/>' +
    '<rect x="19" y="8" width="2" height="8" rx="1" fill="currentColor"/>' +
    '</svg>';

/**
 * @returns {Promise<Function>}
 */
export const getSetup = async() => {
    const tooltip = await getString('buttontitle', component);

    return (editor) => {
        editor.ui.registry.addIcon(icon, iconSvg);

        editor.ui.registry.addButton(buttonName, {
            icon,
            tooltip,
            onAction: () => handleAction(editor),
        });

        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: tooltip,
            onAction: () => handleAction(editor),
        });
    };
};
