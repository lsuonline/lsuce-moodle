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
 * Block displaying information about whether or not there is an etextbook
 * for the course
 *
 * @package    block_etextbook
 * @copyright  2016 Lousiana State University - David Elliott, Robert Russo, Chad Mazilly
 * @author     David Elliott <delliott@lsu.edu> - Along with LSU Moodle Development Team (Robert Russo, Chad Mazily) and LSU Libraries Staff (Emily Frank, David Comeaux, and Jason Peak)
 * @tutorial   https://grok.lsu.edu/Browse.aspx?searchString=E-textbooks&pageSize=10&searchDomain=All&parentCategoryId=0 - Stefanie Howell <showel8@lsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @todo Make block faster
 */

require_once($CFG->dirroot . '/blocks/etextbook/lib.php');

/**
 * Block displaying information about whether or not there is an etextbook
 * for the course
 *
 * @package    block_etextbook
 */

class block_etextbook extends block_base {
    
    /**
     * Function creates block
     * @return void
     */
    public function init() {
        $this->title = get_string('etextbook', 'block_etextbook');
    }
     /**
     * Function gets the xml from the library and populates the blocks content
     * @return string $this->content
     * @access public
     */
    public function get_content() {
        GLOBAL $PAGE, $COURSE, $CFG, $DB;
        /** check if page is a course */
        if($COURSE->id === "1"){
            return $this->content;
        }
            /** Get content from library. */
            $books = simplexml_load_file('http://www.lib.lsu.edu/ebooks/xml2');
            $sql = get_course_info_sql();

            $records = $DB->get_records_sql($sql, array('courseid' => $COURSE->id)); 
            $department = current($records)->department;
            $coursenumber = current($records)->cou_number;
            $sectionnumber = current($records)->sec_number;
            $courseinstructor = current($records)->lastname;
            /** @var $foundabook - Boolean Flag to see if we got a result for this course
             * @todo Bad coding style? 
             */
            $foundabook = false; 

            /**
            * Create an array to hold books in case there is more than one
            * @var $arrayof
            */
            $arrayofbooks = array();

            /**
             * Take each book and see if the books course number etc, matches this course @todo - also bad coding style try to do this more succinctly 
             */
            foreach ($books as $book) {
                /** @var \sections[] An array of section numbers(strings). */
                $sections = explode(',', (string)$book->field_ebook_section);
                foreach($sections as $section){
                    $section = str_pad($section, 3, '0', STR_PAD_LEFT);
                }  
                if ( $department == (string)$book->field_ebook_subject && $coursenumber == substr((string) $book->field_course_number, 0, 4) && $sectionnumber == $section && $courseinstructor == $book->Instructor) {
                    $bookstrings = convert_ebook_xml_to_object($book);
                    /** I made this into a very neat {$a->field_names} get_string situation, but I had to
                     * escape from double quotes, use single quotes to use the double quotes,
                     * but then those double quotes broke the {$a} object. 
                     * @todo figure out how to do this in a more clean way. 
                    */
                    $bookstrings->complete = '<a href = "' . $bookstrings->field_ebook_url . '">'. $bookstrings->field_ebook_title ;
                    $bookstrings->complete = $bookstrings->complete . '<img class = "img-rounded img-responsive etextimg" src = "' . $bookstrings->field_ebook_image . '"></a>';
                    $bookstrings->complete = $bookstrings->complete . get_string('linktolsulibraries', 'block_etextbook');

                    array_push($arrayofbooks, $bookstrings->complete);                
                    $foundabook = true;
                }
            }
            if ( $foundabook ) {
                $this->content = new stdClass;
                $this->content->text = '<div class = "etextdiv">';
                foreach ($arrayofbooks as $booktodisplay){
                    $this->content->text = $this->content->text . $booktodisplay;
                    //$this->content->text = $this->content->text . array_pop($arrayofbookcovers);
                }
                $this->content->text = $this->content->text . "</div>";
            }
        }


}
