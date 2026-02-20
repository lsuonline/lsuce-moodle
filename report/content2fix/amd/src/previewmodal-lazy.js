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
 * Preview modal handlers for report_content2fix.
 *
 * @module     report_content2fix/previewmodal-lazy
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';

const SELECTORS = {
    previewButton: '[data-action="report-content2fix-preview"]',
};

let modalPromise = null;

const getModal = async() => {
    if (!modalPromise) {
        modalPromise = Modal.create();
    }
    return modalPromise;
};

export const init = () => {
    document.addEventListener('click', async event => {
        const trigger = event.target.closest(SELECTORS.previewButton);
        if (!trigger) {
            return;
        }
        event.preventDefault();

        const previewid = trigger.getAttribute('data-preview-id');
        const previewcontent = previewid ? document.getElementById(previewid) : null;
        if (!previewcontent) {
            return;
        }

        const modal = await getModal();
        modal.setTitle(previewcontent.dataset.title || '');
        modal.setBody(previewcontent.innerHTML);
        modal.show();
    });
};
