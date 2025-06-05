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
 * Search functionality for the Sports Grades block.
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sportsgrades;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for searching student athletes.
 */
class search {
    
    /**
     * Search for student athletes based on criteria.
     * 
     * @param array $parms Search parameters
     * @return array Search results
     */
    public static function search_students($parms) {
        global $DB, $USER;

        // Let's work with arrays.
        $parms = json_decode(json_encode($parms), true);
        
        // Check if user has access to any sports or specific students.
        $access = self::get_user_access($USER->id);
        
        if (empty($access)) {
            return ['error' => get_string('noaccess', 'block_sportsgrades')];
        }
        
        // Build the SQL query.
        $sqlselect = "SELECT CONCAT(sm.id, '-', u.id) as uniqueid,
            sm.id AS stumetaid,
            u.id AS userid,
            stu.id AS studentid,
            u.username,
            u.firstname,
            u.lastname,
            u.email,
            stu.universal_id,
            p.sport,
            p.college,
            p.major,
            p.classification";
        
        $sqlfrom = " FROM mdl_user u
            INNER JOIN mdl_enrol_wds_students stu
                ON u.id = stu.userid
            INNER JOIN mdl_enrol_wds_students_meta sm
                ON sm.studentid = stu.id
                AND sm.academic_period_id = 'LSUAM_SUMMER_1_2025'
            INNER JOIN (
                SELECT stumeta.studentid,
                    GROUP_CONCAT(CASE WHEN datatype = 'Athletic_Team_ID' THEN data ELSE NULL END) AS sport,
                    GROUP_CONCAT(CASE WHEN datatype = 'Academic_Unit_Code' THEN data ELSE NULL END) AS college,
                    GROUP_CONCAT(CASE WHEN datatype = 'Program_of_Study_Code' THEN data ELSE NULL END) AS major,
                    GROUP_CONCAT(CASE WHEN datatype = 'Classification' THEN data ELSE NULL END) AS classification
                FROM mdl_enrol_wds_students_meta stumeta
                WHERE stumeta.academic_period_id = 'LSUAM_SUMMER_1_2025'
                GROUP BY stumeta.studentid
                    ) p ON p.studentid = stu.id";
        
        $conditions = [];
        $parmssql = [];
        
        // Filter by access permissions.
        if (!empty($access['sports']) && !$access['all_students']) {
            $sportplaceholders = [];
            $i = 0;
            foreach ($access['sports'] as $sportcode) {
                $parmname = 'sport' . $i;
                $sportplaceholders[] = ':' . $parmname;
                $parmssql[$parmname] = $sportcode;
                $i++;
            }
            $conditions[] = "sm.data IN (" . implode(',', $sportplaceholders) . ")";
        }
        
        if (!empty($access['student_ids']) && !$access['all_students']) {
            $studentplaceholders = [];
            $i = 0;
            foreach ($access['student_ids'] as $studentid) {
                $parmname = 'student' . $i;
                $studentplaceholders[] = ':' . $parmname;
                $parmssql[$parmname] = $studentid;
                $i++;
            }
            
            if (!empty($conditions)) {
                $conditions[] = "OR u.id IN (" . implode(',', $studentplaceholders) . ")";
            } else {
                $conditions[] = "u.id IN (" . implode(',', $studentplaceholders) . ")";
            }
        }
        
        // Apply search filters.
        if (!empty($parms['universal_id'])) {
            $conditions[] = "stu.universal_id LIKE :universal_id";
            $parmssql['universal_id'] = '%' . $parms['universal_id'] . '%';
        }
        
        if (!empty($parms['username'])) {
            $conditions[] = "u.username LIKE :username";
            $parmssql['username'] = '%' . $parms['username'] . '%';
        }
        
        if (!empty($parms['firstname'])) {
            $conditions[] = "u.firstname LIKE :firstname";
            $parmssql['firstname'] = '%' . $parms['firstname'] . '%';
        }
        
        if (!empty($parms['lastname'])) {
            $conditions[] = "u.lastname LIKE :lastname";
            $parmssql['lastname'] = '%' . $parms['lastname'] . '%';
        }
        
        if (!empty($parms['major'])) {
            $conditions[] = "p.major LIKE :major";
            $parmssql['major'] = '%' . $parms['major'] . '%';
        }
        
        if (!empty($parms['classification'])) {
            $conditions[] = "p.classification = :classification";
            $parmssql['classification'] = $parms['classification'];
        }
        
        if (!empty($parms['sport'])) {
            $conditions[] = "sm.data = :sport";
            $parmssql['sport'] = $parms['sport'];
        }
        
        // Build the WHERE clause.
        $sqlwhere = !empty($conditions) ? " WHERE " . implode(' AND ', $conditions) : "";
        
        // Add ORDER BY clause to sort results.
        $sqlorder = " ORDER BY u.lastname ASC, u.firstname ASC";
        
        // Execute the query.
        $sql = $sqlselect . $sqlfrom . $sqlwhere . $sqlorder;      

        try {
            $students = $DB->get_records_sql($sql, $parmssql);
            
            // Process results to add sports information.
            $results = [];
            $processed_student_ids = [];
            
            foreach ($students as $student) {
                // Only process each student once to avoid duplicates.
                if (in_array($student->studentid, $processed_student_ids)) {
                    continue;
                }
                
                // Get sports for each student.
                $sports = self::get_student_sports($student->studentid);
                
                $student->sports = $sports;
                $results[] = $student;
                
                // Mark this student as processed.
                $processed_student_ids[] = $student->studentid;
            }
            
            return ['success' => true, 'results' => array_values($results)];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get sports that a student is enrolled in.
     * 
     * @param int $studentid User ID of the student
     * @return array Array of sport objects with id, code, and name
     */
    public static function get_student_sports($studentid) {
        global $DB;

$studentid = 38295;

        $sql = "SELECT CONCAT(sm.id, '-', s.id) as uniqueid, s.id, s.code, s.name 
            FROM {enrol_wds_sport} s
            INNER JOIN {enrol_wds_students_meta} sm
                ON sm.data = s.code
                AND sm.datatype = 'Athletic_Team_ID'
                AND sm.academic_period_id = 'LSUAM_SUMMER_1_2025'
            WHERE sm.studentid = :studentid 
            GROUP BY uniqueid
            ORDER BY s.name ASC";
        
        $sports = $DB->get_records_sql($sql, ['studentid' => $studentid]);

/*
echo"<pre>";
var_dump($sql);
var_dump($studentid);
var_dump($sports);
echo"</pre>";
die();
*/

        
        // Transform the result to use the sport id as the key.
        $result = [];
        foreach ($sports as $sport) {
            $result[$sport->id] = (object)[
                'id' => $sport->id,
                'code' => $sport->code,
                'name' => $sport->name
            ];
        }
        
        return $result;
    }
    
    /**
     * Get access permissions for a user.
     * 
     * @param int $userid User ID
     * @return array Access permissions
     */
    public static function get_user_access($userid) {
        global $DB, $USER;
        
        $access = [
            'sports' => [],
            'student_ids' => [],
            'all_students' => false
        ];
        
        // Check if user is in the hardcoded list of allowed users.
        $allowed_users = [
            'rrusso33',
        ];
        
        // Admin can access all students.
        if ($USER->username == 'admin') {
            $access['all_students'] = true;
            return $access;
        }
        
        // Check if user is in the hardcoded list.
        if (in_array($USER->username, $allowed_users)) {
            $access['all_students'] = true;
            return $access;
        }
        
        // Get sports that the user has access to through the mentors table.
        $sports_mentors = $DB->get_records('enrol_sports_mentors', ['userid' => $userid]);
        foreach ($sports_mentors as $mentor) {
            $access['sports'][] = $mentor->path; // path is sport code.
        }
        
        // Get specific students that the user has access to.
        $person_mentors = $DB->get_records('enrol_person_mentors', ['userid' => $userid]);
        foreach ($person_mentors as $mentor) {
            $access['student_ids'][] = $mentor->path; // path is student userid.
        }
        
        return $access;
    }
}
