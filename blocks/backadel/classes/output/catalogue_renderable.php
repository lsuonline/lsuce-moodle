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

namespace block_backadel\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use renderer_base;
use renderable;
use templatable;

/**
 * Template data for the catalogue list page.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue_renderable implements renderable, templatable {

    /** @var list<stdClass> */
    protected array $rows;

    protected int $year;

    /** @var list<string> */
    protected array $semesters;

    protected string $filtersource = '';

    protected string $filtersemester = '';

    /**
     * @param stdClass[] $rows Catalogue rows
     * @param int $year Selected year
     * @param string[] $semesters Distinct semester labels
     * @param string $filtersource Optional source filter (URL param)
     * @param string $filtersemester Optional semester filter (URL param)
     */
    public function __construct(
        array $rows,
        int $year,
        array $semesters,
        string $filtersource = '',
        string $filtersemester = '',
    ) {
        $this->rows = array_values($rows);
        $this->year = $year;
        $this->semesters = array_values(array_filter($semesters, static function ($s): bool {
            return $s !== null && $s !== '';
        }));
        $this->filtersource = $filtersource;
        $this->filtersemester = $filtersemester;
    }

    /**
     * @return array{
     *   rows: list<array{
     *     id: int,
     *     filename: string,
     *     shortname: string,
     *     semester: string,
     *     dept: string,
     *     course_num: string,
     *     source: string,
     *     backup_ts_human: string,
     *     file_size_human: string,
     *     status: string,
     *     statusbadgeclasses: string,
     *     pattern: string
     *   }>,
     *   year: int,
     *   semesters: list<array{label: string, url: string, current: bool}>
     * }
     */
    public function export_for_template(renderer_base $output): array {
        $urlparams = ['year' => $this->year];
        if ($this->filtersource !== '') {
            $urlparams['source'] = $this->filtersource;
        }
        $base = new moodle_url('/blocks/backadel/catalogue.php', $urlparams);

        $semesternav = [];
        $semesternav[] = [
            'label' => get_string('all'),
            'url' => $base->out(false),
            'current' => $this->filtersemester === '',
        ];
        foreach ($this->semesters as $sem) {
            $semesternav[] = [
                'label' => $sem,
                'url' => (new moodle_url($base, ['semester' => $sem]))->out(false),
                'current' => $this->filtersemester === $sem,
            ];
        }

        $rows = [];
        foreach ($this->rows as $row) {
            $filesize = $row->file_size ?? null;
            $status = (string) $row->status;
            $statusbadgeclasses = match ($status) {
                'available' => 'badge bg-success text-white',
                'missing' => 'badge bg-warning text-dark',
                'archived' => 'badge bg-secondary text-white',
                default => 'badge bg-secondary text-white',
            };
            $rows[] = [
                'id' => (int) $row->id,
                'filename' => (string) $row->filename,
                'shortname' => (string) ($row->shortname ?? ''),
                'semester' => (string) ($row->semester ?? ''),
                'dept' => (string) ($row->dept ?? ''),
                'course_num' => (string) ($row->course_num ?? ''),
                'source' => (string) $row->source,
                'backup_ts_human' => userdate((int) $row->backup_ts),
                'file_size_human' => ($filesize !== null) ? display_size((int) $filesize) : '',
                'status' => $status,
                'statusbadgeclasses' => $statusbadgeclasses,
                'pattern' => (string) $row->pattern,
            ];
        }

        return [
            'rows' => $rows,
            'year' => $this->year,
            'semesters' => $semesternav,
        ];
    }
}
