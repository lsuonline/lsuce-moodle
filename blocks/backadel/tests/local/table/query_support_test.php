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

declare(strict_types=1);

namespace block_backadel\tests\local\table;

defined('MOODLE_INTERNAL') || die();

use block_backadel\local\table\query_support;

/**
 * Tests for {@see query_support}.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \block_backadel\local\table\query_support
 * @coversDefaultClass \block_backadel\local\table\query_support
 */
final class query_support_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_empty_constraints_returns_always_true(): void {
        [$where, $params] = query_support::where_from_constraints([]);
        $this->assertSame('1=1', $where);
        $this->assertSame([], $params);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_shortname_contains_builds_like(): void {
        [$where, $params] = query_support::where_from_constraints([
            ['field' => 'shortname', 'operator' => 'contains', 'value' => 'TEST'],
        ]);
        $this->assertMatchesRegularExpression('/co\.shortname\s+LIKE/i', $where);
        $this->assertCount(1, $params);
        $this->assertArrayHasKey('sc0', $params);
        $this->assertStringContainsString('%', (string) $params['sc0']);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_invalid_field_throws_coding_exception(): void {
        $this->expectException(\coding_exception::class);
        query_support::where_from_constraints([
            ['field' => 'evil_field', 'operator' => 'contains', 'value' => 'x'],
        ]);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_invalid_operator_throws_coding_exception(): void {
        $this->expectException(\coding_exception::class);
        query_support::where_from_constraints([
            ['field' => 'shortname', 'operator' => 'startswithz', 'value' => 'x'],
        ]);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_isnot_builds_not_equal(): void {
        [$where, $params] = query_support::where_from_constraints([
            ['field' => 'fullname', 'operator' => 'isnot', 'value' => 'Archive'],
        ]);
        $this->assertStringContainsString('<>', $where);
        $this->assertStringContainsString('co.fullname', $where);
        $this->assertSame(['sc0' => 'Archive'], $params);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_sql_injection_attempt_is_escaped(): void {
        $payload = '\'; DROP TABLE courses; --';
        [$where, $params] = query_support::where_from_constraints([
            ['field' => 'idnumber', 'operator' => 'contains', 'value' => $payload],
        ]);
        $this->assertStringNotContainsString($payload, $where);
        $this->assertStringNotContainsString('DROP TABLE', strtoupper($where));
        $this->assertArrayHasKey('sc0', $params);
    }

    /**
     * @covers ::where_from_constraints
     */
    public function test_multiple_constraints_joined_with_and(): void {
        [$where, $params] = query_support::where_from_constraints([
            ['field' => 'shortname', 'operator' => 'contains', 'value' => 'A'],
            ['field' => 'category', 'operator' => 'startswith', 'value' => 'Sci'],
        ]);
        $this->assertStringContainsString(' AND ', $where);
        $this->assertArrayHasKey('sc0', $params);
        $this->assertArrayHasKey('sc1', $params);
        $this->assertCount(2, $params);
    }
}
