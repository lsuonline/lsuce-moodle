/**
 * Manual catalogue course-type override modal (AJAX write + live badge update).
 *
 * @module     block_backadel/catalogue_coursetype_override
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Templates from 'core/templates';
import {call as fetchMany} from 'core/ajax';

/**
 * Escape text for safe insertion into HTML.
 *
 * @param {string} value Raw text.
 * @returns {string} Escaped HTML.
 */
const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
};

/**
 * @param {string} type Normalised coursetype key.
 * @returns {string} Badge element class list.
 */
const badgeClassForType = (type) => {
    switch (type) {
        case 'blueprint':
            return 'badge bg-info text-white coursetype-badge';
        case 'teaching':
            return 'badge bg-secondary text-white coursetype-badge';
        case 'other':
            return 'badge bg-light text-dark border coursetype-badge';
        default:
            return 'badge bg-secondary text-white coursetype-badge';
    }
};

/**
 * @param {string} type Coursetype key.
 * @param {object} strings Localised labels.
 * @returns {string} HTML-safe label text (already escaped where needed).
 */
const labelForType = (type, strings) => {
    switch (type) {
        case 'teaching':
            return escapeHtml(strings.typeTeaching);
        case 'blueprint':
            return escapeHtml(strings.typeBlueprint);
        case 'other':
            return escapeHtml(strings.typeOther);
        default:
            return escapeHtml(type);
    }
};

/**
 * @param {string} effectiveType Effective type or empty.
 * @param {object} strings Localised labels.
 * @returns {string} Inner HTML for the badge cell.
 */
const renderBadgeInnerHtml = (effectiveType, strings) => {
    const t = (effectiveType || '').toLowerCase();
    if (t === '') {
        return '<span class="text-muted coursetype-badge">—</span>';
    }
    const label = labelForType(t, strings);
    const cls = badgeClassForType(t);
    return `<span class="${cls}">${label}</span>`;
};

/**
 * Update the course type badge wrapper for one catalogue row.
 *
 * @param {number|string} catalogueId Row id.
 * @param {string} effectiveType Effective type from the web service.
 * @param {object} strings Localised labels.
 */
const updateBadgeCell = (catalogueId, effectiveType, strings) => {
    const wrapper = document.querySelector(
        `.coursetype-badge-wrapper[data-catalogue-id="${String(catalogueId)}"]`
    );
    if (!wrapper) {
        return;
    }
    wrapper.innerHTML = renderBadgeInnerHtml(effectiveType, strings);
};

/**
 * Update the trigger button data attributes after a successful save.
 *
 * @param {number|string} catalogueId Row id.
 * @param {object} response Web service response payload.
 */
const refreshTriggerDataset = (catalogueId, response) => {
    const trigger = document.querySelector(
        `[data-action="coursetype-override"][data-catalogue-id="${String(catalogueId)}"]`
    );
    if (!trigger) {
        return;
    }
    trigger.dataset.currentType = response.effective_type ? String(response.effective_type) : '';
    trigger.dataset.isOverridden = response.overridden ? '1' : '0';
    const noteVal = response.note !== undefined && response.note !== null ? String(response.note) : '';
    trigger.dataset.currentNote = noteVal;
};

/**
 * Open modal and wire save / clear-disable behaviour.
 *
 * @param {HTMLElement} btn Trigger element.
 * @param {object} strings Localised strings from PHP.
 */
const showOverrideModal = async(btn, strings) => {
    const pendingPromise = new Pending('block_backadel/catalogue_coursetype_override:show');

    try {
        const catalogueId = parseInt(btn.dataset.catalogueId || '0', 10);
        if (!catalogueId) {
            return;
        }
        const currentType = btn.dataset.currentType || '';
        const currentNote = btn.dataset.currentNote || '';

        const rname = `cowtype_${catalogueId}`;
        const clearId = `cow-clear-${catalogueId}`;
        const noteId = `${rname}-note`;
        const type = (currentType || '').toLowerCase();
        const {html: bodyHtml} = await Templates.renderForPromise('block_backadel/local/coursetype_override_modal', {
            rname,
            clearId,
            noteId,
            currentNote,
            teachingChecked: type === 'teaching',
            blueprintChecked: type === 'blueprint',
            otherChecked: type === 'other' || (type !== 'teaching' && type !== 'blueprint'),
            strings,
        });

        const modal = await Modal.create({
            title: strings.modalTitle,
            body: bodyHtml,
            show: true,
            removeOnClose: true,
            returnElement: btn,
        });

        const modalDialog = modal.getModal()[0];
        if (modalDialog) {
            modalDialog.classList.add('block_backadel-modal-dialog');
        }

        const rootEl = modal.getRoot()[0];
        if (!rootEl) {
            return;
        }

        const clearSelector = `#${clearId}`;

        const syncRadiosDisabled = () => {
            const clearEl = rootEl.querySelector(clearSelector);
            const disabled = clearEl ? clearEl.checked : false;
            rootEl.querySelectorAll(`input[name="${rname}"]`).forEach((input) => {
                input.disabled = !!disabled;
            });
        };

        rootEl.addEventListener('change', (e) => {
            const target = e.target;
            if (target && target.id === clearId) {
                syncRadiosDisabled();
            }
        });
        syncRadiosDisabled();

        rootEl.addEventListener('click', (e) => {
            const saveBtn = e.target.closest('[data-action="save-coursetype-override"]');
            if (!saveBtn) {
                return;
            }
            e.preventDefault();

            const pending = new Pending('block_backadel/catalogue_coursetype_override:save');
            const clearEl = rootEl.querySelector(clearSelector);
            const clearOverride = clearEl ? clearEl.checked : false;
            const noteEl = rootEl.querySelector('[data-field="override-note"]');
            const note = noteEl && 'value' in noteEl ? String(noteEl.value || '').trim() : '';

            let coursetype = '';
            if (!clearOverride) {
                const picked = rootEl.querySelector(`input[name="${rname}"]:checked`);
                coursetype = picked && 'value' in picked ? String(picked.value) : '';
            }

            const requests = fetchMany([{
                methodname: 'block_backadel_set_coursetype_override',
                args: {
                    catalogueid: catalogueId,
                    coursetype: coursetype,
                    note: note,
                },
            }]);

            requests[0].then((response) => {
                updateBadgeCell(response.catalogueid, response.effective_type, strings);
                refreshTriggerDataset(response.catalogueid, response);
                const msg = response.overridden ? strings.saved : strings.cleared;
                Notification.addNotification({message: msg, type: 'success'});
                modal.destroy();
                return true;
            }).catch((error) => {
                Notification.exception(error);
            }).finally(() => {
                pending.resolve();
            });
        });
    } catch (error) {
        Notification.exception(error);
    } finally {
        pendingPromise.resolve();
    }
};

/**
 * @param {object} strings Lang strings from PHP.
 */
export const init = (strings) => {
    try {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="coursetype-override"]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            showOverrideModal(btn, strings);
        });
    } catch (err) {
        window.console.error('block_backadel/catalogue_coursetype_override init failed:', err);
    }
};
