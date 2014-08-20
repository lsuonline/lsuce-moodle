<?php


/* ----------------------------------------------------------------------
 * 
 * 
 * 
 * 
 * show.php
 * 
 * Description:
 * This is the main display page used for calling each different reresentation
 * 
 * ----------------------------------------------------------------------
 */
require_once("../../../config.php");
global $CFG, $DB;
require_login();
require_once("$CFG->libdir/formslib.php");

require_once('renderrollsheet.php');
$cid = required_param('cid', PARAM_INT);
$gid = optional_param('gid', '', PARAM_INT);    

$context = context_course::instance($cid);

/** Navigation Bar **/
$PAGE->navbar->ignore_active();
$renderType = '';

$selectgroupsec = optional_param('selectgroupsec', '', PARAM_TEXT);  
 
if(isset($selectgroupsec)){
	if($selectgroupsec == 'all'){
		$renderType = 'all';
	}
	else if($selectgroupsec == 'group'){
		$renderType == 'group';
	} 
	if(is_numeric($selectgroupsec)) {
		$renderType = 'group';
	}
} else {
	$renderType = 'all';
}

if($renderType == 'all' || $renderType == ''){
	$courseName = $DB->get_record('course', array('id'=>$cid), 'shortname', $strictness=IGNORE_MISSING); 
	$PAGE->navbar->add($courseName->shortname, new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $cid));
	$PAGE->navbar->add(get_string('showall', 'block_rollsheet'));
	
}
else if($renderType == 'group'){
	$courseName = $DB->get_record('course', array('id'=>$cid), 'shortname', $strictness=IGNORE_MISSING); 
	$PAGE->navbar->add($courseName->shortname, new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $cid));
	$PAGE->navbar->add(get_string('showbygroup', 'block_rollsheet'));
}

$PAGE->set_url('/blocks/rollsheet/genlist/show.php');
$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'block_rollsheet'));
$PAGE->set_title(get_string('pluginname', 'block_rollsheet'));

echo $OUTPUT->header();
if (has_capability('block/rollsheet:viewblock', $context)) {
echo buildMenu($cid);
}

$logoEnabled = get_config('block_rollsheet', 'customlogoenabled');

if($logoEnabled){
	printHeaderLogo();
}

// Render the page
$selectgroupsec = optional_param('selectgroupsec', '', PARAM_TEXT);   
if (has_capability('block/rollsheet:viewblock', $context)) {
echo renderRollsheet();
}

class rollsheet_form extends moodleform {
 
	function definition() {
    global $CFG;
    global $USER, $DB;
    $mform =& $this->_form; // Don't forget the underscore! 
	}
}

/*
 * 
 * Create the HTML output for the list on the right
 * hand side of the showrollsheet.php page
 * 
 * */
function buildMenu($cid){	
	global $DB, $CFG, $renderType;
	$orderBy = '';
	$orderBy = optional_param('orderby', '', PARAM_TEXT);

	$outputHTML = '<div class = "floatright"><form action="'.$CFG->wwwroot. '/blocks/rollsheet/genlist/show.php?cid='.$cid.'" method="post">
				 Order By: <select name="orderby" id="orderby">
								<option value="lastname">'.get_string('lastname', 'block_rollsheet').'</option>
								<option value="firstname">' .get_string('firstname', 'block_rollsheet').'</option>
						  </select>
						  
				 Filter: <select id="selectgroupsec" name="selectgroupsec">
				 	<option value="all">'.get_string('showall', 'block_rollsheet').'</option>
				 '. buildGroups($cid).'	
				 </select>
				 <input type="submit" value="'.get_string('update', 'block_rollsheet').'"></input>
				</form>
				
				<span class = "floatright">
				
				<form action="../print/page.php" target="_blank">
   				<input type="hidden" name="cid" value="'.$cid.'">
				<input type="hidden" name="rendertype" value="'.$renderType.'">
				
				';
				
				// If a group was selected
				$selectgroupsec = optional_param('selectgroupsec', '', PARAM_TEXT); 
				if(isset($selectgroupsec)){
 					$outputHTML .= '<input type="hidden" name="selectgroupsec" value="'.$selectgroupsec.'">';
				}
				$outputHTML .= '
				<input type="hidden" name="orderby" value="'.$orderBy.'">
					
				
   				<input type="submit" value="'.get_string('printbutton', 'block_rollsheet').'">
				</span>
				</form>
			    </div>
			</div>
				';
	
	return $outputHTML;
}

/*
 * Build up the dropdown menu items with groups that are associated
 * to the currently open course.
 * 
 */
function buildGroups($cid){
        global $DB;
        $warnings = array();
        $groups       = array();
        $context = context_course::instance($cid);
	$buildHTML = '';

        if (!has_capability('moodle/site:accessallgroups', $context)) {
            $groupids = groups_get_user_groups($cid);
            $groupids = $groupids[0]; // ignore groupings
            $groupids = implode(",", $groupids);
            $select = "id IN ($groupids)";
            $groups = $DB->get_records_select('groups',$select);
        } else {
            $groups = $DB->get_records('groups',array('courseid'=>$cid));
        }

	foreach($groups as $group){
		$groupId = $group->id;
		$buildHTML.= '<option value="'.$groupId.'">'. $group->name.'</option>';
	}
	return $buildHTML;	
}

$mform = new rollsheet_form();
$mform->focus();
$mform->display();		

$selectgroupsec = optional_param('selectgroupsec', '', PARAM_TEXT); 
	if(isset($selectgroupsec)){
            $selectedItem = $selectgroupsec;
	    echo '<script>
			document.getElementById("selectgroupsec").value = '.$selectedItem.';
	    </script>';
	 }

 $orderBy = optional_param('orderby', '', PARAM_TEXT);
	if(isset($orderBy)){
 	$orderItem = $orderBy;
 		echo '<script>
 				document.getElementById("orderby").value = "'.$orderItem.'"
 			  </script>';
 			  
 			  if($orderItem == ""){
 			  	echo '<script>
 				document.getElementById("orderby").value = "lastname";
 			  </script>';
 				
 			  }
 	}




        echo $OUTPUT->footer();

