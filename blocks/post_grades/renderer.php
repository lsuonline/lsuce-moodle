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

defined('MOODLE_INTERNAL') || die();

class block_post_grades_renderer extends plugin_renderer_base {
    private function build_legend($compliance, $arealenged) {
        $content = html_writer::start_tag('div', array('class' => 'law_grade_legend_row_container'));

        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', '# Students', array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $compliance->total, array('class' => 'law_grade_legend'));
        $content .= html_writer::end_tag('div');

        $totalnum = $compliance->total - (
            $compliance->info['pass']->total +
            $compliance->info['fail']->total
        );
        $middlepercent = round(($totalnum / $compliance->total) * 100, 2);

        $middle = $compliance->grading['fail']->value . ' - ' .
            $compliance->grading['pass']->value;

        $s = ues::gen_str('block_post_grades');

        $content .= $arealenged;
        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', $middle, array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $middlepercent, array('class' => 'law_grade_legend'));

        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', $s('mean'), array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $compliance->mean, array('class' => 'law_grade_legend'));
        $content .= html_writer::end_tag('div');

        $content .= html_writer::start_tag('div', array('class' => 'law_grade_legend_row'));
        $content .= html_writer::tag('div', $s('median'), array('class' => 'law_grade_legend title'));
        $content .= html_writer::tag('div', $compliance->median, array('class' => 'law_grade_legend'));
        $content .= html_writer::end_tag('div');

        $content .= html_writer::end_tag('div');

        return $content;
    }

    private function compliance_bars($value, $compliant) {
        $content = html_writer::start_tag('div', array('class' => 'law_grade_bar'));

        foreach (range(0, 9) as $index) {
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

    public function display_graph($compliance, $usetitle = true) {

        $title = $compliance->get_explanation();

        $return = $usetitle && !empty($title) ? $this->notification($title) : '';

        if ($compliance instanceof post_grades_return_graphable) {
            $info = $compliance->get_calc_info();
            $grading = $compliance->get_grading_info();

            $percents = range(0, 45, 5);
            $percentsize = count($percents);

            $headers = array();
            $barsrow = array();
            $legendrow = '';

            foreach ($info as $area => $spec) {
                // Start building the table.
                $title = $grading[$area]->operator . ' ' . $grading[$area]->value;
                $percentage = round(($spec->total / $compliance->total) * 100, 2);
                $headers[] = $title;
                $barsrow[] = $this->build_bars($percents, $compliance, $grading[$area], $spec);
                $legendrow .= html_writer::tag('div',
                    html_writer::tag('div', $title,
                    array('class' => 'law_grade_legend title')) .
                    html_writer::tag('div', $percentage,
                    array('class' => 'law_grade_legend'))
                    , array('class' => 'law_grade_legend_row')
                );
            }

            $meanstr = get_string('mean', 'block_post_grades');
            $medianstr = get_string('median', 'block_post_grades');

            $table = new html_table();
            $table->attributes['class'] = 'generaltable jd_curve';

            $table->head = array_merge(
                array($medianstr, $meanstr), $headers, array('')
            );

            $meanmedianbars = array(
                $this->compliance_bars($compliance->median, $compliance->median_compliance),
                $this->compliance_bars($compliance->mean, $compliance->mean_compliance)
            );

            $data = array(
                array_merge($meanmedianbars, $barsrow,
                array($this->build_legend($compliance, $legendrow)))
            );

            $table->data = $data;

            $return .= html_writer::tag('div', html_writer::table($table),
                array('class' => 'jd_curve_graph'));
        }

        return $return;
    }

    public function confirm_return(post_grades_return_process $return, $usecontinue = true) {
        try {
            $processed = $return->process();

            if (is_array($processed)) {
                foreach ($processed as $title => $compliance) {
                    echo $this->heading($title);
                    echo $this->display_graph($compliance);
                }
                if ($usecontinue) {
                    echo $this->continue_button($return->get_url(null));
                }
            } else if ($processed instanceof post_grades_compliance) {
                echo $this->display_graph($processed);
                if ($usecontinue) {
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

            $sheeturl = get_config('block_post_grades', $key);

            $str = get_string('view_gradsheet', 'block_post_grades');
            $post = new single_button(new moodle_url($sheeturl), $str, 'get');
        } else {
            $msg = get_string('message', 'block_post_grades', $a);

            $posturl = new moodle_url('/blocks/post_grades/postgrades.php', array(
                'courseid' => $course->id,
                'groupid' => $group->id,
                'periodid' => $period->id
            ));

            $str = get_string('post_type_grades', 'block_post_grades', $a);
            $post = new single_button($posturl, $str, 'post');
        }

        $gradebookurl = new moodle_url('/grade/report/grader/index.php', array(
            'id' => $course->id, 'group' => $group->id
        ));

        $str = get_string('make_changes', 'block_post_grades', $a);
        $gradebook = new single_button($gradebookurl, $str, 'get');

        $cancelurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $cancel = new single_button($cancelurl, get_string('cancel'));

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