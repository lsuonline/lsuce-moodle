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
 * @param {number} catalogueId
 * @param {string} currentType
 * @param {string} currentNote
 * @param {object} strings
 * @returns {string} Modal body HTML.
 */
const buildBodyHtml = (catalogueId, currentType, currentNote, strings) => {
    const rname = `cowtype_${catalogueId}`;
    const clearId = `cow-clear-${catalogueId}`;
    const type = (currentType || '').toLowerCase();
    const teachingChecked = type === 'teaching' ? 'checked' : '';
    const blueprintChecked = type === 'blueprint' ? 'checked' : '';
    const otherChecked = type === 'other' || (type !== 'teaching' && type !== 'blueprint')
        ? 'checked'
        : '';

    return `
<div class="block_backadel-modal-body">
  <div class="mb-3">
    <div class="fw-semibold mb-2">${escapeHtml(strings.labelType)}</div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="${rname}" id="${rname}-teaching" value="teaching" ${teachingChecked}/>
      <label class="form-check-label" for="${rname}-teaching">${escapeHtml(strings.typeTeaching)}</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="${rname}" id="${rname}-blueprint" value="blueprint" ${blueprintChecked}/>
      <label class="form-check-label" for="${rname}-blueprint">${escapeHtml(strings.typeBlueprint)}</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="${rname}" id="${rname}-other" value="other" ${otherChecked}/>
      <label class="form-check-label" for="${rname}-other">${escapeHtml(strings.typeOther)}</label>
    </div>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" value="1" id="${clearId}" data-field="clear-override" />
    <label class="form-check-label" for="${clearId}">${escapeHtml(strings.clearLabel)}</label>
  </div>
  <div class="mb-3">
    <label class="form-label" for="${rname}-note">${escapeHtml(strings.labelNote)}</label>
    <textarea class="form-control" id="${rname}-note" data-field="override-note"
              rows="3" maxlength="1024">${escapeHtml(currentNote)}</textarea>
  </div>
  <div>
    <button type="button" class="btn btn-primary" data-action="save-coursetype-override">${escapeHtml(strings.save)}</button>
  </div>
</div>`;
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

        const bodyHtml = buildBodyHtml(catalogueId, currentType, currentNote, strings);

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

        const rname = `cowtype_${catalogueId}`;
        const clearSelector = `#cow-clear-${catalogueId}`;

        const syncRadiosDisabled = () => {
            const clearEl = rootEl.querySelector(clearSelector);
            const disabled = clearEl ? clearEl.checked : false;
            rootEl.querySelectorAll(`input[name="${rname}"]`).forEach((input) => {
                input.disabled = !!disabled;
            });
        };

        rootEl.addEventListener('change', (e) => {
            const target = e.target;
            if (target && target.id === `cow-clear-${catalogueId}`) {
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
