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

    /**
     * Parses the option lines of a help text.
     * Creates a CLIOption for each line.
     * @param   string[]    $lines  An array of strings
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
            $description = html_entity_decode($description);
            $description = preg_replace('/\\\(.)/', '$1', $description);

            $options_array[] = new CLIOption(
                $shortname,
                $longname,
                $description,
                $type
            );
        }
        return $options_array;
    }
}