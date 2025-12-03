<?php
require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");

require_once(__DIR__ . '/classes/form.php');

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
    $command = $mform->build_cmd();
    echo "EXECUTING: $command";
    exec($command, $output);
    echo implode("\n", $output);
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