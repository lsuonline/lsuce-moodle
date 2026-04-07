<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     tiny_accordion
 * @category    string
 * @copyright   2026 Catalyst IT Australia
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Accordion';
$string['privacy:metadata'] = 'The tiny_accordion plugin does not store any personal data.';
$string['settings_showremoveicon'] = 'Show remove accordion icon in toolbar';
$string['settings_showremoveicon_desc'] = 'Display the remove accordion button in the TinyMCE toolbar. Only applies when accordion icons are enabled.';
$string['settings_showtoolbaricons'] = 'Show accordion icons in toolbar';
$string['settings_showtoolbaricons_desc'] = 'Display the accordion buttons in the TinyMCE toolbar.';
$string['settings_toolbargroup'] = 'Toolbar group';
$string['settings_toolbargroup_advanced'] = 'Advanced';
$string['settings_toolbargroup_content'] = 'Content';
$string['settings_toolbargroup_desc'] = 'Select which toolbar group the accordion icons appear in. Only applies when accordion icons are enabled.';
$string['settings_toolbargroup_formatting'] = 'Formatting';
$string['settings_toolbargroup_indentation'] = 'Indentation';
$string['settings_toolbargroup_lists'] = 'Lists';
$string['settings_toolbargroup_view'] = 'View';
$string['settings_allowinlinestyle'] = 'Allow inline CSS in accordion attributes';
$string['settings_allowinlinestyle_desc'] = 'If enabled, authors can set inline style on the accordion container and header in the accordion attributes dialog. Prefer CSS classes and theme styles when possible.';
$string['settings_classprefixallowlist'] = 'Accordion class prefix allow-list';
$string['settings_classprefixallowlist_desc'] = 'Optional comma-separated list of allowed class prefixes (e.g. "accordion-,course-"). Leave empty to allow any class name that passes server-side cleaning. Classes not starting with one of these prefixes are rejected in the editor.';
$string['accordionattributes'] = 'Accordion attributes';
$string['accordionattributestitle'] = 'Accordion attributes';
$string['accordionattributesintro'] = 'Set optional CSS classes (recommended) or inline styles on the accordion. Use the keyboard to move the caret outside the accordion when needed.';
$string['accordiondetails'] = 'Accordion block (details)';
$string['accordionsummary'] = 'Header (summary)';
$string['accordionclass'] = 'CSS classes';
$string['accordioninlinestyle'] = 'Inline style';
$string['accordionsaveattributes'] = 'Apply';
$string['accordionneedselection'] = 'Place the cursor inside a TinyMCE accordion first.';
$string['accordionnosummary'] = 'This accordion has no summary element.';
