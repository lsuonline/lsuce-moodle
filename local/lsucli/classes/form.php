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
            $help_text = stripslashes($script->help_text);
            $help_text = htmlspecialchars($help_text);

            $group = [$mform->createElement('static', null, null, "<pre>$help_text </pre>")];
            $mform->addGroup($group, $script->file_name . "_help", 'Documentation', null, false);
            $mform->hideIf($script->file_name . '_help', 'script', 'neq', $script->file_name);

            // Add header for commandline parameters.
            $header_group = [$mform->createElement('static', null, null, '<h4>Commandline Parameters</h4>')];
            $mform->addGroup($header_group, $script->file_name . "_params_header", '', null, false);
            $mform->hideIf($script->file_name . '_params_header', 'script', 'neq', $script->file_name);

            // Add each option as its own group for proper layout.
            $this->add_option_elements($script);
        }
    }

    /**
     * Adds option elements for a script, each option as its own group row.
     * @param CLIScript $script
     * @return void
     */
    private function add_option_elements($script)
    {
        $mform = $this->_form;
        /** @var CLIOption $option */
        foreach ($script->get_options() as $option) {
            $unique = $script->file_name . '_' . $option->longname;
            $enablekey = $unique . '_enabled';
            $groupname = $unique . '_group';
            $group = [];

            // Open wrapper div.
            $group[] = $mform->createElement('html', '<div class="lsucli-option-row">');

            // Label spans full row for clickability - checkbox label includes the option name.
            if ($option->type == OptionType::FLAG) {
                // FLAG: Just a checkbox - presence means enabled.
                $group[] = $mform->createElement(
                    'advcheckbox',
                    $enablekey,
                    '',
                    '<span class="lsucli-label-text">' . htmlspecialchars($option->longname) . '</span>',
                    ['title' => $option->description, 'class' => 'lsucli-checkbox'],
                );
            } else if ($option->type == OptionType::BOOL) {
                // BOOL: Checkbox to enable + toggle switch for true/false.
                $group[] = $mform->createElement(
                    'advcheckbox',
                    $enablekey,
                    '',
                    '<span class="lsucli-label-text">' . htmlspecialchars($option->longname) . '</span>',
                    ['title' => $option->description, 'class' => 'lsucli-checkbox'],
                );
                $group[] = $mform->createElement('html', '<div class="lsucli-control-wrap">');
                $group[] = $mform->createElement(
                    'advcheckbox',
                    $unique,
                    '',
                    '',
                    ['class' => 'lsucli-toggle-switch'],
                    ['false', 'true']
                );
                $group[] = $mform->createElement('html', '</div>');
            } else {
                // STRING or NUMBER: Checkbox to enable + text input for value.
                $group[] = $mform->createElement(
                    'advcheckbox',
                    $enablekey,
                    '',
                    '<span class="lsucli-label-text">' . htmlspecialchars($option->longname) . '</span>',
                    ['title' => $option->description, 'class' => 'lsucli-checkbox'],
                );
                $group[] = $mform->createElement('html', '<div class="lsucli-control-wrap">');
                $group[] = $mform->createElement(
                    'text',
                    $unique,
                    null,
                    ['title' => $option->description, 'class' => 'lsucli-option-value'],
                );
                $group[] = $mform->createElement('html', '</div>');
                if ($option->type == OptionType::NUMBER) {
                    $mform->setType($unique, PARAM_INT);
                } else {
                    $mform->setType($unique, PARAM_TEXT);
                }
            }

            // Close wrapper div.
            $group[] = $mform->createElement('html', '</div>');

            $mform->addGroup($group, $groupname, '', '', false);
            $mform->hideIf($groupname, 'script', 'neq', $script->file_name);

            if ($option->required == true) {
                $mform->addRule($enablekey, get_string('err_required', 'local_lsucli'), 'required', null, 'server');
            }
        }
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
            $unique = $data->script . '_' . $option->longname;
            $enablekey = $unique . '_enabled';

            if ($option->type == OptionType::FLAG) {
                // FLAG: Just check if enabled.
                $enabled = $data->{$enablekey} ?? 0;
                if ($enabled) {
                    $command[] = "--$option->longname";
                }
            } else if ($option->type == OptionType::BOOL) {
                // BOOL: Check if enabled, then get true/false value.
                $enabled = $data->{$enablekey} ?? 0;
                if ($enabled) {
                    $value = $data->{$unique} ?? 'false';
                    $command[] = "--$option->longname=$value";
                }
            } else {
                // STRING or NUMBER: Check if enabled, then get value.
                $enabled = $data->{$enablekey} ?? 0;
                $value = $data->{$unique} ?? null;
                if ($enabled && $value !== null && $value !== '') {
                    if ($option->type == OptionType::STRING) {
                        $command[] = "--$option->longname=" . escapeshellarg($value);
                    } else {
                        $command[] = "--$option->longname=$value";
                    }
                }
            }
        }
        $command[] = $data->{$data->script . '_custom_post'} ?? null;
        $command = array_filter($command, function($v) {
            return $v !== null;
        });
        $command = implode(' ', $command);
        return $command;
    }
}