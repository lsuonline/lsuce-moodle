<?php

interface post_grades_return {
    function is_ready();
}

interface post_grades_return_header {
    function get_explanation();
}

interface post_grades_return_process
    extends post_grades_return, post_grades_return_header {

    function process();

    function get_url($processed);
}

interface post_grades_return_graphable {
    function get_calc_info();

    function get_grading_info();
}

interface post_grades_compliance extends post_grades_return_header {
    function is_compliant();

    function is_required();
}

class post_grades_good_return implements post_grades_return {
    function is_ready() {
        return true;
    }
}

abstract class post_grades_mean_median implements post_grades_compliance {
    var $itemid;
    var $course;
    var $students;
    var $total;
    var $median;
    var $mean;

    function __construct($students, $course, $itemid = null) {
        $this->itemid = $itemid;
        $this->course = $course;
        $this->students = $this->get_graded_students($students);

        $this->total = count($this->students);
        $this->mean = $this->mean_value($this->students, $this->total);
        $this->median = $this->median_value($this->students, $this->total);
    }

    function value_for($setting) {
        return get_config('block_post_grades', $setting);
    }

    function is_incomplete($student, $course_item) {
        $grade = $course_item->get_grade($student->id, false);

        if (empty($grade->id)) {
            return false;
        }

        return $grade->is_overridden() and $grade->finalgrade == null;
    }

    function get_graded_students($students) {
        global $DB;

        $ci = grade_item::fetch_course_item($this->course->id);

        if (empty($this->itemid)) {
            $item = $ci;
        } else {
            $item = grade_item::fetch(array('id' => $this->itemid));
        }

        $anon = grade_anonymous::fetch(array('itemid' => $this->itemid));

        // Filter audits at the return level, for quick edits
        $audits = post_grades::pull_auditing_students($this->course);

        $rtn = array();
        foreach ($students as $stud) {
            if (isset($audits[$stud->id]) or $this->is_incomplete($stud, $ci)) {
                continue;
            }

            $user_params = array('userid' => $stud->id);
            if ($anon) {
                $params = $user_params + array('anonymous_itemid' => $anon->id);
                $finalgrade = $DB->get_field(
                    'grade_anon_grades', 'finalgrade', $params
                );
            } else {
                $params = $user_params + array('itemid' => $item->id);
                $finalgrade = $DB->get_field('grade_grades', 'finalgrade', $params);
            }

            $stud->finalgrade = $finalgrade ? $finalgrade : 0.0;
            $rtn[$stud->id] = $stud;
        }

        return $rtn;
    }

    function mean_value($students, $total) {
        $sum = array_reduce($students, function($in, $student) {
            return $in + $student->finalgrade;
        });

        return round($sum / $total, 1);
    }

    function median_value($students, $total) {
        uasort($students, function($a, $b) {
            if ($a->finalgrade == $b->finalgrade) return 0;
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

    function check($value, $lower, $upper) {
        return $value <= $upper && $value >= $lower;
    }
}

class post_grades_seminar_compliance extends post_grades_mean_median {
    var $value;
    var $lower;
    var $upper;
    var $required;

    function __construct($students, $course) {
        $this->value = $this->value_for('sem_median');

        $points = $this->value_for('sem_median_range');
        $this->lower = $this->value - $points;
        $this->upper = $this->value + $points;
        $this->required = $this->value_for('sem_required');

        parent::__construct($students, $course);
    }

    function is_compliant() {
        return empty($this->required) ?
            true : $this->check($this->median, $this->lower, $this->upper);
    }

    function is_required() {
        return $this->required;
    }

    function get_explanation() {
        return get_string('semexplain', 'block_post_grades', $this);
    }
}

class post_grades_class_size extends post_grades_mean_median
    implements post_grades_return_graphable {

    var $median_value;
    var $median_lower;
    var $median_upper;

    var $mean_value;
    var $mean_lower;
    var $mean_upper;

    var $size;
    var $required;
    var $grading;
    var $info;

    function __construct($students, $ues, $course, $itemid = null) {
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

        // Some sizes might not enforce mean
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

    function get_class_size($total) {
        $is_large = $total >= $this->value_for('number_students');
        $is_small = $total < $this->value_for('number_students_less');

        if (!empty($this->ues->course_first_year) or $is_large) {
            return "large";
        } else if ($is_small) {
            return "small";
        } else {
            return "mid";
        }
    }

    function pull_config() {
        $rtn = array();

        foreach (array('high_pass', 'pass', 'fail') as $area) {
            $a = new stdClass;
            $value = $this->value_for($area . '_value');
            $a->value = $value;
            $a->lower = $this->value_for($area . '_lower');
            $a->upper = $this->value_for($area . '_upper');
            $a->operator = $area == 'fail' ? '<=' : '>=';
            $a->comparision = $area == 'fail' ?
                function($v) use ($value) { return $v->finalgrade <= $value; } :
                function($v) use ($value) { return $v->finalgrade >= $value; };
            $rtn[$area] = $a;
        }

        return $rtn;
    }

    function pull_info() {
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

    function is_compliant() {
        $is_compliant = true;

        if ($this->size == 'large') {
            foreach ($this->info as $area => $info) {
                $is_compliant = (
                    $is_compliant and
                    $this->check($info->total, $info->lower, $info->upper)
                );
            }
        }

        if (empty($this->required)) {
            return true;
        }

        return (
            $is_compliant and
            $this->mean_compliance and
            $this->median_compliance
        );
    }

    function get_calc_info() {
        return $this->info;
    }

    function get_grading_info() {
        return $this->grading;
    }

    function is_required() {
        return $this->required;
    }

    function get_explanation() {
        $this->description = get_string(
            $this->size . '_courses', 'block_post_grades'
        );
        return get_string('sizeexplain', 'block_post_grades', $this);
    }
}

class post_grades_passthrough implements post_grades_compliance {
    function __construct($course) {
        $this->course = $course;
    }

    function is_compliant() {
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

    function is_required() {
        return false;
    }

    function get_explanation() {
        return '';
    }
}

abstract class post_grades_delegating_return implements post_grades_return_process {
    function __construct($base_return) {
        $this->base_return = $base_return;
    }

    function get_explanation() {
        return $this->base_return->get_explanation();
    }

    function get_url($processed) {
        if (empty($processed)) {
            $processed = $this->base_return->process();
        }
        return $this->base_return->get_url($processed);
    }

}

// A JD compliance return wraps a concrete return
class post_grades_compliance_return extends post_grades_delegating_return {
    function __construct($base_return, $students, $ues_course, $itemid = null) {
        parent::__construct($base_return);

        $passthrough = new post_grades_passthrough($ues_course);

        // Determine compliance return
        if ($passthrough->is_compliant()) {
            $this->compliance = $passthrough;
        } else if ($ues_course->course_type == 'SEM') {
            $this->compliance = new post_grades_seminar_compliance(
                $students, $base_return->course
            );
        } else {
            $this->compliance = new post_grades_class_size(
                $students, $ues_course, $base_return->course, $itemid
            );
        }
    }

    function is_ready() {
        return $this->base_return->is_ready() and $this->compliance->is_compliant();
    }

    function process() {
        // Delegate to base return
        if (!$this->base_return->is_ready()) {
            return $this->base_return->process();
        }

        // Prototyping
        return $this->compliance;
    }
}

class post_grades_sequence_compliance extends post_grades_delegating_return {
    function __construct($base, $compliances, $titles = array()) {
        parent::__construct($base);

        $this->compliances = $compliances;
        $this->titles = $titles;
    }

    function is_ready() {
        return array_reduce($this->compliances, function($in, $compliance) {
            return $in || $compliance->is_compliant();
        });
    }

    function process() {
        return array_combine($this->titles, $this->compliances);
    }
}

class post_grades_no_item_return implements post_grades_return_process {
    function __construct($course) {
        global $DB;

        $this->course = $course;

        $filters = ues::where()
            ->courseid->equal($this->course->id)
            ->itemtype->in('manual', 'mod');

        $this->items = $DB->count_records_select('grade_items', $filters->sql());
    }

    function get_explanation() {
        return get_string('noitems', 'block_post_grades');
    }

    function is_ready() {
        return !empty($this->items);
    }

    function get_url($processed) {
        // Instructor has their own gradebook, just route them accordingly
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

    function default_params() {
        global $DB;

        $params = array(
            'courseid' => $this->course->id,
            'fullname' => '?',
            'parent' => null
        );

        $parent_cat = $DB->get_field('grade_categories', 'id', $params);

        $params = array(
            'courseid' => $this->course->id,
            'grademin' => 1.3,
            'grademax' => 4.0,
            'gradepass' => 1.5,
            'display' => 1,
            'itemtype' => 'manual',
            'decimals' => 1,
            'itemname' => get_string('finalgrade_item', 'block_post_grades'),
            'categoryid' => $parent_cat
        );

        //Need for later
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
                'categoryid' => $parent_cat
            );
        }
        return $params;
    }

    function process() {
        grade_regrade_final_grades($this->course->id);

        $params = $this->default_params();

        // No need to recreate; fetch generated or send to gradebook
        if ($this->items) {
            if ($item = grade_item::fetch($params)) {
                return $item;
            } else {
                return false;
            }
        }

        $course_cat = grade_category::fetch_course_category($this->course->id);

        $course_item = grade_item::fetch_course_item($this->course->id);
        $course_item->gradepass = 1.5;
        $course_item->grademin = 1.3;
        $course_item->grademax = 4.0;
        $course_item->decimals = 1;
        $course_item->display = 1;

        $params['aggregationcoef'] =
            $course_cat->aggregation == GRADE_AGGREGATE_WEIGHTED_MEAN ? 1 : 0;

        $course_item->update();

        $new_item = new grade_item($params);
        $new_item->insert();

        return $new_item;
    }
}

class post_grades_no_anonymous_item_return extends post_grades_no_item_return {
    function __construct($course) {
        global $DB;

        $this->course = $course;

        $items = grade_item::fetch_all(array('courseid' => $this->course->id));

        $filters = ues::where()->itemid->in(array_keys($items));

        $this->items = $DB->get_records_select('grade_anon_items', $filters->sql());
    }

    function get_url($processed) {
        return new moodle_url('/grade/report/quick_edit/index.php', array(
            'id' => $this->course->id,
            'item' => 'anonymous',
            'group' => required_param('group', PARAM_INT),
            'itemid' => $processed->itemid
        ));
    }

    function is_ready() {
        return !empty($this->items) and reset($this->items)->complete;
    }

    function get_explanation() {
        return get_string('noanonitem', 'block_post_grades');
    }

    function process() {
        if ($this->items) {
            $db_item = reset($this->items);

            return grade_anonymous::fetch(array('id' => $db_item->id));
        }

        $new_item = parent::process();

        $new_item->itemname = get_string('finalgrade_anon', 'block_post_grades');
        $new_item->update();

        $params = array(
            'itemid' => $new_item->id,
            'complete' => false
        );

        $anon_item = new grade_anonymous($params);
        $anon_item->insert();

        return $anon_item;
    }
}

