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
 * @package   local_natsane
 * @copyright 2017 Robert Russo, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $yesno = [
        0 => get_string('no'),
        1 => get_string('yes')
    ];

    $settings = new admin_settingpage('local_natsane', get_string('convert_kalvidres', 'local_natsane'));

    $ADMIN->add('localplugins', $settings);

    $settings->add(
        new admin_setting_heading(
            'local_natsane_header', '',
            get_string('convert_kalvidres_help', 'local_natsane')
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'local_natsane_kalvidres_conv_hide',
            get_string('hide_kaltura_items', 'local_natsane'),
            get_string('hide_kaltura_items_help', 'local_natsane'),
            0, // Default.
            $yesno
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'local_natsane_kalvidres_postconv_hide',
            get_string('hide_kaltura_items2', 'local_natsane'),
            get_string('hide_kaltura_items2_help', 'local_natsane'),
            0, // Default.
            $yesno
        )
    );
}
