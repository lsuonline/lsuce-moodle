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

use core_course_category;
use html_writer;
use moodleform;
use moodle_url;

/**
 * GET filter form for the Backadel course search admin page.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_filter_form extends moodleform {

    /**
     * @param moodle_url|null $action Form action URL
     * @param mixed $customdata Optional keys: semesters (array slug => label), filtersactive (bool)
     */
    public function __construct($action = null, $customdata = null) {
        parent::__construct($action, $customdata, 'get');
    }

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'q', get_string('search'));
        $mform->setType('q', PARAM_TEXT);

        $cats = ['' => get_string('any')] + core_course_category::make_categories_list();
        $mform->addElement('select', 'category', get_string('category'), $cats);

        $mform->addElement('select', 'status', get_string('table_col_status', 'block_backadel'), [
            '' => get_string('any'),
            'none' => get_string('search_status_none', 'block_backadel'),
            'BACKUP' => get_string('results_status_backup', 'block_backadel'),
            'SUCCESS' => get_string('results_status_success', 'block_backadel'),
            'FAIL' => get_string('results_status_failed', 'block_backadel'),
            'DELETED' => get_string('results_status_deleted', 'block_backadel'),
        ]);
        $mform->setType('status', PARAM_ALPHA);

        $mform->addElement('select', 'coursetype', get_string('catalogue_filter_coursetype', 'block_backadel'), [
            '' => get_string('any'),
            'teaching' => get_string('coursetype_teaching', 'block_backadel'),
            'blueprint' => get_string('coursetype_blueprint', 'block_backadel'),
            'other' => get_string('coursetype_other', 'block_backadel'),
            'undetermined' => get_string('catalogue_coursetype_undetermined', 'block_backadel'),
        ]);
        $mform->setType('coursetype', PARAM_ALPHA);

        $semesters = $this->_customdata['semesters'] ?? ['' => get_string('any')];
        $mform->addElement('select', 'semester', get_string('filter_semester', 'block_backadel'), $semesters);
        $mform->setType('semester', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));

        if (!empty($this->_customdata['filtersactive'])) {
            $clearurl = new moodle_url('/blocks/backadel/search.php');
            $link = html_writer::link($clearurl, get_string('catalogue_clear_filters', 'block_backadel'));
            $mform->addElement('html', html_writer::div($link, 'mt-2'));
        }
    }
}
