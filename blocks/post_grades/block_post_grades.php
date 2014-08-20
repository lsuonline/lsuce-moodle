<?php

class block_post_grades extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_post_grades');
    }

    function applicable_formats() {
        return array('site' => false, 'my' => false, 'course' => true);
    }

    /**
     * @return bool true if this block is configurable
     */
    function has_config() {
        return true;
    }

    function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $DB, $OUTPUT, $COURSE, $CFG;

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $context = context_course::instance($COURSE->id);

        if (!has_capability('block/post_grades:canpost', $context)) {
            return $this->content;
        }

        require_once $CFG->dirroot . '/blocks/post_grades/lib.php';

        $sections = ues_section::from_course($COURSE, true);

        $periods = post_grades::active_periods_for_sections($sections);

        if (empty($periods)) {
            return $this->content;
        }

        $params = array('courseid' => $COURSE->id);

        $_s = ues::gen_str('block_post_grades');

        $not_posted_icon = $OUTPUT->pix_icon('i/completion-manual-n',
            $_s('not_posted'), 'moodle', array('class' => 'icon'));

        $posted_icon = $OUTPUT->pix_icon('i/completion-manual-enabled',
            $_s('posted'), 'moodle', array('class' => 'icon'));

        foreach ($periods as $period) {
            $found = false;

            $bold = html_writer::tag('strong', $_s($period->post_type));
            $screenclass = post_grades::screenclass($period->post_type);

            $filterable = method_exists($screenclass, 'can_post');

            $params['period'] = $period->id;
            foreach ($sections as $sec) {
                $sc = new $screenclass($period,$COURSE,$sec->group());
                if ($filterable and !$sc->can_post($sec)) {
                    continue;
                }

                // Hide label if none present
                if (empty($found)) {
                    $found = true;

                    $this->content->items[] = $bold;
                    $this->content->icons[] = '';
                }

                $group = $sec->group();
                $params['group'] = $group->id;

                $url = new moodle_url('/blocks/post_grades/confirm.php', $params);
                $link = html_writer::link($url, $group->name);

                $this->content->items[] = $link;
                $this->content->icons[] =
                    post_grades::already_posted_section($sec, $period) ?
                    $posted_icon : $not_posted_icon;
            }
        }

        return $this->content;
    }
}
