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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lsuxe\form;

use coding_exception;
use MoodleQuickForm_autocomplete;

global $CFG;
require_once($CFG->libdir . '/form/autocomplete.php');

class groupform_autocomplete extends MoodleQuickForm_autocomplete {

    /** @var bool Only visible frameworks? */
    protected $onlyvisible = false;

    /**
     * Constructor.
     *
     * @param string $elementName Element name
     * @param mixed $elementLabel Label(s) for an element
     * @param array $options Options to control the element's display
     *        Valid options are:
     *        - context context The context.
     *        - contextid int The context id.
     *        - multiple bool Whether or not the field accepts more than one values.
     *        - onlyvisible bool Whether or not only visible framework can be listed.
     */
    public function __construct($elementName = null, $elementLabel = null, $options = array()) {

        $contextid = null;
        if (!empty($options['contextid'])) {
            $contextid = $options['contextid'];
        } else if (!empty($options['context'])) {
            $contextid = $options['context']->id;
        } else {
            $context = \context_system::instance();
            $contextid = $context->id;
        }

        $this->onlyvisible = !empty($options['onlyvisible']);

        $validattributes = array(
            'ajax' => 'block_lsuxe/destcourse_source',
            'data-contextid' => $contextid,
            'data-onlyvisible' => $this->onlyvisible ? '1' : '0',
        );
        if (!empty($options['multiple'])) {
            $validattributes['multiple'] = 'multiple';
        }

        parent::__construct($elementName, $elementLabel, array(), $validattributes);
    }

    /**
     * Set the value of this element.
     *
     * @param  string|array $value The value to set.
     * @return boolean
     */
    public function setValue($value) {
        global $DB;

        $values = (array) $value;
        $ids = array();

        foreach ($values as $onevalue) {
            if (!empty($onevalue) && (!$this->optionExists($onevalue)) &&
                    ($onevalue !== '_qf__force_multiselect_submission')) {
                array_push($ids, $onevalue);
            }
        }

        if (empty($ids)) {
            return $this->setSelected(array());
        }
    }
}
