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

use html_writer;
use moodle_url;
use moodleform;

/**
 * GET filter form for the Backadel catalogue admin page.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue_filter_form extends moodleform {

    /**
     * @param \moodle_url|null $action Form action URL
     * @param mixed $customdata Expects keys years (array), semesters (array), filtersactive (bool optional)
     */
    public function __construct($action = null, $customdata = null) {
        parent::__construct($action, $customdata, 'get');
    }

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'q', get_string('search'));
        $mform->setType('q', PARAM_TEXT);

        $years = $this->_customdata['years'] ?? [];
        $mform->addElement('select', 'year', get_string('catalogue_filter_year', 'block_backadel'), $years);
        $mform->setType('year', PARAM_INT);

        $semesters = $this->_customdata['semesters'] ?? ['' => get_string('any')];
        $mform->addElement('select', 'semester', get_string('catalogue_filter_semester', 'block_backadel'), $semesters);
        $mform->setType('semester', PARAM_TEXT);

        $mform->addElement('select', 'status', get_string('table_col_status', 'block_backadel'), [
            '' => get_string('any'),
            'none' => get_string('catalogue_filter_status_none', 'block_backadel'),
            'available' => get_string('catalogue_status_available', 'block_backadel'),
            'missing' => get_string('catalogue_status_missing', 'block_backadel'),
            'archived' => get_string('catalogue_status_archived', 'block_backadel'),
        ]);
        $mform->setType('status', PARAM_ALPHA);

        $mform->addElement('select', 'source', get_string('catalogue_filter_source', 'block_backadel'), [
            '' => get_string('any'),
            'backadel_current' => 'backadel_current',
            'legacy_moodleus' => 'legacy_moodleus',
            'legacy_openlms' => 'legacy_openlms',
        ]);
        $mform->setType('source', PARAM_ALPHANUMEXT);

        $mform->addElement('select', 'pattern', get_string('catalogue_filter_pattern', 'block_backadel'), [
            '' => get_string('any'),
            'semester_legacy' => 'semester_legacy',
            'semester_legacy_lc' => 'semester_legacy_lc',
            'semester_legacy_clone' => 'semester_legacy_clone',
            'storage_course' => 'storage_course',
            'storagecourse_dept' => 'storagecourse_dept',
            'storage_legacy' => 'storage_legacy',
            'backadel_modern' => 'backadel_modern',
            'moodle_native' => 'moodle_native',
            'unknown' => 'unknown',
        ]);
        $mform->setType('pattern', PARAM_ALPHAEXT);

        $this->add_action_buttons(false, get_string('catalogue_filter_apply', 'block_backadel'));

        if (!empty($this->_customdata['filtersactive'])) {
            $clearurl = new moodle_url('/blocks/backadel/catalogue.php');
            $link = html_writer::link($clearurl, get_string('catalogue_clear_filters', 'block_backadel'));
            $mform->addElement('html', html_writer::div($link, 'mt-2'));
        }
    }
}
