<?php
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");

require_once(__DIR__ . '/classes/cli_option.php');
require_once(__DIR__ . '/classes/cli_script.php');
use local_lsucli\CLIOption;
use local_lsucli\CLIScript;

function pretty_print_r($data) {
    # Wrap in <pre>
    

}


class lsucli_form extends \moodleform {
    public function definition() {
        global $CFG;
        $cliscripts = CLIScript::gen_scripts();
        pretty_print_r($cliscripts);
        $mform = $this->_form;
        // $mform->addElement('static', 'info', 'LSU CLI Tool', 'This tool allows you to view and schedule Moodle CLI scripts.');
        $options = new core\output\choicelist();
        foreach ($cliscripts as $script) {
            // $options->add_option(
            //     $script->file_name,
            //     $script->file_name, [
            //     'description' => $script->help_text,
            // ]);
            $mform->addElement('static', '', $script->file_name, );
        }
        $mform->addElement('submit', 'submitbutton', 'blah');
        $mform->addElement('reset', 'reset', 'reset');
        $mform->addElement('cancel', 'cancel', 'cancel');
    }
}

$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_url('/local/lsucli/index.php');

$mform = new lsucli_form();

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

if ($mform->is_cancelled()) {
    // redirect(new moodle_url('/local/lsucli/index.php'));
    echo 'blah';
}
if ($data = $mform->get_data()) {
    pretty_print_r($data);
}

$mform->display();

// $table = new html_table();
// $table->head = array(get_string('scriptname', 'local_lsucli'), get_string('schedule'), '');

// foreach ($cliscripts as $script) {
//     if (substr($script, -4) !== '.php') {
//         continue;
//     }

//     $schedule_url = new moodle_url('/local/lsucli/schedule.php', array('script' => $script));
//     // $schedule_button = $mform->createElement('button', 'schedule_' . $script, get_string('schedule'));
//     $command_options = CLIOption::parse_from_file($CFG->dirroot . '/admin/cli/' . $script);
//     $row = new html_table_row([
//         $script, 
//         // $OUTPUT->render($schedule_button), 
//         CLIOption::array_to_html($command_options)
//     ]);
//     $table->data[] = $row;
// }

// echo html_writer::table($table);

echo $OUTPUT->footer();


echo "
<style>
    .cli-option-table {
        width: 25vw;
    }
    .cli-option-table.hidden {
        display: none;
    }
    .cli-table-toggle {
        width: 25vw;
        display: block;
    }
    .cli-option-row {
        background: transparent !important;
        cursor: pointer;
    }
    .cli-option-row:hover {
        background-color: #ddd !important;
    }
</style>";
