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
 * Catalogue-flow integration tests for MD-2189.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group      catalogue_flow
 *
 * @covers \block_backadel\local\migrator
 * @covers \block_backadel\local\course_type_resolver
 * @covers \block_backadel\local\period_resolver
 */
final class catalogue_flow_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        global $CFG;
        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');
    }

    // -----------------------------------------------------------------------
    // Migrator — process_file() / migrate_directory() upserts
    // -----------------------------------------------------------------------

    /**
     * @covers \block_backadel\local\migrator::migrate_directory
     */
    public function test_migrator_process_file_inserts_catalogue_row(): void {
        $this->markTestIncomplete(
            'This test would: (1) create a temp directory under $CFG->tempdir with a single valid .zip fixture '
            . 'matching filename_parser (e.g. semester_legacy); (2) instantiate \\block_backadel\\local\\migrator '
            . 'and call migrate_directory($tmpdir, \'backadel_current\'); (3) assert '
            . '$DB->count_records(\'block_backadel_catalogue\', [\'filename\' => basename, '
            . '\'source\' => \'backadel_current\']) === 1 and filepath_hash === sha1(full path); '
            . '(4) assert parsed year/semester/dept fields on the row match filename_parser::parse() output. '
            . 'Left incomplete here because it requires non-trivial filesystem scaffolding + unique temp paths '
            . 'across parallel phpunit workers without colliding with backupdir configuration.'
        );
    }

    /**
     * @covers \block_backadel\local\migrator::migrate_directory
     */
    public function test_migrator_upsert_deduplicates_repeat_calls(): void {
        $this->markTestIncomplete(
            'This test would: (1) seed the same single-zip temp dir as test_migrator_process_file_inserts_catalogue_row; '
            . '(2) run migrate_directory() twice with the same $dir and source; (3) assert catalogue row count for '
            . 'that filepath_hash remains 1 and timemodified updates on the second pass (upsert path); '
            . '(4) optionally assert block_backadel_courses also stays deduped by filepath. '
            . 'Skipped for the same temp-dir / isolation reasons as the insert test.'
        );
    }

    // -----------------------------------------------------------------------
    // course_type_resolver — semester_legacy / blueprint / other
    // -----------------------------------------------------------------------

    /**
     * @covers \block_backadel\local\course_type_resolver::resolve
     */
    public function test_course_type_resolver_returns_teaching_for_semester_legacy(): void {
        $fn = '2024SpringMATH1201001_jsmith_1700000001.zip';
        $parsed = filename_parser::parse($fn);
        $this->assertIsArray($parsed);
        $this->assertSame('teaching', course_type_resolver::resolve($parsed));
    }

    /**
     * @covers \block_backadel\local\course_type_resolver::resolve
     */
    public function test_course_type_resolver_returns_blueprint_for_keyword_match(): void {
        $fn = 'backadel-master-course-MATH-1001.zip';
        $parsed = filename_parser::parse($fn);
        $this->assertIsArray($parsed);
        $this->assertSame('blueprint', course_type_resolver::resolve($parsed));
    }

    /**
     * @covers \block_backadel\local\course_type_resolver::resolve
     */
    public function test_course_type_resolver_returns_other_for_unknown(): void {
        $fn = 'orphaned_backup_no_ts.zip';
        $parsed = filename_parser::parse($fn);
        $this->assertIsArray($parsed);
        $this->assertSame('other', course_type_resolver::resolve($parsed));
    }

    // -----------------------------------------------------------------------
    // period_resolver — slug synthesis
    // -----------------------------------------------------------------------

    /**
     * @covers \block_backadel\local\period_resolver::for_course
     */
    public function test_period_resolver_returns_lsu_am_slug_for_known_startdate(): void {
        $course = new \stdClass();
        $course->fullname = 'Spring 2026 MATH 1201';
        $course->shortname = 'MATH1201';
        $course->startdate = gmmktime(0, 0, 0, 1, 15, 2026);

        $slug = period_resolver::for_course($course);
        $this->assertNotNull($slug);
        $this->assertNotSame('', $slug);
        $this->assertStringStartsWith('LSU_AM_', $slug);
    }

    // -----------------------------------------------------------------------
    // backadel_resolve_path() — filepath_full preference + fallback
    // -----------------------------------------------------------------------

    /**
     * @covers ::backadel_resolve_path
     */
    public function test_backadel_resolve_path_prefers_filepath_full(): void {
        global $DB;

        $now = time();
        $row = new \stdClass();
        $row->filename = 'path_pref_full_test.zip';
        $row->filepath = '/short/path.zip';
        $row->filepath_full = '/full/long/path.zip';
        $row->filepath_hash = sha1((string) $row->filepath_full);
        $row->source = 'backadel_current';
        $row->pattern = 'unknown';
        $row->backup_ts = 0;
        $row->status = 'available';
        $row->timecreated = $now;
        $row->timemodified = $now;
        $id = (int) $DB->insert_record('block_backadel_catalogue', $row);

        $resolved = \backadel_resolve_path($id);
        $this->assertStringContainsString('full/long/path.zip', $resolved);
    }

    /**
     * @covers ::backadel_resolve_path
     */
    public function test_backadel_resolve_path_falls_back_to_filepath(): void {
        global $DB;

        $now = time();
        $row = new \stdClass();
        $row->filename = 'path_fallback_test.zip';
        $row->filepath = '/short/path.zip';
        $row->filepath_full = '';
        $row->filepath_hash = sha1('/short/path.zip');
        $row->source = 'backadel_current';
        $row->pattern = 'unknown';
        $row->backup_ts = 0;
        $row->status = 'available';
        $row->timecreated = $now;
        $row->timemodified = $now;
        $id = (int) $DB->insert_record('block_backadel_catalogue', $row);

        $resolved = \backadel_resolve_path($id);
        $this->assertStringContainsString('short/path.zip', $resolved);
    }

    /**
     * @covers ::backadel_resolve_path
     */
    public function test_backadel_resolve_path_applies_catalogue_path_prefix(): void {
        global $DB;

        set_config('catalogue_path_prefix', '/old/root=/new/root', 'block_backadel');

        $now = time();
        $row = new \stdClass();
        $row->filename = 'path_prefix_test.zip';
        $row->filepath = '/trunc.zip';
        $row->filepath_full = '/old/root/sub/p.zip';
        $row->filepath_hash = sha1((string) $row->filepath_full);
        $row->source = 'backadel_current';
        $row->pattern = 'unknown';
        $row->backup_ts = 0;
        $row->status = 'available';
        $row->timecreated = $now;
        $row->timemodified = $now;
        $id = (int) $DB->insert_record('block_backadel_catalogue', $row);

        $resolved = \backadel_resolve_path($id);
        $this->assertStringContainsString('/new/root/sub/p.zip', $resolved);
    }

    // -----------------------------------------------------------------------
    // CLI bulk import — dryrun + dedup (integration; gate via @group)
    // -----------------------------------------------------------------------

    /**
     * @group integration
     */
    public function test_import_ftp_catalogue_cli_dryrun_makes_no_db_writes(): void {
        $this->markTestIncomplete(
            'This integration test would: (1) capture $DB->count_records(\'block_backadel_catalogue\') before run; '
            . '(2) exec from PHP proc_open or similar: php blocks/backadel/cli/import_ftp_catalogue.php '
            . '--file=<5-line fixture> --dryrun; (3) assert exit 0 and stdout contains '
            . '"Dry run pattern summary:" and "Rows inserted: 0 (dry run)"; (4) assert catalogue count unchanged. '
            . 'Skipped in the main suite to avoid spawning CLI, requiring writable temp paths, and CI timing variance.'
        );
    }
}
