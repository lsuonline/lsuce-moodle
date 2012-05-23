<?php

class block_post_grades_renderer extends plugin_renderer_base {
    public function confirm_period($course, $group, $period) {
        $a = new stdClass;
        $a->post_type = get_string($period->post_type, 'block_post_grades');
        $a->fullname = $course->fullname;
        $a->name = $group->name;

        if (post_grades::already_posted($course, $group, $period)) {
            $msg = get_string('alreadyposted', 'block_post_grades', $a);

            $sheet_url = get_config('block_post_grades', 'mylsu_gradesheet_url');
            $str = get_string('view_gradsheet', 'block_post_grades');
            $post = new single_button(new moodle_url($sheet_url), $str, 'get');
        } else {
            $msg = get_string('message', 'block_post_grades', $a);

            $post_url = new moodle_url('/blocks/post_grades/postgrades.php', array(
                'courseid' => $course->id,
                'groupid' => $group->id,
                'periodid' => $period->id
            ));

            $str = get_string('post_type_grades', 'block_post_grades', $a);
            $post = new single_button($post_url, $str, 'post');
        }

        $gradebook_url = new moodle_url('/grade/report/grader/index.php', array(
            'id' => $course->id, 'group' => $group->id
        ));

        $str = get_string('make_changes', 'block_post_grades', $a);
        $gradebook = new single_button($gradebook_url, $str, 'get');

        $cancel_url = new moodle_url('/course/view.php', array('id' => $course->id));
        $cancel = new single_button($cancel_url, get_string('cancel'));

        $out = $this->output->box_start('generalbox', 'notice');
        $out .= html_writer::tag('p', $msg);
        $out .= html_writer::tag('div',
            $this->output->render($post) .
            $this->output->render($gradebook) .
            $this->output->render($cancel),
            array('class' => 'buttons')
        );
        $out .= $this->output->box_end();
        return $out;
    }
}
