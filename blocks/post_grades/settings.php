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

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/blocks/post_grades/settinglib.php');
    require_once($CFG->dirroot . '/enrol/ues/publiclib.php');
    ues::require_daos();

    $s = ues::gen_str('block_post_grades');

    $periodurl = new moodle_url('/blocks/post_grades/posting_periods.php');
    $reseturl = new moodle_url('/blocks/post_grades/reset.php');

    $a = new stdClass;
    $a->period_url = $periodurl->out();
    $a->reset_url = $reseturl->out();

    $settings->add(new admin_setting_heading('block_post_grades_header',
        '', $s('header_help', $a)));

    $settings->add(new admin_setting_configtext('block_post_grades/domino_application_url',
        $s('domino_application_url'), '', ''));

    $settings->add(new admin_setting_configtext('block_post_grades/mylsu_gradesheet_url',
        $s('mylsu_gradesheet_url'), '', ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/https_protocol',
        $s('https_protocol'), $s('https_protocol_desc'), 0));

    // Begin LAW work.

    $settings->add(new admin_setting_heading('block_post_grades_law_header',
        $s('law_heading'), ''));

    $settings->add(new admin_setting_configtext('block_post_grades/law_domino_application_url',
        $s('law_domino'), '', ''));

    $settings->add(new admin_setting_configtext('block_post_grades/law_mylsu_gradesheet_url',
        $s('law_mylsu_gradesheet_url'), '', ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/law_quick_edit_compliance',
        $s('law_quick_edit_compliance'), $s('law_quick_edit_compliance_help'), 0));

    $options = $DB->get_records_menu('scale', array(), 'name ASC', 'id, name');

    if ($options) {
        $settings->add(new admin_setting_configselect('block_post_grades/scale',
            $s('law_scale'), $s('law_scale_help'), key($options), $options));
    }

    // Large Course Settings.
    $settings->add(new admin_setting_heading('block_post_grades_law_large_header',
        $s('large_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/large_required',
        $s('required'), $s('required_help'), 1));

    $settings->add(new admin_setting_configtext('block_post_grades/large_mean',
        $s('mean'), '', "2.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/large_mean_range',
        $s('point_range'), '', "2.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/large_median',
        $s('median'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/large_median_range',
        $s('point_range'), '', "0.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/number_students',
        $s('number_students'), '', "31"));

    $values = array(
        'high_pass' => array(
            'value' => "3.8", 'lower' => "5", 'upper' => "10"),
        'pass' => array(
            'value' => "3.5", 'lower' => "15", 'upper' => "25"),
        'fail' => array(
            'value' => "2.4", 'lower' => "10", 'upper' => "20")
        );

    foreach ($values as $name => $value) {
        $settings->add(new admin_setting_heading("block_post_grades_law_$name",
            $s($name), ''));

        foreach ($value as $key => $default) {
            if ($key == 'value') {
                $str = "{$name}_{$key}";
            } else if ($key == 'lower') {
                $str = "lower_percent";
            } else {
                $str = "upper_percent";
            }

            $settings->add(new admin_setting_configtext("block_post_grades/{$name}_{$key}",
                $s($str), '', $default));
        }
    }

    // Mid-sized Settings.

    $settings->add(new admin_setting_heading('block_post_grades_law_mid_header',
        $s('mid_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/mid_required',
        $s('required'), $s('required_help'), 1));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_mean',
        $s('mean'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_mean_range',
        $s('point_range'), '', "0.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_median',
        $s('median'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/mid_median_range',
        $s('point_range'), '', "0.2"));

    // Small-sized Settings.

    $settings->add(new admin_setting_heading('block_post_grades_law_small_header',
        $s('small_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/small_required',
        $s('required'), $s('required_help'), 0));

    $settings->add(new admin_setting_configtext('block_post_grades/small_median',
        $s('median'), '', "3.0"));

    $settings->add(new admin_setting_configtext('block_post_grades/small_median_range',
        $s('point_range'), '', "0.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/number_students_less',
        $s('number_students_less'), '', 15));

    // Seminar Settings.

    $settings->add(new admin_setting_heading('block_post_grades_law_sem_header',
        $s('sem_courses'), ''));

    $settings->add(new admin_setting_configcheckbox('block_post_grades/sem_required',
        $s('required'), $s('required_help'), 0));

    $settings->add(new admin_setting_configtext('block_post_grades/sem_median',
        $s('median'), '', "3.2"));

    $settings->add(new admin_setting_configtext('block_post_grades/sem_median_range',
        $s('point_range'), '', "0.2"));

    // Course Settings.

    $filters = ues::where()->department->equal('LAW');

    $courses = ues_course::get_all($filters);

    if (!empty($courses)) {
        $settings->add(new admin_setting_heading(
            'block_post_grades_law_extra_settings',
            $s('law_extra'), '')
        );

        $toname = function($course) {
            return "$course";
        };

        $exceptions = new admin_setting_configmultiselect(
            'block_post_grades/exceptions',
            $s('law_exceptions'), $s('law_exceptions_help'),
            array(), array_map($toname, $courses)
        );

        post_grade_settings_callbacks::$exceptions = $exceptions;
        $exceptions->set_updatedcallback('post_grade_exceptions_callback');

        $settings->add($exceptions);

        $filters->cou_number->less_equal(5300);

        $courses = ues_course::get_all($filters);

        $legal_writing = new admin_setting_configmultiselect(
            'block_post_grades/legal_writing',
            $s('law_legal_writing'), $s('law_legal_writing_help'),
            array(), array_map($toname, $courses)
        );

        post_grade_settings_callbacks::$legal_writing = $legal_writing;
        $legal_writing->set_updatedcallback('post_grade_legal_writing_callback');

        $settings->add($legal_writing);
    }
}