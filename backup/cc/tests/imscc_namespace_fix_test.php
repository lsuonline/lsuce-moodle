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
 * Unit tests for the IMS CC namespace fix.
 *
 * LSU added - Tests the fix for DOMException "Namespace Error" when exporting
 * courses with quizzes to IMS Common Cartridge format. The fix skips the
 * reserved 'xmlns' prefix in general_cc_file::on_create() to avoid
 * createAttributeNS() with an invalid qualified name.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     general_cc_file
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/cc/cc_includes.php');

/**
 * Unit tests for IMS CC namespace fix.
 *
 * LSU added - See file docblock for details.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2026 LSU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class imscc_namespace_fix_test extends \advanced_testcase {

    /**
     * Test that assesment11_resurce_file can be created when the fix is enabled.
     *
     * With the LSU IMS CC namespace fix enabled (or default), creating an
     * assesment11_resurce_file should not throw DOMException.
     */
    public function test_namespace_fix_enabled_creates_assessment_without_error(): void {
        $this->resetAfterTest(true);

        set_config('imscc_namespace_fix', 1, 'backup');

        $rt = new assesment11_resurce_file();
        $this->assertInstanceOf(assesment11_resurce_file::class, $rt);
    }

    /**
     * Test that assesment11_resurce_file can be created when config is not set (defaults to fix).
     *
     * When the setting has never been saved, the fix should default to enabled.
     */
    public function test_namespace_fix_defaults_to_enabled_when_config_not_set(): void {
        $this->resetAfterTest(true);

        // Ensure the setting is not set (e.g. fresh install).
        unset_config('imscc_namespace_fix', 'backup');

        $rt = new assesment11_resurce_file();
        $this->assertInstanceOf(assesment11_resurce_file::class, $rt);
    }

    /**
     * Test that assesment11_resurce_file throws DOMException when fix is disabled (buggy behavior).
     *
     * With the LSU IMS CC namespace fix disabled, the original buggy code path
     * runs and createAttributeNS('...', 'xmlns:dummy') throws DOMException
     * because 'xmlns' is a reserved prefix in XML.
     */
    public function test_namespace_fix_disabled_throws_domexception(): void {
        $this->resetAfterTest(true);

        set_config('imscc_namespace_fix', 0, 'backup');

        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('Namespace Error');

        new assesment11_resurce_file();
    }
}
