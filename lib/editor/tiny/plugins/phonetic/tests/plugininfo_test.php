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

namespace tiny_phonetic\tests;

use advanced_testcase;
use tiny_phonetic\plugininfo;
use context_system;

/**
 * PHPUnit tests for tiny_phonetic plugininfo.
 *
 * @package     tiny_phonetic
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \tiny_phonetic\plugininfo
 */
class plugininfo_test extends advanced_testcase {

    /**
     * Test that get_available_buttons() returns the correct button name.
     */
    public function test_get_available_buttons(): void {
        $this->assertSame(['tiny_phonetic/phonetic'], plugininfo::get_available_buttons());
    }

    /**
     * Test that get_available_menuitems() returns the correct menu item name.
     */
    public function test_get_available_menuitems(): void {
        $this->assertSame(['tiny_phonetic/phonetic'], plugininfo::get_available_menuitems());
    }

    /**
     * Test that get_plugin_configuration_for_context() returns exactly 6 library groups.
     */
    public function test_configuration_returns_six_groups(): void {
        $this->resetAfterTest();
        $config = plugininfo::get_plugin_configuration_for_context(context_system::instance(), [], []);
        $this->assertArrayHasKey('libraries', $config);
        $this->assertCount(6, $config['libraries']);
    }

    /**
     * Test that only the first group has active => true.
     */
    public function test_first_group_is_active(): void {
        $this->resetAfterTest();
        $config = plugininfo::get_plugin_configuration_for_context(context_system::instance(), [], []);
        $this->assertTrue($config['libraries'][0]['active']);
        $this->assertFalse($config['libraries'][1]['active'] ?? false);
        $this->assertFalse($config['libraries'][2]['active'] ?? false);
        $this->assertFalse($config['libraries'][3]['active'] ?? false);
        $this->assertFalse($config['libraries'][4]['active'] ?? false);
        $this->assertFalse($config['libraries'][5]['active'] ?? false);
    }

    /**
     * Test that only group 6 (index 5) has combining => true.
     */
    public function test_group6_is_marked_combining(): void {
        $this->resetAfterTest();
        $config = plugininfo::get_plugin_configuration_for_context(context_system::instance(), [], []);
        $this->assertFalse($config['libraries'][0]['combining'] ?? false);
        $this->assertFalse($config['libraries'][1]['combining'] ?? false);
        $this->assertFalse($config['libraries'][2]['combining'] ?? false);
        $this->assertFalse($config['libraries'][3]['combining'] ?? false);
        $this->assertFalse($config['libraries'][4]['combining'] ?? false);
        $this->assertTrue($config['libraries'][5]['combining']);
    }

    /**
     * Test that a config override via set_config() is reflected in elements.
     */
    public function test_config_override_is_reflected(): void {
        $this->resetAfterTest();
        set_config('librarygroup1', "&#112;\n&#98;", 'tiny_phonetic');
        $config = plugininfo::get_plugin_configuration_for_context(context_system::instance(), [], []);
        $this->assertCount(2, $config['libraries'][0]['elements']);
    }
}
