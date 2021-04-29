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
 * This page handles a user being returned from the BSI server
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/gradelib.php');

require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

// MDL-28463 This is a bit of hack to ensure the new grade is seen by the user's session without them having to logout
if (isset($_SESSION['SESSION']) && isset($_SESSION['SESSION']->gradescorecache)) {
    unset($_SESSION['SESSION']->gradescorecache);
}

$courseurl = new moodle_url('/course/view.php', array('id' => $id));
$url = $courseurl->out();

// Avoid errors being displayed before the user is redirected
$PAGE->set_context(null);

//Output a page containing some script to break out of frames and redirect them

echo '<html><head><title>Redirect page</title></head><body>';

$script = "
    <script type=\"text/javascript\">
    //<![CDATA[
        if (window != top) {
            top.location.href = '{$url}';
        } else {
            location.href = '{$url}';
        }
    //]]
    </script>
";

$clickhere = get_string('return_to_course', BIOSIGID_MODULE_NAME, (object)array('link' => $url));

$noscript = "
    <noscript>
        {$clickhere}
    </noscript>
";

echo $script;
echo $noscript;

echo '</body></html>';