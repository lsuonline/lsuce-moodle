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

    // -----------------------------------------------------------------------
    // Bug-049: get_catalogue_filter_options() must accept an instructor
    // username so the Year / Semester dropdowns aren't empty when the user's
    // only matching rows come from the instructors-JSON fallback.
    // -----------------------------------------------------------------------

    /**
     * Helper: invoke private get_catalogue_filter_options() via reflection.
     *
     * @param string $shortname
     * @param string $instructorusername
     * @return array
     */
    private static function invoke_filter_options(string $shortname, string $instructorusername = ''): array {
        $method = new ReflectionMethod(simple_restore_utils::class, 'get_catalogue_filter_options');
        $method->setAccessible(true);
        return $method->invoke(null, $shortname, $instructorusername);
    }

    /**
     * Bug-049 guard: empty shortname AND empty instructor returns empty —
     * no unbounded table scan.
     *
     * @covers \simple_restore_utils::get_catalogue_filter_options
     */
    public function test_filter_options_empty_inputs_return_empty(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $opts = self::invoke_filter_options('', '');
        $this->assertSame(['years' => [], 'semesters' => []], $opts);
    }

    /**
     * Bug-049: shortname-only call returns the years/semesters of rows
     * whose shortname LIKE-matches (pre-existing behaviour preserved).
     *
     * @covers \simple_restore_utils::get_catalogue_filter_options
     */
    public function test_filter_options_shortname_only_returns_matching_years(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $now = time();
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'teach-2023.zip',
            'filepath'      => '/lsubackupdata/teach-2023.zip',
            'filepath_full' => '/lsubackupdata/teach-2023.zip',
            'filepath_hash' => sha1('/lsubackupdata/teach-2023.zip-shortname'),
            'source'        => 'backadel_current',
            'year'          => 2023,
            'semester'      => 'Fall',
            'shortname'     => 'LA-1203',
            'instructors'   => '["someoneelse"]',
            'pattern'       => 'storage_course',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'teach-2024.zip',
            'filepath'      => '/lsubackupdata/teach-2024.zip',
            'filepath_full' => '/lsubackupdata/teach-2024.zip',
            'filepath_hash' => sha1('/lsubackupdata/teach-2024.zip-shortname'),
            'source'        => 'backadel_current',
            'year'          => 2024,
            'semester'      => 'Spring',
            'shortname'     => 'LA-1203',
            'instructors'   => '["someoneelse"]',
            'pattern'       => 'storage_course',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        $opts = self::invoke_filter_options('LA-1203');
        // Years are returned DESC.
        $this->assertSame([2024, 2023], $opts['years']);
        $this->assertEqualsCanonicalizing(['Fall', 'Spring'], $opts['semesters']);
    }

    /**
     * Bug-049 core: when shortname does NOT match but the instructor username
     * does, the year/semester filter options must still surface from those rows.
     *
     * Simulates a teacher viewing their course "2024 Fall LA 1203 for Charles
     * Fryling" — catalogue rows live under shortname "LA-1203" but the teacher
     * course shortname does not match. Filter dropdowns previously came up
     * empty; with bug-049, the instructor JSON OR-match contributes the years.
     *
     * @covers \simple_restore_utils::get_catalogue_filter_options
     */
    public function test_filter_options_instructor_match_surfaces_years(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $now = time();
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => '2012SpringLA120315827_lafry_1337815526.zip',
            'filepath'      => '/lsubackupdata/2012SpringLA120315827_lafry_1337815526.zip',
            'filepath_full' => '/lsubackupdata/2012SpringLA120315827_lafry_1337815526.zip',
            'filepath_hash' => sha1('/lsubackupdata/2012SpringLA120315827_lafry_1337815526.zip-bug049'),
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
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => '2014FallLA120399999_lafry_1500000000.zip',
            'filepath'      => '/lsubackupdata/2014FallLA120399999_lafry_1500000000.zip',
            'filepath_full' => '/lsubackupdata/2014FallLA120399999_lafry_1500000000.zip',
            'filepath_hash' => sha1('/lsubackupdata/2014FallLA120399999_lafry_1500000000.zip-bug049'),
            'source'        => 'legacy_moodleus',
            'year'          => 2014,
            'semester'      => 'Fall',
            'dept'          => 'LA',
            'course_num'    => '1203',
            'shortname'     => 'LA-1203',
            'instructors'   => '["lafry"]',
            'pattern'       => 'semester_legacy',
            'backup_ts'     => $now - 86400,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        // Shortname-only call (no instructor) should miss these because the
        // teacher course shortname doesn't match the catalogue shortname.
        $opts = self::invoke_filter_options('2024-Fall-LA-1203-for-Charles-Fryling');
        $this->assertSame([], $opts['years'], 'control: shortname-only must NOT match catalogue rows');

        // With instructor username supplied, the OR predicate fires and
        // years come through.
        $opts = self::invoke_filter_options('2024-Fall-LA-1203-for-Charles-Fryling', 'lafry@lsu.edu');
        $this->assertSame([2014, 2012], $opts['years']);
        $this->assertEqualsCanonicalizing(['Fall', 'Spring'], $opts['semesters']);

        // Bare-username also works (no @ to strip).
        $opts = self::invoke_filter_options('2024-Fall-LA-1203-for-Charles-Fryling', 'lafry');
        $this->assertSame([2014, 2012], $opts['years']);
    }

    /**
     * Bug-049 corner case: when ALL the instructor's rows are blueprints
     * (year IS NULL), the Year filter correctly stays empty. Blueprints
     * have no academic year; this is expected behaviour. Bug-050 will
     * present blueprints in their own section with a different filter UX.
     *
     * @covers \simple_restore_utils::get_catalogue_filter_options
     */
    public function test_filter_options_blueprint_only_instructor_returns_empty_years(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $now = time();
        // Two blueprint rows for this instructor — year IS NULL, semester NULL/empty.
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'blueprint-mastercourse-A.zip',
            'filepath'      => '/lsubackupdata/blueprint-mastercourse-A.zip',
            'filepath_full' => '/lsubackupdata/blueprint-mastercourse-A.zip',
            'filepath_hash' => sha1('/lsubackupdata/blueprint-mastercourse-A.zip-bug049'),
            'source'        => 'backadel_current',
            'year'          => null,
            'semester'      => null,
            'shortname'     => null,
            'instructors'   => '["blueprintonly"]',
            'pattern'       => 'blueprint',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'blueprint-materials-B.zip',
            'filepath'      => '/lsubackupdata/blueprint-materials-B.zip',
            'filepath_full' => '/lsubackupdata/blueprint-materials-B.zip',
            'filepath_hash' => sha1('/lsubackupdata/blueprint-materials-B.zip-bug049'),
            'source'        => 'backadel_current',
            'year'          => null,
            'semester'      => '',
            'shortname'     => null,
            'instructors'   => '["blueprintonly"]',
            'pattern'       => 'blueprint',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        $opts = self::invoke_filter_options('', 'blueprintonly');
        $this->assertSame([], $opts['years'], 'blueprint-only catalogue rows correctly yield no Year options');
        $this->assertSame([], $opts['semesters'], 'blueprint-only catalogue rows correctly yield no Semester options');
    }

    /**
     * Bug-049 mixed case: an instructor who has BOTH a teaching row (with
     * year/semester) AND blueprint rows (year=NULL) must see the teaching
     * row's year/semester in the filter options.
     *
     * @covers \simple_restore_utils::get_catalogue_filter_options
     */
    public function test_filter_options_mixed_teaching_and_blueprint_surfaces_years(): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            $this->markTestSkipped('block_backadel_catalogue table not present');
        }

        $now = time();
        // Teaching row with year/semester.
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => '2023SpringEN200012345_mixedteach_1700000000.zip',
            'filepath'      => '/lsubackupdata/2023SpringEN200012345_mixedteach_1700000000.zip',
            'filepath_full' => '/lsubackupdata/2023SpringEN200012345_mixedteach_1700000000.zip',
            'filepath_hash' => sha1('/lsubackupdata/2023SpringEN200012345_mixedteach_1700000000.zip-bug049'),
            'source'        => 'legacy_moodleus',
            'year'          => 2023,
            'semester'      => 'Spring',
            'shortname'     => 'EN-2000',
            'instructors'   => '["mixedteach"]',
            'pattern'       => 'semester_legacy',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);
        // Blueprint row (year=NULL) — should not contribute to year filter.
        $DB->insert_record('block_backadel_catalogue', (object) [
            'filename'      => 'blueprint-EN2000.zip',
            'filepath'      => '/lsubackupdata/blueprint-EN2000.zip',
            'filepath_full' => '/lsubackupdata/blueprint-EN2000.zip',
            'filepath_hash' => sha1('/lsubackupdata/blueprint-EN2000.zip-bug049'),
            'source'        => 'backadel_current',
            'year'          => null,
            'semester'      => null,
            'shortname'     => null,
            'instructors'   => '["mixedteach"]',
            'pattern'       => 'blueprint',
            'backup_ts'     => $now,
            'status'        => 'available',
            'timecreated'   => $now,
            'timemodified'  => $now,
        ]);

        // Course shortname does NOT match the catalogue row, only instructor does.
        $opts = self::invoke_filter_options('2024-Spring-EN-2000-for-J-Doe', 'mixedteach');
        $this->assertSame([2023], $opts['years'], 'teaching row contributes year even when blueprint rows share instructor');
        $this->assertSame(['Spring'], $opts['semesters']);
    }

    // -----------------------------------------------------------------------
    // Bug-055: prep_restore() must stage under make_backup_temp_directory('')
    // — the same path restore_ui_stage_confirm::process() reads. If staging
    // diverges from CONFIRM's lookup directory, the restore engine throws
    // restore_ui_exception('invalidrestorefile') and the user sees a blank
    // failure page.
    // -----------------------------------------------------------------------

    /**
     * The staged copy created by prep_restore() must land at
     * `make_backup_temp_directory('') . '/' . $returnvalue`. Previously
     * prep_restore() used `$CFG->backuptempdir` directly, which can diverge
     * from `make_backup_temp_directory('')` when the dataroot/backuptempdir
     * config has been overridden (rrusso prod-style setup).
     *
     * Drives the legacy backadel branch of selected_backadel() — the simplest
     * code path that exercises prep_restore() end-to-end without needing the
     * full backup_controller. We stash a fake .mbz under
     * `$CFG->dataroot . '/temp/bug055/'`, point `block_backadel.path` at it,
     * and call prep_restore('bug055_fake.mbz', 'backadel', $courseid).
     *
     * @covers \simple_restore_utils::prep_restore
     */
    public function test_prep_restore_stages_under_make_backup_temp_directory(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        // Create the legacy Backadel source directory under dataroot and drop
        // a small fake .mbz there. selected_backadel() (legacy path) reads
        // `$CFG->dataroot . get_config('block_backadel','path') . $fileid`.
        $backadelreldir = '/temp/bug055/';
        $backadelabsdir = $CFG->dataroot . $backadelreldir;
        if (!is_dir($backadelabsdir)) {
            mkdir($backadelabsdir, 0777, true);
        }
        $fakemb = $backadelabsdir . 'bug055_fake.mbz';
        file_put_contents($fakemb, 'MBZ-CONTENT-bug055');
        set_config('path', $backadelreldir, 'block_backadel');

        $stagedname = simple_restore_utils::prep_restore('bug055_fake.mbz', 'backadel', (int) $course->id);

        $this->assertNotEmpty($stagedname, 'prep_restore must return the staged tempdir filename');

        $expectedpath = make_backup_temp_directory('') . '/' . $stagedname;
        $this->assertTrue(
            file_exists($expectedpath),
            "staged file must exist at make_backup_temp_directory('')/{$stagedname} "
            . '— restore_ui_stage_confirm::process() reads this exact directory'
        );
        $this->assertGreaterThan(
            0,
            filesize($expectedpath),
            'staged file must be non-empty (bug-055 added a 0-byte guard)'
        );

        // Source content must match — copy(), not move/truncate.
        $this->assertSame(
            'MBZ-CONTENT-bug055',
            file_get_contents($expectedpath),
            'staged file content must match the source backadel archive verbatim'
        );

        // Cleanup the fixture file (resetAfterTest does not touch dataroot).
        @unlink($fakemb);
        @unlink($expectedpath);
    }

    /**
     * Empty fileid must throw a moodle_exception (no_arguments).
     *
     * @covers \simple_restore_utils::prep_restore
     */
    public function test_prep_restore_throws_on_empty_fileid(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->expectException(\Exception::class);
        simple_restore_utils::prep_restore('', 'backadel', (int) $course->id);
    }

    /**
     * Empty / zero courseid must throw a moodle_exception (no_arguments).
     *
     * @covers \simple_restore_utils::prep_restore
     */
    public function test_prep_restore_throws_on_empty_courseid(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->expectException(\Exception::class);
        simple_restore_utils::prep_restore('anything.mbz', 'backadel', 0);
    }
}
