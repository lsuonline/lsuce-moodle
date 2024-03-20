<?php
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
 * @package    block_quickmail
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evaluations\tasks;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use core\task\manager as task_manager;
require_once(dirname(dirname(dirname(__FILE__))) . '/locallib.php');

class send_emails_task extends scheduled_task {

    // public function get_name() {
    //     return local_evaluations_string::get('send_emails_task');
    // }

    /*
     * This task migrates historical data from Quickmail v1 schema to v2 schema
     *
     * The idea is that, if enabled, this task will continue
     * to transfer data from block_quickmail_log and block_quickmail_drafts until completion
     *
     * Required custom data: none
     */
    public function execute() {
        error_log("\n\n ---------- Evaluations - Executing Send Emails Task ----------");
        try {
            process_mail_que();
        } catch (chunk_size_met_exception $e) {
            return true;
        } catch (\Exception $e) {
            // TODO: Localize this string.
            return 'something has gone wrong in the migration process: ' . $e->getMessage();
        }
    }

}
