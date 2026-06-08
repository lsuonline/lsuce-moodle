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

namespace block_backadel;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for period_resolver::year_for_course() (MD-2189 Bug-082).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class period_resolver_year_test extends \advanced_testcase {

    /**
     * Year extracted from a 4-digit prefix in the idnumber field.
     */
    public function test_year_for_course_from_idnumber(): void {
        $course = (object)[
            'idnumber'  => '2026Spring_LA_1203',
            'shortname' => '',
            'fullname'  => '',
            'startdate' => 0,
        ];
        $this->assertSame(2026, \block_backadel\local\period_resolver::year_for_course($course));
    }

    /**
     * Year falls back to course startdate when no token in idnumber/shortname/fullname.
     */
    public function test_year_for_course_from_startdate(): void {
        $course = (object)[
            'idnumber'  => '',
            'shortname' => '',
            'fullname'  => '',
            'startdate' => mktime(0, 0, 0, 9, 1, 2024),
        ];
        $this->assertSame(2024, \block_backadel\local\period_resolver::year_for_course($course));
    }

    /**
     * Returns 0 when there is no parseable year and no startdate.
     */
    public function test_year_for_course_no_info_returns_zero(): void {
        $course = (object)[
            'idnumber'  => '',
            'shortname' => '',
            'fullname'  => '',
            'startdate' => 0,
        ];
        $this->assertSame(0, \block_backadel\local\period_resolver::year_for_course($course));
    }

    /**
     * Year extracted from shortname when idnumber is empty.
     */
    public function test_year_for_course_from_shortname(): void {
        $course = (object)[
            'idnumber'  => '',
            'shortname' => '2025Fall_CS_101',
            'fullname'  => '',
            'startdate' => 0,
        ];
        $this->assertSame(2025, \block_backadel\local\period_resolver::year_for_course($course));
    }
}
