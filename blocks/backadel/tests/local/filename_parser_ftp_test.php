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
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group ftp_corpus
 * @covers \block_backadel\local\filename_parser
 */
class filename_parser_ftp_test extends \advanced_testcase {

    public function setUp(): void {
        $external = getenv('MOODLE_EXTERNAL_TESTRUNNER');
        $long = defined('PHPUNIT_LONGTEST') && PHPUNIT_LONGTEST;
        if (!(is_string($external) && $external !== '' && $external !== '0') && !$long) {
            $this->markTestSkipped('FTP corpus tests require MOODLE_EXTERNAL_TESTRUNNER or PHPUNIT_LONGTEST.');
        }
        parent::setUp();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provider_lists(): array {
        $root = dirname(__DIR__, 4);
        return [
            'moodleus' => [$root . '/tickets/MD-2189/file-lists/ftp_file_list.moodleus.txt'],
            'openlms' => [$root . '/tickets/MD-2189/file-lists/ftp_file_list.openlms.txt'],
        ];
    }

    /**
     * @dataProvider provider_lists
     */
    public function test_no_fatals_and_distribution(string $path): void {
        $this->assertFileExists($path);
        $handle = fopen($path, 'r');
        $this->assertNotFalse($handle, 'fopen failed for ' . $path);

        $counts = [];
        $total = 0;
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            $total++;
            $basename = basename($trimmed);
            $parsed = filename_parser::parse($basename);
            $this->assertIsArray($parsed, 'parse() must return array for ' . $basename);
            $this->assertArrayHasKey('pattern', $parsed);
            $pattern = (string) $parsed['pattern'];
            if (!isset($counts[$pattern])) {
                $counts[$pattern] = 0;
            }
            $counts[$pattern]++;
        }
        fclose($handle);

        $this->assertGreaterThan(0, $total, 'Corpus file had no non-empty lines.');

        $unknown = $counts['unknown'] ?? 0;
        $pct = ($unknown / $total) * 100.0;
        $this->assertLessThan(
            5.0,
            $pct,
            'unknown pattern share must stay below 5%; counts=' . var_export($counts, true)
        );
    }

    public function test_known_anchor_filenames(): void {
        $cases = [
            [
                'name' => '2012SpringAAAS200017596_mbroom2_1337708564.zip',
                'pattern' => 'semester_legacy',
                'year' => 2012,
                'semester' => 'Spring',
                'dept' => 'AAAS',
            ],
            [
                'name' => 'MaterialsCourse_CHIN-2001_llei_llei_1259772319.zip',
                'pattern' => 'storage_legacy',
            ],
            [
                'name' => 'backadel-2012-ART-4541_mherster.zip',
                'pattern' => 'backadel_modern',
            ],
            [
                'name' => 'backup-moodle2-course-42-MATH-1001-20240315-1430.mbz',
                'pattern' => 'moodle_native',
            ],
            [
                'name' => 'BT101_mtiger1_1261415318.zip',
                'pattern' => 'storage_legacy',
            ],
        ];

        foreach ($cases as $case) {
            $r = filename_parser::parse($case['name']);
            $this->assertIsArray($r);
            $this->assertSame($case['pattern'], $r['pattern']);
            if (isset($case['year'])) {
                $this->assertSame($case['year'], $r['year']);
            }
            if (isset($case['semester'])) {
                $this->assertSame($case['semester'], $r['semester']);
            }
            if (isset($case['dept'])) {
                $this->assertSame($case['dept'], $r['dept']);
            }
        }
    }
}
