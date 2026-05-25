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
 * Polls block_backadel_get_migration_status every 10 s and refreshes the sidebar widget DOM.
 *
 * @module     block_backadel/migration_status_poll
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

const POLL_MS = 10000;

/**
 * Apply a fresh status payload to the widget DOM in-place.
 *
 * @param {{pending_count:number, failed_count:number, is_running:boolean, elapsed_human:string}} data
 */
const applyUpdate = (data) => {
    const widget = document.querySelector('[data-backadel-widget]');
    if (!widget) {
        return;
    }

    const pendingEl = widget.querySelector('[data-backadel-pending]');
    const failedEl  = widget.querySelector('[data-backadel-failed]');
    const statusEl  = widget.querySelector('[data-backadel-status]');

    if (pendingEl) {
        const n = Number(data.pending_count) || 0;
        pendingEl.textContent = n > 0 ? ' (' + n + ')' : '';
        pendingEl.hidden = n === 0;
    }

    if (failedEl) {
        const n = Number(data.failed_count) || 0;
        failedEl.textContent = n > 0 ? ' (' + n + ')' : '';
        failedEl.hidden = n === 0;
    }

    if (statusEl) {
        if (data.is_running) {
            statusEl.textContent = M.util.get_string(
                'status_running',
                'block_backadel',
                data.elapsed_human
            );
        } else {
            statusEl.textContent = M.util.get_string('status_not_running', 'block_backadel');
        }
    }
};

/**
 * Fire a single poll request and schedule the next one.
 */
const poll = () => {
    const widget = document.querySelector('[data-backadel-widget]');
    if (!widget) {
        return;
    }

    Ajax.call([{
        methodname: 'block_backadel_get_migration_status',
        args: {},
        done: (data) => {
            applyUpdate(data);
            setTimeout(poll, POLL_MS);
        },
        fail: () => {
            setTimeout(poll, POLL_MS);
        },
    }]);
};

/**
 * Initialise the poller. Called from block_backadel.php via js_call_amd().
 */
export const init = () => {
    setTimeout(poll, POLL_MS);
};
