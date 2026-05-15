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

use core_text;

/**
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \block_backadel\local\reclassifier
 */
final class reclassifier_test extends \advanced_testcase {

    private function insert_catalogue_row(\stdClass $row): int {
        global $DB;
        if (!isset($row->backup_ts)) {
            $row->backup_ts = 0;
        }
        if (!isset($row->status)) {
            $row->status = 'available';
        }
        $now = time();
        if (!isset($row->timecreated)) {
            $row->timecreated = $now;
        }
        if (!isset($row->timemodified)) {
            $row->timemodified = $now;
        }
        return (int) $DB->insert_record('block_backadel_catalogue', $row);
    }

    public function test_count_matching_empty_patterns_without_null_returns_zero(): void {
        $this->resetAfterTest(true);

        $counter = new reclassifier();
        $this->assertSame(0, $counter->count_matching([], false));
    }

    public function test_count_matching_filters_by_unknown_pattern_only(): void {
        $this->resetAfterTest(true);

        foreach (['unknown', 'storage_course'] as $pat) {
            $row = new \stdClass();
            $row->filename = uniqid($pat . '_') . '.zip';
            $row->filepath = '/tmp/' . $row->filename;
            $row->filepath_full = '/tmp/' . $row->filename;
            $row->filepath_hash = sha1($row->filepath_full);
            $row->source = 'backadel_current';
            $row->pattern = $pat;
            $this->insert_catalogue_row($row);
        }

        $counter = new reclassifier();
        $this->assertSame(1, $counter->count_matching(['unknown'], false));
    }

    public function test_count_matching_null_semester_flag(): void {
        $this->resetAfterTest(true);

        $row = new \stdClass();
        $row->filename = 'nullsem.zip';
        $row->filepath = '/tmp/nullsem.zip';
        $row->filepath_full = '/tmp/nullsem.zip';
        $row->filepath_hash = sha1($row->filepath_full);
        $row->source = 'backadel_current';
        $row->pattern = 'backadel_modern';
        $row->semester = null;
        $this->insert_catalogue_row($row);

        $counter = new reclassifier();
        $this->assertSame(1, $counter->count_matching([], true));
    }

    public function test_reclassify_batch_marks_catalogue_when_file_missing(): void {
        global $DB;
        $this->resetAfterTest(true);

        $path = '/cannot/exist/for/reclass/' . uniqid('', true) . '.zip';
        $row = new \stdClass();
        $row->filename = basename($path);
        $row->filepath = $path;
        $row->filepath_full = $path;
        $row->filepath_hash = sha1($path);
        $row->source = 'backadel_current';
        $row->pattern = 'unknown';
        $cid = $this->insert_catalogue_row($row);

        $courses = new \stdClass();
        $courses->courseid = null;
        $courses->coursefullname = basename($path);
        $courses->courseshortname = basename($path);
        $courses->courseidnumber = null;
        $courses->status = 'available';
        $courses->filepath = $path;
        $courses->filepath_hash = sha1($path);
        $courses->filename = basename($path);
        $courses->filesize = 1;
        $courses->backupcreated = null;
        $courses->semester = null;
        $courses->academicperiodid = null;
        $courses->coursetype = 'other';
        $courses->statusid = null;
        $courses->timecreated = time();
        $coursesid = (int) $DB->insert_record('block_backadel_courses', $courses);

        $rec = new reclassifier();
        $stats = $rec->reclassify_batch(['unknown'], false, 50, 0);
        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['missing']);

        $cat = $DB->get_record('block_backadel_catalogue', ['id' => $cid], '*', MUST_EXIST);
        $this->assertSame('missing', $cat->status);

        $bc = $DB->get_record('block_backadel_courses', ['id' => $coursesid], '*', MUST_EXIST);
        $this->assertSame('missing', $bc->status);
    }

    public function test_reclassify_batch_updates_catalogue_from_parse(): void {
        global $DB;
        $this->resetAfterTest(true);

        $dir = make_temp_directory('block_backadel_recls_' . uniqid('', true));
        $basename = '2024SpringMATH12010011700000000.zip';
        $path = $dir . '/' . $basename;
        touch($path);

        $row = new \stdClass();
        $row->filename = $basename;
        $row->filepath = core_text::substr($path, 0, 255);
        $row->filepath_full = $path;
        $row->filepath_hash = sha1($path);
        $row->source = 'backadel_current';
        $row->pattern = 'unknown';
        $row->year = 2000;
        $row->timemodified = 1;
        $cid = $this->insert_catalogue_row($row);

        $rec = new reclassifier();
        $stats = $rec->reclassify_batch(['unknown'], false, 10, 0);
        $this->assertSame(1, $stats['processed']);

        $cat = $DB->get_record('block_backadel_catalogue', ['id' => $cid], '*', MUST_EXIST);
        $this->assertSame('semester_legacy', $cat->pattern);
        $this->assertSame(2024, (int) $cat->year);
    }

    public function test_reclassify_batch_replaces_teachers(): void {
        global $DB;
        $this->resetAfterTest(true);

        $dir = make_temp_directory('block_backadel_reclt_' . uniqid('', true));
        $basename1 = 'backadel-ENGL-4001_jsmith.zip';
        $path1 = $dir . '/' . $basename1;
        touch($path1);

        $m = new migrator();
        $m->migrate_file($path1, 'backadel_current');

        $course = $DB->get_record('block_backadel_courses', ['filepath_hash' => sha1($path1)], '*', MUST_EXIST);
        $extras = new \stdClass();
        $extras->coursesid = (int) $course->id;
        $extras->userid = null;
        $extras->username = 'stale_teacher';
        $extras->email = null;
        $extras->resolved = 0;
        $extras->resolvedvia = 'none';
        $extras->timecreated = time();
        $DB->insert_record('block_backadel_teachers', $extras);
        $this->assertSame(2, $DB->count_records('block_backadel_teachers', ['coursesid' => $course->id]));

        @unlink($path1);
        $basename2 = 'backadel-ENGL-4001_tbrown.zip';
        $path2 = $dir . '/' . $basename2;
        touch($path2);

        $hashnew = sha1($path2);
        $catalogueupdate = ['filepath_full' => $path2, 'filepath_hash' => $hashnew, 'filepath' => core_text::substr($path2, 0, 255),
                'filename' => $basename2, ];
        foreach ($catalogueupdate as $k => $v) {
            $DB->set_field('block_backadel_catalogue', $k, $v, ['filepath_hash' => sha1($path1)]);
        }
        $coursesupdate = [
            'filepath' => $path2,
            'filepath_hash' => $hashnew,
            'filename' => $basename2,
        ];
        foreach ($coursesupdate as $k => $v) {
            $DB->set_field('block_backadel_courses', $k, $v, ['id' => $course->id]);
        }

        $rec = new reclassifier();
        $stats = $rec->reclassify_batch(['backadel_modern'], false, 10, 0);
        $this->assertGreaterThanOrEqual(1, $stats['processed']);

        $usernames = $DB->get_fieldset_select(
            'block_backadel_teachers',
            'username',
            'coursesid = :c',
            ['c' => $course->id]
        );
        sort($usernames);
        $this->assertSame(['tbrown'], $usernames);
    }

    public function test_reclassify_batch_cursor_advances(): void {
        $this->resetAfterTest(true);

        foreach (range(1, 3) as $i) {
            $dir = make_temp_directory('bac_recurse_' . uniqid((string) $i, true));
            $basename = uniqid('', true) . '-cursor-' . $i . '.zip';
            $path = $dir . '/' . $basename;
            touch($path);

            $row = new \stdClass();
            $row->filename = $basename;
            $row->filepath_full = $path;
            $row->filepath_hash = sha1($path);
            $row->filepath = core_text::substr($path, 0, 255);
            $row->source = 'backadel_current';
            $row->pattern = 'unknown';
            $this->insert_catalogue_row($row);
        }

        /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
        $stub = new class extends migrator {
            /** @var string[] */
            public array $seenpaths = [];

            #[\Override]
            public function reclassify_file(string $filepathfull, string $source): array {
                $this->seenpaths[] = $filepathfull;
                return ['status' => 'reclassified', 'rows' => 0];
            }
        };

        $cursor = new reclassifier($stub);

        $b1 = $cursor->reclassify_batch(['unknown'], false, 1, 0);
        $this->assertSame(1, $b1['processed']);
        $after1 = (int) $b1['last_id'];
        $this->assertNotSame(0, $after1);

        $b2 = $cursor->reclassify_batch(['unknown'], false, 1, $after1);
        $this->assertSame(1, $b2['processed']);
        $this->assertGreaterThan($after1, (int) $b2['last_id']);

        $b3 = $cursor->reclassify_batch(['unknown'], false, 1, (int) $b2['last_id']);
        $this->assertSame(1, $b3['processed']);

        $b4 = $cursor->reclassify_batch(['unknown'], false, 1, (int) $b3['last_id']);
        $this->assertSame(0, $b4['processed']);

        $this->assertSame(3, count($stub->seenpaths));
    }
}
