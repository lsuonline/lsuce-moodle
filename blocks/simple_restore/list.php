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

require_once('../../config.php');
global $CFG;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

$courseid = required_param('id', PARAM_INT);
$restoreto = optional_param('restore_to', 0, PARAM_INT);

$name = optional_param('name', null, PARAM_ALPHANUMEXT);
$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$file = optional_param('fileid', null, PARAM_FILE);

// Needed for admins, as they need to query the courses.
$shortname = optional_param('shortname', null, PARAM_TEXT);

// Catalogue filter params for GET URLs and backups_from_catalogue().
$listurlparams = [
    'id' => $courseid,
    'restore_to' => $restoreto,
];
$listfltq = optional_param('q', '', PARAM_TEXT);
if ($listfltq !== '') {
    $listurlparams['q'] = $listfltq;
}
$listfltyear = optional_param('year', 0, PARAM_INT);
if ($listfltyear > 0) {
    $listurlparams['year'] = $listfltyear;
}
$listfltsem = optional_param('semester', '', PARAM_TEXT);
if ($listfltsem !== '') {
    $listurlparams['semester'] = $listfltsem;
}
$listfltcourse = optional_param('coursetype', '', PARAM_ALPHA);
if ($listfltcourse !== '') {
    $listurlparams['coursetype'] = $listfltcourse;
}
$listfltstatus = optional_param('status', 'available', PARAM_ALPHA);
if ($listfltstatus !== 'available') {
    $listurlparams['status'] = $listfltstatus;
}

// Determine whether archive mode (also recomputed below for capability gating).
$archivemode = $courseid == SITEID && get_config('simple_restore', 'is_archive_server');

// Admin path = SITEID landing or archive-server mode. Teachers always hit a
// real course id, so they never trip this gate even if they happen to hold
// moodle/course:create at the system level.
$adminmode = $archivemode || $courseid == SITEID;

if ($adminmode) {
    // Admin nav entry (settings.php) routes to catalogue.php. Redirect immediately
    // so admins land on the full Backadel catalogue with instructor/year/semester
    // filters instead of the retired shortname-form gate.
    admin_externalpage_setup(
        'block_simple_restore_list',
        '',
        $listurlparams,
        (new moodle_url('/blocks/simple_restore/list.php'))->out(false)
    );
    $catalogueparams = array_filter([
        'q'          => $listfltq,
        'year'       => $listfltyear ?: null,
        'semester'   => $listfltsem ?: null,
        'coursetype' => $listfltcourse ?: null,
        'status'     => ($listfltstatus !== 'available') ? $listfltstatus : null,
    ], fn($v) => $v !== null && $v !== '');
    redirect(
        new moodle_url('/blocks/backadel/catalogue.php', $catalogueparams),
        get_string('admin_redirected_to_catalogue', 'block_simple_restore'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
} else {
    // Teacher path: course-context login + permission check.
    require_login();
}

// Determine whether archive mode.
$archivemode = $courseid == SITEID && get_config('simple_restore', 'is_archive_server');

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('no_course', 'block_simple_restore', '', $courseid);
}

$blockname = get_string('pluginname', 'block_simple_restore');
$heading = simple_restore_utils::heading($restoreto);

$baseurl = new moodle_url('/blocks/simple_restore/list.php', $listurlparams);


// Set context and require capabilities depending on archive_mode.
if ($archivemode) {
    $context = context_system::instance();
    require_capability('block/simple_restore:canrestorearchive', $context);
} else {
    $context = context_course::instance($courseid);
    require_capability('block/simple_restore:canrestore', $context);
}

// Return the number of grades.
$sql = "SELECT COUNT(*) as count
        FROM {course} c
        JOIN {grade_items} gi ON gi.courseid = c.id
        JOIN {grade_grades} gg ON gi.id = gg.itemid
        WHERE NOT gg.finalgrade <=> NULL
        AND gi.courseid = :courseid";
$count = $DB->count_records_sql($sql, array("courseid" => $courseid));

if ($count > 0) {
    $warn = $OUTPUT->notification(simple_restore_utils::_s('have_grades'));

    if (!$adminmode) {
        $PAGE->set_url($baseurl);
        $PAGE->set_context($context);
        $PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php?id='.$course->id));
        $PAGE->set_title($blockname.': '.$heading);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(simple_restore_utils::_s('restore_stopped'));
    echo $warn;
    echo $OUTPUT->continue_button(
        new moodle_url('/course/view.php', array('id' => $course->id))
    );
    echo $OUTPUT->footer();
    die;
}

// The user has chosen a file.
if ($file && $action && $name) {

    // We need to get the course name, etc differently when in archive mode.
    if ($archivemode) {
        simple_restore_utils::includes();

        // Parse the filename for course fullname and category.
        list($fullname, $category) = archive_restore_utils::coursedata_from_filename($file);
        if ($fullname === null || $fullname === '') {
            $fullname = pathinfo($file, PATHINFO_FILENAME);
        }
        if ($category === null || $category === '') {
            $category = 'Archive';
        }

        // Get a category object.
        if (!$DB->record_exists('course_categories', array('name' => $category))) {
            // Create the category if it doesn't exits.
            $category = core_course_category::create(array('name' => $category));
        } else {
            // Otherwise, just fetch it.
            $category = $DB->get_record('course_categories', array('name' => $category));
        }

        // Prep_restore needs a course and a context.
        $courseid = restore_dbops::create_new_course($fullname, $fullname, $category->id);
        $context = context_course::instance($courseid);
    }

    // Move the backup file into place.
    $filename = simple_restore_utils::prep_restore($file, $name, $courseid);
    redirect(new moodle_url('/blocks/simple_restore/restore.php', array(
        'contextid' => $context->id,
        'filename' => $filename,
        'restore_to' => $restoreto
    )));
}

if (!$adminmode) {
    $PAGE->set_context($context);
    $PAGE->set_course($course);
    if (!$archivemode && $course->id != SITEID) {
        $PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', ['id' => $course->id]));
    }
    $PAGE->navbar->add($blockname);
    $PAGE->set_title($blockname.': '.$heading);
    $PAGE->set_heading($blockname.': '.$heading);
    $PAGE->set_url($baseurl);
}

$system = context_system::instance();

$isadmin = has_capability('moodle/course:create', $system);

// Count active catalogue filters for the pill-button badge.
$sractivefiltercount = 0;
if ($listfltq !== '') {
    $sractivefiltercount++;
}
if ($listfltyear > 0) {
    $sractivefiltercount++;
}
if ($listfltsem !== '') {
    $sractivefiltercount++;
}
if ($listfltcourse !== '') {
    $sractivefiltercount++;
}
if ($listfltstatus !== '' && $listfltstatus !== 'available') {
    $sractivefiltercount++;
}

$PAGE->requires->js_call_amd('block_backadel/filter_panel', 'init');
$PAGE->requires->js_call_amd('block_backadel/help', 'init');

echo $OUTPUT->header();

$data = new stdClass;
$data->restore_to = $restoreto;
$data->courseid = $courseid;
// Admins can filter by shortname via the search form. When no shortname was
// submitted, default to the course's own shortname so backup_list() takes
// the catalogue lookup path (year-bucketed layout) instead of the legacy
// filesystem scan. Without this, admins who are not listed as instructors
// in the catalogue get the raw filesystem list (LSUO-102 / Bug-102).
if ($isadmin) {
    $data->shortname = ($shortname !== null && $shortname !== '')
        ? $shortname
        : $course->shortname;
    // Bug-083: expose the text-search token (q param) so backup_list() can
    // pass it as instructorusername when the shortname predicate returns 0 rows.
    // Without this, admin searching 'lafry' gets catalogueshort='<course code>'
    // + instructorusername='' and the instructor-JSON fallback never fires.
    $data->qsearch = $listfltq;
}
$data->lists = array();

simple_restore_utils::backup_list($data);

$PAGE->requires->js_call_amd('block_simple_restore/restore_actions', 'init');

// Obey handled order before rendering Semester Backup sections.
usort($data->lists, function($a, $b) {
    if ($a->order == $b->order) {
        return 0;
    }
    return $a->order < $b->order ? -1 : 1;
});

require_once($CFG->libdir . '/formslib.php');

$hascatalogue = false;
foreach ($data->lists as $lst) {
    if (($lst->source ?? '') === 'catalogue') {
        $hascatalogue = true;
        break;
    }
}
// Bug-041: render the filter panel + ? help button whenever the catalogue
// table exists (i.e. block_backadel is installed), not only when the current
// course already yielded catalogue rows. Previously instructors lost the
// filter UI and inline help any time their course shortname did not match a
// catalogue row exactly. Filters are still useful with 0 catalogue rows
// (they let users narrow the filesystem fallback list).
$cataloguetableexists = $DB->get_manager()->table_exists('block_backadel_catalogue');
if ($hascatalogue || $sractivefiltercount > 0 || $cataloguetableexists) {
    $cf = $data->catalogue_filters ?? [];
    $filtersactive =
        (($cf['q'] ?? '') !== '') ||
        ((int) ($cf['year'] ?? 0) > 0) ||
        (($cf['semester'] ?? '') !== '') ||
        (($cf['coursetype'] ?? '') !== '') ||
        (($cf['status'] ?? 'available') !== 'available');
    $filterform = new \block_simple_restore\form\list_filter_form(
        new moodle_url('/blocks/simple_restore/list.php'),
        [
            'years' => $data->catalogue_years ?? [],
            'semesters' => $data->catalogue_semesters ?? [],
            'filtersactive' => $filtersactive,
            'courseid' => $courseid,
            'restore_to' => $restoreto,
        ],
        'get'
    );
    $filterform->set_data([
        'q' => optional_param('q', '', PARAM_TEXT),
        'year' => optional_param('year', 0, PARAM_INT),
        'semester' => optional_param('semester', '', PARAM_TEXT),
        'coursetype' => optional_param('coursetype', '', PARAM_ALPHA),
        'status' => optional_param('status', 'available', PARAM_ALPHA),
        'id' => $courseid,
        'restore_to' => $restoreto,
    ]);

    // Offcanvas filter panel (reuses block_backadel components).
    // The ? help button is rendered inside the filter panel's flex row via extrabuttonshtml.
    $srhelpbtn = html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary px-2 ms-2',
        'data-action' => 'show-help',
        'data-help-topic' => 'simple_restore_list',
        'data-help-title' => get_string('pluginname', 'block_simple_restore'),
        'aria-label' => get_string('help_button_label', 'block_backadel'),
    ]);
    ob_start();
    $filterform->display();
    $srformhtml = ob_get_clean();
    echo $OUTPUT->render_from_template('block_backadel/local/filter_panel', [
        'collapseid'       => 'sr-list-filters',
        'title'            => get_string('filter_panel_toggle', 'block_backadel'),
        'activecount'      => $sractivefiltercount,
        'formhtml'         => $srformhtml,
        'extrabuttonshtml' => $srhelpbtn,
    ]);
}

$displaylist = function ($in, $list) use ($OUTPUT, $PAGE, $courseid, $course, $data, $restoreto, $isadmin, $listfltcourse) {
    $source = $list->source ?? '';
    if (in_array($source, ['semester_backadel', 'catalogue'], true) && !empty($list->backups)) {
        $shortname = isset($data->shortname) ? $data->shortname : $course->shortname;
        if ($source === 'catalogue') {
            // Bug-050: split the catalogue rows into three coursetype groups and render each in its own
            // section. Teaching backups keep the existing year-bucket layout; blueprint and other rows
            // render in collapsible <details> sections above/below the year buckets so they no longer
            // leak into a spurious "2026" year bucket (where their migration backup_ts had placed them).
            echo $OUTPUT->heading($list->header);
            $parts = \simple_restore_utils::partition_by_coursetype($list->backups);
            $rendered = false;

            // --- Blueprints section (above year buckets) -----------------------------------------
            if (!empty($parts['blueprint'])) {
                $bpopen = ($listfltcourse === 'blueprint');
                echo \simple_restore_utils::render_collapsible_section_open(
                    'sr-blueprints-' . $courseid,
                    get_string('blueprints_section_heading', 'block_simple_restore'),
                    get_string('blueprints_section_help', 'block_simple_restore'),
                    count($parts['blueprint']),
                    $bpopen
                );
                $bptable = new \block_simple_restore\local\table\restore_files_table(
                    'simple_restore_blueprints_' . $courseid,
                    $courseid,
                    (string) $shortname,
                    $restoreto,
                    $isadmin,
                    'flat'
                );
                $bptable->populate($parts['blueprint'], 'catalogue');
                echo html_writer::start_div('table-responsive');
                $bptable->setup_and_out(30);
                echo html_writer::end_div();
                echo '</details>';
                $rendered = true;
            }

            // --- Year buckets (teaching only) ----------------------------------------------------
            if (!empty($parts['teaching'])) {
                $buckets = [];
                foreach ($parts['teaching'] as $backup) {
                    $year = (string) ($backup->year ?? '');
                    if ($year === '') {
                        // Bug-048 guard: never derive 1969/1970 from a zero timestamp.
                        $ts = (int) ($backup->timemodified ?? 0);
                        $year = $ts > 0 ? (string) date('Y', $ts) : 'unknown';
                    }
                    $buckets[$year][] = $backup;
                }
                // Keep the "unknown" bucket at the bottom regardless of locale sort.
                $unknownbucket = $buckets['unknown'] ?? null;
                unset($buckets['unknown']);
                krsort($buckets, SORT_STRING);
                if ($unknownbucket !== null) {
                    $buckets['unknown'] = $unknownbucket;
                }
                foreach ($buckets as $year => $bucket) {
                    $heading = $year === 'unknown'
                        ? get_string('year_bucket_unknown', 'block_simple_restore')
                        : (string) $year;
                    echo $OUTPUT->heading($heading, 4);
                    $table = new \block_simple_restore\local\table\restore_files_table(
                        'simple_restore_semester_' . $courseid . '_y' . $year,
                        $courseid,
                        (string) $shortname,
                        $restoreto,
                        $isadmin,
                        'year'
                    );
                    $table->populate($bucket, 'catalogue');
                    echo html_writer::start_div('table-responsive');
                    $table->setup_and_out(30);
                    echo html_writer::end_div();
                }
                $rendered = true;
            }

            // --- Other backups section (below year buckets) --------------------------------------
            if (!empty($parts['other'])) {
                $otheropen = ($listfltcourse === 'other');
                echo \simple_restore_utils::render_collapsible_section_open(
                    'sr-other-' . $courseid,
                    get_string('other_section_heading', 'block_simple_restore'),
                    get_string('other_section_help', 'block_simple_restore'),
                    count($parts['other']),
                    $otheropen
                );
                $otable = new \block_simple_restore\local\table\restore_files_table(
                    'simple_restore_other_' . $courseid,
                    $courseid,
                    (string) $shortname,
                    $restoreto,
                    $isadmin,
                    'flat'
                );
                $otable->populate($parts['other'], 'catalogue');
                echo html_writer::start_div('table-responsive');
                $otable->setup_and_out(30);
                echo html_writer::end_div();
                echo '</details>';
                $rendered = true;
            }

            return $rendered;
        } else {
            echo $OUTPUT->heading($list->header);
            $table = new \block_simple_restore\local\table\restore_files_table(
                'simple_restore_semester_' . $courseid,
                $courseid,
                (string) $shortname,
                $restoreto,
                $isadmin
            );
            $table->populate($list->backups, $source);  // Pass source so catalogue rows get catalogue_id.
            echo html_writer::start_div('table-responsive');
            $table->setup_and_out(30);
            echo html_writer::end_div();
        }
        return true;
    }
    echo $list->html;
    return $in || !empty($list->backups);
};

$successful = array_reduce($data->lists, $displaylist, false);

if (!$successful) {
    echo $OUTPUT->notification(simple_restore_utils::_s('empty_backups'));
    echo $OUTPUT->continue_button(
        new moodle_url('/course/view.php', array('id' => $courseid))
    );
}

echo $OUTPUT->footer();
