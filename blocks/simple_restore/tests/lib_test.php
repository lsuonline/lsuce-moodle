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

namespace block_simple_restore\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

use ReflectionMethod;
use simple_restore_utils;

/**
 * Tests for {@see \simple_restore_utils} bug-040 catalogue / instructor lookups.
 *
 * Covers:
 *  - username_local_part: strips @domain for email-style usernames, preserves bare usernames.
 *  - backadel_criterion: uses local-part for username suffix, NOT the full @-style username.
 *  - backups_from_catalogue: instructor-fallback fires when shortname yields 0 rows.
 *  - merge_backup_rows: catalogue + filesystem dedup by basename, catalogue wins.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \simple_restore_utils
 */
final class lib_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * @covers \simple_restore_utils::username_local_part
     */
    public function test_username_local_part_strips_at_domain(): void {
        $this->assertSame('lafry', simple_restore_utils::username_local_part('lafry@lsu.edu'));
    }

    /**
     * @covers \simple_restore_utils::username_local_part
     */
    public function test_username_local_part_preserves_bare_username(): void {
        $this->assertSame('admin', simple_restore_utils::username_local_part('admin'));
        $this->assertSame('lafry', simple_restore_utils::username_local_part('lafry'));
    }

    /**
     * @covers \simple_restore_utils::username_local_part
     */
    public function test_username_local_part_empty_string(): void {
        $this->assertSame('', simple_restore_utils::username_local_part(''));
    }

    /**
     * Bug-040: backadel_criterion() must strip @domain when suffix=username,
     * otherwise the legacy filesystem regex never matches files like
     * `..._lafry_...` for users named `lafry@lsu.edu`.
     *
     * @covers \simple_restore_utils::backadel_criterion
     */
    public function test_backadel_criterion_strips_email_domain_for_username_suffix(): void {
        global $USER;
        $this->setUser($this->getDataGenerator()->create_user(['username' => 'lafry@lsu.edu']));
        set_config('suffix', 'username', 'block_backadel');

        $course = (object) ['shortname' => 'irrelevant'];
        $regex = simple_restore_utils::backadel_criterion($course);

        // Must match the bare local-part, not the full email.
        $this->assertStringContainsString('lafry', $regex);
        $this->assertStringNotContainsString('@lsu.edu', $regex);

        // The regex should match a real-world Backadel filename embedding _lafry_.
        $sample = '2012SpringLA120315827_lafry_1337815526.zip';
        $this->assertSame(1, preg_match("/{$regex}/i", $sample));

        // And one ending _lafry.zip.
        $sample2 = 'backadel-2012-Fall-LA-1203-for-Charles-Fryling_lafry.zip';
        $this->assertSame(1, preg_match("/{$regex}/i", $sample2));
    }

    /**
     * Regression: bare-username (no @) still works (e.g. `admin`).
     *
     * @covers \simple_restore_utils::backadel_criterion
     */
    public function test_backadel_criterion_bare_username_still_matches(): void {
        $this->setUser($this->getDataGenerator()->create_user(['username' => 'admin2']));
        set_config('suffix', 'username', 'block_backadel');

        $course = (object) ['shortname' => 'C1'];
        $regex = simple_restore_utils::backadel_criterion($course);

        $this->assertSame(1, preg_match("/{$regex}/i", 'foo_admin2_1234.zip'));
    }

    /**
     * Bug-040 core: when shortname yields 0 catalogue rows, the instructor
     * fallback must fire and surface rows whose `instructors` JSON contains
     * the user's local-part.
     *
     * @covers \simple_restore_utils::backups_from_catalogue
     */
    public function test_backups_from_catalogue_instructor_fallback(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        // Insert a catalogue row whose shortname does NOT match the course
        // we'll search with, but whose instructors JSON contains "lafry".
        $now = time();
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => '2012SpringLA120315827_lafry_1337815526.zip',
            'filepath'      => '/lsubackupdata/2012SpringLA120315827_lafry_1337815526.zip',
            'filepath_full' => '/lsubackupdata/2012SpringLA120315827_lafry_1337815526.zip',
            'filepath_hash' => sha1('/lsubackupdata/2012SpringLA120315827_lafry_1337815526.zip'),
            'source'        => 'legacy_moodleus',
            'year'          => 2012,
            'semester'      => 'Spring',
            'dept'          => 'LA',
            'course_num'    => '1203',
            'shortname'     => 'LA-1203',
            'instructors'   => '["lafry"]',
            'pattern'       => 'semester_legacy',
            'backup_ts'     => $now - 86400,
            'file_size'     => 12345,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        $method = new ReflectionMethod(simple_restore_utils::class, 'backups_from_catalogue');
        $method->setAccessible(true);

        // Search with a shortname that does NOT match the catalogue row,
        // but pass the instructor's email-style username — fallback should fire.
        $rows = $method->invoke(null, 'NoSuchShortname-9999', [], 'lafry@lsu.edu');
        $this->assertCount(1, $rows);
        $this->assertSame('2012SpringLA120315827_lafry_1337815526.zip', $rows[0]->filename);

        // Without the instructor parameter, same shortname yields nothing.
        $rowsempty = $method->invoke(null, 'NoSuchShortname-9999', []);
        $this->assertSame([], $rowsempty);
    }

    /**
     * Shortname match still wins when both are provided and the shortname
     * matches at least one row (no fallback needed).
     *
     * @covers \simple_restore_utils::backups_from_catalogue
     */
    public function test_backups_from_catalogue_shortname_takes_precedence(): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $now = time();
        // Row with matching shortname but DIFFERENT instructor.
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'shortname-match.zip',
            'filepath'      => '/lsubackupdata/shortname-match.zip',
            'filepath_full' => '/lsubackupdata/shortname-match.zip',
            'filepath_hash' => sha1('/lsubackupdata/shortname-match.zip'),
            'source'        => 'backadel_current',
            'shortname'     => 'LA-1203',
            'instructors'   => '["someoneelse"]',
            'pattern'       => 'storage_course',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        // Row that ONLY the instructor fallback would match.
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'instructor-match.zip',
            'filepath'      => '/lsubackupdata/instructor-match.zip',
            'filepath_full' => '/lsubackupdata/instructor-match.zip',
            'filepath_hash' => sha1('/lsubackupdata/instructor-match.zip'),
            'source'        => 'backadel_current',
            'shortname'     => 'OTHER-9999',
            'instructors'   => '["lafry"]',
            'pattern'       => 'storage_course',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        $method = new ReflectionMethod(simple_restore_utils::class, 'backups_from_catalogue');
        $method->setAccessible(true);

        // Shortname matches → fallback should NOT fire; only the shortname row returned.
        $rows = $method->invoke(null, 'LA-1203', [], 'lafry@lsu.edu');
        $this->assertCount(1, $rows);
        $this->assertSame('shortname-match.zip', $rows[0]->filename);
    }

    /**
     * Defensive: empty params return empty array (no unbounded scan).
     *
     * @covers \simple_restore_utils::backups_from_catalogue
     */
    public function test_backups_from_catalogue_empty_inputs_return_nothing(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $method = new ReflectionMethod(simple_restore_utils::class, 'backups_from_catalogue');
        $method->setAccessible(true);

        $this->assertSame([], $method->invoke(null, '', [], ''));
    }

    /**
     * @covers \simple_restore_utils::merge_backup_rows
     */
    public function test_merge_backup_rows_dedupes_by_basename_catalogue_wins(): void {
        $cat = [
            (object) ['filename' => 'shared.zip', 'year' => '2024', 'dept' => 'LA', 'id' => 99],
            (object) ['filename' => 'cat-only.zip', 'year' => '2023', 'dept' => 'LA', 'id' => 100],
        ];
        $fs = [
            // Same basename as catalogue row — catalogue version should win.
            (object) ['filename' => 'shared.zip', 'year' => '', 'dept' => '', 'id' => 0],
            (object) ['filename' => 'fs-only.zip', 'year' => '', 'dept' => '', 'id' => 0],
        ];
        $merged = simple_restore_utils::merge_backup_rows($cat, $fs);

        $this->assertCount(3, $merged);
        // Catalogue order preserved first.
        $this->assertSame('shared.zip', $merged[0]->filename);
        $this->assertSame('2024', $merged[0]->year, 'catalogue row should win on collision');
        $this->assertSame(99, $merged[0]->id);

        $this->assertSame('cat-only.zip', $merged[1]->filename);
        $this->assertSame('fs-only.zip', $merged[2]->filename);
    }

    /**
     * @covers \simple_restore_utils::merge_backup_rows
     */
    public function test_merge_backup_rows_handles_paths_in_filename(): void {
        $cat = [(object) ['filename' => '/abs/path/shared.zip', 'year' => '2024']];
        $fs  = [(object) ['filename' => 'shared.zip', 'year' => '']];
        $merged = simple_restore_utils::merge_backup_rows($cat, $fs);
        $this->assertCount(1, $merged, 'basename comparison should dedupe paths vs basenames');
        $this->assertSame('2024', $merged[0]->year);
    }

    /**
     * @covers \simple_restore_utils::merge_backup_rows
     */
    public function test_merge_backup_rows_skips_rows_without_filename(): void {
        $cat = [(object) ['year' => '2024']]; // no filename
        $fs  = [(object) ['filename' => 'good.zip']];
        $merged = simple_restore_utils::merge_backup_rows($cat, $fs);
        $this->assertCount(1, $merged);
        $this->assertSame('good.zip', $merged[0]->filename);
    }

    /**
     * @covers \simple_restore_utils::merge_backup_rows
     */
    public function test_merge_backup_rows_empty_inputs(): void {
        $this->assertSame([], simple_restore_utils::merge_backup_rows([], []));
    }

    /**
     * Bug-041: list.php gates the filter panel on
     * `$DB->get_manager()->table_exists('block_backadel_catalogue')`. Confirm
     * the table is present in the test environment so the panel WILL render
     * for any user (admin or instructor) regardless of per-course catalogue hits.
     *
     * This is a sanity test — if block_backadel is uninstalled the gate
     * correctly degrades to the legacy behaviour of hiding the panel.
     */
    public function test_bug041_filter_panel_gate_table_exists(): void {
        global $DB;
        $this->assertTrue(
            $DB->get_manager()->table_exists('block_backadel_catalogue'),
            'list.php filter-panel render gate (bug-041) requires this table to exist'
        );
    }
}
