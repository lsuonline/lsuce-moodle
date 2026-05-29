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
 * Simple Restore: open restore confirmation as a {@link ModalForm}.
 *
 * @module block_simple_restore/restore_actions
 */

import ModalForm from 'core_form/modalform';
import * as Toast from 'core/toast';
import {get_strings as getStrings} from 'core/str';

/**
 * @param {HTMLElement} trigger
 * @returns {Promise<void>}
 */
const openRestoreModal = async(trigger) => {
    const courseid = parseInt(trigger.dataset.courseid ?? '0', 10);
    if (Number.isNaN(courseid) || courseid < 1) {
        return;
    }
    const filename = trigger.dataset.filename ?? '';
    if (filename === '') {
        return;
    }
    const restoreToRaw = trigger.dataset.restoreTo ?? '0';
    const restoreTo = parseInt(restoreToRaw, 10);
    const restoreToSafe = Number.isNaN(restoreTo) ? 0 : restoreTo;
    const catalogueIdRaw = trigger.dataset.catalogueId ?? '0';
    const catalogueId = parseInt(catalogueIdRaw, 10);
    const catalogueIdSafe = (Number.isNaN(catalogueId) || catalogueId < 1) ? 0 : catalogueId;

    const [title, saveLabel] = await getStrings([
        {key: 'restore_confirm_title', component: 'block_simple_restore'},
        {key: 'restore_confirm_save', component: 'block_simple_restore'},
    ]);
    const form = new ModalForm({
        formClass: 'block_simple_restore\\form\\restore_confirm_form',
        args: {
            courseid,
            filename,
            // eslint-disable-next-line camelcase
            restore_to: restoreToSafe,
            // eslint-disable-next-line camelcase
            catalogue_id: catalogueIdSafe,
        },
        modalConfig: {title},
        returnFocus: trigger,
    });

    form.addEventListener(form.events.LOADED, () => {
        form.modal.getModal().addClass('block_simple_restore-modal-dialog');
        form.modal.setSaveButtonText(saveLabel);
    });

    form.addEventListener(form.events.FORM_SUBMITTED, async(ev) => {
        const redirecturl = ev.detail?.redirecturl;
        if (redirecturl) {
            window.location.assign(redirecturl);
            return;
        }
        const msg = ev.detail?.message ?? '';
        if (msg !== '') {
            await Toast.add(msg, {type: 'success'});
        }
        window.location.reload();
    });

    form.show();
};

/**
 * Initialise delegated handlers for restore confirmation triggers.
 */
export const init = () => {
    document.body.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-action="restore-confirm"]');
        if (!trigger) {
            return;
        }
        e.preventDefault();
        void openRestoreModal(trigger);
    });
};
