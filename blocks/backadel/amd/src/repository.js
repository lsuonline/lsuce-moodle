/**
 * AJAX repository for block_backadel.
 *
 * @module     block_backadel/repository
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';

/**
 * Fetch rendered help content for a topic.
 *
 * @param {string} topic Help topic key.
 * @returns {Promise<{success: boolean, content: string}>}
 */
export const getHelpContent = (topic) => fetchMany([{
    methodname: 'block_backadel_get_help_content',
    args: {topic},
}])[0];

/**
 * Get the status of the migrate_filesystem_adhoc task.
 *
 * @returns {Promise<{status: string, catalogue_count: number, courses_count: number}>}
 */
export const getMigrateStatus = () => fetchMany([{
    methodname: 'block_backadel_get_migrate_status',
    args: {},
}])[0];
