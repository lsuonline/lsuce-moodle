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

defined('MOODLE_INTERNAL') || die();

interface post_grades_return {
    public function is_ready();
}

interface post_grades_return_header {
    public function get_explanation();
}

interface post_grades_return_process
    extends post_grades_return, post_grades_return_header {

    public function process();

    public function get_url($processed);
}

interface post_grades_return_graphable {
    public function get_calc_info();

    public function get_grading_info();
}

interface post_grades_compliance extends post_grades_return_header {
    public function is_compliant();

    public function is_required();
}

class post_grades_good_return implements post_grades_return {
    public function is_ready() {
        return true;
    }
}

abstract class post_grades_mean_median implements post_grades_compliance {
    public $itemid;
    public $course;
    public $students;
    public $total;
    public $median;
    public $mean;

    public function __construct($students, $course, $itemid = null) {
        $this->itemid = $itemid;
        $this->course = $course;
        $this->students = $this->get_graded_students($students);

        $this->total = count($this->students);
        $this->mean = $this->mean_value($this->students, $this->total);
        $this->median = $this->median_value($this->students, $this->total);
    }

    public function value_for($setting) {
        return get_config('block_post_grades', $setting);
    }

    public function is_incomplete($student, $courseitem) {
        $grade = $courseitem->get_grade($student->id, false);

        if (empty($grade->id)) {
            return false;
        }

        return $grade->is_overridden() and $grade->finalgrade == null;
    }

    public function get_graded_students($students) {
        global $DB;

        $ci = grade_item::fetch_course_item($this->course->id);

        if (empty($this->itemid)) {
            $item = $ci;
        } else {
            $item = grade_item::fetch(array('id' => $this->itemid));
        }

        $anon = grade_anonymous::fetch(array('itemid' => $this->itemid));

        // Filter audits at the return level, for quick edits.
        $audits = post_grades::pull_auditing_students($this->course);

        $rtn = array();
        foreach ($students as $stud) {
            if (isset($audits[$stud->id]) or $this->is_incomplete($stud, $ci)) {
                continue;
            }

            $userparams = array('userid' => $stud->id);
            if ($anon) {
                $params = $userparams + array('anonymous_itemid' => $anon->id);
                $finalgrade = $DB->get_field(
                    'grade_anon_grades', 'finalgrade', $params
                );
            } else {
                $params = $userparams + array('itemid' => $item->id);
                $finalgrade = $DB->get_field('grade_grades', 'finalgrade', $params);
            }

            $stud->finalgrade = $finalgrade ? $finalgrade : 0.0;
            $rtn[$stud->id] = $stud;
        }

        return $rtn;
    }

    public function mean_value($students, $total) {
        $sum = array_reduce($students, function($in, $student) {
            return $in + $student->finalgrade;
        });

        return round($sum / $total, 1);
    }

    public function median_value($students, $total) {
        uasort($students, function($a, $b) {
            if ($a->finalgrade == $b->finalgrade) {
                return 0;
            }
            return $a->finalgrade < $b->finalgrade ? 1 : -1;
        });

        if ($total % 2 != 0) {
            $median = current(array_slice($students, $total / 2));
            return round($median->finalgrade, 1);
        } else {
            $median = array_slice($students, ($total / 2) - 1, 2);
            $sum = current($median)->finalgrade + next($median)->finalgrade;
            return round($sum / 2, 1);
        }
    }

    public function check($value, $lower, $upper) {
        return $value <= $upper && $value >= $lower;
    }
}

class post_grades_seminar_compliance extends post_grades_mean_median {
    public $value;
    public $lower;
    public $upper;
    public $required;

    public function __construct($students, $course) {
        $this->value = $this->value_for('sem_median');

        $points = $this->value_for('sem_median_range');
        $this->lower = $this->value - $points;
        $this->upper = $this->value + $points;
        $this->required = $this->value_for('sem_required');

        parent::__construct($students, $course);
    }

    public function is_compliant() {
        return empty($this->required) ?
            true : $this->check($this->median, $this->lower, $this->upper);
    }

    public function is_required() {
        return $this->required;
    }

    public function get_explanation() {
        return get_string('semexplain', 'block_post_grades', $this);
    }
}

class post_grades_class_size extends post_grades_mean_median
    implements post_grades_return_graphable {

    public $median_value;
    public $median_lower;
    public $median_upper;
    public $mean_value;
    public $mean_lower;
    public $mean_upper;
    public $size;
    public $required;
    public $grading;
    public $info;

    public function __construct($students, $ues, $course, $itemid = null) {
        $this->ues = $ues;

        parent::__construct($students, $course, $itemid);

        $this->size = $this->get_class_size($this->total);
        $this->required = $this->value_for($this->size . '_required');
        $this->grading = $this->pull_config();

        $this->info = $this->pull_info();

        $this->median_value = $this->value_for($this->size . '_median');
        $range = $this->value_for($this->size . '_median_range');
        $this->median_lower = $this->median_value - $range;
        $this->median_upper = $this->median_value + $range;

        // Some sizes might not enforce mean.
        $this->mean_value = $this->value_for($this->size . '_mean');
        if (empty($this->mean_value)) {
            $this->mean_value = $this->median_value;
        } else {
            $range = $this->value_for($this->size . '_mean_range');
        }
        $this->mean_lower = $this->mean_value - $range;
        $this->mean_upper = $this->mean_value + $range;

        $this->mean_compliance = $this->check(
            $this->mean, $this->mean_lower, $this->mean_upper
        );

        $this->median_compliance = $this->check(
            $this->median, $this->median_lower, $this->median_upper
        );
    }

    public function get_class_size($total) {
        $islarge = $total >= $this->value_for('number_students');
        $issmall = $total < $this->value_for('number_students_less');

        if (!empty($this->ues->course_first_year) or $islarge) {
            return "large";
        } else if ($issmall) {
            return "small";
        } else {
            return "mid";
        }
    }

    public function pull_config() {
        $rtn = array();

        foreach (array('high_pass', 'pass', 'fail') as $area) {
            $a = new stdClass;
            $value = $this->value_for($area . '_value');
            $a->value = $value;
            $a->lower = $this->value_for($area . '_lower');
            $a->upper = $this->value_for($area . '_upper');
            $a->operator = $area == 'fail' ? '<=' : '>=';
            $a->comparision = $area == 'fail' ?
                function($v) use ($value) {
                    return $v->finalgrade <= $value;
                } :
                function($v) use ($value) {
                    return $v->finalgrade >= $value;
                };
            $rtn[$area] = $a;
        }

        return $rtn;
    }

    public function pull_info() {
        $info = array();
        foreach ($this->grading as $area => $spec) {
            $a = new stdClass;
            $a->users = array_filter($this->students, $spec->comparision);
            $a->lower = $this->total * ($spec->lower / 100);
            $a->upper = $this->total * ($spec->upper / 100);
            $a->total = count($a->users);
            $a->percent = round(($a->total / $this->total) * 100, 2);

            $info[$area] = $a;
        }

        return $info;
    }

    public function is_compliant() {
        $iscompliant = true;

        if ($this->size == 'large') {
            foreach ($this->info as $area => $info) {
                $iscompliant = (
                    $iscompliant and
                    $this->check($info->total, $info->lower, $info->upper)
                );
            }
        }

        if (empty($this->required)) {
            return true;
        }

        return (
            $iscompliant and
            $this->mean_compliance and
            $this->median_compliance
        );
    }

    public function get_calc_info() {
        return $this->info;
    }

    public function get_grading_info() {
        return $this->grading;
    }

    public function is_required() {
        return $this->required;
    }

    public function get_explanation() {
        $this->description = get_string(
            $this->size . '_courses', 'block_post_grades'
        );
        return get_string('sizeexplain', 'block_post_grades', $this);
    }
}

class post_grades_passthrough implements post_grades_compliance {
    public function __construct($course) {
        $this->course = $course;
    }

    public function is_compliant() {
        return (
            (
                $this->course->course_type == 'CLI' or
                $this->course->course_type == 'IND'
            ) or
            $this->course->course_grade_type == 'LP' or
            !$this->course->course_first_year and
            $this->course->course_exception
        );
    }

    public function is_required() {
        return false;
    }

    public function get_explanation() {
        return '';
    }
}

abstract class post_grades_delegating_return implements post_grades_return_process {
    public function __construct($base_return) {
        $this->base_return = $base_return;
    }

    public function get_explanation() {
        return $this->base_return->get_explanation();
    }

    public function get_url($processed) {
        if (empty($processed)) {
            $processed = $this->base_return->process();
        }
        return $this->base_return->get_url($processed);
    }

}

// A JD compliance return wraps a concrete return.
class post_grades_compliance_return extends post_grades_delegating_return {
    public function __construct($base_return, $students, $uescourse, $itemid = null) {
        parent::__construct($base_return);

        $passthrough = new post_grades_passthrough($uescourse);

        // Determine compliance return.
        if ($passthrough->is_compliant()) {
            $this->compliance = $passthrough;
        } else if ($uescourse->course_type == 'SEM') {
            $this->compliance = new post_grades_seminar_compliance(
                $students, $base_return->course
            );
        } else {
            $this->compliance = new post_grades_class_size(
                $students, $uescourse, $base_return->course, $itemid
            );
        }
    }

    public function is_ready() {
        return $this->base_return->is_ready() and $this->compliance->is_compliant();
    }

    public function process() {
        // Delegate to base return.
        if (!$this->base_return->is_ready()) {
            return $this->base_return->process();
        }

        // Prototyping.
        return $this->compliance;
    }
}

class post_grades_sequence_compliance extends post_grades_delegating_return {
    public function __construct($base, $compliances, $titles = array()) {
        parent::__construct($base);

        $this->compliances = $compliances;
        $this->titles = $titles;
    }

    public function is_ready() {
        return array_reduce($this->compliances, function($in, $compliance) {
            return $in || $compliance->is_compliant();
        });
    }

    public function process() {
        return array_combine($this->titles, $this->compliances);
    }
}

class post_grades_no_item_return implements post_grades_return_process {
    public function __construct($course) {
        global $DB;

        $this->course = $course;

        $filters = ues::where()
            ->courseid->equal($this->course->id)
            ->itemtype->in('manual', 'mod');

        $this->items = $DB->count_records_select('grade_items', $filters->sql());
    }

    public function get_explanation() {
        return get_string('noitems', 'block_post_grades');
    }

    public function is_ready() {
        return !empty($this->items);
    }

    public function get_url($processed) {
        // Instructor has their own gradebook, just route them accordingly.
        if (empty($processed)) {
            return new moodle_url('/grade/report/grader/index.php', array(
                'id' => $this->course->id
            ));
        } else {
            return new moodle_url('/grade/report/singleview/index.php', array(
                'id' => $this->course->id,
                'itemid' => $processed->id,
                'item' => 'grade'
            ));
        }
    }

    public function default_params() {
        global $DB;

        $params = array(
            'courseid' => $this->course->id,
            'fullname' => '?',
            'parent' => null
        );

        $parentcat = $DB->get_field('grade_categories', 'id', $params);

        $params = array(
            'courseid' => $this->course->id,
            'grademin' => 1.3,
            'grademax' => 4.0,
            'gradepass' => 1.5,
            'display' => 1,
            'itemtype' => 'manual',
            'decimals' => 1,
            'itemname' => get_string('finalgrade_item', 'block_post_grades'),
            'categoryid' => $parentcat
        );

        // Need for later.
        $groupid = required_param('group', PARAM_INT);
        $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
        $sections = ues_section::from_course($this->course, true);
        $section = post_grades::find_section($group, $sections);
        $course = $section->course()->fill_meta();
        if ($course->course_grade_type == 'LP') {
            $scale = get_config('block_post_grades', 'scale');
            $params = array(
                'courseid' => $this->course->id,
                'scaleid' => $scale,
                'gradepass' => 2.0,
                'gradetype' => 2,
                'decimals' => 1,
                'itemtype' => 'manual',
                'itemname' => get_string('finalgrade_item', 'block_post_grades'),
                'categoryid' => $parentcat
            );
        }
        return $params;
    }

    public function process() {
        grade_regrade_final_grades($this->course->id);

        $params = $this->default_params();

        // No need to recreate; fetch generated or send to gradebook.
        if ($this->items) {
            if ($item = grade_item::fetch($params)) {
                return $item;
            } else {
                return false;
            }
        }

        $coursecat = grade_category::fetch_course_category($this->course->id);

        $courseitem = grade_item::fetch_course_item($this->course->id);
        $courseitem->gradepass = 1.5;
        $courseitem->grademin = 1.3;
        $courseitem->grademax = 4.0;
        $courseitem->decimals = 1;
        $courseitem->display = 1;

        $params['aggregationcoef'] =
            $coursecat->aggregation == GRADE_AGGREGATE_WEIGHTED_MEAN ? 1 : 0;

        $courseitem->update();

        $newitem = new grade_item($params);
        $newitem->insert();

        return $newitem;
    }
}

class post_grades_no_anonymous_item_return extends post_grades_no_item_return {
    public function __construct($course) {
        global $DB;

        $this->course = $course;

        $items = grade_item::fetch_all(array('courseid' => $this->course->id));

        $filters = ues::where()->itemid->in(array_keys($items));

        $this->items = $DB->get_records_select('grade_anon_items', $filters->sql());
    }

    public function get_url($processed) {
        return new moodle_url('/grade/report/quick_edit/index.php', array(
            'id' => $this->course->id,
            'item' => 'anonymous',
            'group' => required_param('group', PARAM_INT),
            'itemid' => $processed->itemid
        ));
    }

    public function is_ready() {
        return !empty($this->items) and reset($this->items)->complete;
    }

    public function get_explanation() {
        return get_string('noanonitem', 'block_post_grades');
    }

    public function process() {
        if ($this->items) {
            $dbitem = reset($this->items);

            return grade_anonymous::fetch(array('id' => $dbitem->id));
        }

        $newitem = parent::process();

        $newitem->itemname = get_string('finalgrade_anon', 'block_post_grades');
        $newitem->update();

        $params = array(
            'itemid' => $newitem->id,
            'complete' => false
        );

        $anonitem = new grade_anonymous($params);
        $anonitem->insert();

        return $anonitem;
    }
}