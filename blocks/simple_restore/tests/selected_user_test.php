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

use simple_restore_selected_user;

/**
 * Tests for {@see \simple_restore_selected_user} bug-055 collision guard.
 *
 * Bug-055: when {@see \simple_restore_utils::prep_restore()} routes a restore
 * through the catalogue or backadel branch, {@see \simple_restore_utils::selected_backadel()}
 * has already copied the source archive onto `$data->to_path`. The
 * `$data->fileid` in those branches is a catalogue PK or a backadel basename
 * — NOT an `mdl_files.id`. Before the fix, `simple_restore_selected_user::selected()`
 * issued `$DB->get_record('files', ['id' => $data->fileid])` unconditionally,
 * so a numeric fileid that happened to collide with a real `mdl_files.id`
 * would call `file_info_stored::copy_to_pathname($data->to_path)` and wipe
 * the staged archive — yielding `restore_ui_exception('invalidrestorefile')`
 * on the CONFIRM stage.
 *
 * The fix is an early `return true;` when `$data->name` is 'catalogue' or
 * 'backadel'. These tests exercise that guard via the public
 * {@see \simple_restore_selected_user::selected_user()} wrapper.
 *
 * @package    block_simple_restore
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \simple_restore_selected_user
 */
final class selected_user_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Build a $data object equivalent to what prep_restore() hands to
     * selected_user() after selected_backadel() has run. The staged file
     * already exists on disk at $stagedpath.
     */
    private static function make_data(
        int $userid,
        int $courseid,
        int $fileid,
        string $name,
        string $stagedpath,
        string $filename
    ): \stdClass {
        return (object) [
            'userid'   => $userid,
            'courseid' => $courseid,
            'fileid'   => $fileid,
            'name'     => $name,
            'to_path'  => $stagedpath,
            'filename' => $filename,
        ];
    }

    /**
     * Create a real stored_file under the course context so we have a
     * legitimate mdl_files.id to collide with. The presence of this real
     * row is what made bug-055 reproducible: prior to the fix,
     * selected() would copy_to_pathname() THIS file's content over the
     * staged Backadel/catalogue archive at $stagedpath.
     */
    private function make_collision_files_row(int $courseid): \stored_file {
        $context = \context_course::instance($courseid);
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => 1,
            'filepath'  => '/',
            'filename'  => 'collision.zip',
        ];
        return $fs->create_file_from_string($fileinfo, 'COLLISION-CONTENT-FROM-FILES-TABLE');
    }

    /**
     * Bug-055 core: when $data->name === 'catalogue', the guard must early-
     * return true and MUST NOT touch the staged file or mutate $data->filename.
     *
     * Before the fix this was a real silent overwrite: the stored_file content
     * ('COLLISION-CONTENT...') ended up at $stagedpath, the original Backadel
     * archive was lost, and CONFIRM threw invalidrestorefile.
     *
     * @covers \simple_restore_selected_user::selected_user
     */
    public function test_selected_user_early_returns_for_catalogue_without_db_write(): void {
        global $USER;

        $course = $this->getDataGenerator()->create_course();

        // Stage a fake archive at $stagedpath (mirroring what selected_backadel()
        // already did in the real flow).
        $stageddir = make_temp_directory('sr_bug055_cat');
        $stagedpath = $stageddir . '/staged.zip';
        file_put_contents($stagedpath, 'STAGED-CATALOGUE-ARCHIVE');

        $collision = $this->make_collision_files_row((int) $course->id);

        $data = self::make_data(
            (int) $USER->id,
            (int) $course->id,
            (int) $collision->get_id(), // numeric fileid that DOES exist in mdl_files
            'catalogue',
            $stagedpath,
            'original.zip'
        );

        $result = simple_restore_selected_user::selected_user($data);

        $this->assertTrue($result, 'guard must early-return true for catalogue');
        $this->assertSame(
            'STAGED-CATALOGUE-ARCHIVE',
            file_get_contents($stagedpath),
            'guard must NOT overwrite the staged file with mdl_files content'
        );
        $this->assertSame(
            'original.zip',
            $data->filename,
            'guard must NOT mutate $data->filename when bailing for catalogue'
        );

        @unlink($stagedpath);
    }

    /**
     * Bug-055: same contract for the 'backadel' (legacy filesystem) branch.
     *
     * @covers \simple_restore_selected_user::selected_user
     */
    public function test_selected_user_early_returns_for_backadel(): void {
        global $USER;

        $course = $this->getDataGenerator()->create_course();

        $stageddir = make_temp_directory('sr_bug055_bd');
        $stagedpath = $stageddir . '/staged.zip';
        file_put_contents($stagedpath, 'STAGED-BACKADEL-ARCHIVE');

        $collision = $this->make_collision_files_row((int) $course->id);

        $data = self::make_data(
            (int) $USER->id,
            (int) $course->id,
            (int) $collision->get_id(),
            'backadel',
            $stagedpath,
            'original.zip'
        );

        $result = simple_restore_selected_user::selected_user($data);

        $this->assertTrue($result, 'guard must early-return true for backadel');
        $this->assertSame(
            'STAGED-BACKADEL-ARCHIVE',
            file_get_contents($stagedpath),
            'guard must NOT overwrite the staged file with mdl_files content'
        );
        $this->assertSame(
            'original.zip',
            $data->filename,
            'guard must NOT mutate $data->filename when bailing for backadel'
        );

        @unlink($stagedpath);
    }

    /**
     * Existing-branch regression: when $data->name is neither 'catalogue' nor
     * 'backadel' AND $data->fileid does not match any mdl_files row, selected()
     * falls through to its `empty($backup)` guard and also returns true. This
     * is the pre-existing behaviour for the user-upload branch; we cover it
     * here so that any refactor of selected() doesn't regress the no-row case.
     *
     * @covers \simple_restore_selected_user::selected_user
     */
    public function test_selected_user_returns_true_when_no_matching_files_row(): void {
        global $USER;

        $course = $this->getDataGenerator()->create_course();

        $stageddir = make_temp_directory('sr_bug055_other');
        $stagedpath = $stageddir . '/staged.zip';
        file_put_contents($stagedpath, 'STAGED-OTHER');

        $data = self::make_data(
            (int) $USER->id,
            (int) $course->id,
            999999999, // non-existent files.id
            'other_source',
            $stagedpath,
            'original.zip'
        );

        $result = simple_restore_selected_user::selected_user($data);

        $this->assertTrue($result, 'empty($backup) branch must early-return true');
        // No collision happens, so staged file is untouched (no-op).
        $this->assertSame(
            'STAGED-OTHER',
            file_get_contents($stagedpath)
        );

        @unlink($stagedpath);
    }
}
