<?php

class block_lsu_libraries_research_link extends block_base {

	public function init() {
		$this->title = get_string('lsu_libraries_research_link', 'block_lsu_libraries_research_link');
	}

	public function get_content() {
		global $CFG;

		if ($this->content !== null) {
			return $this->content;
		}

		$this->content =  new stdClass;
		$this->content->text = '<a href="http://www.lib.lsu.edu" target="_new"><img src="'. $CFG->wwwroot . '/blocks/lsu_libraries_research_link/pix/icon1.svg" width="76" height="76" alt="' . get_string('icon_alt', 'block_lsu_libraries_research_link') . '"></a><br><br><a href="http://www.lib.lsu.edu" target="_new">LSU Libraries Homepage</a><br><a href="http://search.ebscohost.com/login.aspx?authtype=ip,guest&custid=s8491974&groupid=main&profid=eds-main" target="_new">Discovery Search</a><br><a href="http://askus.lib.lsu.edu" target="_new">Research Support</a>';

		$this->content->footer = '';

		return $this->content;
	}
}
