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

defined('MOODLE_INTERNAL') || die;

class local_cas_help_links_db_seeder {

    public $startDate;
    public $endDate;
    public $hoursLeft;
    public $db;
    public $urls;
    public $studentUsers;
    public $studentUserCount;
    public $uesCourses;
    public $uesCourseCount;
    public $links;
    public $linkCount;

    public function __construct() {
        global $DB;

        $this->db = $DB;
        $this->urls = include('../files/sample_urls.php');
        $this->studentUsers = null;
        $this->studentUserCount = null;
        $this->uesCourses = null;
        $this->uesCourseCount = null;
        $this->links = null;
        $this->linkCount = null;
    }

    /**
     * Deletes all cas help link records
     *
     * @return void
     */
    public function clearLinks() {
        $this->db->delete_records('local_cas_help_links');
    }

    /**
     * Deletes all cas help link log records
     *
     * @return void
     */
    public function clearLogs() {
        $this->db->delete_records('local_cas_help_links_log');
    }

    /**
     * Inserts sample "category" cas help links into the DB
     *
     * @return bool
     */
    public function seedCategoryLinks() {
        // Renamed: $amountAdded to $amountadded.
        $amountadded = 0;

        // Get all course category ids.
        foreach ($this->getCategories() as $category) {
            // Renamed to $categoryId to $categoryid.
            $categoryid = (int) $category->id;

            // 80% chance a category will have a link.
            if (byChance(80)) {
                $this->insertLink('category', $categoryid);

                $amountadded++;
            }
        }

        return $amountadded;
    }

    /**
     * Inserts sample "course" cas help links into the DB
     *
     * @return bool
     */
    public function seedCourseLinks() {
        $amountadded = 0;

        // Get all course ids.
        foreach (get_courses() as $course) {
            $courseid = (int) $course->id;

            // 40% chance a course will have a link.
            if (byChance(40)) {
                $this->insertLink('course', $courseid);

                $amountadded++;
            }
        }

        return $amountadded;
    }

    /**
     * Inserts sample "user" cas help links into the DB
     *
     * @return bool
     */
    public function seedUserLinks() {
        $amountadded = 0;

        // Get all course ids.
        foreach ($this->getInstructorUsers() as $user) {
            $userid = (int) $user->id;

            // 20% chance a course will have a link.
            if (byChance(20)) {
                $this->insertLink('user', $userid);

                $amountadded++;
            }
        }

        return $amountadded;
    }

    /**
     * Inserts logging activity given a range of months into the DB
     *
     * @param string $monthrangestring  ex: 2016-4,2017-2
     * @return bool
     *
     * public function seedLog($monthRangeString) {
     *
     */
    public function seedLog($monthrangestring) {
        $success = false;
        $tickdate = $this->startDate = $this->getDateFromString('start', $monthrangestring);
        $this->endDate = $this->getDateFromString('end', $monthrangestring);
        $this->hoursLeft = $this->getHoursLeft();

        // Iterate through each hour in the range.
        while ($this->hoursLeft > 0) {
            // Calculate clicks this hour (between 0 - 100).
            $clicksthishour = mt_rand(0, 100);

            foreach (range(1, $clicksthishour) as $click) {
                // Get a random user id.
                $userid = $this->getRandomUserId();

                // Get a random UES course.
                $uescourse = $this->getRandomUesCourse();

                // Get a random link.
                $link = $this->getRandomLink();

                // TODO - randomize the specific time portion of timestamp.
                $this->insertLogRecord($userid, $link, $uescourse, $tickdate->getTimestamp());
            }

            // Add an hour to the current timestamp.
            $tickdate->add(new DateInterval('PT1H'));

            $this->hoursLeft--;
            $success = true;
        }

        return $success;
    }

    /**
     * Returns a random UES course,
     * also sets available course list and count if not already set
     *
     * @return object
     */
    private function getRandomUesCourse() {
        if (is_null($this->uesCourses)) {
            // TODO - make sure we're getting a real, active course.
            $this->uesCourses = array_values($this->getUesCourses());
            $this->uesCourseCount = count($this->uesCourses);
        }

        $course = $this->uesCourses[mt_rand(0, $this->uesCourseCount - 1)];

        return $course;
    }

    /**
     * Returns a random student user id,
     * also sets available student user list and count if not already set
     *
     * @return int
     */
    private function getRandomUserId() {
        if (is_null($this->studentUsers)) {
            $this->studentUsers = array_values($this->getStudentUsers());
            $this->studentUserCount = count($this->studentUsers);
        }

        $user = $this->studentUsers[mt_rand(0, $this->studentUserCount - 1)];

        return (int) $user->id;
    }

    /**
     * Returns a random cas help link,
     * also sets available link list and count if not already set
     *
     * @return object
     */
    private function getRandomLink() {
        if (is_null($this->links)) {
            $this->links = array_values($this->getLinks());
            $this->linkCount = count($this->links);
        }

        $link = $this->links[mt_rand(0, $this->linkCount - 1)];

        return $link;
    }

    /**
     * Inserts a generated link record of the given type and id
     *
     * @param  string $type  category|course|user
     * @param  int $id
     * @return int
     */
    private function insertLink($type, $id) {
        $identifier = $type . '_id';

        $link = new stdClass();
        $link->type = $type;
        $link->$identifier = $id;
        $link->display = (int) byChance(85);
        $link->link = $this->getRandomUrl();

        $id = $this->db->insert_record('local_cas_help_links', $link);

        return $id;
    }

    /**
     * Inserts a log record for the given parameters
     *
     * @param  int $userid (was userID)
     * @param  object $link
     * @param  object $uescourse (was uesCourse)
     * @param  int $timestamp
     * @return int
     */
    private function insertLogRecord($userid, $link, $uescourse, $timestamp) {
        // Changed $logRecord to $logrecord.
        $logrecord = new stdClass();
        $logrecord->user_id = $userid;
        $logrecord->time_clicked = $timestamp;
        $logrecord->link_type = $link->type;
        $logrecord->link_url = $link->link;
        $logrecord->course_dept = $uescourse->department;
        $logrecord->course_number = $uescourse->cou_number;

        $id = $this->db->insert_record('local_cas_help_links_log', $logrecord);

        return $id;
    }

    /**
     * Returns an array of objects containing category ids
     *
     * @return array
     */
    private function getCategories() {
        $catids = $this->db->get_records_sql('SELECT id FROM {course_categories} WHERE id != 1');

        return $catids;
    }

    /**
     * Returns an array of objects containing primary instructor user ids
     *
     * @return array
     */
    private function getInstructorUsers() {
        $result = $this->db->get_records_sql('SELECT DISTINCT u.id FROM {enrol_ues_teachers} t
            INNER JOIN {user} u ON u.id = t.userid
            INNER JOIN {enrol_ues_sections} sec ON sec.id = t.sectionid
            INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND cou.cou_number < "5000"
            AND t.primary_flag = "1"
            AND t.status = "enrolled"');

        return $result;
    }

    /**
     * Returns an array of objects containing student user ids
     *
     * @return array
     */
    private function getStudentUsers() {
        $result = $this->db->get_records_sql('SELECT DISTINCT u.id FROM {enrol_ues_students} s
            INNER JOIN {user} u ON u.id = s.userid
            INNER JOIN {enrol_ues_sections} sec ON sec.id = s.sectionid
            WHERE sec.idnumber IS NOT NULL
            AND sec.idnumber <> ""
            AND s.status = "enrolled"');

        return $result;
    }

    /**
     * Returns an array of objects containing ues courses
     *
     * @return array
     */
    private function getUesCourses() {
        $result = $this->db->get_records('enrol_ues_courses');

        return $result;
    }

    /**
     * Returns all cas help link records
     *
     * @return array
     */
    private function getLinks() {
        $result = $this->db->get_records('local_cas_help_links');

        return $result;
    }

    /**
     * Returns the difference in hours between the start and end date
     *
     * @return int
     */
    private function getHoursLeft() {
        $interval = $this->startDate->diff($this->endDate);

        return (int) $interval->format('%a') * 24;
    }

    /**
     * Returns a specific datetime for the start or end of a given "month range string"
     *
     * @param  string $date  start(default)|end
     * @param  string $rangestring  ex: 2016-4,2017-2
     * @return DateTime
     */
    private function getDateFromString($date = 'start', $rangestring) {
        list($start, $end) = explode(',', $rangestring);

        $day = $date == 'end' ? '28' : '1'; // TODO - calculate real last day of month.

        $time = $date == 'end' ? '11:59:59' : '00:00:00';

        $datetime = DateTime::createFromFormat('Y-n-j G:i:s', $$date . '-' . $day . ' ' . $time);

        return $datetime;
    }

    /**
     * Returns a random URL
     *
     * @return string
     */
    private function getRandomUrl() {
        $key = mt_rand(0, 9999);

        return $this->urls[$key];
    }

}

/**
 * Helper function for determining true/false based on a given chance of being true
 *
 * @param  int $pct  (ex: 40 = 40%)
 * @return bool
 */
function byChance($pct) {
    return mt_rand(1, 100) <= $pct;
}