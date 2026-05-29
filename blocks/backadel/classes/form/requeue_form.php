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
use html_writer;
use moodle_url;

/**
 * Dynamic form: re-queue a failed Backadel backup (FAIL → BACKUP).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requeue_form extends dynamic_form {

    #[\Override]
    protected function definition(): void {
        global $DB;

        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $course = $courseid > 0 ? $DB->get_record('course', ['id' => $courseid]) : null;

        $html = html_writer::start_div('mb-3');
        if ($course) {
            $html .= html_writer::tag('p',
                html_writer::tag('strong', get_string('requeue_course_label', 'block_backadel') . ': ') .
                s($course->fullname) . ' (' . s($course->shortname) . ')'
            );
        }
        $html .= html_writer::div(
            get_string('requeue_info', 'block_backadel'),
            'alert alert-info mb-0'
        );
        $html .= html_writer::end_div();
        $mform->addElement('html', $html);
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
        if ($courseid < 1) {
            throw new \moodle_exception('invalidcourseid', 'error');
        }
        if (!$DB->record_exists('block_backadel_statuses', ['coursesid' => $courseid])) {
            throw new \moodle_exception('invalidrequest', 'error', '', null, 'No Backadel status for this course');
        }

        $DB->set_field('block_backadel_statuses', 'status', 'BACKUP', ['coursesid' => $courseid]);

        return [
            'success' => true,
            'message' => get_string('requeued_backup', 'block_backadel'),
        ];
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        $this->set_data((object) ['courseid' => $courseid]);
    }

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/blocks/backadel/failed.php');
    }
}
