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

namespace block_simple_restore\form;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use moodleform;

/**
 * GET filter form for catalogue-backed semester backups on Simple Restore list.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_filter_form extends moodleform {

    /**
     * @param \moodle_url|null $action Form action URL
     * @param mixed $customdata Expects keys: years (array select options), semesters (array), filtersactive (bool optional)
     * @param string $method Submit method passed to Moodle form (typically 'get')
     */
    public function __construct($action = null, $customdata = null, string $method = 'get') {
        parent::__construct($action, $customdata, $method);
    }

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'restore_to');
        $mform->setType('restore_to', PARAM_INT);

        $mform->addElement('text', 'q', get_string('filter_search', 'block_simple_restore'));
        $mform->setType('q', PARAM_TEXT);

        $years = $this->_customdata['years'] ?? [];
        $mform->addElement('select', 'year', get_string('filter_year', 'block_simple_restore'), $years);
        $mform->setType('year', PARAM_INT);

        $semesters = $this->_customdata['semesters'] ?? ['' => get_string('filter_all_semesters', 'block_simple_restore')];
        $mform->addElement('select', 'semester', get_string('filter_semester', 'block_simple_restore'), $semesters);
        $mform->setType('semester', PARAM_TEXT);

        $mform->addElement('select', 'coursetype', get_string('filter_coursetype', 'block_simple_restore'), [
            '' => get_string('any'),
            'teaching' => get_string('coursetype_teaching', 'block_backadel'),
            'blueprint' => get_string('coursetype_blueprint', 'block_backadel'),
            'other' => get_string('coursetype_other', 'block_backadel'),
        ]);
        $mform->setType('coursetype', PARAM_ALPHA);

        $mform->addElement('select', 'status', get_string('filter_status', 'block_simple_restore'), [
            'available' => get_string('filter_status_available', 'block_simple_restore'),
            '' => get_string('filter_status_all', 'block_simple_restore'),
        ]);
        $mform->setType('status', PARAM_ALPHA);

        $this->add_action_buttons(false, get_string('filter_apply', 'block_simple_restore'));

        if (!empty($this->_customdata['filtersactive'])) {
            $clearurl = new moodle_url('/blocks/simple_restore/list.php', [
                'id' => (int) ($this->_customdata['courseid'] ?? 0),
                'restore_to' => (int) ($this->_customdata['restore_to'] ?? 0),
            ]);
            $link = html_writer::link($clearurl, get_string('filter_clear', 'block_simple_restore'));
            $mform->addElement('html', html_writer::div($link, 'mt-2'));
        }
    }
}
