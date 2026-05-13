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
     * @param mixed $customdata Custom data for the form
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

        $this->add_action_buttons(false, get_string('search'));
    }
}
