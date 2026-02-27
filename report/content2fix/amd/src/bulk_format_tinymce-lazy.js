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
import Notification from 'core/notification';
import {getString} from 'core/str';
import Templates from 'core/templates';
import * as ModalSpinner from 'report_content2fix/modal_spinner';
import {show as showBulkConfirmModal, showMessageModal} from 'report_content2fix/bulk_confirm_modal';

const SELECTORS = {
    formatTinymceBulkTrigger: '[data-action="format-tinymce-bulk"]',
    formatBackendBulkTrigger: '[data-action="format-backend-bulk"]',
    /**
     * Report container selector for a given report id.
     * @param {number} reportId Report builder report id (persistent id)
     * @returns {string} CSS selector for the report element
     */
    reportById: (reportId) => `[data-region="core_reportbuilder/report"][data-report-id="${reportId}"]`,
    /** Filter form wrapper (contains the actual form) */
    filtersFormRegion: '[data-region="filters-form"]',
    /** Report table (has data-table-total-rows for validation) */
    reportTable: '[data-region="reportbuilder-table"]',
};

/** Attribute on the report container that holds the report builder persistent id. */
const ATTR_REPORT_ID = 'data-report-id';

const DEFAULT_EDITOR_WAIT_MS = 3500;

/** Id of the loading widget in the bulk processing modal (spinner shown only during web service calls). */
const BULK_LOADING_WIDGET_ID = 'content2fix-bulk-loading-widget';

/**
 * Get the report builder report id from the page when not provided by init config.
 * The report container has data-report-id (same id used by report builder to store filter state).
 *
 * @returns {number|null} Report id or null if no report element on the page
 */
function getReportIdFromPage() {
    const reportEl = document.querySelector('[data-region="core_reportbuilder/report"]');
    if (!reportEl) {
        return null;
    }
    const id = reportEl.getAttribute(ATTR_REPORT_ID);
    if (id === null || id === '') {
        return null;
    }
    const num = parseInt(id, 10);
    return Number.isNaN(num) ? null : num;
}

/**
 * Show confirmation modal (shared module), then start bulk TinyMCE formatting on Start.
 *
 * @param {Object} config
 * @param {Array} config.filterValues Filter values for the web service
 * @param {number} config.entryCount Total entry count for display
 * @param {number} [config.reportId] Report id (optional)
 * @param {number} config.editorWaitMs Milliseconds to wait before auto-saving
 * @param {number} config.maxEntriesPerRun Max entries to process in this run
 */
function showConfirmationAndStartTinyMCE(config) {
    const {filterValues, entryCount, reportId, editorWaitMs, maxEntriesPerRun} = config;
    const waitSeconds = (editorWaitMs > 0 ? editorWaitMs : DEFAULT_EDITOR_WAIT_MS) / 1000;

    showBulkConfirmModal({
        filterValues,
        entryCount,
        title: 'fixformatall_tinymce_confirm_title',
        bodyKey: 'fixformatall_tinymce_confirm_body',
        bodyParams: {count: entryCount, seconds: waitSeconds},
        confirmKey: 'start',
        cancelKey: 'cancel',
    }).then((result) => {
        if (result) {
            startBulkFormatting({
                filterValues: result.filterValues,
                entryCount: result.entryCount,
                reportId,
                editorWaitMs,
                maxEntriesPerRun,
            });
        }
    });
}

/**
 * Create overlay and control modal DOM from template.
 *
 * @param {Object} context Template context: processingTitle, statusText, cancelLabel, pauseLabel
 * @returns {Promise<{{overlay: HTMLElement, statusEl: HTMLElement, cancelBtn: HTMLElement, pauseBtn: HTMLElement,
 *   titleEl: HTMLElement, loadingWidget: HTMLElement, loadingTextEl: HTMLElement, actionsEl: HTMLElement}}>}
 */
function createOverlayAndControlModal(context) {
    return Templates.render('report_content2fix/bulk_processing_modal', context)
        .then((html) => {
            const wrap = document.createElement('div');
            wrap.innerHTML = html;
            const overlay = wrap.firstElementChild;
            const statusEl = overlay.querySelector('[data-region="status"]');
            const loadingWidget = overlay.querySelector('[data-region="loading"]');
            const loadingTextEl = overlay.querySelector('[data-region="loading-text"]');
            const cancelBtn = overlay.querySelector('[data-action="cancel"]');
            const pauseBtn = overlay.querySelector('[data-action="pause"]');
            const titleEl = overlay.querySelector('.content2fix-bulk-title');
            return {
                overlay,
                statusEl,
                cancelBtn,
                pauseBtn,
                titleEl,
                loadingWidget,
                loadingTextEl,
            };
        });
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
 * @param {number} config.maxEntriesPerRun Max entries to process in this run
 */
function startBulkFormatting(config) {
    const {filterValues, entryCount, editorWaitMs, maxEntriesPerRun} = config;
    const maxEntries = maxEntriesPerRun > 0 ? maxEntriesPerRun : 100;
    const waitMs = editorWaitMs > 0 ? editorWaitMs : DEFAULT_EDITOR_WAIT_MS;
    const waitSeconds = waitMs / 1000;
    let processedCount = 0;
    let cancelled = false;
    let totalCount = entryCount;

    Promise.all([
        getString('processing_entries', 'report_content2fix', waitSeconds),
        getString('processing_entry_count', 'report_content2fix'),
        getString('processing_remaining', 'report_content2fix'),
        getString('processing_loading', 'report_content2fix'),
        getString('cancel', 'moodle'),
        getString('processing_pause', 'report_content2fix'),
        getString('processing_resume', 'report_content2fix'),
        getString('processing_complete', 'report_content2fix'),
        getString('processing_summary', 'report_content2fix'),
        getString('processing_limit_reached', 'report_content2fix'),
        getString('closebuttontitle', 'core'),
    ]).then(([
        processingStr, entryCountStr, remainingStr, loadingStr, cancelStr,
        pauseStr, resumeStr,
        completeStr, summaryStr, limitReachedStr, closeStr,
    ]) => {
        const effectiveTotal = () => Math.min(totalCount, maxEntries);
        const initialTotal = effectiveTotal();
        const initialStatusText = entryCountStr
            .replace('{$a->processed}', 0)
            .replace('{$a->total}', initialTotal) +
            ' | ' + remainingStr.replace('{$a}', Math.max(0, initialTotal));

        return createOverlayAndControlModal({
            processingTitle: processingStr,
            statusText: initialStatusText,
            cancelLabel: cancelStr,
            pauseLabel: pauseStr,
        }).then(({overlay, statusEl, cancelBtn, pauseBtn, titleEl, loadingWidget, loadingTextEl}) => {
            document.body.appendChild(overlay);

            let isComplete = false;
            let paused = false;
            let pausedAfterId = 0;

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

            pauseBtn.addEventListener('click', () => {
                if (isComplete) {
                    return;
                }
                paused = !paused;
                pauseBtn.textContent = paused ? resumeStr : pauseStr;
                if (!paused) {
                    runLoop(pausedAfterId);
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
             * Explicitly hide the modal spinner so it never remains visible on completion.
             * @param {boolean} limitReached Whether the run stopped due to max entries per run
             */
            function showComplete(limitReached = false) {
                ModalSpinner.hide(BULK_LOADING_WIDGET_ID);
                setLoading(false);
                isComplete = true;
                titleEl.textContent = completeStr;
                statusEl.textContent = limitReached
                    ? limitReachedStr.replace('{$a}', maxEntries)
                    : summaryStr.replace('{$a}', processedCount);
                pauseBtn.classList.add('d-none');
                cancelBtn.textContent = closeStr;
            }

            /**
             * Show or hide the loading widget in the control modal.
             * @param {boolean} show Whether to show the widget
             * @param {string} [text] Text to display when showing
             */
            function setLoading(show, text) {
                loadingWidget.classList.toggle('content2fix-bulk-loading-hidden', !show);
                loadingWidget.classList.toggle('content2fix-bulk-loading-visible', show);
                if (loadingTextEl) {
                    loadingTextEl.textContent = text || '';
                }
            }

            /**
             * Fetch next entry from web service.
             * Spinner is shown only for the duration of this call via modal_spinner.
             * @param {number} afterId Last processed entry ID
             * @returns {Promise}
             */
            function getNextEntry(afterId) {
                const promise = Ajax.call([{
                    methodname: 'report_content2fix_get_next_filtered_entry',
                    args: {filtervalues: filterValues, afterid: afterId},
                }])[0];
                return ModalSpinner.wrapPromise(BULK_LOADING_WIDGET_ID, promise, {
                    loadingText: loadingStr,
                });
            }

            /**
             * Open TinyMCE modal for entry, wait, then submit.
             * If entry is null or has no id, resolve without opening the modal (e.g. when web service returns no entry).
             * @param {Object|null} entry Entry with id, or null
             * @returns {Promise<number|null>} Resolves with entry id on success, or null if skipped
             */
            function processEntry(entry) {
                if (cancelled) {
                    return Promise.resolve(null);
                }
                if (!entry || (entry.id === null || entry.id === undefined) || entry.id === '') {
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
                            updateStatus(statusEl, processedCount, effectiveTotal(), entryCountStr, remainingStr);
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
                    showComplete(false);
                    return;
                }
                if (paused) {
                    pausedAfterId = afterId;
                    return;
                }
                if (processedCount >= maxEntries) {
                    showComplete(true);
                    return;
                }
                getNextEntry(afterId).then((result) => {
                    if (cancelled) {
                        showComplete(false);
                        return;
                    }
                    if (paused) {
                        pausedAfterId = afterId;
                        return;
                    }
                    totalCount = result.totalcount;
                    updateStatus(statusEl, processedCount, effectiveTotal(), entryCountStr, remainingStr);
                    const hasEntry = result.entry &&
                        result.entry.id !== null && result.entry.id !== undefined && result.entry.id !== '';
                    if (hasEntry) {
                        processEntry(result.entry).then((lastId) => {
                            if (cancelled) {
                                showComplete(false);
                                return;
                            }
                            if (paused) {
                                pausedAfterId = lastId ?? afterId;
                                return;
                            }
                            if (processedCount >= maxEntries) {
                                showComplete(true);
                                return;
                            }
                            runLoop(lastId ?? afterId);
                        });
                    } else {
                        showComplete(false);
                    }
                }).catch((err) => {
                    Notification.exception(err);
                    showComplete(false);
                });
            }

            runLoop(0);
        });
    }).catch(Notification.exception);
}

/**
 * Load current filter values (from report or fallback), then get filtered count, then run onResult.
 *
 * @param {number|null} effectiveReportId
 * @param {Array} fallbackFilterValues
 * @param {number} entryCount
 * @param {function(Array, number): void} onResult Called with (filterValues, entryCount)
 */
function loadFiltersAndCount(effectiveReportId, fallbackFilterValues, entryCount, onResult) {
    if (effectiveReportId) {
        getString('loading_filters', 'report_content2fix')
            .then((loadingFiltersStr) => Templates.render(
                'report_content2fix/bulk_loading_filters',
                {loadingText: loadingFiltersStr}
            ))
            .then((html) => {
                const wrap = document.createElement('div');
                wrap.innerHTML = html;
                const loader = wrap.firstElementChild;
                document.body.appendChild(loader);
                return Ajax.call([{
                    methodname: 'report_content2fix_get_filter_values',
                    args: {reportid: effectiveReportId},
                }])[0].then((fromServer) => {
                    if (loader.parentNode) {
                        loader.remove();
                    }
                    return Array.isArray(fromServer) ? fromServer : [];
                }).catch(() => {
                    if (loader.parentNode) {
                        loader.remove();
                    }
                    return fallbackFilterValues;
                });
            })
            .then((filterValues) => {
                return Ajax.call([{
                    methodname: 'report_content2fix_get_filtered_entry_count',
                    args: {filtervalues: filterValues},
                }])[0].then((result) => {
                    onResult(filterValues, result.entrycount);
                }).catch(() => {
                    onResult(filterValues, entryCount);
                });
            })
            .catch(Notification.exception);
    } else {
        Ajax.call([{
            methodname: 'report_content2fix_get_filtered_entry_count',
            args: {filtervalues: fallbackFilterValues},
        }])[0].then((result) => {
            onResult(fallbackFilterValues, result.entrycount);
        }).catch(() => {
            onResult(fallbackFilterValues, entryCount);
        });
    }
}

/**
 * Show confirmation modal and queue backend format-all task on confirm.
 * On success, show a dismissible modal with task_queued_format_all message and link to task logs.
 *
 * @param {Object} config
 * @param {Array} config.filterValues
 * @param {number} config.entryCount
 * @param {string} config.taskQueuedModalTitle Title for the success modal
 * @param {string} config.taskLogsUrl URL to ad hoc tasks page
 * @param {string} config.taskLogsLinkText Link text (e.g. "Ad hoc tasks")
 */
function showConfirmationAndQueueBackend(config) {
    const {filterValues, entryCount, taskQueuedModalTitle, taskLogsUrl, taskLogsLinkText} = config;

    showBulkConfirmModal({
        filterValues,
        entryCount,
        title: 'fixformatall_backend_confirm_title',
        bodyKey: 'fixformatall_backend_confirm_body',
        bodyParams: {count: entryCount},
        confirmKey: 'start',
        cancelKey: 'cancel',
    }).then((result) => {
        if (!result) {
            return;
        }
        Ajax.call([{
            methodname: 'report_content2fix_queue_format_all_task',
            args: {filtervalues: result.filterValues},
        }])[0].then(() => {
            const linkHtml = '<a href="' + (taskLogsUrl || '') + '" target="_blank" rel="noopener">' +
                (taskLogsLinkText || '') + '</a>';
            return getString('task_queued_format_all', 'report_content2fix', linkHtml);
        }).then((message) => {
            const bodyHtml = '<p class="mb-0">' + message + '</p>';
            return showMessageModal(taskQueuedModalTitle || 'Task queued', bodyHtml);
        }).catch(Notification.exception);
    });
}

/**
 * Initialise bulk format TinyMCE and backend handlers.
 *
 * @param {Object} config
 * @param {number} config.reportId Report builder report id (to fetch current filters on click)
 * @param {Array} config.filterValues Filter values from page load (fallback if no reportId)
 * @param {number} config.entryCount Total filtered entry count
 * @param {number} config.editorWaitMs Milliseconds to wait before auto-saving (from plugin setting)
 * @param {number} config.maxEntriesPerRun Max entries to process per run
 * @param {string} [config.taskQueuedModalTitle] Title for the dismissible success modal after queue
 * @param {string} [config.taskLogsUrl] URL to the ad hoc tasks page (for success message link)
 * @param {string} [config.taskLogsLinkText] Text for the task logs link (e.g. "Ad hoc tasks")
 */
export const init = (config) => {
    const reportId = config?.reportId;
    const fallbackFilterValues = config?.filterValues ?? [];
    const entryCount = config?.entryCount ?? 0;
    const editorWaitMs = config?.editorWaitMs;
    const maxEntriesPerRun = config?.maxEntriesPerRun ?? 100;
    const taskQueuedModalTitle = config?.taskQueuedModalTitle ?? 'Task queued';
    const taskLogsUrl = config?.taskLogsUrl ?? '';
    const taskLogsLinkText = config?.taskLogsLinkText ?? '';

    document.addEventListener('click', (event) => {
        const triggerBackend = event.target.closest(SELECTORS.formatBackendBulkTrigger);
        if (triggerBackend) {
            event.preventDefault();
            event.stopPropagation();
            const effectiveReportId = reportId || getReportIdFromPage();
            loadFiltersAndCount(effectiveReportId, fallbackFilterValues, entryCount, (filterValues, count) => {
                showConfirmationAndQueueBackend({
                    filterValues,
                    entryCount: count,
                    taskQueuedModalTitle,
                    taskLogsUrl,
                    taskLogsLinkText,
                });
            });
            return;
        }

        const trigger = event.target.closest(SELECTORS.formatTinymceBulkTrigger);
        if (!trigger) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        const effectiveReportId = reportId || getReportIdFromPage();

        const loadFiltersThenStart = (filterValues, entryCountOverride) => {
            const effectiveCount = entryCountOverride !== undefined ? entryCountOverride : entryCount;
            showConfirmationAndStartTinyMCE({
                filterValues,
                entryCount: effectiveCount,
                reportId: effectiveReportId || undefined,
                editorWaitMs,
                maxEntriesPerRun,
            });
        };

        loadFiltersAndCount(effectiveReportId, fallbackFilterValues, entryCount, loadFiltersThenStart);
    });
};
