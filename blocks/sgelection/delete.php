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
 * General purpose delete script.
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once 'lib.php';
sge::require_db_classes();

global $OUTPUT, $PAGE;

// Don't allow anyone to use this who shouldn't.
require_login();
sge::allow_only(sge::COMMISSIONER, sge::FACADVISOR);

$id    = required_param('id', PARAM_INT);
$class = required_param('class', PARAM_ALPHANUMEXT);
$eid   = optional_param('election_id', false, PARAM_INT);
$rtn   = required_param('rtn', PARAM_ALPHAEXT);

$object = $class::get_by_id($id);
if($object){
    $object->delete();
    $object->logaction('deleted');
}
$rtnparams = $eid ? array('election_id'=>$eid) : array();
redirect(new moodle_url(sprintf('/blocks/sgelection/%s.php',$rtn), $rtnparams), '', 5);