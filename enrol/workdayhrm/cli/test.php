<?php

// Make sure this can only run via CLI.
define('CLI_SCRIPT', true);

// Include the main Moodle config.
require(__DIR__ . '/../../../config.php');

// This is so we can use the CFG var.
global $CFG;

// Include the CLI lib so we can do this stuff via CLI.
require_once("$CFG->libdir/clilib.php");

require_once('/var/www/html/39/enrol/workdayhrm/classes/workdayhrm.php');

$s = workdayhrm::get_wdhrm_settings();

mtrace("Getting the list of employees from the WorkDay webservice endpoint.");
$employees = workdayhrm::get_wdhrm_employees($s);
$employeecount = count($employees);
mtrace("Returned $employeecount employees from the WorkDay webservice endpoint.\n");

mtrace("Getting the list of enrollable courses from the Moodle DB.");
$courses = workdayhrm::get_wdhrm_courses($s);
$coursecount = count($courses);
mtrace("Returned $coursecount enrollable courses from the Moodle DB.\n");

$cleaned = workdayhrm::clean_wdhrm_employees($s, $employees);

foreach ($cleaned as $employee) {
  mtrace("Checking to see if $employee->Legal_First_Name $employee->Legal_Last_Name exists in the Moodle DB.");
  $exists = workdayhrm::get_wdhrm_exists($s, $employee);

  // Count the fields.
  $ecount = count((array)$employee);

  if ($exists->message == 'exists') {

    // Let us know how many fields this user has.
    mtrace("  Exsiting employee $employee->Legal_First_Name $employee->Legal_Last_Name has $ecount fields.");

    // Display the data we found.
    if(isset($employee->Employee_ID)) {
      mtrace("    Employee_ID: $employee->Employee_ID");
    } else {
      mtrace("    Employee_ID_NULL");
    }
    if(isset($employee->Universal_ID)) {
      mtrace("    Universal_ID: $employee->Universal_ID");
    } else {
      mtrace("    Universal_ID_NULL");
    }
    if(isset($employee->LSUAM_LSU_ID)) {
      mtrace("    LSUAM_LSU_ID: $employee->LSUAM_LSU_ID");
    } else {
      mtrace("    LSUAM_LSU_ID_NULL");
    }
    if(isset($employee->Work_Email)) {
      mtrace("    Work_Email: $employee->Work_Email");
    } else {
      mtrace("    Work_Email_NULL");
    }
    if(isset($employee->Legal_First_Name)) {
      mtrace("    Legal_First_Name: $employee->Legal_First_Name");
    } else {
      mtrace("    Legal_First_Name_NULL");
    }
    if(isset($employee->Legal_Middle_Name)) {
      mtrace("    Legal_Middle_Name: $employee->Legal_Middle_Name");
    } else {
      mtrace("    Legal_Middle_Name_NULL");
    }
    if(isset($employee->Legal_Last_Name)) {
      mtrace("    Legal_Last_Name: $employee->Legal_Last_Name");
    } else {
      mtrace("    Legal_Last_Name_NULL");
    }
    if(isset($employee->Preferred_First_Name)) {
      mtrace("    Preferred_First_Name: $employee->Preferred_First_Name");
    } else {
      mtrace("    Preferred_First_Name_NULL");
    }
    if(isset($employee->Preferred_Middle_Name)) {
      mtrace("    Preferred_Middle_Name: $employee->Preferred_Middle_Name");
    } else {
      mtrace("    Preferred_Middle_Name_NULL");
    }
    if(isset($employee->Preferred_Last_Name)) {
      mtrace("    Preferred_Last_Name: $employee->Preferred_Last_Name");
    } else {
      mtrace("    Preferred_Last_Name_NULL");
    }
    if(isset($employee->Company_ID)) {
      mtrace("    Company_ID: $employee->Company_ID");
    } else {
      mtrace("    Company_ID_NULL");
    }
    if(isset($employee->Manager_Employee_ID)) {
      mtrace("    Manager_Employee_ID: $employee->Manager_Employee_ID");
    } else {
      mtrace("    Manager_Employee_ID_NULL");
    }
    if(isset($employee->Manager_Universal_ID)) {
      mtrace("    Manager_Universal_ID: $employee->Manager_Universal_ID");
    } else {
      mtrace("    Manager_Universal_ID_NULL");
    }
    if(isset($employee->Manager_LSU_ID)) {
      mtrace("    Manager_LSU_ID: $employee->Manager_LSU_ID");
    } else {
      mtrace("    Manager_LSU_ID_NULL");
    }

  } else if ($exists->message == 'new') {
    $insert = workdayhrm::insert_wdhrm_employee($s, $employee);
    mtrace("  Inserted new employee $employee->Employee_ID: $employee->Legal_First_Name $employee->Legal_Last_Name id $insert with $ecount fields.");
  } else {
    mtrace("  Error - No identifying fields found for user $employee->Legal_First_Name $employee->Legal_Last_Name.");
  }
mtrace("Finished checking to see if $employee->Legal_First_Name $employee->Legal_Last_Name exists.\n");
}
mtrace("Finished Inserting data.");

$dupes = workdayhrm::wdhrm_find_duplicates();

foreach ($dupes as $dupe) {
    $ud = workdayhrm::wdhrm_update_duplicates($dupe);
    $dupe->body = json_encode($dupe);
    $dupe->subject = 'Duplicate Account Found';
    if ($ud) {
       $dupe->updatestatus = 'success';
       mtrace("id: $dupe->id - email: $dupe->work_email - name: $dupe->legal_first_name $dupe->legal_last_name - Duplicate email found and updated!");
    } else {
       $dupe->updatestatus = 'failed';
       mtrace("id: $dupe->id - email: $dupe->work_email - name: $dupe->legal_first_name $dupe->legal_last_name - Duplicate update failed!");
    }
    $email = workdayhrm::send_wdhrm_email($dupe, $s);
}
