<?php
/*
    Grade Submit Step 4 - Grade Submission to Banner
    This file is meant to be included from index.php.
*/

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/grader/lib.php';

require_once('grade_report_banner.php');
include_once('SimpleXMLExtended.php');


// Overall Timer to process this page.
$process_timer_start = microtime(true);

$url_post = $CFG->gradeexport_webservice_url;
$url_get = $CFG->gradeexport_webservice_url_get;
$url_get_dangler = $CFG->gradeexport_webservice_url_get_dangler;

$username = $CFG->gradeexport_webservice_username;
$password = $CFG->gradeexport_webservice_password;

$bugging = false;
if (debugging()) {
    $bugging = true;
}
/*
    $username="FinalGradesUser";    //likewise these in settings...
    $password="m00dlebymenon";
*/

$datasource = $CFG->gradeexport_datasource;
$institution = $CFG->gradeexport_institution;

function grade_submit_sendEmail($type = '', $errors = array())
{
    global $CFG, $course, $USER;
    $bugging = false;

    if (debugging()) {
        $bugging = true;
        error_log("\n\n");
        error_log("\n---->Grade Submission: grade_submit_sendEmail ---------->>>> START");
    }

    if (!$CFG->gradeexport_notification_emails) {
        if ($bugging) {
            error_log("\n\n");
            error_log("\n---->Grade Submission: The CFG gradeexport_notification_emails does not exist or is not set.");
        }
        return;
    }

    if ($type == 'success') {
        $subject = get_string('success_email_subject', 'gradeexport_submit');
        $messagetext = get_string('success_email', 'gradeexport_submit');
        $msg = implode("\r\n", $errors);
        $messagetext .= "\r\n\r\n". $msg;
    } elseif ($type == 'partialsuccess') {
        $subject = get_string('partialsuccess_email_subject', 'gradeexport_submit');
        $messagetext = get_string('partialsuccess_email', 'gradeexport_submit');
        $msg = implode("\r\n", $errors);
        $messagetext .= "\r\n\r\n". $msg;
    } elseif ($type == 'failure') {
        $subject = get_string('failure_email_subject', 'gradeexport_submit');
        $messagetext = get_string('failure_email', 'gradeexport_submit');
        $msg = implode("\r\n", $errors);
        $messagetext .= "\r\n\r\n". $msg;
        $messagetext .= "\r\n\r\n". get_string('contact_info_email', 'gradeexport_submit');
    }
    
    
    $from = $CFG->gradeexport_from_email;
    
    $subject .= ": ".$course->shortname;
    
    $nameString = $USER->firstname ." " .$USER->lastname . " (".$USER->email.")";
    $messagetext = "Course: " . $course->shortname . "\r\nSubmitter: ".$nameString. "\r\n\r\n" . $messagetext;
    
    //Now send the email. Have to use the PHP way since we aren't mailing a Moodle user.
    $to = $CFG->gradeexport_notification_emails;
    $headers = "From: " . $from . " <.".$from."> \r\n" .    "X-Mailer: php";
    if ($bugging) {
        error_log("\n");
        error_log("\n---->Grade Submission: Email has been prepped and now going to send with this info:");
        error_log("\nTo: ". $to);
        error_log("\nSubject: ". $subject);
        error_log("\nMessage text: ". $messagetext);
        error_log("\nHeaders: ". $headers);
        error_log("\n\n");
    }
    mail($to, $subject, $messagetext, $headers);
}


function checkAdminUser()
{

    $context = context_system::instance();
    $admin_access = has_capability('moodle/site:config', $context);
    if ($admin_access) {
        return true;
    } else {
        return false;
    }
}

$courseid = required_param('id', PARAM_INT); // course id

$gpr = new grade_plugin_return(array(
    'type' => 'report',
    'plugin' => 'grader',
    'courseid' => $courseid,
    'page' => 0)
);

$context = context_course::instance($id);

$report = new grade_report_banner($courseid, $gpr, $context, 0, 0);

$report->set_pref('studentsperpage', 100000);    //set high to ensure all grades are included.

// final grades MUST be loaded after the processing
$report->load_users();
$numusers = $report->get_numusers();

$report->load_final_grades();
$studentsperpage = $report->get_pref('studentsperpage');
$reporthtml = $report->get_grade_table();
$exportGrades = array();    //array of letter grades we'll use later to export the grades. indexed by idnumber (sourcedid).
$userListBySource = array(); //array of usernames + email indexed by sourcedid to help with displaying messages
$userList = $report->{'users'};

foreach ($report->grades as $usergrade) {
    $usergrade = array_values($usergrade); //ignore the column index: we know we only have the proper final grade column

    $item = $usergrade[0]->{'grade_item'};
    $gradeval = $usergrade[0]->{'finalgrade'};
    $letterGrade = grade_format_gradevalue_letter($gradeval, $item);
    $userid = $usergrade[0]->{'userid'};
    $exportGrades[$userList[$userid]->idnumber] = $letterGrade;
    $userListBySource[$userList[$userid]->idnumber] = $report->users[$userid]->firstname ." ".
        $report->users[$userid]->lastname . " (".$report->users[$userid]->email.")";
    
}

echo "<h2>".get_string('grade_submit_title', 'gradeexport_submit')."</h2>";
// echo get_string('grade_submit_information', 'gradeexport_submit');

//Ok now we have to create XML and submit it to Banner.
//First figure out source data for the current logged in user
global $USER;

// error_log("\n\n --------------------- USER --------------------- \n");
// error_log(print_r($USER, 1));



if (checkAdminUser()) {
    
    // error_log("\n\n ---------------------THE USER IS AN ADMIN --------------------- \n");
    $da_instructors = $DB->get_records_sql(
        "SELECT c.id, c.shortname, u.id as userid, u.username, u.idnumber, u.firstname || ' ' || u.lastname AS name 
            FROM mdl_course c 
                LEFT OUTER JOIN mdl_context cx ON c.id = cx.instanceid 
                LEFT OUTER JOIN mdl_role_assignments ra ON cx.id = ra.contextid AND ra.roleid = '3' 
                LEFT OUTER JOIN mdl_user u ON ra.userid = u.id 
            WHERE cx.contextlevel = '50' AND c.id=?", array($courseid)
    );
    // it's possible that there is more than one instructor so let's just grab the first one back
    // error_log("\nWhat are the instructors: \n". print_r($da_instructors, 1));
    $da_instructors = array_values($da_instructors);
    // error_log("\n----------------------------\n");
    // error_log("\nList of instructors: \n\n". print_r($da_instructors, 1). "\n\n");
    // error_log("\n----------------------------\n");
    $requestorId = $da_instructors[0]->idnumber;
    // error_log("\nGoing to use this instructors (".$da_instructors[0]->username.") id: ". $requestorId. "\n");

    // now, do we have a special override password????........this is for testing.
    if (isset($_GET['over_pass_token'])) {
        $over_pass_token = $_GET['over_pass_token'];
    } else {
        $over_pass_token = '';
    }
    // error_log("\n\noverride pass is: ". $over_pass_token);

    // Add this to the URL to submit
    // &step=4&over_pass_token=m00dleMonSt3r_20_7_t33n

    // WE ARE IN ADMIN SECTION so this should be set.........otherwise ABORT
    if ($over_pass_token == "m00dleMonSt3r_20_7_t33n") {
        // we have successfully cleared the password
        error_log("\nToken accepted to push grades through, going to continue processing grades");            
    } else {
        echo get_string('error_teacher_noidnumber', 'gradeexport_submit');
        echo get_string('contact_info', 'gradeexport_submit');
        
        echo get_string('grades_not_submitted', 'gradeexport_submit');
            echo $OUTPUT->footer();
        exit;
    }

} else {

    $curUserIdNumber = $USER->idnumber; //this should equal their sourcedid value
    if (!$curUserIdNumber) {
        
        error_log("\n\n ---------------------ERROR - there is no user id number --------------------- \n");
        echo get_string('error_teacher_noidnumber', 'gradeexport_submit');
        echo get_string('contact_info', 'gradeexport_submit');
        
        echo get_string('grades_not_submitted', 'gradeexport_submit');
            echo $OUTPUT->footer();
        exit;
        
    } else {
        // error_log("\n\n YUPPERS, there is a USER->idnumber: ". $requestorId. "\n");
        $requestorId = $USER->idnumber;
    }
}

// error_log("\n\n");
// error_log("\nWhat is the requestor id: ". $requestorId. "\n\n");


// die();

/*
Sample of what the XML Wrapper should look like for Banner's API
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<map-type>
    <entries>
        <entry>
            <key>gradesXML</key>
            <value 
                
                xsi:type="xs:string" 
                xmlns:xs="http://www.w3.org/2001/XMLSchema"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                
                <![CDATA[
            <enterprise>
            ......
            </enterprise>]]>
            </value>
        </entry>
    </entries>
</map-type>
*/

// This is the Banner Wrapper
$xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8" standalone="yes"?><map-type></map-type>');

// $xml = new SimpleXMLExtended("<map-type></map-type>");
// $xml->addAttribute('encoding', 'UTF-8');
/* 
$xml = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', $xml);
*/
// Store all the User Grade Stuff in xml_chunk, this will be looped and data stored
// Then insert into the wrapper
$xml_chunk = new SimpleXMLElement("<enterprise></enterprise>");

/* <enterprise>
      <properties..../>
      <membership..../>
          <member...../>
*/

$banner_l1 = $xml->addChild("entries");
$banner_l2 = $banner_l1->addChild("entry");
$banner_l3 = $banner_l2->addChild("key", "gradesXML");
$banner_wrapper = $banner_l2->addChild("value");
$banner_wrapper->addAttribute("xsi:type", "xs:string", "http://www.w3.org/2001/XMLSchema-instance");
$banner_wrapper->addAttribute("xmlns:xmlns:xs", "http://www.w3.org/2001/XMLSchema");


$properties = $xml_chunk->addChild("properties");
$properties->addChild("datasource", $datasource);
$properties->addChild("datetime", date("Y-m-d"));
$requestor = $properties->addChild("extension")->addChild("luminisproperties")->addChild("requestor");
$sourcedid = $requestor->addChild("sourcedid");
$sourcedid->addChild("source", $institution);
$sourcedid->addChild("id", $requestorId);
$requestor->addChild('idtype', 1);

//Done the properties element. Now do the student grades.
$membership = $xml_chunk->addChild('membership');
//Add the course info first.
//first ensure its in the LMB table..
$courseIdNumber = $course->idnumber;

// Aug27th 2021 - We have switched from the enrol lmb to manual via Moodle API's
// Should  no longer use lmb tables
/*
$courseRec = $DB->get_record("enrol_lmb_courses", array(
    "sourcedid" => $courseIdNumber
));
*/
$courseRec = $DB->get_record("course", array(
    "idnumber" => $courseIdNumber
));
if (!$courseRec) {
    echo get_string('enrolnotincourse', 'gradeexport_submit');
    echo $OUTPUT->footer();
    exit;
}
$courseTerm = $courseRec->term;

$sourcedid = $membership->addChild('sourcedid');
$sourcedid->addChild('source', $institution);
$sourcedid->addChild('id', $courseIdNumber);

//Now student grades.
foreach (array_keys($exportGrades) as $userSourcedid) {
    $letterGrade = $exportGrades[$userSourcedid];
    
    //Ensure this user is in the lmb table.
    /*
    $userLMBRec = $DB->get_record("enrol_lmb_people", array(
        "sourcedid" => $userSourcedid
    ));
    */
    $userLMBRec = $DB->get_record("user", array(
        "idnumber" => $userSourcedid
    ));

    if (!$userLMBRec) {
        echo get_string('studentnotincourse', 'gradeexport_submit');

        echo $OUTPUT->footer();
        exit;
    }
    $member = $membership->addChild('member');
    $sourcedid = $member->addChild('sourcedid');
    $sourcedid->addChild('source', $institution);
    $sourcedid->addChild('id', $userSourcedid);
    $member->addChild('idtype', 1);
    
    $role = $member->addChild('role');
    $role->addAttribute('roletype', '01');
    
    //Now get their LMB enrol record
    /*
    $enrolLMBRec = $DB->get_record("enrol_lmb_enrolments", array(
        "coursesourcedid" => $courseIdNumber,
        "personsourcedid" => $userSourcedid,
        "term" => $courseTerm
    ));


    coursesourcedid     20311.202102    mdl_course
    personsourcedid     43070           mdl_user    (XML ID nubmer which is idnumber in mdl_user)
    term                202102          mdl_course
    
    */
    /*
    $enrolLMBRec = $DB->get_record("user_enrolments", array(
        "enrolid" => $courseIdNumber,
        "userid" => $userSourcedid // this needs to be the moodle id
        // "term" => $courseTerm
    ));
    
    if (!$enrolLMBRec) {

        echo get_string('enrolnotinlmb', 'gradeexport_submit');
        echo $OUTPUT->footer();
        if ($bugging) {
            error_log("\n\n---------------------- ERROR -----------------------------\n");
            error_log("\nEnrollment not found: ". print_r($enrolLMBRec, 1). "\n");
            error_log("\nEnrollment not found sourcedid: ". $userSourcedid. "\n\n");
        }
        exit;
    }
    $status = $enrolLMBRec->status;
    */
    $status = 1;
    
    $role->addChild('status', $status);
    $role->addChild('finalresult')->addChild('result', $letterGrade);
    $recordid = $role->addChild('extension')->addChild('luminisrole')->addChild('recordid');
    
    $recordid->addAttribute('id', $requestorId); //instructor id.
    
}

// Add the <enterprise>......</enterprise> xml data chunk
$temp_xml = $xml_chunk->asXML();
$temp_xml = str_replace('<?xml version="1.0"?>', '', $temp_xml);
$banner_wrapper->addCData($temp_xml);

if ($bugging) {

    error_log("\n\n");
    error_log("\nHere is the XML: ");
    error_log("\n\n". $xml->asXML());
    error_log("\n\n");
    // just want to check if we are making it here and what the XML is........
    // kill the connection
    die();
}

//We are done generating the XML.
if ($url_post) {
    $post_string = $xml->asXML();//$xml;
    
    $headers = array(
        'Content-Type: application/xml',
        "Content-length: " . strlen($post_string),
        "Connection: close",
    );
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    
    if (curl_errno($ch)) {
        print curl_error($ch);
    } else {
        curl_close($ch);
    }

    // $path_to_save1 = "/Users/davidlowe/Sites/logs/curl_call_1.txt";
    // file_put_contents($path_to_save1, $post_string);
// } else {
    //Partial success response:
    /*
    $data = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><processingResult statuscode="PartialSuccess"><datasource>SCT GRADE ADAPTER</datasource><courseSourcedID><source>Banner University</source><id>31586.201103</id></courseSourcedID><originalsource>Banner University Blackboard Vista Enterprise</originalsource><errordetail><sourcedid><source>62072</source><id>BANNER University</id></sourcedid><recordid id="61510"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>7001</source><id>BANNER University</id></sourcedid><recordid id="61510"/><errordescription>GE04: Student enrollment does not exist or is inactive.</errordescription></errordetail></processingResult>';
    
    //Success message:
    $data = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><processingResult statuscode="Success"><datasource>SCT GRADE ADAPTER</datasource><courseSourcedID><source>Banner University</source><id>30267.201103</id></courseSourcedID><originalsource>Banner University Blackboard Vista Enterprise</originalsource><errordetail><sourcedid><source>65150</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>93884</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>92837</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>93040</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>81383</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>84079</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>61934</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail><errordetail><sourcedid><source>98456</source><id>BANNER University</id></sourcedid><recordid id="29585"/><errordescription>GE00: Grade has been successfully updated.</errordescription></errordetail></processingResult>';
    
    //Error response:
    $data = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><processingResult statuscode="Failure"><datasource>SCT GRADE ADAPTER</datasource><courseSourcedID><source>Banner University</source><id>31586.201103</id></courseSourcedID><originalsource>Banner University Blackboard Vista Enterprise</originalsource><errordetail><sourcedid><source>62072</source><id>BANNER University</id></sourcedid><recordid id="9819"/><errordescription>GE11: Instructor ID is not assigned to section.</errordescription></errordetail><errordetail><sourcedid><source>79466</source><id>BANNER University</id></sourcedid><recordid id="9819"/><errordescription>GE04: Student enrollment does not exist or is inactive.</errordescription></errordetail></processingResult>';
    */
}


$gradeSubmission = new stdClass();
$gradeSubmission->courseid = $courseid;
$gradeSubmission->{'course_sourcedid'} = $courseIdNumber;
$gradeSubmission->{'submitter_userid'} = $USER->id;
$gradeSubmission->{'submitter_sourcedid'} = $requestorId;

$response = new SimpleXMLElement($data);
if ($bugging) {
    error_log("\n------------------------------------------------------------------------------------------\n");
    error_log("\n---------------->>>>>> GRADE SUBMISSION: First request has completed <<<<<<---------------");
    error_log("\n---------------->>>>>> GRADE SUBMISSION: Process ID is: ".$response->value. " <<<<<<---------------");
    error_log("\n------------------------------------------------------------------------------------------\n");
}


if (isset($response->value)) {

    $final_get_url = $url_get. $response->value. $url_get_dangler;
    // error_log("\n\nWhat is the final URL: \n\n". $final_get_url. "\n\n");

    $headers = array(
        'Content-Type: application/xml',
        "Content-length: " . strlen($post_string),
        "Connection: close",
    );
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $final_get_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    
    $data2 = curl_exec($ch);
    $info2 = curl_getinfo($ch);

    $response2 = new SimpleXMLElement($data2);
 
    $tmp_xml = $response2->{'variable-instance'}->value;
    $xml_obj = simplexml_load_string($tmp_xml);
    // $path_to_save2 = "/Users/davidlowe/Sites/logs/curl_call_2.txt";
    // file_put_contents($path_to_save2, $xml_obj);
}

if ($bugging) {
    error_log("\n------------------------------------------------------------------------------------------\n");
    error_log("\n---------------->>>>>> GRADE SUBMISSION: Second request has completed <<<<<<---------------\n");
    error_log("\n---------------->>>>>> GRADE SUBMISSION: Status Code: ". $xml_obj->attributes()->statuscode. " <<<<<<---------------\n");
    error_log("\n---------------->>>>>> GRADE SUBMISSION: RAW Data Return is: ". print_r($data2, 1) . " <<<<<<---------------\n");
    error_log("\n\n\n---------------->>>>>> GRADE SUBMISSION: Return as SimpleXML is: ". print_r($response2, 1) . " <<<<<<---------------\n\n");
    error_log("\n------------------------------------------------------------------------------------------\n");
}
/*
GE00 - Grade has been successfully updated.
GE01 - Student ID does not exist.
GE02 - Section does not exist.
GE03 - Section is not gradable.
GE04 - Student enrollment does not exist or is inactive.
GE05 - Student enrollment is not gradable.
GE06 - Gradable component records exist - must generate OR midterm grade through component marks.
GE07 - Gradable component records exist - must generate OR final grade through component marks.
GE08 - Grade not valid for section.
GE09 - Grade already rolled to history.
GE10 - Received grade already posted to student enrollment OR No update performed.
GE11 - Instructor ID is not assigned to section.
ELSE - The error number did not match any known error code.
*/

//Parse the response.
if ($xml_obj->attributes()->statuscode == 'Failure') {
    $errors = array();
    //The submission received an error from Banner.
    $html_chunk = '<div class="row-fluid">'.
        '<div class="col12">'.
        '<div class="alert alert-danger" role="alert">'.
        '<p><span style="font-size:25px;">'.
            '<i class="fa fa-close"></i>&nbsp;&nbsp;'.
            '</span>'.
            // '<strong>Your grades have been successfully submitted to Banner with a few exceptions!</strong>'.
            '<strong>There were errors in your grade submission</strong></p><p>Details are below.</p>'.
        '</p>'.
        '</div></div></div>';
    echo $html_chunk; 

    echo "<ul>";
    foreach ($xml_obj->errordetail as $error) {
        $sourcedid = intval($error->sourcedid->id);
        $msg = $error->errordescription." Source: ".$error->sourcedid->id.", ".$userListBySource[$sourcedid];

        echo "<li>".$msg."</li>";
        $errors[] = $msg;

        if ($bugging) {
            error_log("\n---------------->>>>>> Error: ". $msg);
        }

    }
    echo "</ul>";
    echo get_string('contact_info', 'gradeexport_submit');
    
    echo get_string('grades_not_submitted', 'gradeexport_submit');
    
    if ($bugging) {
        error_log("\n\n");
        error_log("\n---->Grade Submission: Failure, going to send email");
    }
    grade_submit_sendEmail('failure', $errors);

    $gradeSubmission->{'succeeded'} = 0;
    $gradeSubmission->{'response_received'} = 1;

} else if ($xml_obj->attributes()->statuscode == 'PartialSuccess') {
    //The submission was successful "partially", meaning there were probably some warning type errors.
    //but it's still considered "submitted".
    
    // echo get_string('grade_submission_partialsuccess', 'gradeexport_submit');
    $html_chunk = '<div class="row-fluid">'.
        '<div class="col12">'.
        '<div class="alert alert-warning" role="alert">'.
        '<p><span style="font-size:25px;">'.
            '<i class="fa fa-exclamation-triangle"></i>&nbsp;&nbsp;'.
            '</span>'.
            '<strong>Your grades have been successfully submitted to Banner with a few exceptions!</strong>'.
        '</p>'.
        '</div></div></div>';
    echo $html_chunk; 

    echo "<ul>";
    foreach ($xml_obj->errordetail as $error) {
        $sourcedid = intval($error->sourcedid->id);
        $msg = $error->errordescription." Source: ".$error->sourcedid->id.", ".$userListBySource[$sourcedid];
        echo "<li>".$msg."</li>";
        $errors[] = $msg;
        if ($bugging) {
            error_log("\n---------------->>>>>> Error: ". $msg);
        }
    }
    echo "</ul>";
    
    echo get_string('contact_info', 'gradeexport_submit');
    
    // echo get_string('grades_are_submitted', 'gradeexport_submit');
    if ($bugging) {
        error_log("\n\n");
        error_log("\n---->Grade Submission: Partial Success, going to send email");
    }
    grade_submit_sendEmail('partialsuccess', $errors);

    $gradeSubmission->{'succeeded'} = 1;
    $gradeSubmission->{'response_received'} = 1;
} else if ($xml_obj->attributes()->statuscode == 'Success') {
    //The submission was successful!
    
    // echo get_string('grade_submission_success', 'gradeexport_submit');
    $html_chunk = '<div class="row-fluid">'.
        '<div class="col12">'.
        '<div class="alert alert-success" role="alert">'.
        '<p><span style="font-size:25px;">'.
            '<i class="fa fa-check"></i>&nbsp;&nbsp;'.
            '</span>'.
            '<strong>Your grades have been successfully submitted to Banner!</strong>'.
        '</p>'.
        '</div></div></div>';

    echo $html_chunk;
    /*
    echo "<ul>";
    foreach ($xml_obj->errordetail as $error)
    {
        $sourcedid=intval($error->sourcedid->source);
        echo "<li>".$error->errordescription." Source: ".$error->sourcedid->source.", ".$userListBySource[$sourcedid]."</li>";
    }
    echo "</ul>";
    */
    echo get_string('contact_info', 'gradeexport_submit');
    
    // echo get_string('grades_are_submitted', 'gradeexport_submit');
    if ($bugging) {
        error_log("\n\n");
        error_log("\n---->Grade Submission: Success, going to send email");
    }
    grade_submit_sendEmail('success');

    $gradeSubmission->{'succeeded'} = 1;
    $gradeSubmission->{'response_received'} = 1;

} else {
    $errors = array();
    //The submission never got a response. Timed out?
    // echo get_string('grade_submission_noresponse', 'gradeexport_submit');
    $html_chunk = '<div class="row-fluid">'.
        '<div class="col12">'.
        '<div class="alert alert-danger" role="alert">'.
        '<p><span style="font-size:25px;">'.
            '<i class="fa fa-close"></i>&nbsp;&nbsp;'.
            '</span>'.
            // '<strong>Your grades have been successfully submitted to Banner with a few exceptions!</strong>'.
            '<strong>There was an error submitting your grades.</strong> <br/><br/><strong>Your grades may or may not have been successfully submitted.</strong>'.
            '<br/><br/>Details are below.'.
        '</p>'.
        '</div></div></div>';
    echo $html_chunk; 
    echo "<ul>";
    $msg = "No response from Banner.";
    echo "<li>".$msg."</li>";
    $errors[] = $msg;
    echo "</ul>";

    echo get_string('contact_info', 'gradeexport_submit');
    echo get_string('grades_not_submitted', 'gradeexport_submit');
    
    if ($bugging) {
        error_log("\n\n");
        error_log("\n---->Grade Submission: Failure (on last else condition), going to send email");
    }
    grade_submit_sendEmail('failure', $errors);
        
    $gradeSubmission->{'succeeded'} = 0;
    $gradeSubmission->{'response_received'} = 0;
}

//Now put an entry in our submissions table.
$gradeSubmission->timesubmitted = time();
$DB->insert_record('grade_submit_lmb_submissions', $gradeSubmission);

if ($bugging) {
    $time_elapsed_secs = microtime(true) - $process_timer_start;
    error_log("\n\nThe Overall time it took to process this page was: ". $time_elapsed_secs. " seconds\n\n");
    echo "<br><br>Total time to process request: ". round($time_elapsed_secs, 2). " seconds.<br>";
    error_log("\n------------------------------------------------------------------------------------------\n");
}

echo $OUTPUT->footer();
