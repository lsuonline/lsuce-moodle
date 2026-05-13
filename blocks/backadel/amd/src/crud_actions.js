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
 * Backadel modal actions: queue backup, re-queue failed, delete archive.
 *
 * @module block_backadel/crud_actions
 */

import ModalForm from 'core_form/modalform';
import * as Toast from 'core/toast';
import {get_strings as getStrings} from 'core/str';

const FORM_MAP = {
    queue: 'block_backadel\\form\\queue_backup_form',
    requeue: 'block_backadel\\form\\requeue_form',
    delete: 'block_backadel\\form\\delete_archive_form',
};

const ACTION_STRINGS = {
    queue:   {title: 'action_confirm_title',  btn: null},
    requeue: {title: 'requeue_title',         btn: 'requeue_btn'},
    delete:  {title: 'delete_archive_title',  btn: 'delete_archive_btn'},
};

/**
 * @param {Event} e
 * @returns {Promise<void>}
 */
const handleClick = async(e) => {
    const trigger = e.target.closest('[data-action]');
    if (!trigger) {
        return;
    }
    const action = trigger.dataset.action;
    if (!FORM_MAP[action]) {
        return;
    }
    e.preventDefault();

    const courseIdRaw = trigger.dataset.courseid;
    if (courseIdRaw === undefined || courseIdRaw === '') {
        return;
    }
    const courseid = parseInt(courseIdRaw, 10);
    if (Number.isNaN(courseid)) {
        return;
    }

    const cfg = ACTION_STRINGS[action];
    const keys = [{key: cfg.title, component: 'block_backadel'}];
    if (cfg.btn) {
        keys.push({key: cfg.btn, component: 'block_backadel'});
    }
    const strings = await getStrings(keys);
    const title = strings[0];
    const btnLabel = cfg.btn ? strings[1] : null;

    const form = new ModalForm({
        formClass: FORM_MAP[action],
        args: {courseid},
        modalConfig: {title},
        returnFocus: trigger,
    });

    form.addEventListener(form.events.LOADED, () => {
        form.modal.getModal().addClass('block_backadel-modal-dialog');
        if (btnLabel) {
            form.modal.setSaveButtonText(btnLabel);
        }
        if (action === 'delete') {
            form.modal.getModal().addClass('border-danger');
        }
    });

    form.addEventListener(form.events.FORM_SUBMITTED, async(ev) => {
        const msg = ev.detail?.message ?? '';
        if (msg !== '') {
            await Toast.add(msg, {type: 'success'});
        }
        window.location.reload();
    });

    form.show();
};

/**
 * Initialise delegated click handling for Backadel modal CRUD actions.
 */
export const init = () => {
    document.body.addEventListener('click', (e) => {
        void handleClick(e);
    });
};
