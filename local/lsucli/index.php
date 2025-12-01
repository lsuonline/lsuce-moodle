<?php
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");

require_once(__DIR__ . '/classes/cli_option.php');
require_once(__DIR__ . '/classes/cli_script.php');

use core_search\external\get_search_areas_list;
use local_lsucli\CLIOption;
use local_lsucli\CLIScript;
use local_lsucli\OptionType;
use tool_brickfield\local\areas\mod_choice\option;

function pretty_print_r($data) {
    # Wrap in <pre>
    echo '<pre>';
    # Print the data
    print_r($data);
    # Close the <pre>
    echo '</pre>';
}


class lsucli_form extends \moodleform {
    public function definition() {
        global $CFG;
        $cliscripts = CLIScript::gen_scripts();
        $mform = $this->_form;
        $scripts = [];
        foreach ($cliscripts as $script) {
            $scripts[$script->file_name] = $script->file_name;
        }
        $mform->addElement('autocomplete', 'script', 'Script to execute', $scripts);
        $this->add_script_elements($cliscripts);
        $mform->addElement('submit', 'submitbutton', 'Run Task');
    }

    private function add_script_elements($cliscripts) {
        $mform = $this->_form;
        foreach ($cliscripts as $script) {
            $group = $this->add_option_elements($script);
            $mform->addGroup($group, $script->file_name, null, null, false);
            $mform->hideIf($script->file_name, 'script', 'neq', $script->file_name);
        }
    }

    /**
    * @return HTML_QuickForm_element[]
    */
    private function add_option_elements($script) {
        $mform = $this->_form;
        $group = [];
        foreach ($script->get_options() as $option) {
            $unique = $script->file_name . '_' . $option->longname;
            if ($option->type == OptionType::BOOL) {
                $group[] =& $mform->createElement('checkbox', $unique, $option->longname);
            } else if ($option->type == OptionType::NUMBER) {
                $group[] =& $mform->createElement('static', $unique, $option->longname, $option->longname);
                $group[] =& $mform->createElement('text', $unique);
                $mform->setType($unique, PARAM_INT);
            } else if ($option->type == OptionType::STRING) {
                $group[] =& $mform->createElement('static', $unique, $option->longname, $option->longname);
                $group[] =& $mform->createElement('text', $unique);
                $mform->setType($unique, PARAM_TEXT);
            } else {
                $group[] =& $mform->createElement('static', $unique, $option->longname);
            }
        }
        return $group;
    }
}

$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_url('/local/lsucli/index.php');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$mform = new lsucli_form();

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
