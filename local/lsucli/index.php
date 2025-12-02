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

    /**
     * @param CLIScript[] $cliscripts
     * @return void
     */
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
     * @param CLIScript $script
     * @return HTML_QuickForm_element[]
     */
    private function add_option_elements($script)
    {
        $mform = $this->_form;
        $group = [];
        $group = [...$group, ...$this->labelwrap(
            'Custom Parameters 1', 
            $mform->createElement(
                'text', 
                $script->file_name . "_custom_pre", 
                null,
                ['title' => 'Arbitrary text to go before all other parameters.']
            )
        )];
        $mform->setType($script->file_name . '_custom_pre', PARAM_TEXT);
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
                $group = [...$group,...$this->labelwrap(
                    $option->longname, 
                    $mform->createElement(
                        'text', 
                        $unique,
                        null,
                        ['title' => $option->description],
                    ),
                )];
                if ($option->type == OptionType::NUMBER) {
                    $mform->setType($unique, PARAM_INT);
                } else {
                    $mform->setType($unique, PARAM_TEXT);
                }
            }
        }
        $group = [...$group, ...$this->labelwrap(
            'Custom Parameters 2', 
            $mform->createElement(
                'text', 
                $script->file_name . "_custom_post", 
                null,
                ['title' => 'Arbitrary text to go after all other parameters.']
            )
        )];
        $mform->setType($script->file_name . '_custom_post', PARAM_TEXT);
        return $group;
    }

    private function labelwrap($pretext, $child, $posttext = '') {
        $elements = [];
        $elements[] = &$this->_form->createElement(
            'static',
            null,
            null,
            '<label data-toggle="tooltip">' . $pretext,
        );
        $elements[] = $child;
        $elements[] = &$this->_form->createElement(
            'static',
            null,
            null,
            $posttext . '</label>',
        );
        return $elements;
    }

    public function reset() {
        $this->_form->updateSubmission(null, null);
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
    $mform->reset();
}

$mform->display();

echo $OUTPUT->footer();
?>
<script>
    // Moodle themes don't always like setting titles on labels properly.
document.querySelectorAll('input').forEach((e, i) => {
    var label = e.closest('label');
    if (label === null)
        return;
    label.setAttribute('title', e.getAttribute('title'));
    label.setAttribute('data-toggle', 'tooltip');
});
</script>