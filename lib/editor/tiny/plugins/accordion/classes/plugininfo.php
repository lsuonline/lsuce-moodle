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

namespace tiny_accordion;

use context;
use editor_tiny\editor;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;
use editor_tiny\plugin_with_menuitems;

/**
 * Tiny tiny_accordion plugin for Moodle.
 *
 * @package     tiny_accordion
 * @copyright   2026 Catalyst IT Australia
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements plugin_with_buttons, plugin_with_configuration, plugin_with_menuitems {

    /**
     * @return string[]
     */
    public static function get_available_buttons(): array {
        return [
            'tiny_accordion/accordionattributes',
        ];
    }

    /**
     * @return string[]
     */
    public static function get_available_menuitems(): array {
        return [
            'tiny_accordion/accordionattributes',
        ];
    }

    /**
     * Whether the plugin and its characteristics are enabled.
     *
     * @param context $context The context that the editor is used within
     * @param array $options The options passed in when requesting the editor
     * @param array $fpoptions The filepicker options passed in when requesting the editor
     * @param editor|null $editor The editor instance in which the plugin is initialised
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): array {
        return [
            // Controls visibility of both accordion toolbar icons.
            'showtoolbaricons' => get_config('tiny_accordion', 'showtoolbaricons') !== '0',
            // Controls visibility of the remove accordion icon only.
            // Only applies when showtoolbaricons is true.
            'showremoveicon'   => get_config('tiny_accordion', 'showremoveicon') !== '0',
            // Controls which toolbar group the accordion icons appear in.
            'toolbargroup'     => get_config('tiny_accordion', 'toolbargroup') ?: 'content',
            // Allow inline style fields in the accordion attributes dialog (classes are always allowed).
            'allowinlinestyle' => get_config('tiny_accordion', 'allowinlinestyle') === '1',
            // Comma-separated class prefixes; empty = any class allowed (subject to HTMLPurifier).
            'classprefixallowlist' => (string) (get_config('tiny_accordion', 'classprefixallowlist') ?? ''),
        ];
    }
}
