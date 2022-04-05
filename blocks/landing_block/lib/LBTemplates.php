<?php
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     Landing Block                                            **
 * @subpackage  University of Lethbridge Custom Landing Block            **
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

class LBTemplates
{

    public function testFunc()
    {
        return "hello";
    }


    /**
     * UI builder
     * @param string title (may or may not be used
     * @return type
     */
    public function printLandingBar($username, $title = "")
    {
        global $CFG;
        if (!$title) {
            $title = 'My Courses';
        }
                // <a class="brand" href="'.$CFG->wwwroot.'"><h4>'.$title.'</h4></a>
        $nav = '<div class="navbar main_landing_block_notifications">'.
            '<div class="navbar-inner">'.
                '<div class="nav-no-collapse header-nav">'.
                    '<ul class="nav navbar-nav navbar-right pull-right">'.
                        '<li><div class="main_landing_block_notifications_elem" data-internal_username="'.$username.'"'.
                        ' data-lbversion="'. $CFG->block_landing_block_version .'"></div></li>'.
                    '</ul>'.
                '</div>'.
            '</div>'.
        '</div>';
        return $nav;
    }

    /**
     * UI builder using the My Overview block
     * @return html
     */
    public function printNewShell($name = '', $url, $url_title, $html_all_courses_this_term = "", $collapseIn = false, $local_db = false, $renderer, $renderable)
    {
        
        global $CFG;

        $no_course_conf_msg = "";

        // error_log("\nprintNewShell() -> What is the url: ". $url. " and title: ". $title);

        if (isset($CFG->block_landing_block_message_no_courses)) {
            $no_course_conf_msg = $CFG->block_landing_block_message_no_courses;
        } else {
            $no_course_conf_msg = "You are currently not registered in any course OR your Instructor has not opened your course for access yet.";
            $no_course_conf_msg .= '<br><br><center><i><a href="'.$url.'">I still want access to this term....</a></i></center>';
        }

        $local_db .= $local_db."";

        $html = '<div class="row-fluid main_landing_block_body">'.
            '<div class="accordion uleth_accordion_snip" role="tablist" aria-multiselectable="true" id="lb_this_current_semester_'.$name.'">'.
                '<div class="card">'.
                    '<div class="card-header" role="tab" id="heading'.$name.'">'.
                        '<a data-toggle="collapse" data-item-id="'.$name.'" data-parent="#lb_this_current_semester_'.$name.'" href="#collapse_'.$name.'" aria-expanded="true">';
        if ($collapseIn) {
            $html .= '<div class="lb_course_term_'.$name.'"><i class="fa fa-chevron-down"></i> '.$url_title.'</div>';
            $collapseIn = "show collapse in";
        } else {
            $html .= '<div class="lb_course_term_'.$name.'"><i class="fa fa-chevron-right"></i> '.$url_title.'</div>';
            $collapseIn = "collapse";
        }

                $html .= '</a>'.
                    '</div>'.
                    '<div id="collapse_'.$name.'" class="'.$collapseIn.'" role="tabpanel" aria-labelledby="heading'.$name.'">'.
                        '<div class="card-body">'.
                            '<div id="lb_expand_term_details_'.$name.'" data-url="'.$url.'" data-local_info="'.$local_db.'">' . $renderer->render($renderable) .'</div>'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</div>'.
        '</div>';

        return $html;
    }
    /**
     * UI builder
     * @return html
     */
    public function printShell($name = '', $url, $url_title, $html_all_courses_this_term = "", $collapseIn = false, $local_db = false)
    {
        global $CFG;
        $no_course_conf_msg = "";

        if (isset($CFG->block_landing_block_message_no_courses) && strlen($CFG->block_landing_block_message_no_courses) > 5) {
            $no_course_conf_msg = $CFG->block_landing_block_message_no_courses;
        } else {
            $no_course_conf_msg = "You are currently not registered in any course OR your Instructor has not opened your course for access yet.";
            $no_course_conf_msg .= '<br><br><center><i><a href="'.$url.'">I still want access to this term....</a></i></center>';
        }

        $local_db .= $local_db."";

        $html = '<div class="row-fluid main_landing_block_body">'.
            '<div class="accordion uleth_accordion_snip" role="tablist" aria-multiselectable="true" id="lb_this_current_semester_'.$name.'">'.
                '<div class="card">'.
                     '<div class="card-header" role="tab" id="heading'.$name.'">'.
                        '<a data-toggle="collapse" data-item-id="'.$name.'" data-parent="#lb_this_current_semester_'.$name.'" href="#collapse_'.$name.'" aria-expanded="true">';

        if ($collapseIn) {
            $html .= '<div class="lb_course_term_'.$name.'"><i class="fa fa-chevron-down"></i> '.$url_title.'</div>';
            $collapseIn = "show collapse in";
        } else {
            $html .= '<div class="lb_course_term_'.$name.'"><i class="fa fa-chevron-right"></i> '.$url_title.'</div>';
            $collapseIn = "collapse";
        }

        $html .= '</a>'.
                    '</div>'.
                    '<div id="collapse_'.$name.'" class="'.$collapseIn.'" role="tabpanel" aria-labelledby="heading'.$name.'">'.
                        '<div class="card-body">';

        // error_log("\nprintShell() ->What is the url: ". $url. " and title: ". $url_title);

        if ($html_all_courses_this_term == "<i class='fa fa-spinner fa-spin fa-2' aria-hidden='true'></i> One Moment Please......") {
            $html .= '<div id="lb_expand_term_details_'.$name.'" data-url="'.$url.'" data-local_info="'.$local_db.'">'.$html_all_courses_this_term.'<br><br><center><i><a href="'.$url.'">Click here to get access to this term....</a></i></center></div>';
        } else {
            if ($html_all_courses_this_term == "") {
                $html .= '<div id="lb_expand_term_details_'.$name.'" data-url="'.$url.'" data-local_info="'.$local_db.'">' .
                $no_course_conf_msg. '</div>';
            } else {
                $html .= '<div id="lb_expand_term_details_'.$name.'" data-url="'.$url.'" data-local_info="'.$local_db.'">' . $html_all_courses_this_term.'</div>';
            }
        }

        $html .= '</div>'.
                    '</div>'.
                '</div>'.
            '</div>'.
        '</div>';

        return $html;
    }

    /**
     * Description
     * @param string name of instance we are grabbing data for
     * @return html output.
     */
    public function getUsersCourseDisplay($name)
    {
        // get the id of the username provided


        // grab the course data using the id of the student

        /*
        // print a quick overview of the course
        if ($content == '<div class="box coursebox"> </div>' || $content == "" || $content == null) {
            $content = '<div class="box coursebox"><h6 class="main">'.$landing_info->getNoCourseMsg($name).'</h6><h4><a href="'.$link_url.'" target="_blank">Click here for "'.$url_title.'"</a></h4></div>';
        }

        $text .= $title;
        $style = 'display: block;';
        $insert_span = '';
        $insert_span = html_writer::tag('span', $content, array('id' => $name, 'style' => $style));
        $text .= html_writer::tag('div', $insert_span, array('id' => 'uofl_'.$name.'_landing'));
        */
    }

    public function getIEDisplay($name, $url, $title)
    {
        global $USER;

        require_once('LBLib.php');
        $text = '';

        $lanlib = new LBLib();

        if ($name == "current_title") {
            $style = 'display: block;';
        } else {
            $style = 'display: none;';
        }

        $content = $lanlib->get_landing_content($url, $USER->username);
        $error_msg_1 = "It is possible that the database is overloaded or otherwise not running properly";
        $error_msg_2 = "Incorrect access detected, this server may be accessed only through";
        $error_msg_3 = "No input file specified";

        if (strpos($content, $error_msg_1) !== false || strpos($content, $error_msg_2) !== false || strpos($content, $error_msg_3) !== false) {
            $content = '<div class="box coursebox"><h4><a href="'.$link_url.'" target="_blank">Click here for "'.$url_title.'"</a></h4></div>';
        }

        if ($content == '<div class="box coursebox"> </div>' || $content == "" || $content == null || strlen($content) < 4) {
            $content = '<div class="box coursebox"><h6 class="main">'.$landing_info->getNoCourseMsg($name).'</h6><h4><a href="'.$link_url.'" target="_blank">Click here for "'.$url_title.'"</a></h4></div>';
        }

        $text .= $title;
        //$style = 'display: block;';
        $insert_span = '';
        $insert_span = html_writer::tag('span', $content, array('id' => $name, 'style' => $style));
        $text .= html_writer::tag('div', $insert_span, array('id' => 'uofl_'.$name.'_landing'));
        return $text;
    }

    /**
     * Description
     * @param array - students courses for this instance, array of objects
     * @param string - current instance name
     * @return html
     *
     * <ul class="pull-right" id="lb_jump_button">
     *   <button class="btn btn-primary" onclick="window.location.href=\''.$url.'course/view.php?id='.$course->id.'\'">Jump To Course</button>
     * </ul>
     */
    public function printCourses($courses, $name, $collapseIn = "", $url, $rawdata = "false")
    {
        global $USER;
        require_once('LBLib.php');
        $lb_lib = new LBLib();

        $count = 0;
        $html = "";
        // error_log("\nWTF??????");
        if (!isset($url)) {
            global $CFG;
            $url = $CFG->wwwroot;
        }

        $rdata_list = array();

        foreach ($courses as $course) {
            $rdata = new stdClass();
            
            $this_course = array($course->id => $course);

            $overview = $lb_lib->lb_print_overview($this_course, $rawdata);


            if ($course->visible == 1 || $lb_lib->checkAdminUser()) {
                $this_url = $url.'course/view.php?id='.$course->id;
                $title_msg = '<h3>'.$course->fullname.'</h3>';
                // $overview = $lb_lib->lb_print_overview($this_course, $rawdata);

                $add_show_hide = '<span class="pull-right"><a data-toggle="collapse" data-item-id="'. $name .'" data-parent="#lb_my_courses_accord_'.
                        $name.'" href="#collapse_'. $name . '_' . $count .'" aria-expanded="true" id="lb_show_hide_toggle">Show/Hide</a></span>';
            } else {
                $context = context_course::instance($course->id);
                $roles = get_user_roles($context, $USER->id, false);
                $is_ta = false; // is the user a teaching assistant?

                foreach ($roles as $role) {
                    if ($role->shortname == 'advancedta' ||
                        $role->shortname == 'ta' ||
                        $role->shortname == 'editingteacher' ||
                        $role->shortname == 'teacher' ||
                        $role->shortname == 'manager'
                    ) {
                        $is_ta = true;
                    }
                }

                if ($is_ta) {
                    $this_url = $url.'course/view.php?id='.$course->id;
                    $title_msg = '<h3>'.$course->fullname.'</h3>';
                    
                    $add_show_hide = '<span class="pull-right"><a data-toggle="collapse" data-item-id="'. $name .'" data-parent="#lb_my_courses_accord_'.
                    $name.'" href="#collapse_'. $name . '_' . $count .'" aria-expanded="true" id="lb_show_hide_toggle">Show/Hide</a></span>';
                } else {
                    $this_url = '#';
                    $title_msg = '<div class="lb_inactive_courses">'.$course->fullname.'</div>';
                    $overview = "This course is currently unavailable. Please ask your professor if it will be in the future.";
                    $add_show_hide = '';
                }
            }

            $rdata->courseid = $course->id;
            $rdata->visible = $course->visible;
            $rdata->this_url = $this_url;
            $rdata->title_msg = $title_msg;
            $rdata->this_term = $lb_lib->getCurrentTerm();
            $rdata->block_name = $name;

            // $rdata->overview = json_encode(array("data" => $overview));
            $rdata->overview = $overview;
            $rdata_list[] = $rdata;

            $collapseIn = "show";


            if ($rawdata == "false") {
                $html .= '<div class="row-fluid">'.
                    '<div class="accordion uleth_accordion_snip" id="lb_my_courses_accord_'.$name.'" role="tablist" aria-multiselectable="true">'.
                        '<div class="card">'.
                            '<div class="card-header" role="tab" id="'.$name.'_heading">'.
                                $add_show_hide.
                                '<a href="'.$this_url.'">'.
                                    $title_msg.
                                '</a>'.
                            '</div>'.
                            '<div id="collapse_'. $name . '_' . $count .'" class="show collapse in" role="tabpanel" aria-labelledby="'.$name.'_heading">'.
                                '<div class="card-body">'.$overview.'</div>'.
                            '</div>'.
                        '</div>'.
                    '</div>'.
                '</div>';
            }

            $count++;
        }

        if ($rawdata == "true") {
            // $rdata->overview = json_encode(array("data" => $overview));
            return json_encode(array("data" => $rdata_list));
        } else {
            return $html;
        }
    }
}
