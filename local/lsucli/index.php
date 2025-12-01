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

class lsucli_form extends \moodleform
{
    public function definition()
    {
        global $CFG;
        $cliscripts = CLIScript::gen_scripts();
        $mform = $this->_form;
        $scripts = [];
        foreach ($cliscripts as $script) {
            $scripts[$script->file_name] = $script->file_name;
        }
        $mform->addElement('autocomplete', 'script', 'Script to execute', $scripts);
        $this->add_script_elements($cliscripts);
        $mform->addElement('static', null, '<command_preview />');
        $mform->addElement('submit', 'submitbutton', 'Run Task');
    }

    private function add_script_elements($cliscripts)
    {
        $mform = $this->_form;
        foreach ($cliscripts as $script) {
            $group = $this->add_option_elements($script);
            $mform->addGroup($group, $script->file_name, 'Parameters', null, false);
            $mform->hideIf($script->file_name, 'script', 'neq', $script->file_name);
        }
    }

    /**
     * @return HTML_QuickForm_element[]
     */
    private function add_option_elements($script)
    {
        $mform = $this->_form;
        $group = [];
        /** @var CLIOption $option */
        foreach ($script->get_options() as $option) {
            $unique = $script->file_name . '_' . $option->longname;
            if ($option->type == OptionType::BOOL) {
                $group[] = &$mform->createElement(
                    'checkbox',
                    $unique,
                    $option->longname,
                    '',
                    ['title' => $option->description],
                );
            } else {
                $group[] = &$mform->createElement(
                    'static',
                    null,
                    null,
                    '
                    <label 
                        title="' . html_entity_decode($option->description) . '"
                    >' .
                        $option->longname
                );
                $group[] = &$mform->createElement('text', $unique, null, null, ['onchange' => 'update_command_preview']);
                $group[] = &$mform->createElement('static', null, null, '</label>');
                if ($option->type == OptionType::NUMBER) {
                    $mform->setType($unique, PARAM_INT);
                } else {
                    $mform->setType($unique, PARAM_INT);
                }
            }
        }
        return $group;
    }
}

$context = context_system::instance();
require_login();
$PAGE->set_context($context);
$PAGE->set_url('/local/lsucli/index.php');
$PAGE->requires->css('/local/lsucli/styles.css');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$mform = new lsucli_form();

if ($data = $mform->get_data()) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

$mform->display();

echo $OUTPUT->footer();
