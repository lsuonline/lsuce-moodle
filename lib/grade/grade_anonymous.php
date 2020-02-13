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

class grade_anonymous extends grade_object {
    public $table = 'grade_anon_items';

    public static $profileid;

    var $required_fields = array('id', 'itemid', 'complete');

    var $id;

    var $itemid;

    var $complete = false;

    var $grade_item;

    var $adjust_boundary;

    public static function fetch($params) {
        return grade_object::fetch_helper(
            'grade_anon_items', 'grade_anonymous', $params
        );
    }

    public static function fetch_all($params) {
        return grade_object::fetch_all_helper(
            'grade_anon_items', 'grade_anonymous', $params
        );
    }

    public function load_item() {
        if (empty($this->grade_item) and !empty($this->itemid)) {
            $this->grade_item = grade_item::fetch(array('id' => $this->itemid));
        }

        return $this->grade_item;
    }

    public function load_grade($userid, $default=true) {
        if (empty($this->itemid) or empty($this->id)) {
            return array();
        }

        $grade = grade_anonymous_grade::fetch(array(
            'anonymous_itemid' => $this->id,
            'userid' => $userid
        ));

        if (!$grade and $default) {
            $instance = new stdClass;

            $instance->anonymous_itemid = $this->id;
            $instance->userid = $userid;

            $grade = new grade_anonymous_grade($instance, false);
        }

        if ($grade) {
            // TODO: rethink db... rawgrade plus itemid?
            $grade->anonymous_item = $this;
            $grade->grade_item = $this->load_item();
            $grade->itemid = $grade->grade_item->id;
            $grade->rawgrade = $grade->finalgrade;
        }

        return $grade;
    }

    public function update_final_grade($userid, $finalgrade=false, $source=null, $feedback=false, $feedbackformat=FORMAT_MOODLE, $usermodified=null) {
        $grade = $this->load_grade($userid);

        if (!$this->is_completed()) {
            // Clients of API should be mindful of scales; empty scale is -1
            if ($grade->id and empty($finalgrade)) {
                return $grade->delete($source);
            }

            $grade->finalgrade = $this->bounded_grade($finalgrade);
            return $grade->id ? $grade->update($source) : $grade->insert($source);
        } else {
            $grade->adjust_value = $finalgrade ?
                $grade->bound_adjust_value($finalgrade) : 0;

            $grade->update($source);

            return $this->load_item()->update_final_grade(
                $userid, $this->bounded_grade($grade->real_grade()), $source,
                $feedback, $feedbackformat, $usermodified
            );
        }
    }

    public function check_completed($course_users, $graded_users) {
        global $DB, $COURSE;

        $courseid = $COURSE->id;
        $profileid = self::anonymous_profile();
        if (empty($profileid)) {
            return array();
        }

        $params = array('fieldid' => $profileid);

        $sql = 'SELECT DISTINCT(d.userid) AS id, d.data FROM {user_info_data} d
                INNER JOIN {user_enrolments} ue ON d.userid = ue.userid
                INNER JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = ' . $courseid . '
                INNER JOIN {enrol_ues_students} stu ON stu.userid = d.userid
                WHERE ue.status = 1 AND stu.status = "unenrolled" AND d.fieldid = :fieldid ORDER BY d.data' ;

        $suspended_users = $DB->get_records_sql($sql, $params);

        $anon_users = $this->anonymous_users($graded_users);
        $course_count = count($course_users) - count($suspended_users);
        $userids = implode(',', array_keys($course_users));
        $select = 'userid IN (' . $userids . ') AND anonymous_itemid = :itemid AND finalgrade IS NOT NULL AND finalgrade <> ""';
        $params = array('itemid' => $this->id);
        $count = $DB->count_records_select('grade_anon_grades', $select, $params);

        if (count($anon_users) != $count) {
            return false;
        }
        return $course_count == $count;
    }

    public function is_completed() {
        return $this->complete;
    }

    public function set_completed($status = true) {
        $this->complete = $status;
        $this->update();

        if ($this->complete) {
            $grades = grade_anonymous_grade::fetch_all(array(
                'anonymous_itemid' => $this->id
            ));

            foreach ($grades as $grade) {
                $this->load_item()->update_final_grade(
                    $grade->userid, $grade->real_grade()
                );
            }
        } else {
            $this->load_item()->delete_all_grades();
        }
    }

    public function adjust_boundary() {
        global $CFG;

        if (is_null($this->adjust_boundary)) {
            $this->adjust_boundary = (float) grade_get_setting(
                $this->load_item()->courseid,
                'anonymous_adjusts',
                $CFG->grade_anonymous_adjusts
            );
        }

        return $this->adjust_boundary;
    }

    public static function anonymous_profile() {
        global $DB;

        if (empty(self::$profileid)) {
            $fields = $DB->get_records('user_info_field');

            if (empty($fields)) {
                debugging('No user profile fields to choose from.');
                return false;
            }

            $fieldid = get_config('moodle', 'grade_anonymous_field');

            $fieldid = empty($fieldid) ? reset($fields)->id : $fieldid;

            if (!isset($fields[$fieldid])) {
                debugging('Selected anonymous profile field does not exists.');
                return false;
            }

            self::$profileid = $fieldid;
        }

        return self::$profileid;
    }

    public static function anonymous_users($real_users) {
        global $DB, $COURSE;
        $courseid = $COURSE->id;
        $profileid = self::anonymous_profile();

        if (empty($profileid)) {
            return array();
        }

        $userids = implode(',', array_keys($real_users));

        $sql = 'SELECT DISTINCT(d.userid) AS id, d.data FROM {user_info_data} d
            INNER JOIN {user_enrolments} ue ON d.userid = ue.userid
            INNER JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = ' . $courseid . '
            INNER JOIN {enrol_ues_students} stu ON stu.userid = d.userid
            WHERE d.userid IN (' . $userids.')
              AND ue.status = 0
              AND stu.status = "enrolled"
              AND d.fieldid = :fieldid ORDER BY d.data' ;

        $params = array('fieldid' => $profileid);
        $anonymous_users = $DB->get_records_sql($sql, $params);

        return $anonymous_users;
    }

    public static function is_supported($course) {
        // Enabled system wide?
        $enabled = (bool)get_config('moodle', 'grade_anonymous_grading');

        $cats = explode(',', get_config('moodle', 'grade_anonymous_cats'));

        $is_cat = (empty($cats) or in_array($course->category, $cats));

        return ($enabled and $is_cat);
    }

    public function delete($source = null) {
        $params = array('anonymous_itemid' => $this->id);

        if ($grades = grade_anonymous_grade::fetch_all($params)) {
            foreach ($grades as $grade) {
                $grade->delete($source);
            }
        }
        return parent::delete($source);
    }

    public function __call($name, $args) {
        if (!method_exists($this->load_item(), $name)) {
            print_error('anonymousnomethod', 'grades', '', $name);
        }

        return call_user_func_array(array($this->load_item(), $name), $args);
    }

    public function __get($name) {
        if (isset($this->load_item()->$name)) {
            return $this->load_item()->$name;
        }

        return null;
    }
}

class grade_anonymous_grade extends grade_object {
    public $table = 'grade_anon_grades';

    var $required_fields = array(
        'id', 'userid', 'anonymous_itemid', 'finalgrade', 'adjust_value'
    );

    var $adjust_value = 0;

    var $anonymous_itemid;

    var $anonymous_item;

    var $userid;

    var $finalgrade;

    var $rawgrade;

    var $itemid;

    var $grade_item;

    var $underlying;

    public static function fetch($params) {
        return grade_object::fetch_helper(
            'grade_anon_grades', 'grade_anonymous_grade', $params
        );
    }

    public static function fetch_all($params) {
        return grade_object::fetch_all_helper(
            'grade_anon_grades', 'grade_anonymous_grade', $params
        );
    }

    public function load_item() {
        if (empty($this->anonymous_item)) {
            $params = array('id' => $this->anonymous_itemid);
            $this->anonymous_item = grade_anonymous::fetch($params);
        }

        return $this->anonymous_item;
    }

    public function load_grade_item() {
        if (empty($this->grade_item)) {
            $this->grade_item = $this->load_item()->load_item();
        }

        return $this->grade_item;
    }

    public function load_grade() {
        if ($this->load_item()->is_completed() and empty($this->underlying)) {
            $this->underlying = grade_grade::fetch(array(
                'userid' => $this->userid,
                'itemid' => $this->load_item()->itemid
            ));
        }

        return $this->underlying;
    }

    public function real_grade() {
        return $this->finalgrade + (float)$this->adjust_value;
    }

    public function anonymous_number() {
        global $DB;

        $params = array(
            'userid' => $this->userid,
            'fieldid' => $this->load_item()->anonymous_profile()
        );

        return $DB->get_field('user_info_data', 'data', $params);
    }

    public function bound_adjust_value($value) {
        $max = abs($this->load_item()->adjust_boundary());
        $min = -1 * $max;

        if ($value < $min) {
            return $min;
        } else if ($value > $max) {
            return $max;
        } else {
            return $value;
        }
    }

    public function __call($name, $args) {
        if ($u = $this->load_grade() and method_exists($u, $name)) {
            return call_user_func_array(array($u, $name), $args);
        }

        return null;
    }
}
