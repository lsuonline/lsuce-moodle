<?php

require_once '../../config.php';
require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

require_once 'lib.php';
require_once $CFG->libdir . '/quick_template/lib.php';

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/ues_meta_viewer:access', $context);

$supported_types = ues_meta_viewer::supported_types();

$type = required_param('type', PARAM_TEXT);

if (!isset($supported_types[$type])) {
    print_error('unsupported_type', 'block_ues_meta_viewer', '', $type);
}

$supported_type = $supported_types[$type];

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 100, PARAM_INT);

$_s = ues::gen_str('block_ues_meta_viewer');

$blockname = $_s('pluginname');
$heading = $_s('viewer', $supported_type->name());

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);
$PAGE->set_heading($blockname . ': '. $heading);
$PAGE->set_title($heading);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($heading);
$PAGE->set_title($heading);
$PAGE->set_url('/blocks/ues_meta_viewer/viewer.php', array('type' => $type));

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

$fields = ues_meta_viewer::generate_keys($type, $USER);

$head = array();
$search = array();
$handlers = array();
$select = array();
$params = array('type' => $type);

foreach ($fields as $field) {
    $handler = ues_meta_viewer::handler($type, $field);

    $head[] = $handler->name();
    $search[] = $handler->html();
    $handlers[] = $handler;

    $value = $handler->value();
    // Only add searched fields as GET param
    if (trim($value) !== '') {
        $params[$field] = $value;
    }
}

$search_table = new html_table();
$search_table->head = $head;
$search_table->data = array(new html_table_row($search));

if (!empty($_REQUEST['search'])) {
    $by_filters = ues_meta_viewer::sql($handlers);

    $count = $type::count($by_filters);
    $res = $type::get_all($by_filters, true, '', '*', $page, $perpage);

    $params['search'] = get_string('search');

    $result = $count ?
        ues_meta_viewer::result_table($res, $handlers) :
        null;
    $posted = true;
} else {
    $count = 0;
    $result = $_s('search');
    $posted = false;
}

$baseurl = new moodle_url('viewer.php', $params);

$data = array(
    'search' => $search_table,
    'posted' => $posted,
    'result' => $result,
    'type' => $type,
    'count' => $count,
    'paging' => $count ? $OUTPUT->paging_bar($count, $page, $perpage, $baseurl->out()) : 0
);

$registers = array(
    'function' => array(
        'print' => function ($params, &$smarty) {
            return html_writer::table($params['table']);
        }
    )
);

quick_template::render('viewer.tpl', $data, 'block_ues_meta_viewer', $registers);

echo $OUTPUT->footer();
