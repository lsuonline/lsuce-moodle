<?php
namespace local_lsucli;

defined('MOODLE_INTERNAL') || die();

class CLIScript {
    public string $file_path;
    public string $file_name;
    public string $help_text;
    public array $options;
    public string $example_text;

    public function __construct($file_name) {
        global $CFG;
        $this->file_path = "$CFG->dirroot/admin/cli/$file_name";
        $file_name = substr($file_name, 0, -4);
        $this->file_name = ltrim(strtolower(preg_replace('/([A-Z])/', '_$1', $file_name)), '_');
        $this->parse_help_text();
    }

    /**
     * Scans the script file for its information.
     */

    function parse_help_text() {
        $contents = file_get_contents($this->file_path);
        $lines = explode(PHP_EOL, $contents);
        $help_lines = [];
        $option_lines = [];
        $example_lines = [];
        $stage = 0;
        foreach ($lines as $line) {
            if ($stage == 0) {
                if (strpos($line, 'Options:') !== false) {
                    $stage = 1;
                    continue;
                }
                $help_lines[] = $line;
            } else if ($stage == 1) {
                if (trim($line) === '' && count($option_lines) > 0) {
                    $stage = 2;
                    continue;
                }
                $option_lines[] = $line;
            } else if ($stage == 2) {
                if (preg_match('/\"\;?/', $line) || $line == 'EOT;') {
                    break;
                }
                $example_lines[] = $line;
            }
        }
        $this->help_text = implode(PHP_EOL, $help_lines);
        $this->options = CLIOption::parse_lines($option_lines);
        $this->example_text = implode(PHP_EOL, $example_lines);
    }

    /**
     * Scans the admin/cli folder for cli scripts and parses them for information.
     * @return CLIScript[]
     */
    public static function gen_scripts(): array {
        global $CFG;
        $results = [];
        $scripts = array_diff(scandir($CFG->dirroot . '/admin/cli'), array('..', '.'));
        foreach ($scripts as $script) {
            $new_script = new CLIScript($script);
            $results[$new_script->file_name] = $new_script;
        }
        return $results;
    }

    /**
     * @return CLIOption[]
     */
    public function get_options(): array {
        return $this->options;
    }
}