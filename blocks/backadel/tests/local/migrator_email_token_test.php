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

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

/**
 * End-to-end coverage for {@see migrator::resolve_instructors()} and
 * {@see migrator::upsert_catalogue()} with email-shaped instructor tokens
 * — bug-067, bug-070.
 *
 * Bug-067: proves that when the parsed instructor array contains "wjian15@lsu.edu":
 *   - the local-part ("wjian15") is what gets written to the username column,
 *   - the resolved Moodle user's email is written to the email column,
 *   - resolved = 1 and resolvedvia = 'email'.
 *
 * Bug-070: proves that block_backadel_catalogue.instructors stores the local-part
 * ("wjian15") not the full email ("wjian15@lsu.edu"), so simple_restore's
 * LIKE predicate '"%wjian15%"' still matches after the bug-059 parser change.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group      backadel_email_token
 *
 * @covers \block_backadel\local\migrator
 */
final class migrator_email_token_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        instructor_resolver::reset_cache();
    }

    /**
     * Direct ReflectionMethod invocation of the private resolve_instructors()
     * proves the email-token branch writes the correct columns into the
     * block_backadel_teachers table for a pre-seeded courses row.
     *
     * @covers \block_backadel\local\migrator::resolve_instructors
     */
    public function test_resolve_instructors_writes_correct_columns_for_email_token(): void {
        global $DB;

        // Seed the Moodle user whose email matches the parsed instructor token.
        $user = $this->getDataGenerator()->create_user([
            'username' => 'wjian15',
            'email'    => 'wjian15@lsu.edu',
        ]);

        // Seed a minimal block_backadel_courses row to act as the FK target.
        $coursesid = $DB->insert_record('block_backadel_courses', (object) [
            'courseid'         => null,
            'coursefullname'   => 'Email Token Coverage',
            'courseshortname'  => 'EMAIL-TOKEN',
            'courseidnumber'   => null,
            'status'           => 'available',
            'filepath'         => '/tmp/email-token-coverage.zip',
            'filepath_hash'    => sha1('/tmp/email-token-coverage.zip'),
            'filename'         => 'email-token-coverage.zip',
            'filesize'         => null,
            'timecreated'      => time(),
            'backupcreated'    => null,
            'semester'         => null,
            'academicperiodid' => null,
            'coursetype'       => 'other',
            'statusid'         => null,
        ]);

        // Invoke the private resolve_instructors() with a parsed array that
        // carries an email-shaped instructor token.
        $migrator = new migrator();
        $method = new \ReflectionMethod(migrator::class, 'resolve_instructors');
        $method->setAccessible(true);
        $method->invoke($migrator, ['instructors' => ['wjian15@lsu.edu']], (int) $coursesid);

        $rows = $DB->get_records('block_backadel_teachers', ['coursesid' => $coursesid]);
        $this->assertCount(1, $rows, 'exactly one teacher row must be written for the email token');

        $row = reset($rows);
        $this->assertSame('wjian15', $row->username, 'local part must be stored in username column');
        $this->assertSame('wjian15@lsu.edu', $row->email, 'resolved user email must be stored in email column');
        $this->assertSame((int) $user->id, (int) $row->userid, 'userid must point at the resolved Moodle user');
        $this->assertSame(1, (int) $row->resolved, 'resolved flag must be 1 for a successful email match');
        $this->assertSame('email', $row->resolvedvia, 'resolvedvia must be "email" for an email-token hit');
    }

    /**
     * Bug-070: upsert_catalogue() must store the local-part in the instructors
     * JSON column even when the parsed instructor token is a full email address.
     *
     * simple_restore's LIKE predicate is '"%wjian15%"' (local-part surrounded by
     * double quotes). If the catalogue stores '["wjian15@lsu.edu"]' the match
     * fails because the closing '"' is not immediately after 'wjian15'.
     *
     * @covers \block_backadel\local\migrator::upsert_catalogue
     */
    public function test_upsert_catalogue_stores_local_part_not_full_email(): void {
        global $DB;

        // Use a real temp file so filesize() succeeds inside upsert_catalogue().
        $tmpfile = tempnam(sys_get_temp_dir(), 'backadel_test_');
        file_put_contents($tmpfile, 'dummy');

        $migrator = new migrator();
        $method = new \ReflectionMethod(migrator::class, 'upsert_catalogue');
        $method->setAccessible(true);

        $parsed = [
            'instructors'   => ['wjian15@lsu.edu', 'pfsouth@lsu.edu'],
            'year'          => 2020,
            'semester'      => 'Fall',
            'dept'          => 'ACCT',
            'course_num'    => '2101',
            'shortname_hint' => 'ACCT-2101',
            'pattern'       => 'backadel_instructor',
            'backup_ts'     => 0,
        ];

        $method->invoke($migrator, 'ftp', $tmpfile, $parsed);
        @unlink($tmpfile);

        $row = $DB->get_record('block_backadel_catalogue', ['filepath_hash' => sha1($tmpfile)]);
        $this->assertNotFalse($row, 'catalogue row must be inserted');

        $stored = json_decode($row->instructors, true);
        $this->assertIsArray($stored);
        $this->assertContains(
            'wjian15',
            $stored,
            'catalogue instructors must contain local-part "wjian15", not full email'
        );
        $this->assertContains(
            'pfsouth',
            $stored,
            'catalogue instructors must contain local-part "pfsouth", not full email'
        );
        $this->assertNotContains(
            'wjian15@lsu.edu',
            $stored,
            'catalogue instructors must NOT contain the full email token'
        );
    }
}
