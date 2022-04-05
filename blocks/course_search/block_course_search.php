<?php

//block for course search

class block_course_search extends block_base {
    
    function init() {
        $this->title = get_string('pluginname', 'block_course_search');
    }

    function get_content() {
        global $CFG, $OUTPUT;

        //if(!is_siteadmin()){
          //  return;
        //}
        
        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

        if (empty($this->instance)) {
            $this->content->text   = '';
            return $this->content;
        }

        $strsearch  = get_string('search');
        $strgo      = get_string('go');

        $this->content->text  = '<div class="searchform">';
        $this->content->text .= '<form action="'.$CFG->wwwroot.'/course/search.php" style="display:inline"><fieldset class="invisiblefieldset">';
        $this->content->text .= '<legend class="accesshide">'.$strsearch.'</legend>';
        $this->content->text .= '<input name="id" type="hidden" value="'.$this->page->course->id.'" />';  // course
        $this->content->text .= '<label class="accesshide" for="searchform_search">'.$strsearch.'</label>'.
                                '<input id="searchform_search" name="search" type="text" size="16" />';
        $this->content->text .= '&nbsp;&nbsp;<button class="btn btn-info" id="searchform_button" type="submit" title="'.$strsearch.'">'.$strgo.'</button><br />';
        // $this->content->text .= $OUTPUT->help_icon('search');
        $this->content->text .= '</fieldset></form></div>';

        return $this->content;
    }

    
    function applicable_formats() {
        return array('all' => true);
    }

    
}


