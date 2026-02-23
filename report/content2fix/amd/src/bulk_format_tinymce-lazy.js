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
 * Bulk "Format with TinyMCE" handler for report_content2fix.
 *
 * @module     report_content2fix/bulk_format_tinymce-lazy
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import ModalForm from 'core_form/modalform';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import {getString} from 'core/str';

const SELECTORS = {
    formatTinymceBulkTrigger: '[data-action="format-tinymce-bulk"]',
};

const DEFAULT_EDITOR_WAIT_MS = 3500;

/**
 * Show confirmation modal, then start bulk formatting on Start.
 *
 * @param {Object} config
 * @param {Array} config.filterValues Filter values for the web service
 * @param {number} config.entryCount Total entry count for display
 * @param {number} config.editorWaitMs Milliseconds to wait before auto-saving
 */
function showConfirmationAndStart(config) {
    const {filterValues, entryCount, editorWaitMs} = config;
    const waitSeconds = (editorWaitMs > 0 ? editorWaitMs : DEFAULT_EDITOR_WAIT_MS) / 1000;

    Promise.all([
        getString('fixformatall_tinymce_confirm_title', 'report_content2fix'),
        getString('fixformatall_tinymce_confirm_body', 'report_content2fix', waitSeconds),
        getString('start', 'report_content2fix'),
        getString('cancel', 'moodle'),
    ]).then(([title, body, startStr, cancelStr]) => {
        return ModalSaveCancel.create({
            title,
            body: $('<p></p>').text(body)[0].outerHTML,
            buttons: {
                save: startStr,
                cancel: cancelStr,
            },
        }).then((modal) => {
            modal.getRoot().on(ModalEvents.save, (e) => {
                e.preventDefault();
                modal.hide();
                startBulkFormatting({filterValues, entryCount, editorWaitMs});
            });
            return modal.show();
        });
    }).catch(Notification.exception);
}

/**
 * Create overlay and control modal DOM.
 *
 * @param {Object} strings String keys for UI
 * @returns {{overlay: HTMLElement, statusEl: HTMLElement, cancelBtn: HTMLElement, titleEl: HTMLElement}}
 */
function createOverlayAndControlModal(strings) {
    const overlay = document.createElement('div');
    // Use very high z-index (10000+) so we stay above Moodle modals (typically 1050-1060).
    overlay.className = 'content2fix-bulk-overlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;' +
        'background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;' +
        'pointer-events:auto;';

    const controlModal = document.createElement('div');
    controlModal.className = 'content2fix-bulk-control';
    controlModal.style.cssText = 'background:#fff;padding:24px;border-radius:8px;min-width:320px;max-width:480px;' +
        'box-shadow:0 4px 20px rgba(0,0,0,0.2);z-index:10001;pointer-events:auto;';

    const titleEl = document.createElement('h5');
    titleEl.textContent = strings.processing;
    titleEl.className = 'mb-3';

    const statusEl = document.createElement('p');
    statusEl.className = 'mb-3';
    statusEl.setAttribute('data-region', 'status');

    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-secondary';
    cancelBtn.textContent = strings.cancel;
    cancelBtn.setAttribute('data-action', 'cancel');

    controlModal.appendChild(titleEl);
    controlModal.appendChild(statusEl);
    controlModal.appendChild(cancelBtn);
    overlay.appendChild(controlModal);

    return {overlay, statusEl, cancelBtn, titleEl};
}

/**
 * Update status text in the control modal.
 *
 * @param {HTMLElement} statusEl
 * @param {number} processed
 * @param {number} total
 * @param {string} processedStr
 * @param {string} remainingStr
 */
function updateStatus(statusEl, processed, total, processedStr, remainingStr) {
    statusEl.textContent = processedStr.replace('{$a->processed}', processed).replace('{$a->total}', total) +
        ' | ' + remainingStr.replace('{$a}', Math.max(0, total - processed));
}

/**
 * Run bulk TinyMCE formatting.
 *
 * @param {Object} config
 * @param {Array} config.filterValues
 * @param {number} config.entryCount
 * @param {number} config.editorWaitMs Milliseconds to wait before auto-saving
 */
function startBulkFormatting(config) {
    const {filterValues, entryCount, editorWaitMs} = config;
    const waitMs = editorWaitMs > 0 ? editorWaitMs : DEFAULT_EDITOR_WAIT_MS;
    const waitSeconds = waitMs / 1000;
    let processedCount = 0;
    let cancelled = false;
    let totalCount = entryCount;

    Promise.all([
        getString('processing_entries', 'report_content2fix', waitSeconds),
        getString('processing_entry_count', 'report_content2fix'),
        getString('processing_remaining', 'report_content2fix'),
        getString('cancel', 'moodle'),
        getString('processing_complete', 'report_content2fix'),
        getString('processing_summary', 'report_content2fix'),
        getString('close', 'moodle'),
    ]).then(([processingStr, entryCountStr, remainingStr, cancelStr, completeStr, summaryStr, closeStr]) => {
        const {overlay, statusEl, cancelBtn, titleEl} = createOverlayAndControlModal({
            processing: processingStr,
            cancel: cancelStr,
        });
        document.body.appendChild(overlay);
        updateStatus(statusEl, 0, totalCount, entryCountStr, remainingStr);

        let isComplete = false;
        cancelBtn.addEventListener('click', () => {
            if (isComplete) {
                cleanup();
                window.location.reload();
            } else {
                cancelled = true;
                cleanup();
                window.location.reload();
            }
        });

        /**
         * Remove overlay from DOM.
         */
        function cleanup() {
            if (overlay.parentNode) {
                overlay.remove();
            }
        }

        /**
         * Show completion summary and change Cancel to Close.
         */
        function showComplete() {
            isComplete = true;
            titleEl.textContent = completeStr;
            statusEl.textContent = summaryStr.replace('{$a}', processedCount);
            cancelBtn.textContent = closeStr;
        }

        /**
         * Fetch next entry from web service.
         * @param {number} afterId Last processed entry ID
         * @returns {Promise}
         */
        function getNextEntry(afterId) {
            return Ajax.call([{
                methodname: 'report_content2fix_get_next_filtered_entry',
                args: {filtervalues: filterValues, afterid: afterId},
            }])[0];
        }

        /**
         * Open TinyMCE modal for entry, wait, then submit.
         * @param {Object} entry Entry with id
         * @returns {Promise<number>} Resolves with entry id on success
         */
        function processEntry(entry) {
            if (cancelled) {
                return Promise.resolve(null);
            }
            return new Promise((resolve, reject) => {
                getString('fixformattinymce', 'report_content2fix').then((title) => {
                    const modalForm = new ModalForm({
                        formClass: 'report_content2fix\\form\\tinymce_edit_form',
                        modalConfig: {title, large: true},
                        args: {entryid: entry.id},
                        saveButtonText: getString('savechanges', 'moodle'),
                        returnFocus: null,
                    });

                    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                        processedCount++;
                        updateStatus(statusEl, processedCount, totalCount, entryCountStr, remainingStr);
                        resolve(entry.id);
                    });

                    modalForm.addEventListener(modalForm.events.ERROR, (e) => {
                        if (e.detail && e.detail.message) {
                            Notification.addNotification({type: 'error', message: e.detail.message});
                        }
                        resolve(entry.id);
                    });

                    modalForm.addEventListener(modalForm.events.LOADED, () => {
                        setTimeout(() => {
                            if (cancelled) {
                                return;
                            }
                            const form = modalForm.getFormNode();
                            if (form) {
                                $(form).trigger('submit');
                            }
                        }, waitMs);
                    });

                    modalForm.show().catch(reject);
                }).catch(reject);
            });
        }

        /**
         * Process entries in sequence until none remain or cancelled.
         * @param {number} afterId Last processed entry ID (0 for first)
         */
        function runLoop(afterId) {
            if (cancelled) {
                showComplete();
                return;
            }
            getNextEntry(afterId).then((result) => {
                if (cancelled) {
                    showComplete();
                    return;
                }
                totalCount = result.totalcount;
                updateStatus(statusEl, processedCount, totalCount, entryCountStr, remainingStr);
                if (result.entry) {
                    processEntry(result.entry).then((lastId) => {
                        if (cancelled) {
                            showComplete();
                            return;
                        }
                        runLoop(lastId);
                    });
                } else {
                    showComplete();
                }
            }).catch((err) => {
                Notification.exception(err);
                showComplete();
            });
        }

        runLoop(0);
    }).catch(Notification.exception);
}

/**
 * Initialise bulk format TinyMCE handlers.
 *
 * @param {Object} config
 * @param {Array} config.filterValues Filter values from the report
 * @param {number} config.entryCount Total filtered entry count
 * @param {number} config.editorWaitMs Milliseconds to wait before auto-saving (from plugin setting)
 */
export const init = (config) => {
    const filterValues = config?.filterValues ?? [];
    const entryCount = config?.entryCount ?? 0;
    const editorWaitMs = config?.editorWaitMs;

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(SELECTORS.formatTinymceBulkTrigger);
        if (!trigger) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        showConfirmationAndStart({filterValues, entryCount, editorWaitMs});
    });
};
