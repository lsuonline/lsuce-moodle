<?php
global $CFG, $DB;
require_login();
/*
 * 
 * 
 *
 * 
 * */ 
function renderPicSheet(){
	global $DB, $cid, $CFG, $OUTPUT;
        $pageCounter = 1;
        $usersPerPage = get_config('block_rollsheet', 'usersPerPage');
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

        $parentDivOpen = html_writer::start_tag('div', array('class' => 'placeholder'));
        $parentDivClose = html_writer::end_tag('div');
        $rowDivOpen = html_writer::start_tag('div', array('class' => 'ROWplaceholder'));

        $disclaimer = html_writer::tag('p',get_string('pdisclaimer', 'block_rollsheet'), array('class' => 'center disclaimer'));

        while(!empty($result)){

            if($groupName) {
                $title = html_writer::div(html_writer::tag('p',$courseName->fullname . ' ' . substr($groupName->name, -3) . ' &mdash; ' . get_string('picturesheet', 'block_rollsheet') . ': page ' . $pageCounter), NULL, array('class' => 'rolltitle center'));
	    } else {
                $title = html_writer::div(html_writer::tag('p',$courseName->fullname . ' &mdash; ' . get_string('picturesheet', 'block_rollsheet') . ': page ' . $pageCounter), NULL, array('class' => 'rolltitle center'));
	    }

            $pageCounter++;
            $userPicture ='';
            $j = 0;

	    foreach($result as $face){
		$j++;
		$userPicture .= html_writer::div($OUTPUT->user_picture($face, array('courseid' => $cid, 'size' => 100, 'class' => 'welcome_userpicture')) . html_writer::tag('p',$face->firstname . ' ' . $face->lastname, array('class' => 'center')), NULL, array('class' => 'floatleft'));
		array_shift($result);
		if ($j == $usersPerPage) { break; }
            }

            echo $title;
            echo $userPicture;
            echo $disclaimer;
            }
}
