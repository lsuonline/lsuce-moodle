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
 * Security-related classes.
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class ballotsecurity {

    public $voter,$preview,$election;

    public function __construct($voter,$preview,$election){
        $this->voter = $voter;
        $this->preview = $preview;
        $this->election = $election;
    }
    /**
     * If the polls aren't open, allow only voters with doanything status
     * to use this form (including especially the ballot editing features).
     */
    public function pollsclosed(){
        return !$this->voter->is_privileged_user && !$this->election->polls_are_open();
    }

    /**
     * If a voter doesn't have at least part-time enrollment, deny access
     * unless the voter has doanything status.
     */
    public function notevenparttime(){
        return !$this->voter->is_privileged_user && !$this->voter->at_least_parttime();
    }

    /**
     * Only allow voters with doanything status to use the preview form.
     */
    public function nopreviewpermission() {
        return !$this->voter->is_privileged_user && $this->preview;
    }

    /**
     * Don't allow a second vote.
     */
    public function alreadyvoted(){
        return $this->voter->already_voted($this->election) && !$this->voter->is_privileged_user();
    }

    public function missingmeta(){
        return !$this->voter->is_privileged_user && $this->voter->is_missing_metadata();
    }

    public function ineligible(){
        return !$this->voter->is_privileged_user && !$this->voter->eligible($this->election);
    }

    public static function allchecks($voter,$preview,$election){
        $security = new self($voter,$preview,$election);

        if($security->pollsclosed()){
            print_error(sge::_str('err_pollsclosed'));
        }

        if($security->notevenparttime()){
            print_error(sge::_str('err_notevenparttime'));
        }

        if($security->nopreviewpermission()){
            print_error(sge::_str('err_nopreviewpermission'));
        }

        if($security->alreadyvoted()){
            print_error(sge::_str('err_alreadyvoted'));
        }

        if($security->missingmeta()){
            print_error(sge::_str('err_missingmeta', $voter->is_missing_metadata()));
        }

        if($security->ineligible()){
            print_error(sge::_str('err_ineligible'));
        }

    }
}