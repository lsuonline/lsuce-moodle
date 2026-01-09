<?php

require_once(__DIR__ . '/cli_option.php');
require_once(__DIR__ . '/cli_script.php');

use local_lsucli\CLIOption;
use local_lsucli\CLIScript;
use local_lsucli\OptionType;

class lsucli_form extends \moodleform
{
    /** @var CLIScript[] $cliscripts */
    public $cliscripts = [];
    public function definition()
    {
        global $CFG;
        $this->cliscripts = CLIScript::gen_scripts();
        $mform = $this->_form;
        $scripts = [];
        foreach ($this->cliscripts as $script) {
            $scripts[$script->file_name] = $script->file_name;
        }
        $mform->addElement('autocomplete', 'script', 'Script to execute', $scripts);
        $this->add_script_elements($this->cliscripts);
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

    public function build_cmd() {
        setlocale(LC_CTYPE, "en_US.UTF-8");
        $data = $this->get_data();
        $script = $this->cliscripts[$data->script];
        $command = [
            "php", 
            $script->file_path,
            $data->{$data->script . '_custom_pre'} ?? null
        ];
        foreach ($script->get_options() as $option) {
            $key = $data->script . '_' . $option->longname;
            $value = $data->{$key} ?? null;
            if ($value == null)
                continue;
            if ($option->type == OptionType::BOOL && $value == 1) {
                $command[] = "--$option->longname";
                continue;
            }
            if ($option->type == OptionType::STRING) {
                $command[] = "--$option->longname=" . escapeshellarg($value);
            } else {
                $command[] = "--$option->longname=$value";
            }
        }
        $command[] = $data->{$data->script . '_custom_post'} ?? null;
        $command = array_filter( $command, function($v) {
            return $v !== null;
        });
        $command = implode(' ', $command);
        return $command;
    }
}