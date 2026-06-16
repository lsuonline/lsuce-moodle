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

namespace report_content2fix;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for report_filter_values.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_filter_values_test extends \advanced_testcase {

    /**
     * Test from_request normalises group-style keys to flat keys.
     */
    public function test_from_request_normalises_group_keys(): void {
        $request = [
            ['name' => 'course:fullname_group[course:fullname_operator]', 'value' => '2'],
            ['name' => 'course:fullname_group[course:fullname_value]', 'value' => 'Math 101'],
        ];

        $obj = local\report_filter_values::from_request($request);
        $flat = $obj->to_flat_map();

        $this->assertSame('2', $flat['course:fullname_operator'] ?? null);
        $this->assertSame('Math 101', $flat['course:fullname_value'] ?? null);
        $this->assertCount(2, $flat);
    }

    /**
     * Test from_request decodes JSON values.
     */
    public function test_from_request_decodes_json_values(): void {
        $request = [
            ['name' => 'malformed_content:timechecked_operator', 'value' => '4'],
            ['name' => 'malformed_content:timechecked_value', 'value' => '{"from": 0, "to": 7}'],
        ];

        $obj = local\report_filter_values::from_request($request);
        $flat = $obj->to_flat_map();

        $this->assertSame('4', $flat['malformed_content:timechecked_operator'] ?? null);
        $this->assertIsArray($flat['malformed_content:timechecked_value'] ?? null);
        $this->assertSame(0, ($flat['malformed_content:timechecked_value'] ?? [])['from'] ?? null);
        $this->assertSame(7, ($flat['malformed_content:timechecked_value'] ?? [])['to'] ?? null);
    }

    /**
     * Test from_flat_map preserves and normalises keys.
     */
    public function test_from_flat_map(): void {
        $flat = [
            'course:fullname_operator' => '1',
            'course:fullname_value' => 'Test',
        ];

        $obj = local\report_filter_values::from_flat_map($flat);
        $this->assertEquals($flat, $obj->to_flat_map());
    }

    /**
     * Test from_flat_map normalises group-style keys.
     */
    public function test_from_flat_map_normalises_group_keys(): void {
        $input = [
            'course:fullname_group[course:fullname_operator]' => '0',
        ];

        $obj = local\report_filter_values::from_flat_map($input);
        $flat = $obj->to_flat_map();

        $this->assertSame('0', $flat['course:fullname_operator'] ?? null);
        $this->assertArrayNotHasKey('course:fullname_group[course:fullname_operator]', $flat);
    }
}
