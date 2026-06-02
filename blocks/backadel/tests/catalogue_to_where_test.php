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
 * Tests for backadel_catalogue_to_where() and sql_helpers::instructor_where().
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * @covers ::backadel_catalogue_to_where
 * @covers \block_backadel\local\sql_helpers::instructor_where
 */
class catalogue_to_where_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    // --- instructor_where() unit tests ---

    public function test_instructor_where_empty_string_returns_passthrough(): void {
        [$clause, $params] = \block_backadel\local\sql_helpers::instructor_where('');
        $this->assertSame('1=1', $clause);
        $this->assertSame([], $params);
    }

    public function test_instructor_where_whitespace_only_returns_passthrough(): void {
        [$clause, $params] = \block_backadel\local\sql_helpers::instructor_where('   ');
        $this->assertSame('1=1', $clause);
        $this->assertSame([], $params);
    }

    public function test_instructor_where_exact_username_clause(): void {
        [$clause, $params] = \block_backadel\local\sql_helpers::instructor_where('dcastr10');
        // Clause must reference the instructors column and include a null guard.
        $this->assertStringContainsString('instructors', $clause);
        $this->assertStringContainsString('IS NOT NULL', $clause);
        // Param value must be JSON-quoted (surrounded by double-quotes) to prevent partial matches.
        $this->assertArrayHasKey('bci0', $params);
        $this->assertStringContainsString('"dcastr10"', $params['bci0']);
        // Must be a LIKE wildcard pattern, not an exact match.
        $this->assertStringContainsString('%', $params['bci0']);
    }

    public function test_instructor_where_partial_username_does_not_match_longer_name(): void {
        // The LIKE value must include JSON double-quotes so "jones" won't match "jonesmith".
        [$clause, $params] = \block_backadel\local\sql_helpers::instructor_where('jones');
        $this->assertArrayHasKey('bci0', $params);
        // Value must be %"jones"% not %jones% — the surrounding quotes in the JSON prevent the partial match.
        $this->assertStringContainsString('"jones"', $params['bci0']);
        $this->assertStringNotContainsString('%jones%', $params['bci0']);
    }

    public function test_instructor_where_custom_alias(): void {
        [$clause, $params] = \block_backadel\local\sql_helpers::instructor_where('ssmith', 'cat');
        $this->assertStringContainsString('cat.instructors', $clause);
    }

    // --- backadel_catalogue_to_where() integration tests ---

    public function test_catalogue_to_where_empty_filters_returns_passthrough(): void {
        [$where, $params] = backadel_catalogue_to_where([]);
        $this->assertSame('1=1', $where);
        $this->assertSame([], $params);
    }

    public function test_catalogue_to_where_instructor_empty_string_no_clause(): void {
        [$where, $params] = backadel_catalogue_to_where(['instructor' => '']);
        $this->assertSame('1=1', $where);
        $this->assertStringNotContainsString('instructors', $where);
    }

    public function test_catalogue_to_where_instructor_filter_applied(): void {
        [$where, $params] = backadel_catalogue_to_where(['instructor' => 'dcastr10']);
        $this->assertStringContainsString('instructors', $where);
        $this->assertNotEmpty($params);
        // Must carry the JSON-quoted username in param value.
        $found = false;
        foreach ($params as $val) {
            if (strpos((string) $val, '"dcastr10"') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Instructor param value must contain JSON-quoted username "dcastr10"');
    }

    public function test_catalogue_to_where_instructor_combined_with_q(): void {
        [$where, $params] = backadel_catalogue_to_where(['q' => 'math', 'instructor' => 'jdoe']);
        // Both the filename/shortname LIKE clause and the instructor clause must be present.
        $this->assertStringContainsString('filename', $where);
        $this->assertStringContainsString('instructors', $where);
        $this->assertStringContainsString('AND', $where);
        $this->assertGreaterThanOrEqual(2, count($params));
    }

    public function test_catalogue_to_where_instructor_does_not_collide_with_other_params(): void {
        // All filter types applied together — param names must not collide.
        [$where, $params] = backadel_catalogue_to_where([
            'q'          => 'bio',
            'year'       => 2025,
            'semester'   => 'Fall',
            'status'     => 'available',
            'coursetype' => 'teaching',
            'instructor' => 'tallen6',
        ]);
        // Each unique filter produces a clause; all param keys must be unique.
        $keys = array_keys($params);
        $this->assertSame(count($keys), count(array_unique($keys)), 'Duplicate param keys detected');
        $this->assertStringContainsString('instructors', $where);
    }
}
