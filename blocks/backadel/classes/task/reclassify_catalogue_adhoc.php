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
 * Chunked catalogue re-classification driven by filename patterns / null semester.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reclassify_catalogue_adhoc extends \core\task\adhoc_task {

    /** @var int */
    private const BATCH_LIMIT = 500;

    public function get_name(): string {
        return get_string('task_reclassify_catalogue_adhoc', 'block_backadel');
    }

    public function execute(): void {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGPIPE, SIG_IGN);
        }
        ignore_user_abort(true);

        $rawcustom = $this->get_custom_data();
        $custom = json_decode(json_encode($rawcustom ?? new \stdClass()), true) ?? [];

        $patterns = $custom['patterns'] ?? [];
        if (!is_array($patterns)) {
            $patterns = [];
        }
        $patterns = array_values(array_filter(array_map('strval', $patterns)));

        $nullsemester = !empty($custom['null_semester']);
        $cursorid = (int) ($custom['cursor_id'] ?? 0);
        $chainid = (string) ($custom['chain_id'] ?? '');
        $chainstarted = (int) ($custom['chain_started_ts'] ?? time());
        $totalprocessed = (int) ($custom['total_processed'] ?? 0);
        $totalreclassified = (int) ($custom['total_reclassified'] ?? 0);
        $totalmissing = (int) ($custom['total_missing'] ?? 0);
        $totalunparseable = (int) ($custom['total_unparseable'] ?? 0);
        $sourcefilter = isset($custom['source']) ? (string) $custom['source'] : null;
        if ($sourcefilter === '') {
            $sourcefilter = null;
        }

        if ($chainid === '') {
            $chainid = substr(sha1((string) microtime(true)), 0, 8);
            $chainstarted = time();
        }

        $timeoutmin = (int) get_config('block_backadel', 'migrate_chunk_timeout_minutes');
        if ($timeoutmin < 1) {
            $timeoutmin = 15;
        }
        $deadline = time() + ($timeoutmin * 60);

        @mtrace(sprintf(
            'block_backadel reclassify_catalogue_adhoc[%s]: start cursor_id=%d budget=%dmin chain_started=%s totals processed=%d',
            $chainid, $cursorid, $timeoutmin, date('c', $chainstarted), $totalprocessed
        ));

        $reclassifier = new \block_backadel\local\reclassifier();
        $chunkprocessed = 0;
        $chunkreclassified = 0;
        $chunkmissing = 0;
        $chunkunparseable = 0;
        $lastbatchfull = false;
        $stoppedearly = false;

        while (true) {
            if (time() >= $deadline) {
                $stoppedearly = $lastbatchfull;
                break;
            }

            $batch = $reclassifier->reclassify_batch(
                $patterns,
                $nullsemester,
                self::BATCH_LIMIT,
                $cursorid,
                $sourcefilter,
            );

            $processed = (int) $batch['processed'];
            if ($processed === 0) {
                $stoppedearly = false;
                break;
            }

            $chunkprocessed += $processed;
            $chunkreclassified += (int) $batch['reclassified'];
            $chunkmissing += (int) $batch['missing'];
            $chunkunparseable += (int) $batch['unparseable'];
            $cursorid = (int) $batch['last_id'];

            $totalprocessed += $processed;
            $totalreclassified += (int) $batch['reclassified'];
            $totalmissing += (int) $batch['missing'];
            $totalunparseable += (int) $batch['unparseable'];

            if ($chunkprocessed % 2000 === 0) {
                @mtrace(sprintf(
                    'block_backadel reclassify_catalogue_adhoc[%s]: %d rows in this chunk so far (cursor=%d)',
                    $chainid,
                    $chunkprocessed,
                    $cursorid
                ));
            }

            $lastbatchfull = ($processed >= self::BATCH_LIMIT);
            if ($processed < self::BATCH_LIMIT) {
                $stoppedearly = false;
                break;
            }
        }

        @mtrace(sprintf(
            'block_backadel reclassify_catalogue_adhoc[%s]: chunk done. processed=%d reclassified=%d missing=%d unparseable=%d '
                . 'chain_total_processed=%d stopped_early=%s',
            $chainid,
            $chunkprocessed,
            $chunkreclassified,
            $chunkmissing,
            $chunkunparseable,
            $totalprocessed,
            $stoppedearly ? 'yes' : 'no'
        ));

        if ($stoppedearly) {
            $successor = new self();
            $successor->set_custom_data((object) [
                'patterns' => $patterns,
                'null_semester' => $nullsemester,
                'cursor_id' => $cursorid,
                'chain_id' => $chainid,
                'chain_started_ts' => $chainstarted,
                'total_processed' => $totalprocessed,
                'total_reclassified' => $totalreclassified,
                'total_missing' => $totalmissing,
                'total_unparseable' => $totalunparseable,
                'source' => $sourcefilter,
            ]);
            \core\task\manager::queue_adhoc_task($successor, true);
            @mtrace(sprintf(
                'block_backadel reclassify_catalogue_adhoc[%s]: queued successor at cursor_id=%d.',
                $chainid,
                $cursorid
            ));
        } else {
            @mtrace(sprintf(
                'block_backadel reclassify_catalogue_adhoc[%s]: chain complete. No successor queued.',
                $chainid
            ));
        }
    }
}
