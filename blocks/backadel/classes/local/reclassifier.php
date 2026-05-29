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
 * Cursor-based batch re-classification over catalogue rows.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reclassifier {

    /** @var migrator */
    private $migrator;

    public function __construct(?migrator $m = null) {
        $this->migrator = $m ?? new migrator();
    }

    /**
     * Count catalogue rows matching the given filter criteria.
     *
     * @param string[] $patterns
     * @param bool $nullsemester
     * @param string|null $source optional source key
     * @return int
     */
    public function count_matching(array $patterns, bool $nullsemester, ?string $source = null): int {
        global $DB;

        if ($patterns === [] && !$nullsemester) {
            return 0;
        }

        $built = $this->build_filter_sql($DB, $patterns, $nullsemester, $source);
        if ($built === null) {
            return 0;
        }

        [$wheresql, $params] = $built;
        $sql = "SELECT COUNT('x') FROM {block_backadel_catalogue} WHERE " . $wheresql;
        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Process a batch of catalogue rows in primary-key order.
     *
     * @param string[] $patterns
     * @param bool $nullsemester
     * @param int $limit
     * @param int $afterid
     * @param string|null $source
     * @return array{processed: int, reclassified: int, missing: int, unparseable: int, last_id: int}
     */
    public function reclassify_batch(
        array $patterns,
        bool $nullsemester,
        int $limit,
        int $afterid,
        ?string $source = null,
    ): array {
        global $DB;

        $out = [
            'processed' => 0,
            'reclassified' => 0,
            'missing' => 0,
            'unparseable' => 0,
            'last_id' => $afterid,
        ];

        if ($patterns === [] && !$nullsemester) {
            return $out;
        }

        $built = $this->build_filter_sql($DB, $patterns, $nullsemester, $source);
        if ($built === null) {
            return $out;
        }

        [$filtersql, $filterparams] = $built;
        $params = array_merge(['afterid' => $afterid], $filterparams);
        $sql = "SELECT * FROM {block_backadel_catalogue}
                 WHERE id > :afterid
                   AND ($filtersql)
              ORDER BY id ASC";

        $rs = $DB->get_recordset_sql($sql, $params, 0, $limit);
        try {
            foreach ($rs as $row) {
                $out['processed']++;
                $out['last_id'] = (int) $row->id;
                $path = (string) $row->filepath_full;
                if ($path === '') {
                    $path = (string) $row->filepath;
                }
                $result = $this->migrator->reclassify_file($path, (string) $row->source);
                $st = $result['status'] ?? '';
                if ($st === 'reclassified') {
                    $out['reclassified']++;
                } else if ($st === 'missing') {
                    $out['missing']++;
                } else if ($st === 'unparseable') {
                    $out['unparseable']++;
                }
            }
        } finally {
            $rs->close();
        }

        return $out;
    }

    /**
     * @param string[] $patterns
     * @param string|null $source
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private function build_filter_sql(
        \moodle_database $DB,
        array $patterns,
        bool $nullsemester,
        ?string $source,
    ): ?array {
        $parts = [];
        $params = [];

        if ($patterns !== []) {
            [$insql, $inparams] = $DB->get_in_or_equal($patterns, SQL_PARAMS_NAMED, 'pat');
            $parts[] = 'pattern ' . $insql;
            $params = array_merge($params, $inparams);
        }
        if ($nullsemester) {
            $parts[] = 'semester IS NULL';
        }
        if ($parts === []) {
            return null;
        }

        $sql = implode(' OR ', $parts);
        if ($source !== null && $source !== '') {
            $sql = '(' . $sql . ') AND source = :rclsrc';
            $params['rclsrc'] = $source;
        }

        return [$sql, $params];
    }
}
