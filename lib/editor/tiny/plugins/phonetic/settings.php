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
 * Tiny phonetic settings.
 *
 * @package     tiny_phonetic
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('editortiny', new admin_category('tiny_phonetic', new lang_string('pluginname', 'tiny_phonetic')));

    $settings = new admin_settingpage('tiny_phonetic_settings', new lang_string('settings', 'tiny_phonetic'));

    if ($ADMIN->fulltree) {
        // Pulmonic Consonants (group 1).
        $default1 = "&#112;\r\n&#112;&#688;\r\n&#98;\r\n&#116;\r\n&#116;&#688;\r\n&#100;\r\n&#648;\r\n&#598;\r\n"
            . "&#99;\r\n&#607;\r\n&#107;\r\n&#107;&#688;\r\n&#103;\r\n&#113;\r\n&#610;\r\n&#660;\r\n"
            . "&#109;\r\n&#625;\r\n&#110;\r\n&#627;\r\n&#626;\r\n&#331;\r\n&#628;\r\n&#665;\r\n"
            . "&#114;\r\n&#640;\r\n&#638;\r\n&#637;\r\n&#632;\r\n&#946;\r\n&#102;\r\n&#118;\r\n"
            . "&#952;\r\n&#240;\r\n&#115;\r\n&#122;\r\n&#643;\r\n&#679;\r\n&#658;\r\n&#676;\r\n"
            . "&#642;\r\n&#656;\r\n&#231;\r\n&#669;\r\n&#120;\r\n&#611;\r\n&#967;\r\n&#641;\r\n"
            . "&#295;\r\n&#661;\r\n&#104;\r\n&#614;\r\n&#620;\r\n&#622;\r\n&#651;\r\n&#633;\r\n"
            . "&#635;\r\n&#106;\r\n&#624;\r\n&#108;\r\n&#621;\r\n&#654;\r\n&#671;\r\n&#619;\r\n&#119;\r\n";

        $settings->add(new admin_setting_configtextarea(
            'tiny_phonetic/librarygroup1',
            new lang_string('librarygroup1', 'tiny_phonetic'),
            new lang_string('librarygroup1_desc', 'tiny_phonetic'),
            $default1
        ));

        // Vowels (group 2).
        $default2 = "&#105;\r\n&#121;\r\n&#616;\r\n&#649;\r\n&#623;\r\n&#117;\r\n&#618;\r\n&#655;\r\n&#650;\r\n"
            . "&#101;\r\n&#248;\r\n&#600;\r\n&#601;\r\n&#602;\r\n&#629;\r\n&#612;\r\n&#111;\r\n"
            . "&#603;\r\n&#603;&#771;\r\n&#339;\r\n&#339;&#771;\r\n&#604;\r\n&#605;\r\n&#606;\r\n"
            . "&#7463;\r\n&#596;\r\n&#596;&#771;\r\n&#230;\r\n&#592;\r\n&#097;\r\n&#630;\r\n"
            . "&#593;\r\n&#593;&#771;\r\n&#594;\r\n";

        $settings->add(new admin_setting_configtextarea(
            'tiny_phonetic/librarygroup2',
            new lang_string('librarygroup2', 'tiny_phonetic'),
            new lang_string('librarygroup2_desc', 'tiny_phonetic'),
            $default2
        ));

        // Non-Pulmonic Consonants (group 3).
        $default3 = "&#664;\r\n&#448;\r\n&#7502;\r\n&#450;\r\n&#449;\r\n&#595;\r\n&#599;\r\n&#644;\r\n&#608;\r\n&#667;\r\n";

        $settings->add(new admin_setting_configtextarea(
            'tiny_phonetic/librarygroup3',
            new lang_string('librarygroup3', 'tiny_phonetic'),
            new lang_string('librarygroup3_desc', 'tiny_phonetic'),
            $default3
        ));

        // Other (group 4).
        $default4 = "&#653;\r\n&#613;\r\n&#668;\r\n&#674;\r\n&#673;\r\n&#597;\r\n&#657;\r\n&#634;\r\n&#615;\r\n"
            . "&#741;\r\n&#742;\r\n&#743;\r\n&#744;\r\n&#745;\r\n&#741;&#745;\r\n&#745;&#741;\r\n"
            . "&#743;&#741;\r\n&#744;&#743;\r\n&#743;&#741;&#743;\r\n&#8593;\r\n&#8595;\r\n&#8599;\r\n&#8600;\r\n";

        $settings->add(new admin_setting_configtextarea(
            'tiny_phonetic/librarygroup4',
            new lang_string('librarygroup4', 'tiny_phonetic'),
            new lang_string('librarygroup4_desc', 'tiny_phonetic'),
            $default4
        ));

        // Spacing Modifiers (group 5).
        $default5 = "&#688;\r\n&#695;\r\n&#690;\r\n&#7518;\r\n&#740;\r\n&#737;\r\n&#8319;\r\n&#736;\r\n"
            . "&#712;\r\n&#716;\r\n&#720;\r\n&#721;\r\n";

        $settings->add(new admin_setting_configtextarea(
            'tiny_phonetic/librarygroup5',
            new lang_string('librarygroup5', 'tiny_phonetic'),
            new lang_string('librarygroup5_desc', 'tiny_phonetic'),
            $default5
        ));

        // Combining (group 6).
        $default6 = "&#805;\r\n&#812;\r\n&#825;\r\n&#796;\r\n&#799;\r\n&#817;\r\n&#776;\r\n&#829;\r\n"
            . "&#809;\r\n&#815;\r\n&#804;\r\n&#816;\r\n&#828;\r\n&#820;\r\n&#797;\r\n&#798;\r\n"
            . "&#793;\r\n&#792;\r\n&#810;\r\n&#826;\r\n&#827;\r\n&#834;\r\n&#794;\r\n&#734;\r\n"
            . "&#860;\r\n&#865;\r\n&#774;\r\n&#779;\r\n&#769;\r\n&#772;\r\n&#768;\r\n&#783;\r\n"
            . "&#780;\r\n&#770;\r\n&#7620;\r\n&#7621;\r\n&#7624;\r\n";

        $settings->add(new admin_setting_configtextarea(
            'tiny_phonetic/librarygroup6',
            new lang_string('librarygroup6', 'tiny_phonetic'),
            new lang_string('librarygroup6_desc', 'tiny_phonetic'),
            $default6
        ));
    }
}
