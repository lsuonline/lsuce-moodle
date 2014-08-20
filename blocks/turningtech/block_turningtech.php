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
 * A block which displays turningtech links
 *
 * @package    block_turningtech
 * @copyright  Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/mod/turningtech/lib.php');
/**
 * Class for turningtech block
 * @copyright  Turning Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class block_turningtech extends block_base {
    // Maintain reference to integration service.
    /**
     * @var unknown_type
     */
    private $service;
    /**
     * set values for the block
     * 
     * @return unknown_type
     */
    public function init() {
        $this->title = get_string('blocktitle', 'block_turningtech');
        $this->service = new TurningTechIntegrationServiceProvider();
        if (!during_initial_install()) { 
		TurningTechTurningHelper::updateuserdevicemappings(); 
		} 
    }
    
    public function cron(){
        global $DB; // Global database object
        require $CFG->dirroot.'/mod/turningtech/lib/IntegrationServiceProvider.php';

        // Get the instances of the block
        $instances = $DB->get_records( 'block_instance', array('blockid'=>'turningtech') );
        $result = array('success'=>0, 'failures'=>0);
        
        // Iterate over the instances
        foreach ($instances as $instance) {

            // Recreate block object
            $block = block_instance('turningtech', $instance);

            //get the course object 
            $context = $block->parentcontextid->get_course_context(false);
            $course = get_course($context->instanceid);
            
            //get the enrollment of the course
            $enrolleduserids = get_enrolled_users('', '', 0, 'u.id');
            $keypadidfield = $DB->get_field('user_info_field', 'id', array('shortname'=>'user_keypadid'));
            
            //get keypadids
            $sql = sprintf('SELECT userid,data FROM {userinfo_data} WHERE userid IN (%s) AND fieldid = %d', $enrolleduserids, $keypadidfield);
            $userdeviceids = $DB->get_records_sql($sql);
            
            foreach($userdeviceids as $u){
                
                $data = new stdClass();
                $data->userid = $u->userid;
                $data->allcourses = true;
                $data->deviceid = $u->data;
                
                if (strlen($data->deviceid)== '8') {
                    $data->typeid = 2;
                }else{
                    $data->typeid = 1; //not sure exactly what typeid means
                }
                $map = TurningTechDeviceMap::generateFromForm($data);
                
                if($map->save()){
                    $result['success']++;
                }else{
                    $result['failures']++;
                }
            }

        }   
        return true;
    }
    /**
     * (non-PHPdoc)
     * 
     * @see docroot/blocks/block_base#specialization()
     */
    public function specialization() {
    }
    /**
     * (non-PHPdoc)
     * 
     * @see docroot/blocks/block_base#get_content()
     */
    public function get_content() {
        global $CFG, $USER, $COURSE;
        // Set up content.
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        // Verify the user is a student in the current course.
        $tooltip = get_string('managemydevicestool', 'block_turningtech');
        if (TurningTechMoodleHelper::isuserstudentincourse($USER, $COURSE)) {
            $devicemap = TurningTechTurningHelper::getdeviceidbycourseandstudent($COURSE, $USER);
            if ($devicemap) {
                $link = $devicemap->displayLink();
                $this->content->text = get_string('usingdeviceid', 'block_turningtech', $link);
            } else {
                $this->content->text = get_string('nodeviceforthiscourse', 'block_turningtech');
            }
            $this->content->footer .= "<div class='homelink'>
            <a href='{$CFG->wwwroot}/mod/turningtech/index.php?id={$COURSE->id}'
              title = '{$tooltip}'>" .
                                             get_string('managemydevices', 'block_turningtech') . "</a></div>\n";
        } else if (TurningTechMoodleHelper::isuserinstructorincourse($USER, $COURSE)) {
            $context = context_course::instance($COURSE->id);
            if (!has_capability('moodle/site:config', $context)) {
                $this->content->text = "<a href='{$CFG->wwwroot}/mod/turningtech/device_lookup.php?id={$COURSE->id}'>" .
                 get_string('searchturningtechcourse', 'block_turningtech') .
                                                 "</a>\n";
            } else {
                $this->content->text = "<a href='{$CFG->wwwroot}/mod/turningtech/search_device.php?id={$COURSE->id}'>" .
                 get_string('searchturningtechcourse', 'block_turningtech') .
                                                 "</a>\n";
            }
        }
        if (! empty($this->content->text)) {
            $this->content->text .= "<link rel='stylesheet' type='text/css' href=
            '{$CFG->wwwroot}/mod/turningtech/css/style.css'>";
        }
        return $this->content;
    }
}
