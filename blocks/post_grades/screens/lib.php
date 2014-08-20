<?php

// Screens can implement this interface if the the screen requires
// certain course data before application
interface post_filtered {
    public function can_post($section);
}

abstract class post_grades_screen {
    function is_law() {
        $class = get_class($this);
        return preg_match('/law/', $class) ? true : false;
    }

    function get_return_state() {
        require_once dirname(__FILE__) . '/returnlib.php';

        if (!$this->is_law()) {
            return new post_grades_good_return();
        }

        $_s = ues::gen_str('block_post_grades');

        // Will need this later
        $sections = ues_section::from_course($this->course, true);
        $section = post_grades::find_section($this->group, $sections);

        // Filter Audits at teh screen level
        $auditers = post_grades::pull_auditing_students($section);
        foreach ($auditers as $audit) {
            unset($this->students[$audit->id]);
        }

        // Shim for 1.9
        $course = $section->course()->fill_meta();

        if ($course->course_grade_type == 'LP') {
            $scale = get_config('block_post_grades', 'scale');

            $course_item = grade_item::fetch_course_item($this->course->id);

            // Force scale always for pass/fail courses
            if ($course_item->scaleid != $scale) {
                $course_item->gradetype = 2;
                $course_item->scaleid = $scale;
                $course_item->gradepass = 2.0;

                $course_item->update();
            }
        }

        $passthrough = array('CLI', 'IND');

        $legal_writing = !empty($course->course_legal_writing);
        $exception = !empty($course->exception);

        if ($course->course_type == 'SEM') {
            $return = new post_grades_no_item_return($this->course);
        } else if ($course->course_first_year and $legal_writing) {
            $return = new post_grades_no_item_return($this->course);

            if ($return->is_ready() and count($sections) > 1) {
                // Perform compliance on everyone
                $constructor = $this->constructor();
                $cloned = $constructor($this->period, $this->course, 0);

                $compliances = array(
                    new post_grades_class_size(
                        $cloned->students, $course, $this->course
                    ),
                    new post_grades_class_size(
                        $this->students, $course, $this->course
                    )
                );

                $titles = array(
                    $_s('course_compliance'),
                    $_s('section_compliance', $this->group->name)
                );

                return new post_grades_sequence_compliance(
                    $return, $compliances, $titles
                );
            }
        } else if ($course->course_first_year) {
            // Anonymous grade checks
            $return = new post_grades_no_anonymous_item_return($this->course);
        } else if ($course->course_grade_type == 'LP' or $exception or
            in_array($course->course_type, $passthrough)) {

            $return = new post_grades_no_item_return($this->course);
        } else {
            // Anonymous grade checks
            $return = new post_grades_no_anonymous_item_return($this->course);
        }

        return new post_grades_compliance_return($return, $this->students, $course);
    }

    abstract function html();
}

abstract class post_grades_student_table extends post_grades_screen {
    function __construct($period, $course, $group) {
        $this->course = $course;
        $this->period = $period;
        $this->group = $group;

        $groupid = empty($this->group) ? 0 : $this->group->id;

        $this->context = context_course::instance($this->course->id);
        $graded = get_config('moodle', 'gradebookroles');

        $this->students = get_role_users(explode(',', $graded), $this->context,
            false, '', 'u.lastname, u.firstname', null, $groupid);
    }

    function constructor() {
        $class = get_class($this);

        return function($period, $course, $group) use ($class) {
            return new $class($period, $course, $group);
        };
    }

    abstract function is_acceptable($student);

    function html() {
        $table = new html_table();

        $table->head = array(
            get_string('lastname') . ', ' . get_string('firstname') . ' (' . get_string('alternatename') . ')',
            get_string('idnumber'),
            get_string('grade', 'grades')
        );

        $course_item = grade_item::fetch(array(
            'itemtype' => 'course',
            'courseid' => $this->course->id
        ));

        foreach ($this->students as $student) {
            if (!$this->is_acceptable($student)) {
                continue;
            }

            $line = new html_table_row();

            if (isset($student->alternatename)) {
                $name = "$student->lastname, $student->alternatename ($student->firstname)";
            } else {
                $name = "$student->lastname, $student->firstname";
            }
            $url = new moodle_url('/grade/report/quick_edit/index.php', array(
                'item' => 'user',
                'itemid' => $student->id,
                'group' => $this->group->id,
                'id' => $this->course->id
            ));

            $grade_grade = grade_grade::fetch(array(
                'itemid' => $course_item->id,
                'userid' => $student->id
            ));

            if (empty($grade_grade)) {
                $grade_grade = new grade_grade();
                $grade_grade->finalgrade = null;
            }

            // Don't bother showing incompletes
            if ($grade_grade->is_overridden() and $grade_grade->finalgrade == null) {
                continue;
            }

            $line->cells[] = html_writer::link($url, $name);
            $line->cells[] = $student->idnumber;
            $line->cells[] = grade_format_gradevalue(
                $grade_grade->finalgrade,
                $course_item, true,
                $course_item->get_displaytype()
            );

            $table->data[] = $line;
        }

        if (empty($table->data)) {
            global $OUTPUT;
            $post = get_string($this->period->post_type, 'block_post_grades');
            $msg = get_string('no_students', 'block_post_grades', $post);
            return $OUTPUT->notification($msg);
        } else {
            return html_writer::table($table);
        }
    }
}
