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
 * Shared confirmation modal for bulk format actions (TinyMCE and Backend).
 * Shows entry count, optional filter summary, and Start/Cancel.
 *
 * @module     report_content2fix/bulk_confirm_modal
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalSaveCancel from 'core/modal_save_cancel';
import ModalCancel from 'core/modal_cancel';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import {getString} from 'core/str';
import Templates from 'core/templates';

/**
 * Normalise a filter form key to the flat key reportbuilder expects.
 * Form group elements submit as "groupname[flatkey]" (e.g. course:fullname_group[course:fullname_operator]).
 *
 * @param {string} name Raw name from form or payload
 * @returns {string} Flat key (e.g. course:fullname_operator)
 */
function normaliseFilterKey(name) {
    const m = String(name).match(/^[^\[]+\[([^\]]+)\]$/);
    return m ? m[1] : name;
}

/**
 * Build a map from flat filter key to value from the filter values array.
 *
 * @param {Array<{name: string, value: string}>} filterValues
 * @returns {Object<string, string>}
 */
function filterValuesToMap(filterValues) {
    const map = {};
    filterValues.forEach(({name, value}) => {
        const flatKey = normaliseFilterKey(name);
        map[flatKey] = value;
    });
    return map;
}

/**
 * Format filter values into human-readable lines for the confirmation modal.
 * Matches the three report builder filters: Course full name, Component, Last checked.
 *
 * @param {Array<{name: string, value: string}>} filterValues
 * @returns {Promise<string[]>} Resolves to an array of lines to display
 */
export function formatFiltersSummary(filterValues) {
    if (!Array.isArray(filterValues) || filterValues.length === 0) {
        return Promise.resolve([]);
    }
    const map = filterValuesToMap(filterValues);
    const get = (key) => (map[key] !== undefined && map[key] !== '' ? map[key] : null);
    const op = (key) => get(key);
    const val = (key) => get(key);

    return Promise.all([
        getString('filter_label_coursefullname', 'report_content2fix'),
        getString('filter_label_component', 'report_content2fix'),
        getString('filter_label_lastchecked', 'report_content2fix'),
        getString('filterisanyvalue', 'core_reportbuilder'),
        getString('filtercontains', 'core_reportbuilder'),
        getString('filterdoesnotcontain', 'core_reportbuilder'),
        getString('filterisequalto', 'core_reportbuilder'),
        getString('filterisnotequalto', 'core_reportbuilder'),
        getString('filterstartswith', 'core_reportbuilder'),
        getString('filterendswith', 'core_reportbuilder'),
        getString('filterisempty', 'core_reportbuilder'),
        getString('filterisnotempty', 'core_reportbuilder'),
        getString('filterrange', 'core_reportbuilder'),
        getString('filterdatelast', 'core_reportbuilder'),
        getString('filterdatecurrent', 'core_reportbuilder'),
    ]).then(([
        labelCourse, labelComponent, labelLastChecked,
        strAny, strContains, strDoesNotContain, strEqual, strNotEqual,
        strStartsWith, strEndsWith, strEmpty, strNotEmpty,
        strRange, strLast, strCurrent,
    ]) => {
        const textOps = {
            '0': strAny,
            '1': strContains,
            '2': strDoesNotContain,
            '3': strEqual,
            '4': strNotEqual,
            '5': strStartsWith,
            '6': strEndsWith,
            '7': strEmpty,
            '8': strNotEmpty,
        };
        const selectOps = {'0': strAny, '1': strEqual, '2': strNotEqual};
        const dateOps = {'0': strAny, '3': strRange, '4': strLast, '5': strCurrent};

        const lines = [];
        const courseOp = op('course:fullname_operator');
        const courseVal = val('course:fullname_value');
        const courseOpStr = courseOp !== null && textOps[courseOp] !== undefined
            ? textOps[courseOp]
            : strAny;
        const courseDisplay = (courseOp === '0' || courseOp === 0 || courseOpStr === strAny)
            ? courseOpStr
            : courseOpStr + (courseVal ? ' "' + courseVal + '"' : '');
        lines.push(labelCourse + ': ' + courseDisplay);

        const compOp = op('malformed_content:component_operator');
        const compVal = val('malformed_content:component_value');
        const compOpStr = compOp !== null && selectOps[compOp] !== undefined
            ? selectOps[compOp]
            : strAny;
        const compDisplay = (compOp === '0' || compOp === 0 || compOpStr === strAny)
            ? compOpStr
            : compOpStr + (compVal ? ' "' + compVal + '"' : '');
        lines.push(labelComponent + ': ' + compDisplay);

        const timeOp = op('malformed_content:timechecked_operator');
        const timeOpStr = timeOp !== null && dateOps[timeOp] !== undefined
            ? dateOps[timeOp]
            : strAny;
        lines.push(labelLastChecked + ': ' + timeOpStr);

        return lines;
    });
}

/**
 * Show the bulk format confirmation modal.
 * Resolves with { filterValues, entryCount } when user clicks Start; resolves with null when cancelled.
 *
 * @param {Object} config
 * @param {Array<{name: string, value: string}>} config.filterValues Filter values for the report
 * @param {number} config.entryCount Total entry count for display
 * @param {string} config.title String key for modal title (e.g. 'fixformatall_tinymce_confirm_title')
 * @param {string} config.bodyKey String key for body (e.g. 'fixformatall_tinymce_confirm_body')
 * @param {Object} [config.bodyParams] Placeholders for the body string (e.g. { count, seconds })
 * @param {string} [config.confirmKey='start'] String key for confirm button (report_content2fix)
 * @param {string} [config.cancelKey='cancel'] String key for cancel button (moodle)
 * @returns {Promise<{filterValues: Array, entryCount: number}|null>}
 */
export function show(config) {
    const {
        filterValues,
        entryCount,
        title,
        bodyKey,
        bodyParams = {},
        confirmKey = 'start',
        cancelKey = 'cancel',
    } = config;

    const titlePromise = typeof title === 'string' && title.startsWith('fixformatall_')
        ? getString(title, 'report_content2fix')
        : Promise.resolve(title);
    const bodyPromise = bodyKey
        ? getString(bodyKey, 'report_content2fix', bodyParams)
        : Promise.resolve('');
    const confirmStrPromise = getString(confirmKey, 'report_content2fix').catch(() => getString('start', 'report_content2fix'));
    const cancelStrPromise = getString(cancelKey, 'moodle');

    return Promise.all([
        titlePromise,
        bodyPromise,
        getString('filters_applied', 'report_content2fix'),
        confirmStrPromise,
        cancelStrPromise,
    ]).then(([titleStr, bodyStr, filtersAppliedLabel, startStr, cancelStr]) => {
        return formatFiltersSummary(filterValues).then((lines) => {
            const filtersSummary = Array.isArray(lines)
                ? lines.map((line) => ({line: String(line)}))
                : [];
            const hasFilters = filtersSummary.length > 0;
            const templateContext = {
                body: bodyStr,
                filtersAppliedLabel,
                filtersSummary,
                hasFiltersSummary: hasFilters,
            };
            return Templates.render('report_content2fix/bulk_confirm_body', templateContext);
        }).then((bodyHtml) => ModalSaveCancel.create({
            title: titleStr,
            body: bodyHtml,
            buttons: {
                save: startStr,
                cancel: cancelStr,
            },
        }))
        .then((modal) => {
            return new Promise((resolve) => {
                let resolved = false;
                const done = (value) => {
                    if (!resolved) {
                        resolved = true;
                        resolve(value);
                    }
                };
                modal.getRoot().on(ModalEvents.save, (e) => {
                    e.preventDefault();
                    done({filterValues, entryCount});
                    modal.hide();
                });
                modal.getRoot().on(ModalEvents.hidden, () => {
                    done(null);
                });
                modal.show();
            });
        });
    }).catch(Notification.exception);
}

/**
 * Show a dismissible modal with a title and body (e.g. success message).
 * Uses ModalCancel so only one "Close" button is shown in the footer.
 *
 * @param {string} title Modal title
 * @param {string} bodyHtml HTML body (e.g. message with link)
 * @param {string} [closeButtonKey='closebuttontitle'] String key for close button (core)
 * @returns {Promise<void>} Resolves when the modal is closed
 */
export function showMessageModal(title, bodyHtml, closeButtonKey = 'closebuttontitle') {
    const closeStrPromise = getString(closeButtonKey, 'core').catch(() => 'Close');

    return closeStrPromise.then((closeStr) => {
        return ModalCancel.create({
            title,
            body: bodyHtml,
            buttons: {
                cancel: closeStr,
            },
        });
    }).then((modal) => {
        // Single close button: hide header X so only the footer Close button is shown.
        modal.getRoot().addClass('content2fix-message-modal');
        return new Promise((resolve) => {
            let resolved = false;
            const done = () => {
                if (!resolved) {
                    resolved = true;
                    resolve();
                }
            };
            modal.getRoot().on(ModalEvents.cancel, (e) => {
                e.preventDefault();
                modal.hide();
                done();
            });
            modal.getRoot().on(ModalEvents.hidden, () => {
                done();
            });
            modal.show();
        });
    }).catch(Notification.exception);
}
