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

/**
 * @package    block_simple_restore
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Be sure no one accesses the page directly.
defined('MOODLE_INTERNAL') || die();

/**
 * Resolve a block_backadel_catalogue row to an absolute backup file path (MD-2189 §3.3).
 *
 * If filepath is absolute (starts with /) it is returned directly. Otherwise it is
 * resolved relative to $CFG->dataroot. A catalogue_path_prefix setting (format old=new)
 * is applied when configured.
 *
 * @param int $catalogue_id Primary key in block_backadel_catalogue.
 * @return string Absolute path on disk.
 */
function backadel_resolve_path(int $catalogueid): string {
    global $DB, $CFG;

    $rec = $DB->get_record(
        'block_backadel_catalogue',
        ['id' => $catalogueid],
        'filepath, filepath_full, filename',
        IGNORE_MISSING
    );
    if (!$rec) {
        return '';
    }
    // Filepath_full (TEXT) is preferred; filepath (char 255) may be truncated for index compat.
    $path = (!empty($rec->filepath_full)) ? (string) $rec->filepath_full : (string) $rec->filepath;

    $resolved = (strncmp($path, '/', 1) === 0)
        ? $path
        : $CFG->dataroot . '/' . ltrim($path, '/');

    // Defensive: collapse any double-slashes that crept in from stale DB data written before bug-039 was
    // fixed. These are always absolute filesystem paths, so replacing // with / is always safe here.
    $resolved = str_replace('//', '/', $resolved);

    $prefix = (string) get_config('block_backadel', 'catalogue_path_prefix');
    if ($prefix !== '' && strpos($prefix, '=') !== false) {
        [$old, $new] = explode('=', $prefix, 2);
        if ($old !== '') {
            $resolved = str_replace($old, $new, $resolved);
        }
    }

    return $resolved;
}

abstract class simple_restore_utils {
    // We don't need the includes on every request.
    public static function includes() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    public static function backadel_shortname($shortname) {
        if (preg_match('/\s/', $shortname)) {
            $matchers = array('/\s/', '/\//');
            return preg_replace($matchers, '-', $shortname);
        }
        return $shortname;
    }

    public static function selected_backadel($data) {
        global $CFG;

        // Catalogue path: keyed on explicit name='catalogue' marker (MD-2189 §3.3).
        // Numeric-fileid fallback kept for safety but explicit name is preferred to avoid
        // mis-routing legacy files with numeric basenames (e.g. 12345.zip).
        $iscatalogue = (isset($data->name) && $data->name === 'catalogue')
                    || (!isset($data->name) && is_numeric($data->fileid));
        if ($iscatalogue) {
            $realpath = backadel_resolve_path((int) $data->fileid);
            if (!file_exists($realpath)) {
                return true;
            }
            copy($realpath, $data->to_path);
            $data->filename = basename($realpath);
            return true;
        }

        // Legacy: fileid is a relative filename appended to the configured backadel path.
        $backadelpath = get_config('block_backadel', 'path');
        $realpath = $CFG->dataroot . $backadelpath . $data->fileid;

        if (!file_exists($realpath)) {
            return true;
        }

        // Validate the source backup is not empty. A 0-byte ZIP cannot produce
        // a working course and may still create a broken shell that breaks the
        // Snap renderer (Call to a member function get_filename() on null).
        $sourcesize = @filesize($realpath);
        if ($sourcesize === 0) {
            throw new moodle_exception(
                'error_backup_empty',
                'block_simple_restore',
                '',
                $data->fileid
            );
        }

        copy($realpath, $data->to_path);
        $data->filename = $data->fileid;
        return true;
    }

    public static function backadel_backups($search) {
        global $CFG;
        $backadelpath = get_config('block_backadel', 'path');
        if (empty($backadelpath)) {
            return array();
        }
        $backadelpath = $CFG->dataroot . $backadelpath;

        $bysearch = function ($file) use ($search) {
            return preg_match("/{$search}/i", $file);
        };
        // $dirpath is the directory the file lives in (may be root or a year subdir).
        $tobackup = function ($file, $dirpath) {
            $backadel = new stdClass;
            $fullpath = $dirpath . $file;
            $backadel->id = $file;
            $backadel->filename = $file;
            $backadel->filepath = $fullpath;
            $backadel->filesize = filesize($fullpath);
            $backadel->timemodified = filemtime($fullpath);

            return $backadel;
        };

        $backups = [];

        // Collect top-level files (legacy flat layout).
        $potentials = array_filter(scandir($backadelpath) ?: [], $bysearch);
        foreach ($potentials as $file) {
            $backups[] = $tobackup($file, $backadelpath);
        }

        // Also scan year subdirectories (4-digit numeric dirs, e.g. "2024", "2025")
        // so files stored in backadelpath/year/ appear in the filesystem fallback
        // list before the periodic cron catalogues them (MD-2189 Bug-082).
        $isyeardir = function ($entry) use ($backadelpath) {
            return is_numeric($entry) && strlen($entry) === 4
                && is_dir($backadelpath . $entry);
        };
        $yeardirs = array_filter(scandir($backadelpath) ?: [], $isyeardir);
        foreach ($yeardirs as $yeardir) {
            $subpath = $backadelpath . $yeardir . '/';
            $subdirfiles = array_filter(scandir($subpath) ?: [], $bysearch);
            foreach ($subdirfiles as $file) {
                $backups[] = $tobackup($file, $subpath);
            }
        }

        return $backups;
    }

    public static function backadel_criterion($course) {
        global $USER;
        $crit = get_config('block_backadel', 'suffix');
        if (empty($crit)) {
            return "";
        }
        if ($crit == 'username') {
            // Backadel filenames embed only the bare local-part of the username
            // (e.g. "_lafry_" / "_lafry."), NOT the full email-style username
            // ("_lafry@lsu.edu_") that LSU adopted later. Strip the @domain so
            // the regex matches both legacy bare usernames and new email-style
            // ones. preg_quote() guards against regex metacharacters in usernames.
            // Bug-040: was previously emitting "_lafry@lsu.edu[_\.]" which never matched.
            $local = self::username_local_part((string) $USER->username);
            $search = '_' . preg_quote($local, '/');
        } else {
            $search = preg_quote((string) $course->{$crit}, '/');
        }
        return "{$search}[_\.]";
    }

    /**
     * Strip @domain from an email-style username, preserving non-email usernames as-is.
     *
     * Both Backadel filenames and the catalogue's instructors JSON store the
     * bare local-part (e.g. "lafry"), so any code path that compares a
     * Moodle $USER->username against either source must normalise first.
     *
     * @param string $username Raw username (may or may not contain '@').
     * @return string Local-part (substring before first '@') or original string if no '@'.
     */
    public static function username_local_part(string $username): string {
        $local = strstr($username, '@', true);
        return $local !== false ? $local : $username;
    }

    /**
     * Lookup distinct catalogue years / semesters matching a Moodle course shortname
     * and/or an instructor username.
     *
     * Bug-049: when a user's catalogue rows are predominantly blueprint files
     * (shortname is empty / does not match the Moodle course shortname), the
     * shortname-only predicate yielded 0 rows and the Year / Semester filter
     * dropdowns came up empty. Mirror the OR-shortname-OR-instructor pattern
     * used by {@see query_catalogue_rows()} so the filter options reflect the
     * same row-set the list will actually display.
     *
     * Rows where year IS NULL (blueprint rows) are correctly excluded from
     * the Year fieldset — blueprints have no academic year. If a user has
     * ONLY blueprints, the Year filter will (correctly) remain empty; the
     * teaching-course rows surfaced via instructor match still contribute
     * their years.
     *
     * @param string $shortname Course shortname (LIKE predicate on `shortname`). Empty = skip.
     * @param string $instructorusername Optional username for instructors-JSON LIKE match.
     * @return array{years: int[], semesters: string[]}
     */
    private static function get_catalogue_filter_options(
        string $shortname,
        string $instructorusername = ''
    ): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            return ['years' => [], 'semesters' => []];
        }

        $where = [];
        $params = [];

        if ($shortname !== '') {
            $where[] = $DB->sql_like('shortname', ':sn', false, true, false);
            $params['sn'] = $DB->sql_like_escape($shortname);
        }

        if ($instructorusername !== '') {
            // Match a JSON array literal entry: instructors stores ["lafry"], so
            // search for the quoted local-part. LIKE keeps this DB-portable
            // (avoids JSON_CONTAINS / JSON_QUOTE which differ between PG and MariaDB).
            $local = self::username_local_part($instructorusername);
            if ($local !== '') {
                $where[] = $DB->sql_like('instructors', ':instr', false, true, false);
                $params['instr'] = '%"' . $DB->sql_like_escape($local) . '"%';
            }
        }

        // Refuse to run an unbounded scan: at least one predicate must be present.
        if (empty($where)) {
            return ['years' => [], 'semesters' => []];
        }

        $wheresql = '(' . implode(' OR ', $where) . ')';

        $years = $DB->get_fieldset_sql(
            "SELECT DISTINCT year
               FROM {block_backadel_catalogue}
              WHERE {$wheresql}
                AND year IS NOT NULL
           ORDER BY year DESC",
            $params
        );
        if (!$years) {
            $years = [];
        }
        $semesters = $DB->get_fieldset_sql(
            "SELECT DISTINCT semester
               FROM {block_backadel_catalogue}
              WHERE {$wheresql}
                AND semester IS NOT NULL
                AND semester <> ''
           ORDER BY semester ASC",
            $params
        );
        if (!$semesters) {
            $semesters = [];
        }

        return [
            'years' => array_map('intval', $years),
            'semesters' => array_values(array_map(static fn($s): string => (string) $s, $semesters)),
        ];
    }

    /**
     * Query the block_backadel_catalogue table for backups matching a course shortname (optionally filtered).
     *
     * Returns an empty array if the table does not exist or no rows match.
     *
     * Bug-040: when $instructorusername is provided AND the shortname predicate
     * yields no rows, retries the same query OR-matching against the instructors
     * JSON column (e.g. `["lafry"]`). The instructor match uses LIKE on a
     * quoted local-part of the username for DB portability (PG / MariaDB).
     *
     * @param string $shortname Course shortname to look up. Empty string skips the shortname predicate.
     * @param array $filters Optional keys: q, year (int), semester, coursetype, status ('' = any status).
     * @param string $instructorusername Current user's username — falls back to instructor-match when shortname yields 0 rows.
     * @return array Normalised backup objects with id, filename, filesize, timemodified, year, coursetype.
     */
    private static function backups_from_catalogue(
        string $shortname,
        array $filters = [],
        string $instructorusername = ''
    ): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('block_backadel_catalogue')) {
            return [];
        }
        // Need at least one identifier to find rows: either a shortname or a username.
        if ($shortname === '' && $instructorusername === '') {
            return [];
        }
        $rows = self::query_catalogue_rows($shortname, $filters);

        // Fallback: catalogue rows for this instructor regardless of course shortname.
        // The catalogue's `instructors` column is a JSON array of bare local-parts
        // (`["lafry"]`). When a teacher's course has no shortname-matching catalogue
        // rows (the common case — teacher course "2024 Fall LA 1203 for Charles
        // Fryling" never matches catalogue shortname "LA-1203"), surface their files
        // by matching the instructor field instead. Catalogue shortname is the
        // preferred predicate; instructor match supplements it.
        if (empty($rows) && $instructorusername !== '') {
            $rows = self::query_catalogue_rows('', $filters, $instructorusername);
        }

        return $rows;
    }

    /**
     * Internal: build & run the catalogue SQL with the given predicates.
     *
     * Either $shortname or $instructorusername must be non-empty; the caller
     * (backups_from_catalogue) is responsible for choosing which to pass.
     *
     * @param string $shortname Course shortname (LIKE predicate on cat.shortname). Empty = skip.
     * @param array $filters Same shape as {@see backups_from_catalogue}.
     * @param string $instructorusername Optional username for instructors-JSON LIKE match.
     * @return array Normalised backup objects (see backups_from_catalogue).
     */
    private static function query_catalogue_rows(
        string $shortname,
        array $filters = [],
        string $instructorusername = ''
    ): array {
        global $DB;

        $where = [];
        $params = [];

        if ($shortname !== '') {
            $where[] = $DB->sql_like('cat.shortname', ':sn', false, true, false);
            $params['sn'] = $DB->sql_like_escape($shortname);
        }

        if ($instructorusername !== '') {
            // Match a JSON array literal entry: instructors stores ["lafry"], so
            // search for the quoted local-part. LIKE keeps this DB-portable
            // (avoids JSON_CONTAINS / JSON_QUOTE which differ between PG and MariaDB).
            //
            // Bug-047: blueprint catalogue rows (pattern=storage_legacy, e.g.
            // `MaterialsCourse_AAAS_2000_tsimpson_<ts>.zip`) embed the instructor's
            // username in the slug/shortname/filename rather than in the
            // instructors JSON (which is `[]` for that pattern). Also OR-match
            // shortname and filename so an instructor sees their own blueprint
            // backups in the catalogue path (and therefore gets the catalogue id
            // needed for the Download button).
            $local = self::username_local_part($instructorusername);
            if ($local !== '') {
                $instrlike = $DB->sql_like('cat.instructors', ':instrjson',  false, true, false);
                $shortlike = $DB->sql_like('cat.shortname',   ':instrshort', false, true, false);
                $filelike  = $DB->sql_like('cat.filename',    ':instrfile',  false, true, false);
                $where[] = "({$instrlike} OR {$shortlike} OR {$filelike})";
                $escaped = $DB->sql_like_escape($local);
                $params['instrjson']  = '%"' . $escaped . '"%';
                // Word-boundary on the slug / filename: leading `_` is a literal
                // underscore (not a LIKE single-char wildcard), so escape it with
                // the default backslash escape char that sql_like() emits.
                // shortname end form: `_tsimpson` at end of slug.
                $params['instrshort'] = '%\\_' . $escaped;
                // filename form: `_tsimpson_<ts>.zip` (underscore on both sides).
                $params['instrfile']  = '%\\_' . $escaped . '\\_%';
            }
        }

        // Refuse to run an unbounded scan: at least one predicate must be present.
        if (empty($where)) {
            return [];
        }

        $statusflt = isset($filters['status']) ? (string) $filters['status'] : 'available';
        if ($statusflt !== '') {
            $where[] = 'cat.status = :st';
            $params['st'] = $statusflt;
        }

        $yearflt = isset($filters['year']) ? (int) $filters['year'] : 0;
        if ($yearflt > 0) {
            $where[] = 'cat.year = :year';
            $params['year'] = $yearflt;
        }

        $semflt = isset($filters['semester']) ? trim((string) $filters['semester']) : '';
        if ($semflt !== '') {
            $where[] = 'cat.semester = :semester';
            $params['semester'] = $semflt;
        }

        $effectivecoursetype = \block_backadel\local\sql_helpers::effective_coursetype_sql('cat');

        $ctype = isset($filters['coursetype']) ? (string) $filters['coursetype'] : '';
        if (in_array($ctype, ['teaching', 'blueprint', 'other'], true)) {
            $where[] = "{$effectivecoursetype} = :coursetype";
            $params['coursetype'] = $ctype;
        }

        $needle = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($needle !== '') {
            $likeescaped = '%' . $DB->sql_like_escape($needle) . '%';
            $likefn = $DB->sql_like('cat.filename', ':qf', false, true, false);
            $liked = $DB->sql_like('COALESCE(cat.dept, \'\')', ':qd', false, true, false);
            $likec = $DB->sql_like('COALESCE(cat.course_num, \'\')', ':qc', false, true, false);
            $where[] = "({$likefn} OR {$liked} OR {$likec})";
            $params['qf'] = $likeescaped;
            $params['qd'] = $likeescaped;
            $params['qc'] = $likeescaped;
        }

        $wheresql = implode(' AND ', $where);

        $sql = "SELECT cat.id, cat.filename, cat.file_size, cat.backup_ts, cat.year,
                       cat.semester, cat.dept, cat.course_num, cat.pattern, cat.status,
                       COALESCE({$effectivecoursetype}, 'other') AS coursetype
                  FROM {block_backadel_catalogue} cat
                 WHERE {$wheresql}
              ORDER BY CASE COALESCE({$effectivecoursetype}, 'other')
                           WHEN 'blueprint' THEN 0
                           WHEN 'teaching' THEN 1
                           ELSE 2
                       END ASC,
                       cat.backup_ts DESC";

        $rows = $DB->get_records_sql($sql, $params);

        return array_values(array_map(static function ($row) {
            // Bug-048: when both the stored year column and the parsed
            // backup_ts are empty we leave year as '' rather than fall back
            // to date('Y', 0) which would produce "1969"/"1970" depending
            // on server timezone. Downstream rendering treats '' as the
            // "Other backups" bucket / em-dash in the Year column.
            $backupts = (int) ($row->backup_ts ?? 0);
            $year = isset($row->year) && (string) $row->year !== ''
                ? (string) $row->year
                : ($backupts > 0 ? (string) date('Y', $backupts) : '');
            return (object)[
                'id'           => (int) $row->id,
                'filename'     => (string) ($row->filename ?? ''),
                'filesize'     => (int) ($row->file_size ?? 0),
                'timemodified' => $backupts,
                'year'         => $year,
                'semester'     => (string) ($row->semester ?? ''),
                'dept'         => (string) ($row->dept ?? ''),
                'course_num'   => (string) ($row->course_num ?? ''),
                'pattern'      => (string) ($row->pattern ?? ''),
                'status'       => (string) ($row->status ?? 'available'),
                'coursetype'   => (string) ($row->coursetype ?? 'other'),
            ];
        }, $rows ?: []));
    }

    /**
     * Bug-050: Partition catalogue/backup rows by their {@code coursetype} into the three
     * sections rendered on the Restore Courses page.
     *
     * - 'blueprint' — Master/template courses (no academic year/semester).
     * - 'teaching'  — Live-course backups that fit into year buckets.
     * - 'other'     — Anything else (including unknown/missing coursetype, archived stubs, etc.).
     *
     * Match is case-insensitive on the coursetype field. Within each bucket the input order
     * is preserved (the caller controls overall ordering via the SQL ORDER BY clause).
     *
     * @param array $rows Rows from {@see backups_from_catalogue()} / {@see merge_backup_rows()}.
     * @return array{blueprint: array, teaching: array, other: array}
     */
    public static function partition_by_coursetype(array $rows): array {
        $result = ['blueprint' => [], 'teaching' => [], 'other' => []];
        foreach ($rows as $row) {
            $ctype = strtolower((string) ($row->coursetype ?? ''));
            if ($ctype === 'blueprint') {
                $result['blueprint'][] = $row;
            } else if ($ctype === 'teaching') {
                $result['teaching'][] = $row;
            } else {
                $result['other'][] = $row;
            }
        }
        return $result;
    }

    /**
     * Bug-050: Build the opening HTML for a {@code <details>}/{@code <summary>} collapsible
     * section used by the Blueprints / Other backup groups. Native HTML — no JavaScript
     * required to open/close. The caller is responsible for closing the wrapper with a
     * matching {@code </details>}.
     *
     * Moodle's {@see html_writer::start_tag()} treats every attribute value as a string and
     * therefore can't cleanly emit a bare boolean attribute like {@code <details open>}; we
     * hand-roll the opening tag for that reason. id/class/title/helptext are escaped via
     * {@see s()} for safety.
     *
     * @param string $id          DOM id for the {@code <details>} element.
     * @param string $title       Human-readable section title.
     * @param string $helptext    Short inline help text. Empty string suppresses.
     * @param int    $count       Item count rendered as a Bootstrap badge.
     * @param bool   $open        When true, emit {@code <details open>} (initially expanded).
     * @return string Opening markup (caller must append rows and close with </details>).
     */
    public static function render_collapsible_section_open(
        string $id,
        string $title,
        string $helptext,
        int $count,
        bool $open
    ): string {
        $openattr = $open ? ' open' : '';
        $badge = html_writer::tag(
            'span',
            (string) $count,
            ['class' => 'badge bg-secondary ms-2']
        );
        $help = $helptext !== ''
            ? html_writer::tag('small', s($helptext), ['class' => 'text-muted ms-2 fw-normal'])
            : '';
        $summary = html_writer::tag(
            'summary',
            html_writer::tag('span', s($title)) . $badge . $help,
            ['class' => 'h4 d-flex align-items-center py-2']
        );
        return '<details id="' . s($id) . '" class="sr-collapsible mt-3"' . $openattr . '>' . $summary;
    }

    /**
     * Merge catalogue + filesystem result sets, deduping by basename.
     *
     * Catalogue rows are richer (year, semester, dept, coursetype, status, file
     * id) and win on collision; filesystem rows only fill in basenames that the
     * catalogue does not yet cover (e.g. files not yet ingested by migration).
     * Result preserves catalogue ordering first, then any extra filesystem rows.
     *
     * @param array $catalogue Rows from {@see backups_from_catalogue}.
     * @param array $filesystem Rows from {@see backadel_backups}.
     * @return array Merged unique-by-basename row list.
     */
    public static function merge_backup_rows(array $catalogue, array $filesystem): array {
        $seen = [];
        $merged = [];
        foreach ($catalogue as $row) {
            $base = basename((string) ($row->filename ?? ''));
            if ($base === '' || isset($seen[$base])) {
                continue;
            }
            $seen[$base] = true;
            $merged[] = $row;
        }
        foreach ($filesystem as $row) {
            $base = basename((string) ($row->filename ?? ''));
            if ($base === '' || isset($seen[$base])) {
                continue;
            }
            $seen[$base] = true;
            $merged[] = $row;
        }
        return $merged;
    }

    public static function backup_list($data) {
        global $DB, $USER;
        $course = isset($data->shortname)
            ? (object)['shortname' => $data->shortname]
            : $DB->get_record('course', ['id' => $data->courseid]);

        $filters = [
            'q' => optional_param('q', '', PARAM_TEXT),
            'year' => optional_param('year', 0, PARAM_INT),
            'semester' => optional_param('semester', '', PARAM_TEXT),
            'coursetype' => optional_param('coursetype', '', PARAM_ALPHA),
            'status' => optional_param('status', 'available', PARAM_ALPHA),
        ];
        $data->catalogue_filters = $filters;

        // In admin mode $data->shortname is the course code entered in the search form;
        // $course is the site course (id=SITEID) whose shortname is irrelevant for catalogue lookup.
        $catalogueshort = (isset($data->shortname) && (string) $data->shortname !== '')
            ? (string) $data->shortname
            : (string) ($course->shortname ?? '');
        // Bug-040: in teacher mode (no admin shortname filter) also OR-match the
        // catalogue against the current user's username, so instructors find
        // their own historical backups even when no catalogue row's shortname
        // matches their Moodle course shortname.
        // Bug-083: in admin mode, if a text-search token (q param) was submitted,
        // use it as instructorusername so the instructor-JSON OR fallback fires in
        // backups_from_catalogue() when the shortname predicate returns 0 rows
        // (e.g. admin searches 'lafry' — shortname='lafry' finds nothing, but
        // instructors JSON LIKE '%"lafry"%' returns their catalogue rows).
        if (!isset($data->shortname)) {
            // Teacher/instructor path: always match by current user's username.
            $instructorusername = (string) $USER->username;
        } else {
            // Admin path: use the explicit q-search token if provided, otherwise
            // empty (shortname-only lookup; LSUO-102 default preserved).
            $instructorusername = (isset($data->qsearch) && (string) $data->qsearch !== '')
                ? (string) $data->qsearch
                : '';
        }
        // Bug-049: pass the instructor username so the Year/Semester filter
        // dropdowns match the same OR-shortname-OR-instructor row-set the list
        // body queries. Without this, teachers with mostly-blueprint backups
        // see "All years / All semesters" with no actual options.
        $catopts = self::get_catalogue_filter_options($catalogueshort, $instructorusername);

        $yearopts = [
            0 => get_string('filter_all_years', 'block_simple_restore'),
        ];
        foreach ($catopts['years'] as $y) {
            $iy = (int) $y;
            $yearopts[$iy] = (string) $iy;
        }
        $data->catalogue_years = $yearopts;

        $semopts = [
            '' => get_string('filter_all_semesters', 'block_simple_restore'),
        ];
        foreach ($catopts['semesters'] as $sem) {
            $semopts[$sem] = $sem;
        }
        $data->catalogue_semesters = $semopts;

        $list = new stdClass;
        $list->header = get_string('semester_backups', 'block_simple_restore');
        $list->order  = 10;
        $list->html   = '';

        // Bug-040: merge catalogue rows + filesystem rows instead of either/or.
        // Catalogue rows (richer metadata: year/semester/dept/coursetype/status)
        // win on key collisions; filesystem rows fill in any gaps for files that
        // haven't been ingested yet. Dedupe by basename so a file present on
        // both sides shows once. The list is tagged as "catalogue" if any
        // catalogue row participates so the UI gets the catalogue render path.
        $cataloguerows = self::backups_from_catalogue($catalogueshort, $filters, $instructorusername);

        $search = isset($data->shortname)
            ? self::backadel_shortname($data->shortname)
            : self::backadel_criterion($course);
        $fsrows = ($search !== '') ? self::backadel_backups($search) : [];

        $merged = self::merge_backup_rows($cataloguerows, $fsrows);

        if (!empty($merged)) {
            $list->backups = $merged;
            // Tag as catalogue when catalogue rows were involved so the UI uses
            // the year-bucketed render path; otherwise it's a pure filesystem list.
            $list->source = !empty($cataloguerows) ? 'catalogue' : 'semester_backadel';
        } else {
            $list->backups = [];
            $list->source = 'semester_backadel';
        }

        $data->lists[] = $list;

        return (
            self::course_backups($data) &&
            self::user_backups($data)
        );
    }

    public static function course_backups($data) {
        if (isset($data->shortname)) {
            $courses = self::filter_courses($data->shortname);
        } else {
            $courses = enrol_get_my_courses();
        }

        $tohtml = function($in, $course) use ($data) {
            global $DB, $OUTPUT;

            $ctx = context_course::instance($course->id);

            $backups = $DB->get_records('files', array(
                'component' => 'backup',
                'contextid' => $ctx->id,
                'filearea' => 'course',
                'mimetype' => 'application/vnd.moodle.backup'
            ), 'timemodified DESC');

            if (empty($backups)) {
                return $in;
            }

            return $in . (
                $OUTPUT->heading($course->shortname) .
                simple_restore_utils::build_table(
                    $backups,
                    'course',
                    $data->courseid,
                    $data->restore_to
                )
            );
        };

        $list = new stdClass;
        $list->html = array_reduce($courses, $tohtml, '');
        $list->backups = !empty($list->html);
        $list->order = 100;

        $data->lists[] = $list;

        return true;
    }

    public static function user_backups($data) {
        global $USER, $DB, $PAGE, $OUTPUT;

        $usercontext = context_user::instance($USER->id);
        $context = context_course::instance($data->courseid);

        $params = array(
            'component' => 'user',
            'filearea' => 'backup',
            'contextid' => $usercontext->id,
        );
        $correctfiles = function($file) { return $file->filename != '.';
        };
        $backupfiles = $DB->get_records('files', $params);

        $params = array(
            'contextid' => $usercontext->id,
            'currentcontext' => $context->id,
            'filearea' => 'backup',
            'component' => 'user',
            'returnurl' => $PAGE->url->out(false)
        );

        $str = get_string('managefiles', 'backup');
        $url = new moodle_url('/backup/backupfilesedit.php', $params);

        $list = new stdClass;
        $list->header = get_string('choosefilefromuserbackup', 'backup');
        $list->backups = array_filter($backupfiles, $correctfiles);
        $list->order = 200;

        $list->html = (
            $OUTPUT->heading($list->header) .
            $OUTPUT->single_button($url, $str, 'post', array('class' => 'center padded'))
        );

        if ($list->backups) {
            $list->html .= self::build_table(
                $list->backups,
                'user',
                $data->courseid,
                $data->restore_to
            );
        }

        $data->lists[] = $list;

        return true;
    }


    public static function permission($cap, $context) {
        return has_capability("block/simple_restore:{$cap}", $context);
    }

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- legacy public API, renaming would break 18+ call sites.
    public static function _s($name, $a=null) {
        return get_string($name, 'block_simple_restore', $a);
    }

    public static function build_table($backups, $name, $courseid, $restoreto) {
        $table = new html_table();
        $table->head = array(
            get_string('name'),
            get_string('size'),
            get_string('modified')
        );

        $torow = function($backup) use ($name, $courseid, $restoreto) {
            $link = html_writer::link(
                new moodle_url('/blocks/simple_restore/list.php', array(
                    'id' => $courseid,
                    'name' => $name,
                    'action' => 'choosefile',
                    'restore_to' => $restoreto,
                    'fileid' => $backup->id
                )), $backup->filename);
                $name = new html_table_cell($link);
                $size = new html_table_cell(display_size($backup->filesize));
                $modified = new html_table_cell(date('d M Y, h:i:s A',
                                            $backup->timemodified));
                return new html_table_row(array($name, $size, $modified));
        };
        $table->data = array_map($torow, $backups);
        return html_writer::table($table);
    }

    public static function filter_courses($shortname) {
        global $DB;
        $likesql = $DB->sql_like('shortname', ':sn', false, true, false);
        $params = ['sn' => '%' . $DB->sql_like_escape($shortname) . '%'];
        return $DB->get_records_select('course', $likesql, $params);
    }

    public static function heading($restoreto) {
        switch($restoreto){
            case 0:
                return self::_s('delete_restore');
            case 1:
                return self::_s('restore_course');
            case 2:
                return self::_s('restore_course_archive');
        }
    }

    public static function prep_restore($fileid, $name, $courseid) {
        global $USER;

        // Get the includes.
        self::includes();

        if (empty($fileid) || empty($courseid)) {
            throw new Exception(self::_s('no_arguments'));
        }

        $filename = restore_controller::get_tempdir_name($courseid, $USER->id);
        // Must match backup/util/ui/restore_ui_stage.class.php (CONFIRM stage), which
        // resolves archives via make_backup_temp_directory(''), not raw $CFG->tempdir.
        $backuptempdir = make_backup_temp_directory('');
        $pathname = $backuptempdir . '/' . $filename;

        $data = new stdClass;
        $data->userid = $USER->id;
        $data->courseid = $courseid;
        $data->fileid = $fileid;
        $data->name = $name;
        $data->to_path = $pathname;
        $data->filename = $filename;

        self::selected_backadel($data);

        simple_restore_selected_user::selected_user($data);

        if (empty($data->filename)) {
            throw new Exception(self::_s('no_file'));
        }

        // Final size check on the staged temp copy — covers user-area backups
        // and catches a corrupted copy that may have ended up zero bytes.
        if (file_exists($data->to_path) && @filesize($data->to_path) === 0) {
            // Clean up the empty temp copy so it doesn't linger.
            @unlink($data->to_path);
            throw new moodle_exception(
                'error_backup_empty',
                'block_simple_restore',
                '',
                $data->fileid
            );
        }

        return $filename;
    }

    /**
     * Simple Restore post restore fixes
     * NOT DONE.
     * REQUIRES CPS AND UES TO FUNCTION.
     * DO NOT CALL!!!
     *
     * @param  $data
     * @param  int  other['userid']
     * @param  int  other['restore_to'] 0,1,2
     * @param  int  other['courseid']
     */
    public static function simple_restore_complete($data) {
        try {
            global $DB, $CFG, $USER;
            require_once($CFG->dirroot . '/blocks/cps/classes/lib.php');

            $sectionid = $data->other['ues_section_id'];
            $restoreto = $data->other['restore_to'];
            $oldcourse = get_course($data->other['courseid']);

            $skip = array(
                'id', 'category', 'sortorder',
                'sectioncache', 'modinfo', 'newsitems'
            );

            $course = $DB->get_record('course', array('id' => $oldcourse->id));

            $resetgrades = cps_setting::get(array(
                'name' => 'user_grade_restore',
                'userid' => $USER->id
            ));

            // Defaults to reset grade items.
            if (empty($resetgrades)) {
                $resetgrades = new stdClass;
                $resetgrades->value = 1;
            }

            // Maintain the correct config.
            foreach (get_object_vars($oldcourse) as $key => $value) {
                if (in_array($key, $skip)) {
                    continue;
                }

                $course->$key = $value;
            }

            $DB->update_record('course', $course);

            if ($resetgrades->value == 1) {
                require_once($CFG->libdir . '/gradelib.php');

                $items = grade_item::fetch_all(array('courseid' => $course->id));
                foreach ($items as $item) {
                    $item->plusfactor = 0.00000;
                    $item->multfactor = 1.00000;
                    $item->update();
                }

                grade_regrade_final_grades($course->id);
            }

            // This is an import, ignore.
            if ($restoreto == 1) {
                return true;
            }

            $keepenrollments = (bool) get_config('simple_restore', 'keep_roles_and_enrolments');
            $keepgroups = (bool) get_config('simple_restore', 'keep_groups_and_groupings');

            // No need to re-enroll.
            if ($keepgroups && $keepenrollments) {
                $enrolinstances = $DB->get_records('enrol', array(
                    'courseid' => $oldcourse->id,
                    'enrol' => 'ues'
                ));

                // Cleanup old instances.
                $ues = enrol_get_plugin('ues');

                foreach (array_slice($enrolinstances, 1) as $instance) {
                    $ues->delete_instance($instance);
                }

            } else {
                $sections = ues_section::from_course($course);

                // Nothing to do.
                if (empty($sections)) {
                    return true;
                }

                // Rebuild enrollment.
                ues::enrollUsers(ues_section::from_course($course));
            }

            return true;

        } catch (Exception $e) {
            return false;
        }
    }
}

class archive_restore_utils extends simple_restore_utils {
    /**
     * Get course display name and category name from a backup archive filename.
     *
     * Legacy names prefixed with backadel- use the original underscore/hyphen heuristic.
     * Other names are resolved via {@see \block_backadel\local\filename_parser::parse()}.
     * Unknown or unparseable names return [null, null]; callers should substitute defaults.
     *
     * @param string $filename Archive basename or path
     * @return array{0: ?string, 1: ?string} Fullname and Moodle course category name
     */
    public static function coursedata_from_filename($filename) {
        $prefix = 'backadel';
        if (substr($filename, 0, strlen($prefix)) == $prefix) {
            $stripped = substr($filename, strlen($prefix) + 1);
            $chunks     = explode('_', $stripped);
            $meta       = $chunks[0];
            $metachunks = explode('-', $meta);
            $fullname   = implode(' ', $metachunks);
            $category   = $metachunks[2];

            return array($fullname, $category);
        }

        $parsed = \block_backadel\local\filename_parser::parse($filename);
        if ($parsed === null || ($parsed['pattern'] ?? '') === 'unknown') {
            return array(null, null);
        }

        $shortnamehint = $parsed['shortname_hint'] ?? null;
        $dept = $parsed['dept'] ?? null;
        $coursenum = $parsed['course_num'] ?? null;
        $year = $parsed['year'] ?? null;
        $semester = $parsed['semester'] ?? null;

        $fullname = null;
        if (is_string($shortnamehint) && $shortnamehint !== '') {
            $fullname = $shortnamehint;
        } else if (is_string($dept) && $dept !== '' && is_string($coursenum) && $coursenum !== '') {
            $fullname = trim($dept . ' ' . $coursenum);
        }
        if ($fullname === null || $fullname === '') {
            $fullname = pathinfo($filename, PATHINFO_FILENAME);
        }

        $category = null;
        if ($year !== null && is_string($semester) && $semester !== '') {
            $category = $semester . ' ' . $year;
        } else if (is_string($semester) && $semester !== '') {
            $category = $semester;
        }

        if ($category === null || $category === '') {
            $category = 'Archive';
        }

        return array($fullname, $category);
    }
}

class simple_restore {
    public $userid;
    public $course;
    public $context;
    public $filename;
    public $restore_to;

    public function __construct($course, $filename, $restoreto = 0) {
        if (empty($course)) {
            throw new Exception(simple_restore_utils::_s('no_context'));
        }
        if (empty($filename)) {
            throw new Exception(simple_restore_utils::_s('no_file'));
        }

        global $USER;

        $this->userid = $USER->id;
        $this->course = $course;
        $this->context = context_course::instance($course->id);
        $this->filename = $filename;
        $this->restore_to = $restoreto;
    }

    private function process_confirm() {
        $restore = restore_ui::engage_independent_stage(
            restore_ui::STAGE_CONFIRM, $this->context->id
        );
        $restore->process();
        return $restore;
    }

    private function process_destination($restore) {
        $_POST['sesskey']   = sesskey();
        $_POST['filepath']  = $this->rip_value($restore, 'filepath');
        $_POST['target']    = $this->restore_to;
        $_POST['targetid']  = $this->course->id;

        $rtn = restore_ui::engage_independent_stage(
            restore_ui::STAGE_DESTINATION, $this->context->id
        );
        $rtn->process();
        return $rtn;
    }

    private function process_schema($rc) {
        // File dependencies.
        $filedependencies = array(
            'block' => 1, 'comments' => 1, 'filters' => 1
        );

        $_POST['stage'] = restore_ui::STAGE_SCHEMA;
        $restore = new restore_ui($rc, array('contextid' => $this->context->id));

        // Forge posts.
        $_POST['restore'] = $restore->get_restoreid();

        // Get all tasks from the UI object through reflection.
        $tasks = $this->rip_ui($restore)->get_tasks();
        foreach ($tasks as $task) {
            $settings = $task->get_settings();
            foreach ($settings as $setting) {
                $settingname = $setting->get_name();

                if (preg_match('/(.+)_(\d+)_(.+)/', $settingname, $matches)) {
                    $module = $matches[1];
                    $type = $matches[3];
                    $adminsettingkey = $module.'_'.$type;
                } else {
                    $adminsettingkey = $settingname;
                }
                $adminsetting = get_config('simple_restore', $adminsettingkey);
                if (!is_numeric($adminsetting)) {
                    continue;
                }

                if ($adminsetting && isset($filedependencies[$settingname])) {
                    $basepath = $task->get_taskbasepath();
                    if (!file_exists("$basepath/$settingname.xml")) {
                        continue;
                    }
                }
                // Set admin value.
                // Some settings may be locked by permission.
                if ($setting->get_status() == base_setting::NOT_LOCKED) {
                    $setting->set_value($adminsetting);
                }
            }
        }

        $restore->process();
        $restore->save_controller();
        return $restore;
    }

    private function rip_value($restore, $property) {
        $reflector = new ReflectionObject($restore);
        $prop = $reflector->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($restore);
    }

    private function rip_stage($restore) {
        return $this->rip_value($restore, 'stage');
    }

    private function rip_ui($restore) {
        return $this->rip_value($this->rip_stage($restore), 'ui');
    }

    private function process_final($restore) {
        $_POST['stage'] = restore_ui::STAGE_PROCESS;
        $rc = restore_ui::load_controller($restore->get_restoreid());
        $final = new restore_ui($rc, array('contextid' => $this->context->id));
        $final->process();
        $final->execute();
        $final->destroy();
        unset($final);
    }

    public function execute() {
        global $OUTPUT, $PAGE;

        simple_restore_utils::includes();

        $useasync = (bool)get_config('simple_restore', 'async_toggle');

        if ($useasync) {
            // Prepare a progress bar which can display optionally during long-running
            // operations while setting up the UI.
            $slowprogress = new \core\progress\display_if_slow(get_string('preparingui', 'backup'));

            // Overall, allow 10 units of progress.
            $slowprogress->start_progress('', 10);

            // This progress section counts for loading the restore controller.
            $slowprogress->start_progress('', 1, 1);

            $backupmode = backup::MODE_ASYNC;
            // Prefer to use bool.
            $useasync = true;
        } else {
            $backupmode = backup::MODE_GENERAL;
            // Prefer to use bool.
            $useasync = false;
        }

        // Archive mode.
        if ($this->restore_to == 2 && get_config('simple_restore', 'is_archive_server')) {
            return $this->archive_mode_execute();
        }

        // Confirmed ... process destination.
        $confirmed = $this->process_destination($this->process_confirm());

        // Setting up controller ... tmp tables.
        $rc = new restore_controller(
            $confirmed->get_filepath(),
            $confirmed->get_course_id(),
            backup::INTERACTIVE_YES,
            $backupmode,
            $this->userid,
            $confirmed->get_target()
        );

        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }

        if ($useasync) {

            // Get the renderer so we can use the backup status template.
            $renderer = $PAGE->get_renderer('core', 'backup');

            $restore = new restore_ui($rc, array('contextid' => $this->context->id));
            $restore->set_progress_reporter($slowprogress);

            if (!$restore->is_independent()) {
                // Use a temporary (disappearing) progress bar to show the precheck progress if any.
                $precheckprogress = new \core\progress\display_if_slow(get_string('preparingdata', 'backup'));
                $restore->get_controller()->set_progress($precheckprogress);

                if ($rc->get_status() == backup::STATUS_SETTING_UI) {
                    $rc->finish_ui();
                }
                if ($rc->get_status() == backup::STATUS_NEED_PRECHECK) {
                    if (!$rc->precheck_executed()) {
                        $rc->execute_precheck(true);
                    }
                    $precheckresults = $rc->get_precheck_results();
                    if (!empty($precheckresults['errors'] ?? []) || !empty($precheckresults['warnings'] ?? [])) {
                        echo $renderer->precheck_notices($precheckresults);
                        echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $this->course->id)));
                        echo $OUTPUT->footer();
                        die();
                    }
                }
                $restore->save_controller();
            }

            echo $renderer->progress_bar($restore->get_progress_bar());

            // Asynchronous restore.
            // Create adhoc task for restore.
            $restoreid = $restore->get_restoreid();
            $asynctask = new \core\task\asynchronous_restore_task();
            $asynctask->set_userid($this->userid);
            $asynctask->set_custom_data(array('backupid' => $restoreid));
            \core\task\manager::queue_adhoc_task($asynctask);

            // Add ajax progress bar and initiate ajax via a template.
            $restoreurl = new moodle_url('/backup/restorefile.php', array('contextid' => $this->context->id));
            $courseurl = course_get_url($this->course->id);
            $progresssetup = array(
                    'backupid' => $restoreid,
                    'contextid' => $this->context->id,
                    'courseurl' => $courseurl,
                    'restoreurl' => $restoreurl->out()
            );
            echo $renderer->render_from_template('core/async_backup_status', $progresssetup);

            $restore->destroy();
            unset($restore);
        } else {
            // The old way.
            $this->process_final($this->process_schema($rc));
        }

        // Probably good to do this.
        unset($confirmed);

        // Restore blocks.
        if ($this->restore_to == 0) {
            blocks_delete_all_for_context($this->context->id);
            blocks_add_default_course_blocks($this->course);
        }

        // It's important to pass the previous course's config.
        $coursesettings = array(
            'restore_to' => $this->restore_to,
            'course' => $this->course
        );

        return true;
    }

    /**
     * Create a new course from the selected backup file.
     *
     * This method is inspired by @see core_course_external::duplicate_course.
     * found in /course/externallib.php.
     *
     * @global type $CFG
     * @global type $DB
     * @global type $USER
     * @return boolean
     * @throws moodle_exception
     */
    public function archive_mode_execute() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/enrol/manual/lib.php');
        simple_restore_utils::includes();

        // Enrol the current user as teacher.
        $plugin       = new enrol_manual_plugin();
        $plugin->add_instance($this->course);

        $instances    = enrol_get_instances($this->course->id, true);
        $isntance     = null;
        foreach ($instances as $enrolinstance) {
            if ($enrolinstance->enrol == 'manual') {
                $instance = $enrolinstance;
                break;
            }
        }

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $plugin->enrol_user($instance, $USER->id, $roleid);

        // Setup tempdir for the restore process.
        $tempdir = isset($CFG->backuptempdir) ? $CFG->backuptempdir : $CFG->tempdir;
        $tempdir = substr($tempdir, -1) === '/' ? $tempdir : $tempdir . '/';
        $extractname = restore_controller::get_tempdir_name($this->course->id, $USER->id);
        $extractpath = $tempdir . $extractname;
        $filepath    = $tempdir . $this->filename;

        if (!has_capability('moodle/restore:userinfo', $this->context, $USER->id)) {
            // Delete, abort, etc.
            echo "deleting temporary course files and materials.";
            fulldelete($filepath);
            delete_course($this->course);

            // Update course count in catagories.
            fix_course_sortorder();

            // In order to restore Archived courses,
            // this role must be granted the capability moodle/restore:userinfo - Ask your administrator.
            throw new restore_controller_exception("no userinfo cap");
        }

        // Zip file needs to be unzipped.
        if (!file_exists($filepath. "/moodle_backup.xml")) {
            $fb = get_file_packer('application/vnd.moodle.backup');
            $fb->extract_to_pathname("$tempdir" . $this->filename, $extractpath);
        }

        $rc = new restore_controller($extractname, $this->course->id,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_NEW_COURSE);

        // Iterate through our settings and make sure they are reflected in the restore plan.
        $configsettings = array_values($this->get_settings());

        foreach ($configsettings as $config) {
            if ($rc->get_plan()->setting_exists($config->name)) {
                $setting = $rc->get_plan()->get_setting($config->name);
                if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                    $setting->set_value($config->value);
                }
            }
        }

        // Setup restore process and ensure there are no errors.
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($filepath);
                }

                $errorinfo = '';

                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }

                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }
                throw new moodle_exception('backupprecheckerrors', 'webservice', '', $errorinfo);
            }
        }

        // Get the correct course name - prevents dupe names.
        list($this->course->fullname, $this->course->shortname) =
                restore_dbops::calculate_course_names(
                        $this->course->id,
                        $this->course->fullname,
                        $this->course->shortname
                        );

        $rc->execute_plan();
        $rc->destroy();

        // Set shortname and fullname back, ensure visibility.
        $this->course->visible = 1;
        $DB->update_record('course', $this->course);

        // Clean up after ourselves.
        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($filepath);
        }

        return true;
    }

    private function get_settings() {
        global $DB;
        $settings = $DB->get_records('config_plugins', array('plugin' => 'simple_restore'), null, 'id,name,value');
        return $settings;
    }
}

class simple_restore_selected_user {
    public static function selected_user($data) {
        return self::selected($data);
    }

    private static function selected($data) {
        global $DB, $CFG;
        // Catalogue / Backadel restores already copied the archive onto $data->to_path in
        // selected_backadel(). $data->fileid there is a catalogue PK or backadel basename —
        // not an mdl_files.id — and colliding with an unrelated files row can overwrite or
        // wipe the staged copy, yielding restore_ui_exception invalidrestorefile on CONFIRM.
        if (isset($data->name) && ($data->name === 'catalogue' || $data->name === 'backadel')) {
            return true;
        }

        $backup = $DB->get_record('files', array('id' => $data->fileid));
        if (empty($backup)) {
            return true;
        }
        $fs = get_file_storage();
        $browser = get_file_browser();
        $filecontext = context::instance_by_id($backup->contextid);
        $storedfile = $fs->get_file(
            $filecontext->id,
            $backup->component,
            $backup->filearea,
            $backup->itemid,
            $backup->filepath,
            $backup->filename
        );
        $fileinfo = new file_info_stored(
            $browser,
            $filecontext,
            $storedfile,
            $CFG->wwwroot.'/pluginfile.php',
            '',
            false,
            simple_restore_utils::permission(
                'canrestore',
                context_course::instance($data->courseid)
            ),
            false,
            true
        );
        $fileinfo->copy_to_pathname($data->to_path);
        $data->filename = $backup->filename;
        return true;
    }
}
