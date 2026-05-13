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

namespace block_backadel\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Chunked, resumable filesystem migration adhoc task for block_backadel.
 *
 * On each invocation the task processes backup archives until a configurable
 * wall-clock budget is exhausted, then queues a successor adhoc task carrying
 * a cursor so the next chunk continues exactly where this one left off.
 *
 * Custom data schema (stored as JSON in mdl_task_adhoc.customdata):
 *   {
 *     "runs":             [{dir: string, source: string}, ...],
 *     "dir_index":        int,   // index into runs[]
 *     "file_index":       int,   // index into sorted basenames of runs[dir_index]
 *     "chain_id":         string,
 *     "chain_started_ts": int,
 *     "files_done":       int,
 *     "files_inserted":   int
 *   }
 *
 * NULL / empty custom_data is treated as the first chunk: the run list is
 * built fresh from admin config, so tasks queued with no custom_data (e.g.
 * from migrate.php) work transparently.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_filesystem_adhoc extends \core\task\adhoc_task {

    public function get_name(): string {
        return get_string('task_migrate_filesystem_adhoc', 'block_backadel');
    }

    public function execute(): void {
        global $CFG;

        $custom = (array) ($this->get_custom_data() ?? []);

        // ---- Resolve / freeze the run list on first chunk -----------------------
        if (empty($custom['runs']) || !is_array($custom['runs'])) {
            $runs = $this->build_runs_from_config();
            if ($runs === []) {
                mtrace('block_backadel migrate_filesystem_adhoc: no runs configured (block_backadel/path empty); aborting.');
                return;
            }
            $custom = [
                'runs'             => $runs,
                'dir_index'        => 0,
                'file_index'       => 0,
                'chain_id'         => substr(sha1((string) microtime(true)), 0, 8),
                'chain_started_ts' => time(),
                'files_done'       => 0,
                'files_inserted'   => 0,
            ];
        }

        $runs          = $custom['runs'];
        $dirindex      = (int) ($custom['dir_index']  ?? 0);
        $fileindex     = (int) ($custom['file_index'] ?? 0);
        $chainid       = (string) ($custom['chain_id'] ?? '??');
        $chainstarted  = (int) ($custom['chain_started_ts'] ?? time());
        $filesdone     = (int) ($custom['files_done'] ?? 0);
        $filesinserted = (int) ($custom['files_inserted'] ?? 0);

        // ---- Resolve timeout (clamped to >= 1 minute) ---------------------------
        $timeoutmin = (int) get_config('block_backadel', 'migrate_chunk_timeout_minutes');
        if ($timeoutmin < 1) {
            $timeoutmin = 15;
        }
        $deadline = time() + ($timeoutmin * 60);

        mtrace(sprintf(
            'block_backadel migrate_filesystem_adhoc[%s]: starting chunk at dir_index=%d file_index=%d, '
                . 'budget=%dmin, chain_started=%s, files_done_so_far=%d',
            $chainid, $dirindex, $fileindex, $timeoutmin, date('c', $chainstarted), $filesdone
        ));

        $migrator = new \block_backadel\local\migrator();

        $chunkprocessed = 0;
        $chunkinserted  = 0;
        $stoppedearly   = false;

        // ---- Iterate dirs -------------------------------------------------------
        $rundefs = array_values($runs);
        while ($dirindex < count($rundefs)) {
            $run    = $rundefs[$dirindex];
            $dir    = (string) ($run['dir'] ?? '');
            $source = (string) ($run['source'] ?? 'backadel_current');

            $basenames = \block_backadel\local\migrator::list_archives($dir);
            $dirtotal  = count($basenames);

            if ($fileindex >= $dirtotal) {
                // Finished this dir — advance to next.
                mtrace(sprintf(
                    'block_backadel migrate_filesystem_adhoc[%s]: completed dir %s (%d files).',
                    $chainid, $dir, $dirtotal
                ));
                $dirindex++;
                $fileindex = 0;
                continue;
            }

            // Process files from current cursor until deadline or end of dir.
            for ($i = $fileindex; $i < $dirtotal; $i++) {
                if (time() >= $deadline) {
                    $stoppedearly = true;
                    $fileindex = $i;   // next chunk starts here
                    break 2;           // exit inner for + outer while
                }

                $basename       = $basenames[$i];
                $inserted       = $migrator->migrate_file($dir . '/' . $basename, $source);
                $chunkprocessed++;
                $chunkinserted  += $inserted;
                $filesdone++;
                $filesinserted  += $inserted;

                if ($chunkprocessed % 200 === 0) {
                    mtrace(sprintf(
                        'block_backadel migrate_filesystem_adhoc[%s]: %d files processed in this chunk so far (dir %s).',
                        $chainid, $chunkprocessed, $dir
                    ));
                }
            }

            if (!$stoppedearly) {
                // Reached end of dir naturally; advance.
                $dirindex++;
                $fileindex = 0;
            }
        }

        // ---- Decide: queue successor or finish chain? ---------------------------
        $remaining = $this->count_remaining($rundefs, $dirindex, $fileindex);

        mtrace(sprintf(
            'block_backadel migrate_filesystem_adhoc[%s]: chunk done. processed=%d, inserted=%d, '
                . 'chain_total_processed=%d, chain_total_inserted=%d, remaining=%d, stopped_early=%s',
            $chainid, $chunkprocessed, $chunkinserted,
            $filesdone, $filesinserted, $remaining, $stoppedearly ? 'yes' : 'no'
        ));

        if ($stoppedearly && $remaining > 0) {
            $successor = new self();
            $successor->set_custom_data((object) [
                'runs'             => $rundefs,
                'dir_index'        => $dirindex,
                'file_index'       => $fileindex,
                'chain_id'         => $chainid,
                'chain_started_ts' => $chainstarted,
                'files_done'       => $filesdone,
                'files_inserted'   => $filesinserted,
            ]);
            \core\task\manager::queue_adhoc_task($successor, true);
            mtrace(sprintf(
                'block_backadel migrate_filesystem_adhoc[%s]: queued successor task to continue at dir_index=%d file_index=%d.',
                $chainid, $dirindex, $fileindex
            ));
        } else {
            mtrace(sprintf(
                'block_backadel migrate_filesystem_adhoc[%s]: chain complete. No successor queued.',
                $chainid
            ));
        }
    }

    /**
     * Build (dir, source) run list from admin config — mirrors the old scheduled task.
     *
     * @return array<int, array{dir: string, source: string}>
     */
    private function build_runs_from_config(): array {
        global $CFG;
        $runs = [];

        $relpath = get_config('block_backadel', 'path');
        if (is_string($relpath) && trim($relpath) !== '') {
            $rootdir = rtrim($CFG->dataroot, '/') . '/' . ltrim(trim($relpath), '/');
            $runs[] = ['dir' => $rootdir, 'source' => 'backadel_current'];
        }

        $legacyraw = get_config('block_backadel', 'migration_extra_paths');
        if (is_string($legacyraw) && $legacyraw !== '') {
            $split = preg_split('/\R/', $legacyraw);
            foreach ((array) $split as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $runs[] = ['dir' => $line, 'source' => 'legacy_moodleus'];
                }
            }
        }

        return $runs;
    }

    /**
     * Count archive files still to process across all runs starting at the cursor.
     *
     * @param array $runs     Frozen run list.
     * @param int   $dirindex Current dir_index (next unprocessed dir).
     * @param int   $fileindex Current file_index within runs[$dirindex].
     * @return int
     */
    private function count_remaining(array $runs, int $dirindex, int $fileindex): int {
        $remaining = 0;
        for ($i = $dirindex; $i < count($runs); $i++) {
            $count = count(\block_backadel\local\migrator::list_archives((string) ($runs[$i]['dir'] ?? '')));
            $remaining += max(0, $count - ($i === $dirindex ? $fileindex : 0));
        }
        return $remaining;
    }
}
