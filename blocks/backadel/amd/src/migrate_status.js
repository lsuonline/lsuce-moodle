/**
 * Live status polling for the Backadel filesystem migration adhoc task.
 *
 * On page load: immediately polls to check if a migration task is already queued.
 * While queued: disables the run button and polls every 5 s.
 * When task disappears: shows a success banner with updated counts.
 *
 * @module     block_backadel/migrate_status
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getMigrateStatus} from './repository';
import Notification from 'core/notification';

const POLL_MS = 5000;

let pollTimer = null;
let wasQueued = false;

/**
 * Update all data-region elements with the latest response data.
 *
 * @param {string} status  'idle' or 'queued'
 * @param {object} data    Full response from get_migrate_status
 */
const applyState = (status, data) => {
    const btn         = document.querySelector('[data-action="run-migrate"]');
    const cntCat      = document.querySelector('[data-region="migrate-count-catalogue"]');
    const cntCourses  = document.querySelector('[data-region="migrate-count-courses"]');
    const successBadge = document.querySelector('[data-region="migrate-success"]');

    if (cntCat)     { cntCat.textContent = data.catalogue_count; }
    if (cntCourses) { cntCourses.textContent = data.courses_count; }

    if (status === 'queued') {
        wasQueued = true;
        if (btn)          { btn.disabled = true; }
        if (successBadge) { successBadge.classList.add('d-none'); }

        if (!pollTimer) {
            pollTimer = setInterval(poll, POLL_MS);
        }
    } else {
        // Idle — task finished or was never queued.
        if (btn)     { btn.disabled = false; }

        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }

        if (wasQueued && successBadge) {
            successBadge.classList.remove('d-none');
        }
    }
};

const poll = () => {
    getMigrateStatus()
        .then((r) => applyState(r.status, r))
        .catch(Notification.exception);
};

/**
 * Initialise migrate-status polling on page load.
 */
export const init = () => {
    poll();
};
