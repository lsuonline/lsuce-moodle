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
 * @copyright 2024 onwards LSUOnline & Continuing Education
 * @copyright 2024 onwards Robert Russo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//defined('MOODLE_INTERNAL') || die();

/**
 *
 */
class workdaystudent {

    // Start the dtrace counter.
    private static $dtc = 0;

    /**
     * Grabs the settings from config_plugins.
     *
     * @return @object $s
     */
    public static function get_settings() {
        $s = new stdClass();

        // Get the settings.
        $s = get_config('enrol_workdaystudent');

        // TODO: Remove me.
        if (!isset($s->campus)) {
            $s->username = 'INSERT USERNAME HERE';
            $s->password = 'INSERT PASSWORD HERE';
            $s->wsurl = 'https://wd2-impl-services1.workday.com/ccx/service/customreport2/lsu1/ITS_INT_RPT_ISU';
            $s->units = 'Raas-LSU1103-INTS0052E-LSUAM-Moodle-Academic-Units';
            $s->periods = 'RaaS-LSU1104-INTS0052F-LSUAM-Moodle-Academic-Periods';
            $s->courses = 'RaaS-LSU1101-INTS0052C-LSUAM-Moodle-Courses';
            $s->sections = 'RaaS-LSU1102-INTS0052D-LSUAM-Moodle-Course-Sections';
            $s->students = 'RaaS-LSU1099-INTS0052A-LSUAM-Moodle-Student-Demographic';
            $s->registrations = 'RaaS-LSU1105-INTS0052G-LSUAM-Moodle-Student-Registrations';
            $s->campus = 'AU00000079';
            $s->campusname = 'LSUAM';
            $s->metafields = 'Academic_Level, Academic_Unit_ID, Program_of_Study_Code, Classification, Degree_Candidacy, Buckley_Hold';
            $s->sportfield = 'Current_Athletic_Teams_group';
        }

        return $s;
    }

    public static function get_students($s, $periodid, $studentid) {
        // Log what we're doing.
        mtrace("Fetching students from webservice endpoint.");

        // Set the start time.
        $starttime = microtime(true);

        // Set the endpoint.
        $endpoint = 'students';

        // Set some aprms up.
        $parms = array();

        // Set the required campus id.
        $parms['Institution!Academic_Unit_ID'] = $s->campus;

        // Set the required term.
        $parms['Academic_Period!Academic_Period_ID'] = $periodid;

        // Set the Student ID if we're looking up a single student.
        if ($studentid !== '') {
            $parms['Universal_Id'] = $studentid;
        }

        $parms['format'] = 'json';

        // Build out the settins based on settings, endpoint, and parms.
        $s = self::buildout_settings($s, $endpoint, $parms);

        // Get the data.
        $students = self::get_data($s);

        // Get a count of students.
        $studentcount = count($students);

        // Populate some stuff for later.
        $students[0]->studentcount = $studentcount;

        // Set the endtime.
        $endtime = microtime(true);

        // Calculate the elapsed time.
        $elapsedtime = round($endtime - $starttime, 1);

        // Log what we did.
        mtrace("Retreived $studentcount students in $elapsedtime seconds.");

        // Return the data.
        return $students;
    }

    public static function id_fake_courses($course) {
        // Identify courses with *s or all 0 as their course number.
        preg_match('/\*|0000/', $course->Course_Number, $match);

        // Return the matches.
        return $match;
    }

    public static function check_period($period) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_periods';

        // Set the parameters.
        $parms = array('academic_period_id' => $period->Academic_Period_ID);

        // Get the academic unit record.
        $ap = $DB->get_record($table, $parms);

        return $ap;
    }


    public static function check_unit($unit) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_units';

        // Set the parameters.
        $parms = array('academic_unit_id' => $unit->Academic_Unit_ID);

        // Get the academic unit record.
        $au = $DB->get_record($table, $parms);

        return $au;
    }

    public static function get_academic_year($period) {
        // Find the year.
        preg_match('/(\d\d\d\d-\d\d\d\d).*/', $period->Academic_Year, $ayear);

        // Make sure we found the year.
        $academicyear = isset($ayear[1]) ? $ayear[1] : 0;

        //Return the year.
        return $academicyear;
    }

    public static function get_period_year($period) {
        // Find the year.
        preg_match('/.+?(\d\d\d\d).*/', $period->Academic_Period, $pyear);

        // Make sure we found the year.
        $periodyear = isset($pyear[1]) ? $pyear[1] : 0;

        //Return the year.
        return $periodyear;
    }

    public static function update_period($period, $ap) {
        global $DB;

        // Build the cloned object.
        $ap2 = clone($ap);

        // Set start dates.
        $startdate = strtotime($period->Start_Date);
        $enddate = strtotime($period->End_Date);

        // Get the period year.
        $periodyear = workdaystudent::get_period_year($period);

        // Get the academic year.
        $academicyear = workdaystudent::get_academic_year($period);

        // Keep the ids from $ap and populate the rest from $period.
        $ap2->academic_period_id = $period->Academic_Period_ID;
        $ap2->academic_period = $period->Academic_Period;
        $ap2->period_type = $period->Period_Type;
        $ap2->period_year = $periodyear;
        $ap2->academic_calendar = $period->Academic_Calendar;
        $ap2->academic_year = $academicyear;
        $ap2->start_date = $startdate;
        $ap2->end_date = $enddate;

        // Compare the objects.
        if ($ap == $ap2) {
            self::dtrace("Academic period $ap->academic_period_id matched $period->Academic_Period_ID, skipping.");
            return $ap;
        } else {
            // Set the table.
            $table = 'enrol_oes_periods';

            // Update the record.
            $success = $DB->update_record($table, $ap2, true);

            if ($success) {
                self::dtrace("Academic period $ap->academic_period_id has been updated from the endpoint.");

                // Return the updated object.
                return $ap2;
            } else {
                mtrace("Updating $ap->academic_period_id failed and has not been updated.");

                // Return the original object.
                return $ap;
            }
        }
    }

    public static function insert_period($period) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_periods';

        // Create the object.
        $tap = new stdClass();

        // Set start dates.
        $startdate = strtotime($period->Start_Date);
        $enddate = strtotime($period->End_Date);

        // Get the period year.
        $periodyear = workdaystudent::get_period_year($period->Academic_Period);

        // Get the academic year.
        $academicyear = workdaystudent::get_academic_year($period->Academic_Year);

        // Populate the temporary period table.
        $tap->academic_period_id = $period->Academic_Period_ID;
        $tap->academic_period = $period->Academic_Period;
        $tap->period_type = $period->Period_Type;
        $tap->period_year = $periodyear;
        $tap->academic_calendar = $period->Academic_Calendar;
        $tap->academic_year = $academicyear;
        $tap->start_date = $startdate;
        $tap->end_date = $enddate;

        $ap = $DB->insert_record($table, $tap);
        self::dtrace("Inserted academic_period_id: $tap->academic_period_id.");

        return $ap;
    }

    public static function check_section($section) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_sections';

        // Set the parameters.
        $parms = array('section_listing_id' => $section->Section_Listing_ID);

        // Get the academic unit record.
        $as = $DB->get_record($table, $parms);

        return $as;
    }


    public static function check_course($course) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_courses';

        // Set the parameters.
        $parms = array('course_listing_id' => $course->Course_Listing_ID);

        // Get the academic unit record.
        $ac = $DB->get_record($table, $parms);

        return $ac;
    }

    public static function update_section($section, $as) {
        global $DB;

        // Build the cloned object.
        $as2 = clone($as);

        // Keep id, section_listing_id, idnumber, and status from $as and populate the rest from $section.
        $as2->course_section_definition_id = $section->Course_Section_Definition_ID;
        $as2->section_number = $section->Section_Number;
        $as2->course_definition_id = $section->Course_Definition_ID;
        $as2->academic_unit_id = $section->Academic_Unit_ID;
        $as2->academic_period_id = $section->Academic_Period_ID;
        $as2->course_section_title = $section->Course_Section_Title;
        $as2->course_section_abbreviated_title = $section->Course_Section_Abbreviated_Title;
        $as2->delivery_mode = $section->Delivery_Mode;
        $as2->class_type = $section->Class_Type;

        // Compare the objects.
        if ($as == $as2) {
            self::dtrace("Course $as->section_listing_id matched $section->Section_Listing_ID, skipping.");
            return $as;
        } else {
            // Set the table.
            $table = 'enrol_oes_sections';

            // Update the record.
            $success = $DB->update_record($table, $as2, true);

            if ($success) {
                self::dtrace("Academic unit $as->section_listing_id has been updated from the endpoint.");

                // Return the updated object.
                return $as2;
            } else {
                mtrace("Updating $as->section_listing_id failed and has not been updated.");

                // Return the original object.
                return $as;
            }
        }
    }

    public static function insert_section($section) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_sections';

        // Create the object.
        $tas = new stdClass();

        // Build the object from $section 
        $tas->section_listing_id = $section->Section_Listing_ID;
        $tas->course_section_definition_id = $section->Course_Section_Definition_ID;
        $tas->section_number = $section->Section_Number;
        $tas->course_definition_id = $section->Course_Definition_ID;
        $tas->academic_unit_id = $section->Academic_Unit_ID;
        $tas->academic_period_id = $section->Academic_Period_ID;
        $tas->course_section_title = $section->Course_Section_Title;
        $tas->course_section_abbreviated_title = $section->Course_Section_Abbreviated_Title;
        $tas->delivery_mode = $section->Delivery_Mode;
        $tas->class_type = $section->Class_Type;
        $tas->idnumber = null;
        $tas->status = 'Pending';

        $as = $DB->insert_record($table, $tas);
        self::dtrace("Inserted section_listing_id: $tas->section_listing_id.");

        return $as;
    }

    public static function update_course($course, $ac) {
        global $DB;

        // Build the cloned object.
        $ac2 = clone($ac);

        // Keep the id and course_listing_id from $ac and populate the rest from $course.
        $ac2->academic_unit_id = $course->Academic_Unit_ID;
        $ac2->course_definition_id = $course->Course_Definition_ID;
        $ac2->course_number = $course->Course_Number;
        $ac2->course_subject_abbreviation = $course->Course_Subject_Abbreviation;
        $ac2->course_subject = $course->Course_Subject;
        $ac2->subject_code = $course->Subject_Code;
        $ac2->course_abbreviated_title = $course->Course_Abbreviated_Title;
        $ac2->academic_level = $course->Academic_Level;

        // Compare the objects.
        if ($ac == $ac2) {
            self::dtrace("Course $ac->course_listing_id matched $course->Course_Listing_ID, skipping.");
            return $ac;
        } else {
            // Set the table.
            $table = 'enrol_oes_courses';

            // Update the record.
            $success = $DB->update_record($table, $ac2, true);

            if ($success) {
                self::dtrace("Academic unit $ac->course_listing_id has been updated from the endpoint.");

                // Return the updated object.
                return $ac2;
            } else {
                mtrace("Updating $ac->course_listing_id failed and has not been updated.");

                // Return the original object.
                return $ac;
            }
        }
    }

    public static function insert_course($course) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_courses';

        // Create the object.
        $tac = new stdClass();

        // Build the object from $unit.
        $tac->course_listing_id = $course->Course_Listing_ID;
        $tac->academic_unit_id = $course->Academic_Unit_ID;
        $tac->course_definition_id = $course->Course_Definition_ID;
        $tac->course_number = $course->Course_Number;
        $tac->course_subject_abbreviation = $course->Course_Subject_Abbreviation;
        $tac->course_subject = $course->Course_Subject;
        $tac->subject_code = $course->Subject_Code;
        $tac->course_abbreviated_title = $course->Course_Abbreviated_Title;
        $tac->academic_level = $course->Academic_Level;

        $ac = $DB->insert_record($table, $tac);
        self::dtrace("Inserted course_listing_id: $tac->course_listing_id.");

        return $ac;
    }

    public static function insert_update_course($s, $course) {
        $ac = self::check_course($course);
        if (isset($ac->id)) {
            $ac = self::update_course($course, $ac);
        } else {
            $ac = self::insert_course($course);
        }
        return $ac;
    }

    public static function insert_update_section($section) {
        $as = self::check_section($section);
        if (isset($as->id)) {
            $as = self::update_section($section, $as);
        } else {
            $as = self::insert_section($section);
        }
        return $as;
    }

    public static function insert_update_period($s, $period) {
        $ap = self::check_period($period);
        if (isset($ap->id)) {
            $ap = self::update_period($period, $ap);
        } else {
            $ap = self::insert_period($period);
        }
        return $ap;
    }

    public static function update_unit($unit, $au) {
        global $DB;

        // Build the cloned object.
        $au2 = clone($au);

        // Keep the ids from $au and populate the rest from $unit.
        $au2->academic_unit_subtype = $unit->Academic_Unit_Subtype;
        $au2->academic_unit_code = $unit->Academic_Unit_Code;
        $au2->academic_unit = $unit->Academic_Unit;
        $au2->superior_unit_id = $unit->Superior_ID;

        // Compare the objects.
        if ($au == $au2) {
            self::dtrace("Academic unit $au->academic_unit_id matched $unit->Academic_Unit_ID, skipping.");
            return $au;
        } else {
            // Set the table.
            $table = 'enrol_oes_units';

            // Update the record.
            $success = $DB->update_record($table, $au2, true);

            if ($success) {
                self::dtrace("Academic unit $au->academic_unit_id has been updated from the endpoint.");

                // Return the updated object.
                return $au2;
            } else {
                mtrace("Updating $au->academic_unit_id failed and has not been updated.");

                // Return the original object.
                return $au;
            }
        }
    }

    public static function insert_unit($unit) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_units';

        // Create the object.
        $tau = new stdClass();

        // Build the object from $unit.
        $tau->academic_unit_id = $unit->Academic_Unit_ID;
        $tau->academic_unit_subtype = $unit->Academic_Unit_Subtype;
        $tau->academic_unit_code = $unit->Academic_Unit_Code;
        $tau->academic_unit = $unit->Academic_Unit;
        $tau->superior_unit_id = isset($unit->Superior_ID) ? $unit->Superior_ID : '';

        $au = $DB->insert_record($table, $tau);
        self::dtrace("Inserted academic_unit_id: $tau->academic_unit_id.");

        return $au;
    }

    public static function insert_update_unit($s, $unit) {
        $au = self::check_unit($unit);
        if (isset($au->id)) {
            $au = self::update_unit($unit, $au);
        } else {
            $au = self::insert_unit($unit);
        }
        return $au;
    }

    public static function get_sections($s, $parms) {
    // Set the endpoint.
    $endpoint = 'sections';

        // Set some more parms up.
        if (isset($s->campus)) {
            $parms['Institution!Academic_Unit_ID'] = $s->campus;
        }
        $parms['format'] = 'json';

        // Build out the settins based on settings, endpoint, and parms.
        $s = self::buildout_settings($s, $endpoint, $parms);

        // Get the sections.
        $sections = self::get_data($s);

        return $sections;
    }

    public static function get_courses($s) {
    // Set the endpoint.
    $endpoint = 'courses';

        // Set some aprms up.
        $parms = array();
        if (isset($s->campus)) {
            $parms['Institution!Academic_Unit_ID'] = $s->campus;
        }
        $parms['format'] = 'json';

        // Build out the settins based on settings, endpoint, and parms.
        $s = self::buildout_settings($s, $endpoint, $parms);

        // Get the units.
        $courses = self::get_data($s);

        return $courses;
    }

    public static function sort_courses($courses) {
        usort($courses, function($a, $b) {
            // First comparison based on subject.
            $coursecomparison = strcmp($a->Course_Subject_Abbreviation, $b->Course_Subject_Abbreviation);
    
            // If 'name' values are equal, compare based on 'count'
            if ($coursecomparison == 0) {
                return strcmp($a->Course_Number, $b->Course_Number);
            }
    
            return $coursecomparison;
        });
        return $courses;
    }

    public static function format_date($longago) {
        $date = date("Y-m-d\TH:i:s", strtotime("$longago"));
        return $date;
    }

    public static function get_local_units($s) {
        global $DB;
        $table = 'enrol_oes_units';
        $parms = array('academic_unit_subtype' => 'Institution');

        $units = $DB->get_records($table, $parms);

        return $units;
    }

    public static function get_units($s, $date = null) {
        // Set the endpoint.
        $endpoint = 'units';

        // Set some aprms up.
        $parms = array();

        // Check the campus.
        if (isset($s->campus)) {
            $parms['Superior_Unit!Academic_Unit_ID'] = $s->campus;
        }

        // Check the date.
        if (isset($date)) {
            $parms['Last_Updated'] = $date;
        }

        $parms['format'] = 'json';

        // Build out the settins based on settings, endpoint, and parms.
        $s = self::buildout_settings($s, $endpoint, $parms);

        // Get the units.
        $units = self::get_data($s);

        return $units;
    }

    public static function buildout_settings($s, $endpoint, $parms) {
        // Build the urlencoded params.
        $params = http_build_query($parms, '', '%26');

        // Build the entire urlencoded URL.
        $s->url = urlencode($s->wsurl . '/' . $s->$endpoint . '?') . $params;

        return $s;
    }

    public static function get_dates() {
        // Get the current year.
        $currentyear = date('Y');

        // Get the integer value of the current month.
        $currentmonth = intval(date('m'));

        // Set the start date to the 1st of the year.
        $startdate = $currentyear . '-01-01';

        // Set the end date to the last of either this or next year, depending on month.
        if ($currentmonth < 9) {
            $enddate = ($currentyear) . '-12-31';
        } else {
            $enddate = ($currentyear + 1) . '-12-31';
        }

        // Build the return array.
        $dates = array(
            'Start_Date' => $startdate,
            'End_Date' => $enddate
        );

        return $dates;
    }

    public static function delete_studentmeta($stu) {
        global $DB;
        $starttime = microtime(true);

        // Set the deleted table.
        $dtable = 'enrol_oes_students_meta';

        // Set the deleted parms.
        $dparms = array('studentid' => $stu->id);

        // Delete the records for this student.
        $deleted = $DB->delete_records($dtable, $dparms);

        $endtime = microtime(true);
        $elapsedtime = round($endtime - $starttime, 4);
        self::dtrace("  - Cleaning metadata for $stu->universal_id took $elapsedtime seconds.");

        // Return the bool.
        return $deleted;
    }

    public static function insert_studentmeta($s, $stu, $metafield, $metadata) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_students_meta';

        // Build the $data object.
        $data = new stdClass();

        // Fill out the object.
        $data->studentid = $stu->id;
        $data->datatype = $metafield;
        $data->data = $metadata;

        // Insert the record.
        $inserted = $DB->insert_record($table, $data, true);

        return $inserted;
    }

    public static function delete_insert_studentmeta($s, $stu, $student) {
        // Determine what data we're talking about.
        $metafields = explode(',', $s->metafields);
        $sportfield = $s->sportfield;
        $athletecounter = 0;
        $deleted = self::delete_studentmeta($stu);

        if ($deleted) {
            self::dtrace("  - Beginning processing of student metadata for $stu->universal_id.");
            foreach ($metafields as $metafield) {
                $metafield = trim($metafield);
                if (isset($student->$metafield)) {
                    $metadata = $student->$metafield;
                    self::dtrace("    $student->Universal_Id - $metafield: $metadata");
                    $updated = self::insert_studentmeta($s, $stu, $metafield, $metadata);
                }
            }

            foreach ($metafields as $metafield) {
                if (isset($student->$sportfield)) {
                    $athletecounter++;
                    foreach ($student->$sportfield as $team) {

                        // Update and insert sports and codes as needed.
                        $sport = self::create_update_sportcodes($s, $team);

                        $sports[] = $sport->code;
                        self::dtrace("    $student->Universal_Id - $student->First_Name $student->Last_Name is on team $sport->code: $sport->name.");
                        $supdated= self::insert_studentmeta($s, $stu, 'Athletic_Team_ID', $sport->code);
                    }
                    break;
                }
            }
            self::dtrace("Finished processing of student metadata for $stu->universal_id.");
        } else {
            mtrace("Could not delete student metadata for $student->Universal_ID.");
        }
    }

    public static function check_sportcodes($team) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_sport';

        // Set the parms.
        $parms = array('code' => $team->Athletic_Team_ID);

        // Get tthe data.
        $sport = $DB->get_record($table, $parms);

        return $sport;
    }

    public static function create_sportcode($s, $team) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_sport';

        // Build the new obj.
        $sport = new stdClass();

        // Populate the obj.
        $sport->code = $team->Athletic_Team_ID;
        $sport->name = $team->Athletic_Team;

        // Insert the data.
        $created = $DB->insert_record($table, $sport, $returnid = true);

        if ($created) {
            self::dtrace("  - Inserted sport code: $sport->code with name: $sport->name at id: $created.");
            $sport->id = $created;
            return $sport;
        } else {
            self::dtrace("  - Failed to insert sport code: $sport->code with name: $sport->name.");
            return false;
        }


    }

    public static function update_sportcode($s, $sport, $team) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_sport';

        // Make sure we're only updating mismatches.
        if ($sport->code == $team->Athletic_Team_ID
            && $sport->name == $team->Athletic_Team) {

            // Log we did not update anything.
            self::dtrace("  - Sport code matches team name, skipping.");

            return $sport;
        } else {
            // Set the new sport name.
            $sport->name = $team->Athletic_Team;

            // Update the table.
            $updated = $DB->update_record($table, $sport);

            if ($updated) {
                self::dtrace("  - Updated $sport->code name to $sport->name.");
            } else {
                self::dtrace("  - Failed to update $sport->code name to $sport->name.");
            }

            return $sport;
        }
    }

    public static function create_update_sportcodes($s, $team) {
        // Check if the sport code exists.
        $sport = self::check_sportcodes($team);

        if (isset($sport->id)) {
            // Update the sport.
            $updated = self::update_sportcode($s, $sport, $team);
            return $updated;
        } else {
            // Create the student.
            $created = self::create_sportcode($s, $team);
            return $created;
        }
    }

    /**
     * Gets the data from the webservice endpoint.
     *
     * @param  @object $s
     *
     * @return @array of @objects
     */
    public static function get_data($s) {
        // Deal with some unfortunate stuff.
        $s->url = urldecode(urldecode($s->url));

        // Initiate the curl.
        $curl = curl_init($s->url);

        // Set the curl options.
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, "$s->username:$s->password");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        // Get the data.
        $json_response = curl_exec($curl);

        // Get the status for debugging.
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close the curl.
        curl_close($curl);

        // Decode the json.
        $dataobj = json_decode($json_response);

        // Get the data we need.
        if (isset($dataobj->Report_Entry)) {
            $datas = $dataobj->Report_Entry;
        } else {
            $datas = null;
        }

        // Return the data.
        return $datas;
    }

    public static function check_istudent($s, $student) {
        global $DB;

        // Build out the email query.
        $esuffix = $s->campusname . '_Email';
        $email = $student->$esuffix;

        // Build out the legacy idnumber query.
        $lidsuffix = $s->campusname . '_Legacy_ID';
        $lid = $student->$lidsuffix;

        $sql = 'SELECT *
                FROM {enrol_oes_students} stu
                WHERE stu.universal_id = "' . $student->Universal_Id . '"
                OR stu.email = "' . $email . '"
                OR if(stu.school_id IS NOT NULL, stu.school_id = "' . $lid . '", "")';

        $stus = $DB->get_records_sql($sql);
        if (count($stus) > 1) {
            foreach ($stus as $stu) {
                $schoolid = !is_null($stu->school_id) ?
                    ' ' . $stu->school_id . ',' :
                    "";
                mtrace('DB ID: ' . $stu->id . ', ' .
                    $stu->universal_id . ', ' .
                    $stu->email . ',' .
                    $stu->school_id . ' - ' .
                    $stu->firstname . ' ' .
                    $stu->lastname . ' is a dupe of remote user ' . 
                    $student->Universal_Id . ', ' .
                    $email . ', ' .
                    $lid . ', ' .
                    $student->First_Name . ' ' .
                    $student->Last_Name . '.');
            }

            // Do not process this student.
            $stu = null;

        } else if (count($stus) == 1) {
            // Grab the student from the array. 
            self::dtrace("  Student found with universal_id: $student->Universal_Id, email: $email, school_id: $lid.");
            $stu = reset($stus);

        } else {
            self::dtrace("  No student with universal_id: $student->Universal_Id, email: $email, school_id: $lid.");
            $stu = null;
        }

        return $stu;
    }

    public static function update_istudent($s, $stu, $student) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_students';

        // Build out the email query.
        $esuffix = $s->campusname . '_Email';
        $email = $student->$esuffix;

        // Build out the legacy idnumber query.
        $lidsuffix = $s->campusname . '_Legacy_ID';
        $lid = isset($student->$lidsuffix) ? $student->$lidsuffix : null;

        // Build the two objects to compare.
        $stu1 = clone $stu;
        $stu2 = new stdClass();

        // Un/populate the two objects.
        unset($stu1->id);
        unset($stu1->username);
        unset($stu1->userid);
        unset($stu1->lastupdate);
        $stu2->universal_id = $student->Universal_Id;
        $stu2->email = $email;
        $stu2->school_id = $lid;
        $stu2->firstname = $student->First_Name;
        $stu2->preferred_firstname = isset($student->Preferred_First_Name) ? $student->Preferred_First_Name : null;
        $stu2->lastname = $student->Last_Name;
        $stu2->preferred_lastname = isset($student->Preferred_Last_Name) ? $student->Preferred_Last_Name : null;
        $stu2->middlename = isset($student->Middle_Name) ? $student->Middle_Name : null;

        // If the objects match.
        if ($stu1 == $stu2) {
            self::dtrace("  - Student objects match, no update necessary.");

            // Return the original student.
            return false;
        } else {
            // Set the id.
            $stu2->id = $stu->id;

            // Set the datestamp.
            $stu2->lastupdate = time();

            // Update the record.
            $istudent = $DB->update_record($table, $stu2);

            self::dtrace("  - Updated student with universal_id: $stu->universal_id - $student->Universal_Id, email: $stu->email - $email, school_id: $stu->school_id - $lid.");

            return $istudent;
        }
    }

    public static function create_istudent($s, $student) {
        global $DB;

        // Build out the email query.
        $esuffix = $s->campusname . '_Email';
        $email = $student->$esuffix;

        // Build out the legacy idnumber query.
        $lidsuffix = $s->campusname . '_Legacy_ID';
        $lid = isset($student->$lidsuffix) ? $student->$lidsuffix : null;

        // Set the table;
        $table = 'enrol_oes_students';

        // Build the object.
        $data = new stdClass();
        $data->universal_id = $student->Universal_Id;
        $data->email = $email;
        $data->username = $email;
        $data->school_id = $lid;
        $data->userid = null;
        $data->firstname = $student->First_Name;
        $data->preferred_firstname = isset($student->Preferred_First_Name) ? $student->Preferred_First_Name : null;
        $data->lastname = $student->Last_Name;
        $data->preferred_lastname = isset($student->Preferred_Last_Name) ? $student->Preferred_Last_Name : null;
        $data->middlename = isset($student->Middle_Name) ? $student->Middle_Name : null;
        $data->lastupdate = time();

        $success = $DB->insert_record($table, $data, true);

        if (is_int($success)) {
            $stu = $DB->get_record($table, array('id' => $success));
            self::dtrace("  - Created student with universal_id: $stu->universal_id, email: $stu->email, school_id: $stu->school_id.");
            return $stu;
        } else {
            mtrace("  - Failed to create interstitial student.");
            var_dump($student);
            return false;
        }
    }

    public static function create_update_istudent($s, $student) {
        $starttime = microtime(true);

        // Check if the student exists.
        $stu = self::check_istudent($s, $student);

        if (isset($stu->id)) {
            // Update the student.
            $updated = self::update_istudent($s, $stu, $student);

            $endtime = microtime(true);
            $elapsedtime = round($endtime - $starttime, 4);
            self::dtrace("  Student: $stu->universal_id took $elapsedtime seconds to process.");

            return $stu;

        } else {
            // Create the student.
            $stu = self::create_istudent($s, $student);

            $endtime = microtime(true);
            $elapsedtime = round($endtime - $starttime, 4);
            self::dtrace("  Student: $stu->universal_id took $elapsedtime seconds to process.");

            return $stu;
        }
    }

    public static function create_update_iteacher($s, $teacher) {
        $starttime = microtime(true);
        
        // Check if the teacher exists.
        $tea = self::check_iteacher($s, $teacher);

        if (isset($tea->id)) {
            // Update the teacher.
            $updated = self::update_iteacher($s, $tea, $teacher);

            $endtime = microtime(true);
            $elapsedtime = round($endtime - $starttime, 4);
            self::dtrace(" User: $tea->universal_id took $elapsedtime seconds to process.");

            return $tea;

        } else {
            // Create the teacher.
            $tea = self::create_iteacher($s, $teacher);

            if (!$tea) {
                return false;
            }

            $endtime = microtime(true);
            $elapsedtime = round($endtime - $starttime, 4);
            self::dtrace(" User: $tea->universal_id took $elapsedtime seconds to process.");

            return $tea;
        }
    }

    public static function check_iteacher($s, $teacher) {
        global $DB;

        if (!isset($teacher->Instructor_Email) || !isset($teacher->Instructor_ID)) {
            self::dtrace("Instructor Email / ID not found, skipping.");
            var_dump($teacher);
            return false;
        }

        $sql = 'SELECT *
                FROM {enrol_oes_teachers} tea
                WHERE tea.universal_id = "' . $teacher->Instructor_ID . '"
                OR tea.email = "' . $teacher->Instructor_Email . '"';

        $teas = $DB->get_records_sql($sql);
        if (count($teas) > 1) {
            foreach ($teas as $tea) {
                mtrace('DB ID: ' . $tea->id . ', ' .
                    $tea->universal_id . ', ' .
                    $tea->email . ',' .
                    $tea->firstname . ' ' .
                    $tea->lastname . ' is a dupe of remote user ' .
                    $teacher->Instructor_ID . ', ' .
                    $teacher->Instructor_Email . ', ' .
                    $teacher->Instructor_First_Name . ' ' .
                    $teacher->Instructor_Last_Name . '.');
            }

            // Do not process this teacher.
            $tea = null;

        } else if (count($teas) == 1) {
            // Grab the teacher from the array.
            self::dtrace(" User found with universal_id: $teacher->Instructor_ID, email: $teacher->Instructor_Email.");
            $tea = reset($teas);

        } else {
            self::dtrace(" No user with universal_id: $teacher->Instructor_ID, email: $teacher->Instructor_Email.");
            $tea = null;
        }

        return $tea;
    }

    public static function update_iteacher($s, $tea, $teacher) {
        global $DB;

        // Set the table.
        $table = 'enrol_oes_teachers';

        // Build out the email.
        $email = $teacher->Instructor_Email;

        // Build the two objects to compare.
        $tea1 = clone $tea;
        $tea2 = new stdClass();

        // Un/populate the two objects.
        unset($tea1->id);
        unset($tea1->username);
        unset($tea1->userid);
        unset($tea1->school_id);
        unset($tea1->preferred_firstname);
        unset($tea1->preferred_lastname);
        unset($tea1->middlename);
        unset($tea1->lastupdate);
        $tea2->universal_id = $teacher->Instructor_ID;
        $tea2->email = $email;
        $tea2->firstname = $teacher->Instructor_First_Name;
        $tea2->lastname = $teacher->Instructor_Last_Name;

        // If the objects match.
        if ($tea1 == $tea2) {
            self::dtrace(" User objects match, no update necessary.");

            // Return the original teacher.
            return false;
        } else {
            // Set the id.
            $tea2->id = $tea->id;

            // Set the datestamp.
            $tea2->lastupdate = time();

            // Update the record.
            $iteacher = $DB->update_record($table, $tea2);

            self::dtrace(" Updated user with universal_id: $tea->universal_id - $teacher->Instructor_ID, email: $tea->email - $email.");

            return $iteacher;
        }
    }

    public static function create_iteacher($s, $teacher) {
        global $DB;

        // Build out the email query.
        if (isset($teacher->Instructor_Email)) {
            $email = $teacher->Instructor_Email;
        } else {
            mtrace(" Failed to create interstitial user. No email provided.");
            mtrace($teacher->Instructor_ID);
            return false;
        }

        // Set the table;
        $table = 'enrol_oes_teachers';

        // Build the object.
        $data = new stdClass();
        $data->universal_id = $teacher->Instructor_ID;
        $data->email = $email;
        $data->username = $email;
        $data->userid = null;
        $data->firstname = $teacher->Instructor_First_Name;
        $data->lastname = $teacher->Instructor_Last_Name;
        $data->lastupdate = time();

        $success = $DB->insert_record($table, $data, true);

        if (is_int($success)) {
            $tea = $DB->get_record($table, array('id' => $success));
            self::dtrace(" Created user with universal_id: $tea->universal_id, email: $tea->email.");
            return $tea;
        } else {
            mtrace(" Failed to create interstitial user.");
            var_dump($teacher);
            return false;
        }
    }

    public static function insert_update_teacher_enrollment($sectionid, $universalid, $role, $status) {
        global $DB;
        $table = 'enrol_oes_enrollments';

//        if (is_null($universalid) && is_null($role)) {

            $usql = 'SELECT * FROM {enrol_oes_enrollments} e
                    WHERE e.section_listing_id = "' . $sectionid . '"
                        AND (e.status = "enroll" OR e.status = "enrolled")
                        AND (e.role = "teacher" OR e.role = "primary")';

            $uenrs = $DB->get_records_sql($usql);

            $unenrolls = array();
            if (!empty($uenrs)) {
                foreach ($uenrs as $uenr) {
                    $sql = 'UPDATE {enrol_oes_enrollments} e
                                SET e.status = "unenroll",
                                    e.prevstatus = "' . $uenr->status . '",
                                    e.role = "' . $uenr->role . '",
                                    e.prevrole = "' . $uenr->role . '"
                            WHERE e.section_listing_id = "' . $sectionid . '"
                                AND e.universal_id = "' . $uenr->universal_id . '"
                                AND (e.status = "enroll" OR e.status = "enrolled")
                                AND (e.role = "teacher" OR e.role = "primary")';
                    $unenrolls[] = $DB->execute($sql);
                    self::dtrace("  $uenr->universal_id set to unenroll in $sectionid.");
                }

                self::dtrace(" All enrolled teachers in $sectionid set to unenroll.");
            }
//            return $unenrolls;
//        }

        if (!is_null($universalid) && !is_null($role)) {

            $parm = array('section_listing_id' => $sectionid, 'universal_id' => $universalid);

            $enr = $DB->get_record($table, $parm);

            if (!$enr) {
                $data = new stdClass();

                $data->universal_id = $universalid;
                $data->section_listing_id = $sectionid;
                $data->role = $role;
                $data->status = $status;

                $enroll = $DB->insert_record($table, $data, true);
                self::dtrace(" - Inserted $universalid in $sectionid with role: $data->role and status: $data->status");

                return $enroll;
            } else {
                $data = clone($enr);

                $data->universal_id = $universalid;
                $data->section_listing_id = $sectionid;
                $data->prevrole = $enr->role;
                $data->role = $role;
                $data->status = $status;
                if ($enr->status == 'unenroll' && ($enr->prevstatus == 'enroll' || $enr->prevstatus == 'enrolled')) {
                    $data->prevstatus = $data->prevstatus;
                } else {
                    $data->prevstatus = $enr->status;
                }

                // Compare the objects.
                if ($data == $enr) {
                    self::dtrace(" - Enrollment entry: $data->id matches exactly, skipping.");

                    return $enr;
                } else {
                    $enroll = $DB->update_record($table, $data, true);
                    self::dtrace(" - Updated: $data->id - $universalid in $sectionid with role: $data->role and status: $data->status");

                    return $enroll;
                }
            }
        }
    //    self::dtrace(" - User: $universalid present in $sectionid with role: $enr->role and status: $enr->status");
    }

    /**
     * Finds similar objects.
     *
     * @param  @object $obj1
     * @param  @object $obj2
     *
     * @return @float $similarity
     */
    public static function wdstu_compareobjects($obj1, $obj2) {
        // Get the object variables as arrays for each of the objects.
        $properties1 = get_object_vars($obj1);
        $properties2 = get_object_vars($obj2);

        // Set this up for later.
        $countsimilar = 0;

        // Count the total object variables across both objects.
        $counttotal = count($properties1) + count($properties2);

        // Loop through the properties for 1st object and find matching values.
        foreach ($properties1 as $key => $value) {
            // If we find a match, increment the similarity count.
            if (isset($properties2[$key]) && $properties2[$key] == $value) {
                $countsimilar++;
            }
        }

        // Get the similarity percentage.
        $similarity = round(($countsimilar / $counttotal) * 100, 2);

        // Return the similarity percentage.
        return $similarity;
    }

    public static function dtrace($message) {
        global $CFG;
// TODO: CHANGE debugdisplay TO 1.
        if ($CFG->debugdisplay == 1) {
            $mtrace = mtrace($message);
            return $mtrace;
        } else {
            self::$dtc++;
            if (self::$dtc % 50 === 0) {
                $mtrace = print(".\n");
            } else {
                $mtrace = print(".");
            }
            return $mtrace;
        }
    }

    /**
     * Contructs and sends error emails using Moodle functionality.
     *
     * @package   enrol_workdaystudent
     *
     * @param     @object $emaildata
     * @param     @object $s
     *
     * @return    @bool
     */
    public static function send_wdstu_email($emaildata, $s) {
        global $CFG, $DB;

        // Get email subject from email log.
        $emailsubject = $emaildata->subject;

        // Get email content from email log.
        $emailcontent = $emaildata->body;

        // Grab the list of usernames from Moodle.
        $usernames = explode(",", $s->contacts);

        // Set up the users array.
        $users = array();

        // Loop through the usernames and add each user object to the user array.
        foreach ($usernames as $username) {

            // Make sure we have no spaces.
            $username = trim($username);

            // Add the user object to the array.
            $users[] = $DB->get_record('user', array('username' => $username));
        }

        // Send an email to each of the above users.
        foreach ($users as $user) {

            // Email the message.
            email_to_user($user,
                get_string("workdaystudent_emailname", "enrol_workdaystudent"),
                $emailsubject . " - " . $CFG->wwwroot,
                $emailcontent);
        }
    }
}

/*
class enrol_workdaystudent extends enrol_plugin {

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
/*
    public static function add_enroll_instance($course) {
        return $instance;
    }
}
*/
