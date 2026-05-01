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

namespace tiny_phonetic;

use context;
use editor_tiny\editor;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;
use editor_tiny\plugin_with_menuitems;

/**
 * Tiny phonetic plugin for Moodle.
 *
 * @package     tiny_phonetic
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements plugin_with_buttons, plugin_with_configuration, plugin_with_menuitems {

    /**
     * @return string[]
     */
    public static function get_available_buttons(): array {
        return [
            'tiny_phonetic/phonetic',
        ];
    }

    /**
     * @return string[]
     */
    public static function get_available_menuitems(): array {
        return [
            'tiny_phonetic/phonetic',
        ];
    }

    /**
     * Return plugin configuration for the current context.
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param editor|null $editor
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): array {
        $groups = ['librarygroup1', 'librarygroup2', 'librarygroup3', 'librarygroup4', 'librarygroup5', 'librarygroup6'];
        $libraries = [];

        foreach ($groups as $i => $key) {
            $raw = get_config('tiny_phonetic', $key) ?? '';
            $elements = array_values(array_filter(array_map('trim', explode("\n", $raw))));
            $libraries[] = [
                'key'       => 'group' . ($i + 1),
                'groupname' => get_string($key, 'tiny_phonetic'),
                'elements'  => $elements,
                'active'    => $i === 0,
                'combining' => $i === 5,
            ];
        }

        return ['libraries' => $libraries];
    }
}
