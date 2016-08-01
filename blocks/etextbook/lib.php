<?php

/**
 * The etextbook block helper functions and callbacks
 *
 * @package   block_etextbook
 * @copyright 2016 David Elliott <delliott@lsu.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @api 
 */

defined('MOODLE_INTERNAL') || die();
 /**
  * Takes the XML retrieved from the library web site and converts to
  * an object with field names that correspond to the XML field names.
  * Also takes the CDATA and turns it into a normal string. 
  * for example - 
  * <field_ebook_title>
  *     <![CDATA[
  *         Bringing the Reggio Approach to Your Early Years Practice
  *     ]]>
  * </field_ebook_title>
  * Becomes - 
  * $bookstrings->'field_ebook_title' = 'Bringing the Reggio Approach to Your Early Years Practice';
  * 
  * LSU Library XML currently located at http://www.lib.lsu.edu/ebooks/xml2
  * and Etextbook search site for students can be found at http://www.lib.lsu.edu/ebooks
  * 
  * @param object $book With a *description* of this argument, these may also
  *    span multiple lines.
  *
  * @return void
  */
    function convert_ebook_xml_to_object($book){
            $bookstrings = new stdClass();
            foreach($book as $key => $field){
                $bookstrings->$key = (string)($field);        
            }
            return $bookstrings;
    }
    /**
     * Function checks what kind of DB Moodle is using and gets the course number, department and section number 
     * @return string sql query
     * @access idk
     */
    
    function get_course_info_sql(){
        GLOBAL $CFG, $DB;
        if($CFG->dbtype == 'mysqli'){
            //MySQL
            return "SELECT CONCAT(uc.department, ' ', uc.cou_number, ' ', us.sec_number) AS course, u.lastname, uc.department, uc.cou_number, us.sec_number
                    FROM mdl_course c
                    INNER JOIN {enrol_ues_sections} us ON us.idnumber = c.idnumber
                    INNER JOIN {enrol_ues_teachers} t ON us.id = t.sectionid
                    INNER JOIN {user} u ON u.id = t.userid
                    INNER JOIN {enrol_ues_courses} uc ON us.courseid = uc.id
                    WHERE t.primary_flag = 1 AND c.id = :courseid";
        }
    }
