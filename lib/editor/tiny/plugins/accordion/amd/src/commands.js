// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Toolbar and menu registration for accordion attributes.
 *
 * @module      tiny_accordion/commands
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import {attributesButtonName, component} from 'tiny_accordion/common';
import {getAccordionDetails, handleAttributesAction} from 'tiny_accordion/ui';

/**
 * @returns {Promise<Function>}
 */
export const getSetup = async() => {
    const buttonText = await getString('accordionattributes', component);

    return (editor) => {
        editor.ui.registry.addButton(attributesButtonName, {
            icon: 'settings',
            tooltip: buttonText,
            onAction: () => handleAttributesAction(editor),
            onSetup: (api) => {
                const update = () => {
                    const details = getAccordionDetails(editor);
                    api.setEnabled(!!details);
                };
                editor.on('NodeChange', update);
                editor.on('keyup', update);
                update();
                return () => {
                    editor.off('NodeChange', update);
                    editor.off('keyup', update);
                };
            },
        });

        editor.ui.registry.addMenuItem(attributesButtonName, {
            icon: 'settings',
            text: buttonText,
            onAction: () => handleAttributesAction(editor),
        });

        editor.ui.registry.addContextMenu(attributesButtonName, {
            update: (node) => {
                const details = editor.dom.getParent(node, 'details.mce-accordion', editor.getBody());
                return details ? attributesButtonName : '';
            },
        });

        // Include TinyMCE's native accordion buttons alongside ours so they all
        // appear together. Our registration wins for mce-accordion elements
        // (registered after the native plugin), so we must carry Toggle/Delete too.
        editor.ui.registry.addContextToolbar(attributesButtonName, {
            predicate: (node) => {
                const details = editor.dom.getParent(node, 'details.mce-accordion', editor.getBody());
                return !!details;
            },
            items: `accordiontoggle accordionremove ${attributesButtonName}`,
            position: 'node',
            scope: 'node',
        });
    };
};
