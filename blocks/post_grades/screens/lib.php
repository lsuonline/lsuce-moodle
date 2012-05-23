<?php

// Screens can implement this interface if the the screen requires
// certain course data before application
interface post_filtered {
    public function can_post($section);
}

abstract class post_grades_screen {
    function is_ready() {
        return true;
    }

    abstract function html();
}

abstract class post_grades_student_table extends post_grades_screen {
    function __construct($period, $course, $group) {
        $this->course = $course;
        $this->period = $period;
        $this->group = $group;

        $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        $graded = get_config('moodle', 'gradebookroles');

        $this->students = get_role_users(explode(',', $graded), $context,
            false, '', 'u.lastname, u.firstname', null, $this->group->id);
    }

    abstract function is_acceptable($student);

    function html() {
        $table = new html_table();

        $table->head = array(
            get_string('fullname'),
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

            $name = "$student->lastname, $student->firstname";
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
