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

class LBReturn
{

    private $name;
    private $coll;
    private $this_loop_site_url;
    private $url_title;
    private $uid;
    private $username;
    
    public function __construct($data = null)
    {
        if ($data != null) {
            list($name, $coll, $this_loop_site_url, $url_title, $uid, $username) = $data;
            $this->name = $name;
            $this->coll = $coll;
            $this->this_loop_site_url = $this_loop_site_url;
            $this->url_title = $url_title;
            $this->uid = $uid;
            $this->username = $username;
        }
    }

    public function LBBackDoor()
    {
        // if (debugging()) {
        //     error_log("\n");
        //     error_log("\nLBReturn -> LBBackDoor() -> START");
        //     // mtrace('Marking users as started');
        // }

        require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
        require_once($CFG->dirroot . '/blocks/landing_block/lib/LBTemplates.php');

        $USER = get_complete_user_data('id', $this->uid);

        $template = new LBTemplates();

        try {
            $courses = enrol_get_users_courses($this->uid);
            $html_all_courses_this_term = $template->printCourses($courses, $this->name, $this->coll, $this->this_loop_site_url);
            $html_content = $template->printShell($this->name, $this->this_loop_site_url, $this->url_title, $html_all_courses_this_term, $this->coll, true);
            return $html_content;

        } catch (Exception $ex) {

            $html_content = $template->printShell($this->name, $this->this_loop_site_url, $this->url_title, $html_all_courses_this_term, $this->coll);
            return $html_content;
        }
    }
}

// if you want to use the debugging() func then include CFG
// require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

// if (debugging()) {
//     error_log("\n");
//     error_log("\n");
//     error_log("\nLBReturn-> Have landed on LBReturn.php and now going to get post data.");
// }
if (isset($_POST['cereal_data'])) {
    // if (debugging()) {
    //     error_log("\nLBReturn-> post data has been set, currently it's: ". $_POST['cereal_data']);
    // }

    $data = unserialize($_POST['cereal_data']);
    $LB_obj = new LBReturn($data);

    echo $LB_obj->LBBackDoor();

} else {
    // if (debugging()) {
    echo 'Uh oh.......no POST data came through, nothing to show here :-(';
    // }
}
