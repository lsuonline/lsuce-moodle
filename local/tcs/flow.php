<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */

require_once(dirname(dirname(dirname(__FILE__))). '/config.php');
// include_once('lib/TcsLib.php');
// include_once('lib/StudentListAjax.php');

// global $DB, $USER;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/tcs/flow.php');

//Create the breadcrumbs
$PAGE->navbar->add(get_string('tcs_app_flow_title', 'local_tcs'), new moodle_url('flow.php'));

//Set page headers.
$PAGE->set_title(get_string('tcs_app_flow_title', 'local_tcs'));
$PAGE->set_heading(get_string('tcs_app_flow_title', 'local_tcs'));

// $PAGE->requires->js_call_amd('local_tcs/tcs_flow', 'init');
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/tcs/js/mermaid/mermaid.js'), true);

require_login();


echo $OUTPUT->header();

echo('<div class="mermaid">
    graph LR
      A --- Bf
      B-->C[fa:fa-ban forbidden]
      B-->D(fa:fa-spinner);
</div>');


/*
- Oval: start and end points
- Rounded rectangle: A web interface like a page or partial page. Use numbers to help identify components
- rhombus: decision point (user makes choice) general flow is yes is down and right is no
- triangle: conditional branches (system makes choice in background)
- small circle: jump point
- ripped rectangle: system action, a background task (like collect data with failed login attempt)



Mermaid has:
- circle
- left indent rectangle
- rhombus
- left/right outdent rectangle
- parallelogram (leaning right)
- parallelogram alt(falling back)
- trapezoid /   \
- trapezoid alt \   /


*/
echo $OUTPUT->footer();
error_log("\nindex.php -> FINISHED");
