<?php
require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

//admin_externalpage_setup('local_lsucli');

function pretty_print_r($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

enum OptionType: int {
    case BOOL = 1;
    case STRING = 2;
    case NUMBER = 3;
}

class CommandlineOption {
    public ?string $shortname;
    public string $longname;
    public ?string $description;
    public OptionType $type;

    public function __construct($shortname, $longname, $description, OptionType $type) {
        $this->shortname = $shortname;
        $this->longname = $longname;
        $this->description = $description;
        $this->type = $type;
    }

    public function __toHtml() {
        $html = '<tr style="background: transparent;">';
        if ($this->type === OptionType::BOOL) {
            $html .= '<td><input type="checkbox" /></td>';
        } else {
            $html .= '<td></td>';
        }
        $html .= '<td title="' . $this->description . '">' . htmlspecialchars($this->longname) . '</td>';
        if ($this->type === OptionType::NUMBER) {
            $html .= '<td ><input type="number" /></td>';
        } elseif ($this->type === OptionType::STRING) {
            $html .= '<td><input type="text" /></td>';
        } else {
            $html .= '<td></td>';
        }
        $html .= '</tr>';
        return $html;
    }

    static function find_help_option_text($contents) {
        $lines = explode(PHP_EOL, $contents);
        $helplines = [];
        $inoptions = false;
        foreach ($lines as $line) {
            if (strpos($line, 'Options:') !== false) {
                $inoptions = true;
                continue;
            }
            if ($inoptions) {
                if (trim($line) === '') {
                    break;
                }
                $helplines[] = $line;
            }
        }
        return implode(PHP_EOL, $helplines);
    }

    static function parse_from_helptext($helptext) {
        $lines = explode(PHP_EOL, $helptext);
        $options_array = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] !== '-') {
                continue;
            }

            $words = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            
            // if (strstr($filepath, 'fix_course_sequence.php') !== false) {
            //     pretty_print_r($words);
            // }
            
            $shortname = null;
            $longname = null;
            $description = '';
            $type = OptionType::BOOL;

            $word = array_shift($words);
            if (preg_match('/^-\S,?$/', $word)) {
                $shortname = $word[1];
                $word = array_shift($words);
            }
            if (substr($word, 0, 2) !== '--') {
                // Invalid option format.
                continue;
            }
            $longtext = substr($word, 2);
            if (strpos($longtext, '=') !== false) {
                $split = explode('=', $longtext, 2);
                $longname = $split[0];
                $typehint = $split[1];
                $number_texts = ['N', 'INTEGER', 'INT', 'NUMBER', 'FLOAT', 'DOUBLE'];
                if ( in_array(strtoupper($typehint), $number_texts) ) {
                    $type = OptionType::NUMBER;
                } else {
                    $type = OptionType::STRING;
                }
            } else {
                $longname = $longtext;
            }
            if ($longname === 'help') {
                // Skip help option.
                continue;
            }

            $description = implode(' ', $words);

            $options_array[] = new CommandlineOption(
                $shortname,
                $longname,
                $description,
                $type
            );
        }
        return $options_array;
    }

    static function parse_from_file($filepath) {
        $contents = file_get_contents($filepath);
        # Find the $help variable.

        $helptext = self::find_help_option_text($contents);

        return self::parse_from_helptext($helptext);
    }

    static function array_to_html($options) {
        $html = '<table>';
        foreach ($options as $option) {
            $html .= $option->__toHtml();
        }
        $html .= '</table>';
        return $html;
    }
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/lsucli/index.php');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$cliscripts = array_diff(scandir($CFG->dirroot . '/admin/cli'), array('..', '.'));

$table = new html_table();
$table->head = array(get_string('scriptname', 'local_lsucli'), get_string('schedule'), 'blah');

foreach ($cliscripts as $script) {
    if (substr($script, -4) !== '.php') {
        continue;
    }

    $schedule_url = new moodle_url('/local/lsucli/schedule.php', array('script' => $script));
    $schedule_button = new single_button($schedule_url, get_string('schedule'));
    $command_options = CommandlineOption::parse_from_file($CFG->dirroot . '/admin/cli/' . $script);
    $row = new html_table_row([
        $script, 
        $OUTPUT->render($schedule_button), 
        CommandlineOption::array_to_html($command_options)
    ]);
    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
