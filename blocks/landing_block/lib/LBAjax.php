<?php

/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */
// TODO: would like to explore this bit below.....came across online
// define('AJAX_SCRIPT', true);

class LBAjax
{

    /**
     * Description
     * @param type $params
     * @return type
     */
    public function getCourseTitle($params)
    {

        global $DB, $CFG, $USER, $PAGE;

        $PAGE->set_context(context_system::instance());

        require_once($CFG->dirroot . '/blocks/landing_block/lib/LBTemplates.php');
        $template = new LBTemplates();
        $instance_name = isset($params['instance_name']) ? $params['instance_name'] : null;
        $caller = isset($params['caller']) ? $params['caller'] : null;
        $username = isset($params['username']) ? $params['username'] : null;
        $rawdata = isset($params['rawdata']) ? $params['rawdata'] : null;

        $coll = "";
        $no_course_conf_msg = "";

        if (isset($CFG->block_landing_block_message_no_courses)) {
            $no_course_conf_msg = $CFG->block_landing_block_message_no_courses;
        } else {
            $no_course_conf_msg = "";
        }

        if ($DB->record_exists('user', array('username' => $username))) {
            /* get the id of the username provided */
            $uid = $DB->get_field('user', 'id', array('username' => $username));

            /* get full user object so that when we query the DB from now on, we
             are doing it as the user.  This means we'll only see things the
             user can, but also that we'll get the status of their courses,
             not some non-existant user. */
            $USER = get_complete_user_data('id', $uid);
            if (isset($CFG->block_landing_block_use_new_course_overview) &&
                $CFG->block_landing_block_use_new_course_overview == 1 &&
                $caller == "2"
            ) {
                $tab = "timeline";
                $renderable = new \block_landing_block\output\main($tab);
                $renderer = $PAGE->get_renderer('block_landing_block');
                $html_all_courses_this_term = $renderer->render($renderable);
                // $html_all_courses_this_term = $template->printNewShell($name, $this_loop_site_url, $url_title, $html_all_courses_this_term, $coll, true, $renderer, $renderable);
            } else {
                try {
                    // $courses = enrol_get_users_courses($uid, true, 'id, shortname, modinfo', 'visible DESC, sortorder ASC');
                    $courses = enrol_get_users_courses($uid);
                    $html_all_courses_this_term = $template->printCourses($courses, $instance_name, $coll, $CFG->wwwroot."/", $rawdata);
                } catch (Exception $ex) {
                    $html_array = array(
                        'data' => array(),
                        'html' => 'no courses found',
                        'instance' => $instance_name,
                        'no_course' => $no_course_conf_msg
                    );

                    die(json_encode($html_array));
                }
            }

            $html_array = array(
                'data' => array(),
                'html' => $html_all_courses_this_term,
                'instance' => $instance_name,
                'no_course' => $no_course_conf_msg
            );

            die(json_encode($html_array));
        } else {
            $html_array = array(
                'data' => array(),
                'html' => 'no courses found',
                'instance' => $instance_name,
                'no_course' => $no_course_conf_msg
            );
            die(json_encode($html_array));
        }
    }
}
