<?php

class block_post_grades_renderer extends plugin_renderer_base {
    private function build_legend($compliance, $area_lenged) {
        $content = html_writer::start_tag('div', array('class' => 'law_grade_legend_row_container'));

        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', '# Students', array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $compliance->total, array('class' => 'law_grade_legend'));
        $content .= html_writer::end_tag('div');

        $total_num = $compliance->total - (
            $compliance->info['pass']->total +
            $compliance->info['fail']->total
        );
        $middle_percent = round(($total_num / $compliance->total) * 100, 2);

        $middle = $compliance->grading['fail']->value . ' - ' .
            $compliance->grading['pass']->value;

        $_s = ues::gen_str('block_post_grades');

        $content .= $area_lenged;
        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', $middle, array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $middle_percent, array('class' => 'law_grade_legend'));

        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', $_s('mean'), array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $compliance->mean, array('class' => 'law_grade_legend'));
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', $_s('median'), array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $compliance->median, array('class' => 'law_grade_legend'));
        $content .= html_writer::end_tag('div');

        $content .= html_writer::end_tag('div');

        return $content;
    }

    private function compliance_bars($value, $compliant) {
        $content = html_writer::start_tag('div', array('class' => 'law_grade_bar'));

        foreach (range(0,9) as $index) {
            if ($index < 3) {
                $class = 'blank';
            } else if ($compliant) {
                $class = 'compliant';
            } else {
                $class = 'not_compliant';
            }

            $param = array('class' => 'law_grade_bar_inner ' . $class);

            $content .= html_writer::start_tag('div',
                array('class' => 'law_grade_bar_row'));

            $content .= html_writer::tag('div', '&nbsp;', $param);
            $content .= html_writer::tag('div',
                $index == 5 ? html_writer::tag('span', $value, array('style' => 'color: #eee')) : '&nbsp;',
                $param);
            $content .= html_writer::tag('div', '&nbsp;', $param);

            $content .= html_writer::end_tag('div');
        }

        $content .= html_writer::end_tag('div');
        return $content;
    }

    private function build_bars($percents, $compliance, $grade, $actual) {
        $range = range(0, count($percents));
        $selectors = array_slice(array_reverse($range), 1);

        $is = $compliance->check($actual->percent, $grade->lower, $grade->upper) ?
            'compliant' : 'not_compliant';

        $areas = array(
            'lower' => $grade->lower / 5,
            'actual' => $actual->percent / 5,
            'upper' => $grade->upper / 5
        );

        $content = html_writer::start_tag('div', array('class' => 'law_grade_bar'));
        foreach ($selectors as $number) {
            $inner = '';
            foreach ($areas as $area => $value) {
                $class = $value >= $number ? $area : 'blank';
                $extra = ($area == 'actual' and $class != 'blank') ?
                    $class . ' ' . $is : $class;

                $inner .= html_writer::tag('div', '&nbsp;',
                    array('class' => 'law_grade_bar_inner ' . $extra));
            }
            $content .= html_writer::tag('div', $inner,
                array('class' => 'law_grade_bar_row'));
        }
        $content .= html_writer::end_tag('div');

        return $content;
    }

    public function display_graph($compliance, $use_title = true) {

        $title = $compliance->get_explanation();

        $return = $use_title && !empty($title) ? $this->notification($title) : '';

        if ($compliance instanceof post_grades_return_graphable) {
            $info = $compliance->get_calc_info();
            $grading = $compliance->get_grading_info();

            $percents = range(0, 45, 5);
            $percent_size = count($percents);

            $headers = array();
            $bars_row = array();
            $legend_row = '';

            foreach ($info as $area => $spec) {
                // Start building the table
                $title = $grading[$area]->operator . ' ' . $grading[$area]->value;
                $percentage = round(($spec->total / $compliance->total) * 100, 2);
                $headers[] = $title;
                $bars_row[] = $this->build_bars($percents, $compliance, $grading[$area], $spec);
                $legend_row .= html_writer::tag('div',
                    html_writer::tag('div', $title,
                    array('class' => 'law_grade_legend title')) .
                    html_writer::tag('div', $percentage,
                    array('class' => 'law_grade_legend'))
                    , array('class' => 'law_grade_legend_row')
                );
            }

            $mean_str = get_string('mean', 'block_post_grades');
            $median_str = get_string('median', 'block_post_grades');

            $table = new html_table();
            $table->attributes['class'] = 'generaltable jd_curve';

            $table->head = array_merge(
                array($median_str, $mean_str), $headers, array('')
            );

            $mean_median_bars = array(
                $this->compliance_bars($compliance->median, $compliance->median_compliance),
                $this->compliance_bars($compliance->mean, $compliance->mean_compliance)
            );

            $data = array(
                array_merge($mean_median_bars, $bars_row,
                array($this->build_legend($compliance, $legend_row)))
            );

            $table->data = $data;

            $return .= html_writer::tag('div', html_writer::table($table),
                array('class' => 'jd_curve_graph'));
        }

        return $return;
    }

    public function confirm_return(post_grades_return_process $return, $use_continue = true) {
        try {
            $processed = $return->process();

            if (is_array($processed)) {
                foreach ($processed as $title => $compliance) {
                    echo $this->heading($title);
                    echo $this->display_graph($compliance);
                }
                if ($use_continue) {
                    echo $this->continue_button($return->get_url(null));
                }
            } else if ($processed instanceof post_grades_compliance) {
                echo $this->display_graph($processed);
                if ($use_continue) {
                    echo $this->continue_button($return->get_url(null));
                }
            } else {
                echo $this->box_start();
                echo $this->notification($return->get_explanation());
                echo $this->continue_button($return->get_url($processed));
                echo $this->box_end();
            }
        } catch (Exception $e) {
            echo $this->notification($e->getMessage());
        }
    }

    public function confirm_period($course, $group, $period) {
        $a = new stdClass;
        $a->post_type = get_string($period->post_type, 'block_post_grades');
        $a->fullname = $course->fullname;
        $a->name = $group->name;

        if (post_grades::already_posted($course, $group, $period)) {
            $msg = get_string('alreadyposted', 'block_post_grades', $a);

            $key = preg_match('/law/', $period->post_type) ?
                'law_mylsu_gradesheet_url' : 'mylsu_gradesheet_url';

            $sheet_url = get_config('block_post_grades', $key);

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
