<?php
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . "/gradelib.php");

class local_aairsgradebook_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function gradebookservice_parameters() {
        return new external_function_parameters(
                array('source' => new external_value(PARAM_TEXT, 'string $source source of the grade such as "mod/assignment"', VALUE_DEFAULT, 'mod/assignment')
				,'courseid' => new external_value(PARAM_TEXT, 'int $courseid id of course', VALUE_DEFAULT, 'courseid')
				,'itemtype' => new external_value(PARAM_TEXT, 'string $itemtype type of grade item - mod, block', VALUE_DEFAULT, 'mod')
				,'itemmodule' => new external_value(PARAM_TEXT, 'string $itemmodule more specific then $itemtype - assignment, forum, etc.; maybe NULL for some item types', VALUE_DEFAULT, 'assignment')
				,'iteminstance' => new external_value(PARAM_TEXT, 'ID of the item module', VALUE_DEFAULT, '0')
				,'itemnumber' => new external_value(PARAM_TEXT, 'int $itemnumber most probably 0, modules can use other numbers when having more than one grades for each user', VALUE_DEFAULT, '0')
				,'grades' => new external_value(PARAM_TEXT, 'mixed $grades grade (object, array) or several grades (arrays of arrays or objects), NULL if updating grade_item definition only', VALUE_DEFAULT, 'NULL')
				,'itemdetails' => new external_value(PARAM_TEXT, 'mixed $itemdetails object or array describing the grading item, NULL if no change', VALUE_DEFAULT, 'NULL')
				)
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function gradebookservice($source = 'mod/assignment', $courseid ='courseid', $itemtype = 'mod', $itemmodule = 'assignment', $iteminstance = '0', $itemnumber = '0', $grades = NULL, $itemdetails = NULL) {
        global $USER;
		global $CFG;
		global $DB;

        //Parameter validation
        //REQUIRED
		
        $params = self::validate_parameters(self::gradebookservice_parameters(),
                array('source' => $source
					,'courseid' => $courseid
				));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }
		
		if ($itemdetails != "null" && $itemdetails != null)
		{
			$itemdetails = json_decode(urldecode($itemdetails), true);
			
			//enable use of ID or IDnumber
			if (!$DB->record_exists('course', array('id'=>$courseid)))
			{
			
			//map to numerical courseID
			$course = $DB->get_record('course', array('idnumber'=>$courseid));
			$courseid = $course->id;
			$itemdetails['courseid'] = $course->id;
			
			}else
			{
				//if the sent ID is a courseid, use it.
				$course->id = $courseid;

			}
			
			if ($itemdetails['categoryid'] != null && $itemdetails['categoryid'] != '' && $itemdetails['categoryid'] != 'null')
			{

				//convert category into something moodle can use
				$category = $DB->get_record_sql('SELECT id FROM {grade_categories} WHERE fullname = ? and courseid = ?', array($itemdetails['categoryid'], $courseid));
				
				//if the category exists
				if ($category->id != null && $category->id != '')
				{
					//use the category ID we retrieved
					$itemdetails['categoryid'] = $category->id;

				}else
				{
					$itemdetails['categoryid'] = null;
				}
			}
			
		}else
		{
		$itemdetails = null;
		}
		
		
		
		if($grades != "null" && $grades != null)
		{
			$grades = json_decode(urldecode($grades), true);
			
			//translate userID to numberical userID
			$user = $DB->get_record('user', array('username'=>$grades['userid']));
			$grades['userid'] = $user->id;	

			//get real gradeItemId
			
		}else
		{
		$grades = null;
		}
		
		//return $itemdetails['categoryid'];
		
		//run the update grade function which creates / updates the grade
		$result = grade_update($source,$courseid,$itemtype,$itemmodule,$iteminstance,$itemnumber,$grades,$itemdetails);
		
		//get the ID of the grade we just created
		$grade_item = $DB->get_record_sql('SELECT id FROM {grade_items} WHERE idnumber = ? and courseid = ?', array($itemdetails['idnumber'], $courseid));
		
		if ($grade_item->id != null && $grade_item->id != '' && $itemdetails['categoryid'] != '' && $itemdetails['categoryid'] != null && $itemdetails['categoryid'] != 'null')
		{
			//return $grade_item->id;
			//change the category of the Grade we just updated/created
			$record = new stdClass();
			$record->id         = $grade_item->id;
			$record->categoryid = $itemdetails['categoryid'];
			$DB->update_record(grade_items, $record);

		}
		
		return $result;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function gradebookservice_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }


}
