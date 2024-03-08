<?php
/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('courses', new admin_externalpage('localCourseEvaluations', 'Course Evaluations', "$CFG->wwwroot/local/evaluations/index.php"));

if ($ADMIN->fulltree) {

    $settings = new admin_settingpage('localsettings'.'evaluations', get_string('main_title', 'local_evaluations'), 'moodle/site:config', false);
    $ADMIN->add('localplugins', $settings);
      
    // Preamble --------------------------------------------------------------------------------.
    $settings->add(new admin_setting_heading('localsettingsevaluations', get_string('preamble_title', 'local_evaluations'), ''));
    $settings->add(new admin_setting_confightmleditor(
        'local_eval_preamble',
        get_string('eval_preamble_header', 'local_evaluations'),
        get_string('eval_preamble_desc', 'local_evaluations'),
        ''
    ));

    // General Settings  --------------------------------------------------------------------------------.
    $settings->add(new admin_setting_heading('local_eval_gen_set', get_string('general_settings', 'local_evaluations'), ''));

      // early reminder
    $settings->add(new admin_setting_configtext(
        'local_eval_early_message_delay',
        get_string('early_message_delay', 'local_evaluations'),
        get_string('early_message_delay_desc', 'local_evaluations'),
        15,
        PARAM_INT
    ));
    // message queue time
    $settings->add(new admin_setting_configtext(
        'local_eval_message_que_limit',
        get_string('message_que_limit', 'local_evaluations'),
        get_string('message_que_limit_desc', 'local_evaluations'),
        2,
        PARAM_INT
    ));
    // current term
    $settings->add(new admin_setting_configtext(
        'local_eval_current_term',
        get_string('current_term', 'local_evaluations'),
        get_string('current_term_desc', 'local_evaluations'),
        201402,
        PARAM_TEXT
    ));
}
