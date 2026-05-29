<?php
declare(strict_types=1);

namespace block_backadel\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for migrate_filesystem_adhoc — specifically the stdClass crash on resumed chunks.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \block_backadel\task\migrate_filesystem_adhoc
 */
final class migrate_filesystem_adhoc_test extends \advanced_testcase {

    /**
     * Resumed chunk with stdClass custom data must not crash.
     *
     * Reproduces Robert's exact failure: Moodle stores custom_data as JSON and decodes
     * it with json_decode() (no assoc flag), producing stdClass. Before the fix, line 117
     * ($run['dir']) threw "Cannot use object of type stdClass as array".
     *
     * @covers \block_backadel\task\migrate_filesystem_adhoc::execute
     */
    public function test_resumed_chunk_stdclass_runs_no_crash(): void {
        $this->resetAfterTest();

        $tempdir = make_temp_directory('adhoc_test_' . uniqid('', true));

        // Build custom data exactly as Moodle decodes it from the DB — stdClass tree.
        $encoded = json_encode([
            'runs'             => [['dir' => $tempdir, 'source' => 'backadel_current']],
            'dir_index'        => 0,
            'file_index'       => 0,
            'chain_id'         => 'deadbeef',
            'chain_started_ts' => time() - 60,
            'files_done'       => 10,
            'files_inserted'   => 7,
        ]);
        $stdclassdata = json_decode($encoded); // NO second arg — stdClass tree, same as Moodle.

        $task = new migrate_filesystem_adhoc();
        $task->set_custom_data($stdclassdata);

        ob_start();
        try {
            $task->execute();
        } finally {
            ob_end_clean();
        }

        // If we reach here without TypeError the bug is fixed.
        $this->assertTrue(true, 'execute() completed without stdClass array-access crash');
    }

    /**
     * First chunk with null custom data builds runs from config and completes.
     *
     * @covers \block_backadel\task\migrate_filesystem_adhoc::execute
     */
    public function test_first_chunk_null_custom_data_no_crash(): void {
        $this->resetAfterTest();

        // block_backadel/path not set → no runs → early return.
        set_config('path', '', 'block_backadel');
        set_config('migration_extra_paths', '', 'block_backadel');

        $task = new migrate_filesystem_adhoc();
        // No set_custom_data() — simulates a freshly-queued task.

        ob_start();
        try {
            $task->execute();
        } finally {
            ob_end_clean();
        }

        $this->assertTrue(true, 'execute() with no config completed without crash');
    }

    /**
     * count_remaining() is reachable via execute() on resume and must not crash.
     *
     * Uses dir_index past the end of runs so the while-loop exits immediately and
     * count_remaining() is called with the stdClass-derived (now array) run list.
     *
     * @covers \block_backadel\task\migrate_filesystem_adhoc::execute
     */
    public function test_count_remaining_via_execute_no_crash(): void {
        $this->resetAfterTest();

        $tempdir = make_temp_directory('adhoc_test_cr_' . uniqid('', true));

        $encoded = json_encode([
            'runs'             => [['dir' => $tempdir, 'source' => 'backadel_current']],
            'dir_index'        => 1,   // Past the only dir — loop exits immediately.
            'file_index'       => 0,
            'chain_id'         => 'cafebabe',
            'chain_started_ts' => time() - 120,
            'files_done'       => 50,
            'files_inserted'   => 45,
        ]);
        $stdclassdata = json_decode($encoded);

        $task = new migrate_filesystem_adhoc();
        $task->set_custom_data($stdclassdata);

        ob_start();
        try {
            $task->execute();
        } finally {
            ob_end_clean();
        }

        $this->assertTrue(true, 'count_remaining() completed without stdClass crash');
    }
}
