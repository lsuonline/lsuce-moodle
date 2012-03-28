<?php

class block_ues_people extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_ues_people');
    }

    function applicable_format() {
        return array('course' => true, 'site' => false, 'my' => false);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $PAGE, $COURSE, $OUTPUT, $CFG;

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $permission = (
            has_capability('moodle/site:accessallgroups', $context) or
            has_capability('block/ues_people:viewmeta', $context)
        );

        if (!$permission) {
            require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
            ues::require_daos();
            $sections = ues_section::from_course($COURSE);

            $permission = ues_user::is_teacher_in($sections);
        }

        $content = new stdClass;
        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        $this->content = $content;

        if ($permission) {
            $str = get_string('participants');
            $url = new moodle_url('/blocks/ues_people/index.php', array(
                'id' => $COURSE->id
            ));

            $this->content->items[] = html_writer::link($url, $str);
            $this->content->icons[] = $OUTPUT->pix_icon('i/users', $str);
        }

        return $this->content;
    }
}
