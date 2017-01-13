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
 * @package   local_cas_help_links
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
// defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');

$context = context_system::instance();

global $PAGE, $USER, $CFG;

$PAGE->set_url($CFG->wwwroot . '/local/cas_help_links/category_settings.php');
$PAGE->set_context($context);

require_login();
require_capability('local/cas_help_links:editglobalsettings', $context);

//////////////////////////////////////////////////////////
/// 
/// HANDLE FORM SUBMISSION
/// 
//////////////////////////////////////////////////////////
if ($data = data_submitted() and confirm_sesskey()) {
    
    try {
        
        \local_cas_help_links_input_handler::handle_category_settings_input($data); // @TODO - make this

    } catch (Exception $e) {
        
        var_dump($e);die; // @TODO: make this really do something, validation? errors?

    }

}

//////////////////////////////////////////////////////////
/// 
/// RENDER PAGE
///
/// (NOTE: it is assumed this is a primary instructor or site admin)
/// 
//////////////////////////////////////////////////////////

// get all data
$categorySettingsData = \local_cas_help_links_utility::get_all_category_settings();

// PAGE RENDERING STUFF
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/style.css"));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . "/local/cas_help_links/vendor/styles/bootstrap-toggle.min.css"));
// $PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/cas_help_links/module.js"));
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/cas_help_links/vendor/scripts/bootstrap-toggle.min.js"));

echo $OUTPUT->header();

?>

<div id="component-category-settings">
    
    <form method="POST">

        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />

        <h3>Category Links and Settings</h3>

        <div class="category-list-container col-xs-12">
            <table>
                <?php foreach ($categorySettingsData as $category) {
                    echo '<tr>
                            <td>
                                <div class="checkbox">
                                    <label>
                                        <input class="display-toggle" ' . $category['link_checked'] . ' type="checkbox" name="' . $category['display_input_name'] . '" data-toggle="toggle" data-style="ios">&nbsp;&nbsp;&nbsp;&nbsp;' . $category['category_name'] . '
                                    </label>
                                </div>
                            </td>

                            <td>
                                <input type="text" name="' . $category['link_input_name'] . '"';

                                // if a user-category link exists, add value to input
                                if ($category['link_id']) {
                                    echo ' value="' . $category['link_url'] . '"';
                                }

                                echo '></td><td>';

                                if ( ! $category['link_id']) {
                                    echo '<p class="current-user-category-url default-url">(Using System Default)</p>';
                                }

                                echo '</td></tr>';
                } ?>
            </table>
        </div>

        <button type="submit">Save Changes</button>
    </form>
</div>

<?php

echo $OUTPUT->footer();
