<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * List and edit offices.
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once('classes/office.php');
require_once 'offices_form.php';
require_once 'lib.php';

global $DB, $OUTPUT, $PAGE;
sge::allow_only(sge::FACADVISOR, sge::COMMISSIONER);

// Only required to return the user to the correct ballot page.
$election_id = optional_param('election_id', false, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/sgelection/officelist.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(sge::_str('office_page_header'));

require_login();

// Setup nav, depending on voter.
$voter    = new voter($USER->id);
$renderer = $PAGE->get_renderer('block_sgelection');
$renderer->set_nav(null, $voter);

$formactnprms = $election_id ? array('election_id'=>$election_id) : null;
$returnparams = array('rtn'=>'officelist');
if($election_id){
    $returnparams['election_id'] = $election_id;
}

$form = new office_form(new moodle_url('offices.php',$formactnprms), $returnparams);
echo $OUTPUT->header();
$form->display();

$offices = office::get_all();
$table = new html_table();
$table->head = array('Office', 'College', '# seats', 'Weight', 'Edit', 'Delete');

foreach($offices as $o){
    $name = $o->name;

    $commonparams = array('id'=>$o->id);
    if ($election_id) {
        $commonparams += array('election_id' => $election_id);
    }

    $linkparams   = array_merge($commonparams, array('rtn'=>'officelist'));
    $dletparams   = array_merge($commonparams, array('class' => 'office', 'rtn'=>'officelist'));

    $link = html_writer::link(new moodle_url('offices.php', $linkparams), 'edit');
    $dlet = html_writer::link(new moodle_url('delete.php',  $dletparams), 'delete');
    $table->data[] = new html_table_row(array($name, $o->college, $o->number, $o->weight, $link, $dlet));
}

echo html_writer::table($table);
echo $OUTPUT->footer();


