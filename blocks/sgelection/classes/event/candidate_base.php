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
 * The candidate_* event base class.
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State Unviersity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_sgelection\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The candidate_deleted event class.
 * @since     Moodle 2.7
 * @copyright 2014 YOUR NAME
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class candidate_base extends base {
    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'block_sgelection_candidate';
    }

    public function get_description() {
        return \sge::_str('candidatelogmessage', $this->data);
    }

    public function get_url() {
        global $DB;
        $eid = $DB->get_field($this->objecttable, 'election_id', array('id'=>$this->objectid));
        return new \moodle_url('/blocks/sgelection/candidates.php', array('id'=>$this->objectid, 'election_id'=>$eid));
    }
}