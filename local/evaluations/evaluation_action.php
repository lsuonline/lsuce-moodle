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
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');

// ----- Parameters ----- //
$action = required_param('action', PARAM_TEXT);
$eval_id = required_param('eval_id', PARAM_INT);
$dept = required_param('dept', PARAM_TEXT);

// ----- Security ------ //

if (!$eval = $DB->get_record('evaluations', array('id' => $eval_id))) {
    print_error(get_string('eval_id_invalid', 'local_evaluations'));
}

$context = context_course::instance($eval->course);

$am_i_enrolled = is_enrolled($context, $USER->id, 'moodle/course:update', true);

if (has_capability('local/evaluations:instructor', $context) && $am_i_enrolled || !is_dept_admin($dept, $USER)) {
    print_error(get_string('restricted', 'local_evaluations'));
}

// ----- Main ----- //
//Check the action specified and take the appropriate action.
switch ($action) {
    case 'delete':
        delete_eval($eval_id);
        break;

    case 'force_start':
        force_start_eval($eval_id);
        break;

    case 'force_complete':
        force_complete_eval($eval_id);
        break;
}

redirect($CFG->wwwroot . '/local/evaluations/evaluations.php?dept='.$dept);
