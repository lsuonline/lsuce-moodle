<?php

/*

    Grade Submit Step 2 - Check grades
    
    This file is meant to be included from index.php.

*/

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/querylib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/grader/lib.php';

require_once('grade_report_banner.php');



$courseid = required_param('id', PARAM_INT); // course id

/**
 * In addition to perform parent::insert(), calls force_regrading() method too.
 *
 * @param string $source From where was the object inserted (mod/forum, manual, etc.)
 * @return int PK ID if successful, false otherwise
 */
class column_insert extends grade_item {

    public function insert($source = null)
    {
        global $CFG, $DB;

        if (empty($this->courseid)) {
            print_error('cannotinsertgrade');
        }

        // load scale if needed
        $this->load_scale();

        // add parent category if needed
        if (empty($this->categoryid) and !$this->is_course_item() and !$this->is_category_item()) {
            $course_category = grade_category::fetch_course_category($this->courseid);
            $this->categoryid = $course_category->id;

        }

        // always place the new items at the end, move them after insert if needed
        $last_sortorder = $DB->get_field_select('grade_items', 'MAX(sortorder)', "courseid = ?", array($this->courseid));
        if (!empty($last_sortorder)) {
            $this->sortorder = $last_sortorder + 2;
        } else {
            $this->sortorder = 1;
        }

        // add proper item numbers to manual items
        if ($this->itemtype == 'manual') {
            if (empty($this->itemnumber)) {
                $this->itemnumber = 0;
            }
        }

        // make sure there is not 0 in outcomeid
        if (empty($this->outcomeid)) {
            $this->outcomeid = null;
        }

        $this->timecreated = $this->timemodified = time();

        if (parent::insert($source)) {
            // force regrading of items if needed
            $this->force_regrading();
            return $this->id;

        } else {
            debugging("Could not insert this grade_item in the database!");
            return false;
        }
    }
}

