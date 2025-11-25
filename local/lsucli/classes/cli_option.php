<?php
namespace local_lsucli;

defined('MOODLE_INTERNAL') || die();


enum OptionType: int {
    case BOOL = 1;
    case STRING = 2;
    case NUMBER = 3;
}

class CLIOption {
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

    public function html() {
        # On click, toggle the input checkbox or focus the input.
        $html = '<tr
            class="cli-option-row"
            title="' . $this->description . '"
            onclick="
                const input = this.querySelector(\'input\');
                if (input) {
                    if (input.type === \'checkbox\') {
                        input.checked = !input.checked;
                    } else {
                        input.focus();
                    }
                }"
            >';
        if ($this->type === OptionType::BOOL) {
            $html .= '<td><input type="checkbox" /></td>';
        } else {
            $html .= '<td></td>';
        }
        $html .= '<td>' . htmlspecialchars($this->longname) . '</td>';
        $html .= '<td>';
        if ($this->type === OptionType::NUMBER) {
            $html .= '<input type="number" />';
        } elseif ($this->type === OptionType::STRING) {
            $html .= '<input type="text" />';
        }
        $html .= '</td></tr>';
        return $html;
    }

    /*
    * @return CLIOption[]
    */
    static function parse_lines(array $lines) {
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

            $options_array[] = new CLIOption(
                $shortname,
                $longname,
                $description,
                $type
            );
        }
        return $options_array;
    }


    static function array_to_html($options) {
        if (count($options) === 0) {
            return '';
        }
        $html = '<a href="javascript:void(0)" 
            class="cli-table-toggle"
            onclick="this.nextElementSibling.classList.toggle(\'hidden\');">
                Show/Hide Options
            </a>';
        $html .= '<table class="hidden">';
        foreach ($options as $option) {
            $html .= $option->html();
        }
        $html .= '</table>';
        return $html;
    }
}