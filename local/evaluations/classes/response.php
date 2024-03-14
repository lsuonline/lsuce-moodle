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
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Dustin Durrand http://oohoo.biz
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * This class handles everything related to creating new responses.
 */
class response {
    private $id; //The database id of the response.
    private $question_id; //The id of the question that this response is responding to.
    private $response; //The value'd response if it exists. (4_excellent, 5_superior, 4_rate_wc)
    private $user_id;  //The user who made this response.
    private $comment; //The text response to a question if it exists. (comment, years)
    
    function __construct($id = 0, $question_id = null, $response= null, $user_id = null, $comment = '') {
        
        $this->id = $id;
        
        if ($id > 0) { // DB LOAD
            $this->db_load();
        } else { //PARAM LOAD
            $this->question_id = $question_id;
            $this->response = $response;
            $this->user_id = $user_id;
            $this->comment = $comment;
        }
    }
    
    /**
     * Save new responses to the database. If the response has an id other than
     * 0 then this method will fail because we should never allow a response
     * to be edited.
     * 
     * @global moodle_database $DB
     */
    function save(){
        global $DB;
        
        if ($this->id != 0) {
            return; //WE SHOULD NEVER ALLOW A RESPONSE TO BE EDITED
        }
        
        //Save the new response to the database.
        $response = new stdClass();
        $response->question_id = $this->question_id;
        $response->response = $this->response;
        $response->user_id = $this->user_id;
        $response->question_comment = $this->comment;
        
        $DB->insert_record('evaluation_response', $response);
        
    }
    
    /**
     * Load contents of this response from the database.
     * 
     * @global moodle_database $DB
     */
    function db_load() {
        global $DB;
        
        //explicitly not loading user_id
        //SHOULD never be attached to a response except on the inital save(anonymous evaluations are assumed)
        $response = get_record('evaluation_response', array('id'=>$this->id), 'id, question_id, question_comment');
        
        if (!$response) {
            print_error(get_string('invalid_responseid', 'local_evaluations'));
        }
        
        $this->question_id = $response->question_id;
        $this->response = $response->response;
        $this->user_id = $response->user_id;
        $this->comment = $response->comment;
    }

}
