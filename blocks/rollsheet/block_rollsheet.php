<?php
class block_rollsheet extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_rollsheet');
    }

    function applicable_formats() {
        return array('site' => false, 'my' => false, 'course-view' => true);
    }

    function has_config() {
        return true;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $PAGE, $COURSE, $OUTPUT, $CFG;
        $context = context_course::instance($COURSE->id);
        $permission = (
            has_capability('block/rollsheet:viewblock', $context)
        );

        $blockHidden = get_config('block_rollsheet', 'hidefromstudents');
        $content = new stdClass;
        $content->items = array();
        $content->icons = array();
        $content->footer = '';
        $this->content = $content;
        $icon_class = array('class' => 'icon');
        $cid = optional_param('id', '', PARAM_INT);
        $sheetstr = get_string('genlist', 'block_rollsheet');
        $picstr = get_string('genpics', 'block_rollsheet');

        $sheeturl = new moodle_url('/blocks/rollsheet/genlist/show.php', array('cid' => $COURSE->id));
        $picurl = new moodle_url('/blocks/rollsheet/genpics/show.php', array('cid' => $COURSE->id));

        $membergroups = groups_get_user_groups($COURSE->id);
        $membergroups = $membergroups[0];
        if(count($membergroups) == 1) {
            $selectgroupsec = implode("", $membergroups);
            $sheeturl .= '&rendertype=group&selectgroupsec=' . $selectgroupsec;
            $picurl .= '&rendertype=group&selectgroupsec=' . $selectgroupsec;
        }

        if ($permission) {
            $content->items[] = html_writer::link($sheeturl, $sheetstr);
            $content->items[] = html_writer::link($picurl, $picstr);
            $content->icons[] = $OUTPUT->pix_icon('i/users', $sheetstr, 'moodle', $icon_class);
            $content->icons[] = $OUTPUT->pix_icon('i/users', $picstr, 'moodle', $icon_class);
        }
        return $this->content;
    }
}
