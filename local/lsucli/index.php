<?php
require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

require_once(__DIR__ . '/classes/commandline_option.php');
use local_lsucli\CommandlineOption;

//admin_externalpage_setup('local_lsucli');

function pretty_print_r($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/lsucli/index.php');


echo $OUTPUT->header();

echo "
<style>
    .commandline-option-table {
        width: 25vw;
    }
    .commandline-option-table.hidden {
        display: none;
    }
    .commandline-table-toggle {
        width: 25vw;
        display: block;
    }
    .commandline-option-row {
        background: transparent !important;
        cursor: pointer;
    }
    .commandline-option-row:hover {
        background-color: #ddd !important;
    }
</style>";

echo $OUTPUT->heading(get_string('lsucli', 'local_lsucli'));

$cliscripts = array_diff(scandir($CFG->dirroot . '/admin/cli'), array('..', '.'));

$table = new html_table();
$table->head = array(get_string('scriptname', 'local_lsucli'), get_string('schedule'), '');

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
