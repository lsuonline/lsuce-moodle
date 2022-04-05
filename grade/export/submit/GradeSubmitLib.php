<?php
/**
 * ************************************************************************
 * *                   Grade Submission
 * ************************************************************************
 * @package     local
 * @subpackage  Grade Export Submit
 
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

class GradeSubmitLib
{
    // public function __construct()
    // {
    // }

    public function simulateLastStep($status) {

        //Parse the response.
        if ($status == 'Failure') {
            $errors = array();
            //The submission received an error from Banner.
            $html_chunk = '<div class="row-fluid">'.
                '<div class="col12">'.
                '<div class="alert alert-danger" role="alert">'.
                '<p><span style="font-size:25px;">'.
                    '<i class="fa fa-close"></i>&nbsp;&nbsp;'.
                    '</span>'.
                    // '<strong>Your grades have been successfully submitted to Banner with a few exceptions!</strong>'.
                    '<strong>There were errors in your grade submission</strong></p><p>Details are below.</p>'.
                '</p>'.
                '</div></div></div>';
            echo $html_chunk; 

            // echo get_string('grade_submission_error', 'gradeexport_submit');
            echo "<ul>";
            echo "<li>Student Error's would show here.</li>";
            echo "</ul>";
            echo get_string('contact_info', 'gradeexport_submit');
            
            echo get_string('grades_not_submitted', 'gradeexport_submit');
            
            // grade_submit_sendEmail('failure', $errors);

            // $gradeSubmission->{'succeeded'} = 0;
            // $gradeSubmission->{'response_received'} = 1;

        } else if ($status == 'PartialSuccess') {
            //The submission was successful "partially", meaning there were probably some warning type errors.
            //but it's still considered "submitted".
            $html_chunk = '<div class="row-fluid">'.
                '<div class="col12">'.
                '<div class="alert alert-warning" role="alert">'.
                '<p><span style="font-size:25px;">'.
                    '<i class="fa fa-exclamation-triangle"></i>&nbsp;&nbsp;'.
                    '</span>'.
                    '<strong>Your grades have been successfully submitted to Banner with a few exceptions!</strong>'.
                '</p>'.
                '</div></div></div>';
            echo $html_chunk; 
            // echo get_string('grade_submission_partialsuccess', 'gradeexport_submit');
            echo '<p>Results of the submission are below.</p>';
            echo "<ul>";
            echo "<li>Student Error's would show here.</li>";
            echo "</ul>";
            echo get_string('contact_info', 'gradeexport_submit');
            

            // echo get_string('grades_are_submitted', 'gradeexport_submit');
            // grade_submit_sendEmail('partialsuccess', $errors);

            // $gradeSubmission->{'succeeded'} = 1;
            // $gradeSubmission->{'response_received'} = 1;
        } else if ($status == 'Success') {
            //The submission was successful!
            $html_chunk = '<div class="row-fluid">'.
                '<div class="col12">'.
                '<div class="alert alert-success" role="alert">'.
                '<p><span style="font-size:25px;">'.
                    '<i class="fa fa-check"></i>&nbsp;&nbsp;'.
                    '</span>'.
                    '<strong>Your grades have been successfully submitted to Banner!</strong>'.
                '</p>'.
                '</div></div></div>';

            echo $html_chunk;
            echo get_string('grade_submission_success', 'gradeexport_submit');
            /*
            echo "<ul>";
            foreach ($xml_obj->errordetail as $error)
            {
                $sourcedid=intval($error->sourcedid->source);
                echo "<li>".$error->errordescription." Source: ".$error->sourcedid->source.", ".$userListBySource[$sourcedid]."</li>";
            }
            echo "</ul>";
            */
            echo get_string('contact_info', 'gradeexport_submit');
            
            // echo get_string('grades_are_submitted', 'gradeexport_submit');
            // grade_submit_sendEmail('success');

            // $gradeSubmission->{'succeeded'} = 1;
            // $gradeSubmission->{'response_received'} = 1;

        } else {
            $errors = array();
            //The submission never got a response. Timed out?
            // echo get_string('grade_submission_noresponse', 'gradeexport_submit');
            $html_chunk = '<div class="row-fluid">'.
                '<div class="col12">'.
                '<div class="alert alert-danger" role="alert">'.
                '<p><span style="font-size:25px;">'.
                    '<i class="fa fa-close"></i>&nbsp;&nbsp;'.
                    '</span>'.
                    // '<strong>Your grades have been successfully submitted to Banner with a few exceptions!</strong>'.
                    '<strong>There was an error submitting your grades.</strong> <br/><br/><strong>Your grades may or may not have been successfully submitted.</strong>'.
                    '<br/><br/>Details are below.'.
                '</p>'.
                '</div></div></div>';
            echo $html_chunk; 
            echo "<ul>";
            $msg = "No response from Banner.";
            echo "<li>".$msg."</li>";
            $errors[] = $msg;
            echo "</ul>";

            echo get_string('contact_info', 'gradeexport_submit');
            
            // echo get_string('grades_not_submitted', 'gradeexport_submit');
            
            // grade_submit_sendEmail('failure', $errors);
                
            // $gradeSubmission->{'succeeded'} = 0;
            // $gradeSubmission->{'response_received'} = 0;
        }

        //Now put an entry in our submissions table.
        // $gradeSubmission->timesubmitted = time();
        // $DB->insert_record('grade_submit_lmb_submissions', $gradeSubmission);

        // if ($bugging) {
            $time_elapsed_secs = microtime(true) - $process_timer_start;
            error_log("\n\nThe Overall time it took to process this page was: ". $time_elapsed_secs. " seconds\n\n");
            echo "<br><br>Total time to process request: ". round($time_elapsed_secs, 2). " seconds.<br>";
            error_log("\n------------------------------------------------------------------------------------------\n");
        // }
    }
}
