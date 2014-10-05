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
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class ballot {

    public $election;
    public $candidates;
    public $resolutions;
    public $voter;
    private $ispreview;

    /**
     * Uses user attribute to limit ballot elements.
     * @param type $user
     * return ballot_item[]
     */
//    public function filter_by_user($user){

//    }

    public function sort_by(){

    }

    public function get_candidates(){
        if(!$this->voter->college()){
            throw new Exception("User college is not set; required to vote.");
        }

        $candidates = candidate::get_full_candidates($this->election, $this->voter);
    }

    public function get_resolutions(){

    }

    public function record_votes(){
        if($this->preview){
            return;
        }
    }

    private function mark_user_as_voted(){

    }

    private function record_voter_meta(){

    }
}