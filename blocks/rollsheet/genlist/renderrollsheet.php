<?php
global $CFG, $DB;
require_login();

/*
 * 
 * Retrieve and print the logo for the top of the
 * sign in sheet.
 * 
 * */
function printHeaderLogo(){
	global $DB;
 	$imageURL =  $DB->get_field('block_rollsheet', 'field_value', array('id'=>1), $strictness=IGNORE_MISSING);
	echo '<img src="'.$imageURL.'"/><br><div class="printHeaderLogo"></div>';
}

/*
 * 
 * 
 *
 * 
 * */ 
function renderRollsheet(){
	global $DB, $cid, $CFG, $OUTPUT;
        $pageCounter = 0;
        $usersPerTable = get_config('block_rollsheet', 'studentsPerPage' );
	$cid = required_param('cid', PARAM_INT);
	$selectedGroupId = optional_param('selectgroupsec', '', PARAM_INT);
	$appendOrder = '';
	$orderBy = optional_param('orderby', '', PARAM_TEXT);		
		if($orderBy == 'byid'){
			$appendOrder = ' order by u.id';
		}
		else if($orderBy == 'firstname'){
			$appendOrder = ' order by u.firstname, u.lastname';
		}
		else if($orderBy == 'lastname'){
			$appendOrder = ' order by u.lastname, u.firstname';
		}
		 else {
			$appendOrder = ' order by u.lastname, u.firstname, u.idnumber';
		}

	// Check if we need to include a custom field
	$groupName = $DB->get_record('groups', array('id'=>$selectedGroupId), $fields='*', $strictness=IGNORE_MISSING); 
        $groupids = groups_get_user_groups($cid);
        $groupids = $groupids[0]; // ignore groupings
        $groupids = implode(",", $groupids);
        $context = context_course::instance($cid);
        $mainuserfields = user_picture::fields('u', array('id'), 'userid');
        $student = "'student'";
        $ctxlevel = $context->contextlevel;

        if($groupName) {
                $query = "SELECT u.id, u.idnumber, $mainuserfields
                                FROM {course} c
                                INNER JOIN {context} cx ON c.id = cx.instanceid AND cx.contextlevel = $ctxlevel
                                INNER JOIN {role_assignments} ra ON cx.id = ra.contextid
                                INNER JOIN {role} r ON ra.roleid = r.id
                                INNER JOIN {user} u ON ra.userid = u.id
                                INNER JOIN {groups_members} gm ON u.id = gm.userid
                                INNER JOIN {groups} g ON gm.groupid = g.id AND c.id = g.courseid
                                WHERE r.shortname = $student AND gm.groupid = ?" . $appendOrder;
                $result = $DB->get_records_sql($query,array($selectedGroupId));
        } else if (!has_capability('moodle/site:accessallgroups', $context)) {
                $query = "SELECT CONCAT(u.id, g.id) AS groupuserid, u.id, u.idnumber, $mainuserfields
                                FROM {course} c
                                INNER JOIN {context} cx ON c.id = cx.instanceid AND cx.contextlevel = $ctxlevel
                                INNER JOIN {role_assignments} ra ON cx.id = ra.contextid
                                INNER JOIN {role} r ON ra.roleid = r.id
                                INNER JOIN {user} u ON ra.userid = u.id
                                INNER JOIN {groups_members} gm ON u.id = gm.userid
                                INNER JOIN {groups} g ON gm.groupid = g.id AND c.id = g.courseid
                                WHERE r.shortname = $student AND gm.groupid IN ($groupids) " . $appendOrder;
                $result = $DB->get_records_sql($query, array($cid));
        } else {
                $query = "SELECT u.id, u.idnumber, $mainuserfields
                                FROM {course} c
                                INNER JOIN {context} cx ON c.id = cx.instanceid AND cx.contextlevel = $ctxlevel
                                INNER JOIN {role_assignments} ra ON cx.id = ra.contextid
                                INNER JOIN {role} r ON ra.roleid = r.id
                                INNER JOIN {user} u ON ra.userid = u.id
                                WHERE r.shortname = $student AND c.id = ?" . $appendOrder;
                $result = $DB->get_records_sql($query, array($cid));
	}

	$courseName = $DB->get_record('course', array('id'=>$cid), 'fullname', $strictness=IGNORE_MISSING); 

        $totalUsers = count($result);
        $usernumber = 0;
        while(!empty($result)){
            $pageCounter++;

            if($groupName) {
                $title = html_writer::div(html_writer::tag('p',get_string('signaturesheet', 'block_rollsheet') . ' &mdash; ' . $courseName->fullname . ': Section ' . substr($groupName->name, -3) . '&nbsp;&nbsp;&nbsp;&nbsp;Page: ' . $pageCounter . '&nbsp;&nbsp;&nbsp;&nbsp;Room # _____'), NULL, array('class' => 'rolltitle center'));
            } else {
                $title = html_writer::div(html_writer::tag('p',get_string('signaturesheet', 'block_rollsheet') . ' &mdash; ' . $courseName->fullname . '&nbsp;&nbsp;&nbsp;&nbsp;Page: ' . $pageCounter . '&nbsp;&nbsp;&nbsp;&nbsp;Room # _____'), NULL, array('class' => 'rolltitle center'));
            }

	    $disclaimer = html_writer::tag('p',get_string('absences', 'block_rollsheet'), array('class' => 'absences'));
	    $disclaimer .= html_writer::tag('p',get_string('disclaimer', 'block_rollsheet'), array('class' => 'center disclaimer'));
	
            $k = 1;
	    $table = new html_table();
	    $table->attributes['class'] = 'roll';

            $addTextField = get_config('block_rollsheet', 'includecustomtextfield');
            $addIdField = get_config('block_rollsheet', 'includeidfield');
            $numExtraFields = get_config('block_rollsheet', 'numExtraFields');
            $emptyField = '';

            $userdata = array();
            
		$j = 0;

            $userdatas = array();

	    foreach($result as $face){
                $usernumber++;
	        $j++;	
		$userdata = array($usernumber);
		$userdata[] = ($face->firstname . ' ' . $face->lastname);

                if($addIdField){
                    $userdata[2] = $face->idnumber;
                }

                if($addTextField){
                    $userdata[3] = ' ';
                }

		for ($i = 0; $i < $numExtraFields; $i++) {
                    $userdata[] = $emptyField;
		}

                array_shift($result);

	        $userdatas[$j] = $userdata;

                if ($k++ == $usersPerTable) { 
		    break;
		}


            }

	$table->head = array(null);
	$table->head[1] = get_string('fullName', 'block_rollsheet');

        // Id number field
        if($addIdField){
                $table->head[2] = get_string('idnumber', 'block_rollsheet');
        }

        // Additional custom text field
        if($addTextField){
                $table->head[3] = get_config('block_rollsheet', 'customtext');
        }

        for ($i = 0; $i < $numExtraFields; $i++) {
           $table->head[] = get_string('date', 'block_rollsheet');
        }

	$table->data = $userdatas;

        echo $title;

	echo html_writer::table($table);

        echo $disclaimer;
    }
}
