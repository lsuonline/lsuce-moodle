<?php

class block_ues_meta_viewer extends block_list {
    function init() {
        $this->title= get_string('pluginname', 'block_ues_meta_viewer');
    }

    function applicable_formats() {
        return array('site' => true, 'my' => true, 'course' => false);
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $USER;

        $content = new stdClass;

        $content->items = array();
        $content->icons = array();
        $content->footer = '';

        require_once $CFG->dirroot . '/blocks/ues_meta_viewer/lib.php';

        $meta_types = ues_meta_viewer::supported_types();

        // Check capability
        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('block/ues_meta_viewer:access', $context)) {
            $base = '/blocks/ues_meta_viewer/viewer.php';

            foreach ($meta_types as $type => $support) {
                $url = new moodle_url($base, array('type' => $type));

                $str = get_string('viewer', 'block_ues_meta_viewer', $support->name());

                $content->items[] = html_writer::link($url, $str);
            }
        }

        $this->content = $content;

        return $this->content;
    }
}
