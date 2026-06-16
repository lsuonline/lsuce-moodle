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
// along with Moodle.  If not, see <http://moodle.org/licenses/>.

/**
 * Modal handler for "Format with TinyMCE" action.
 *
 * @module     report_content2fix/format_tinymce_modal-lazy
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {getString} from 'core/str';

const SELECTORS = {
    formatTinymceTrigger: '[data-action="format-tinymce"]',
};

/**
 * Initialise format TinyMCE modal handlers.
 */
export const init = () => {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(SELECTORS.formatTinymceTrigger);
        if (!trigger) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        const entryid = trigger.getAttribute('data-entryid');
        if (!entryid) {
            return;
        }

        getString('fixformattinymce', 'report_content2fix').then((title) => {
            const modalForm = new ModalForm({
                formClass: 'report_content2fix\\form\\tinymce_edit_form',
                modalConfig: {
                    title,
                    large: true,
                },
                args: {
                    entryid: parseInt(entryid, 10),
                },
                saveButtonText: getString('savechanges', 'moodle'),
                returnFocus: trigger,
            });

            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                window.location.reload();
            });

            modalForm.addEventListener(modalForm.events.ERROR, (e) => {
                if (e.detail && e.detail.message) {
                    Notification.addNotification({
                        type: 'error',
                        message: e.detail.message,
                    });
                }
            });

            modalForm.show();
        }).catch(Notification.exception);
    });
};
