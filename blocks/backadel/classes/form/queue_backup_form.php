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

declare(strict_types=1);

namespace block_backadel\form;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_form\dynamic_form;
use moodle_url;
use stdClass;

/**
 * Dynamic form: queue one course for the next Backadel backup run (status BACKUP).
 *
 * Mirrors queuing logic in backup.php (insert when the course is not already in the status table).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_backup_form extends dynamic_form {

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
    }

    #[\Override]
    protected function get_context_for_dynamic_submission(): \context {
        return context_system::instance();
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        require_capability('block/backadel:managebackups', $this->get_context_for_dynamic_submission());
    }

    #[\Override]
    public function process_dynamic_submission(): array {
        global $DB;

        $data = $this->get_data();
        $courseid = (int) ($data->courseid ?? 0);
        if ($courseid < 2) {
            throw new \moodle_exception('invalidcourseid', 'error');
        }
        if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new \moodle_exception('invalidcourseid', 'error');
        }

        $currentids = $DB->get_fieldset_select('block_backadel_statuses', 'coursesid', '');
        if (!$currentids || !in_array($courseid, $currentids, false)) {
            $status = new stdClass();
            $status->coursesid = $courseid;
            $status->status = 'BACKUP';
            $DB->insert_record('block_backadel_statuses', $status);
        }

        return [
            'success' => true,
            'message' => get_string('queued_backup', 'block_backadel'),
        ];
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $this->set_data((object) ['courseid' => $courseid]);
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/blocks/backadel/search.php');
    }
}
