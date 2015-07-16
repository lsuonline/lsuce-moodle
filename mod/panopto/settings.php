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
 * Url module admin settings and defaults
 *
 * @package    mod_panopto
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2015 Robert Russo and Louisiana State University {@link http://www.lsu.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('panopto/framesize',
        get_string('framesize', 'panopto'), get_string('configframesize', 'panopto'), 130, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('panopto/requiremodintro',
        get_string('requirepanoptodesc', 'panopto'), get_string('configrequirepanoptodesc', 'panopto'), 1));
    $settings->add(new admin_setting_configtext('panopto/panoptoserver', get_string('panoptoserver', 'panopto'),
        get_string('configpanoptoserver', 'panopto'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('panopto/authinstance', get_string('authinstance', 'panopto'),
        get_string('configauthinstance', 'panopto'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configmultiselect('panopto/displayoptions',
        get_string('displayoptions', 'panopto'), get_string('configdisplayoptions', 'panopto'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('panoptomodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('panopto/printintro',
        get_string('printintro', 'panopto'), get_string('printintroexplain', 'panopto'), 1));
    $settings->add(new admin_setting_configselect('panopto/display',
        get_string('displayselect', 'panopto'), get_string('displayselectexplain', 'panopto'), RESOURCELIB_DISPLAY_AUTO, $displayoptions));
    $settings->add(new admin_setting_configtext('panopto/popupwidth',
        get_string('popupwidth', 'panopto'), get_string('popupwidthexplain', 'panopto'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('panopto/popupheight',
        get_string('popupheight', 'panopto'), get_string('popupheightexplain', 'panopto'), 450, PARAM_INT, 7));
}
