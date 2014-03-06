<?php
class block_signinsheet extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_signinsheet');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $PAGE, $COURSE, $OUTPUT, $CFG;
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        $permission = (
            has_capability('block/signinsheet:viewblock', $context)
        );

        $blockHidden = get_config('block_signinsheet', 'hidefromstudents');
        $content = new stdClass;
        $content->items = array();
        $content->icons = array();
        $content->footer = '';
        $this->content = $content;
        $icon_class = array('class' => 'icon');
        $cid = optional_param('id', '', PARAM_INT);
        $sheetstr = get_string('genlist', 'block_signinsheet');
        $picstr = get_string('genpics', 'block_signinsheet');
        $sheeturl = new moodle_url('/blocks/signinsheet/genlist/show.php', array('cid' => $COURSE->id));
        $picurl = new moodle_url('/blocks/signinsheet/genpics/show.php', array('cid' => $COURSE->id));
        if ($permission) {
            $content->items[] = html_writer::link($sheeturl, $sheetstr);
            $content->items[] = html_writer::link($picurl, $picstr);
            $content->icons[] = $OUTPUT->pix_icon('i/users', $sheetstr, 'moodle', $icon_class);
            $content->icons[] = $OUTPUT->pix_icon('i/users', $picstr, 'moodle', $icon_class);
        }
        return $this->content;
    }
}
