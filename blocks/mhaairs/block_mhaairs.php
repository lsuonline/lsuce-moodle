<?php

require_once($CFG->dirroot.'/blocks/mhaairs/block_mhaairs_util.php');

class block_mhaairs extends block_base {

	function init() {
		$this->title = get_string('mhaairs', 'block_mhaairs');
	}
  
	function get_content() {
	
		global $CFG, $COURSE, $USER;

		if (empty($this->instance))
		{
			$this->content = '';
			return $this->content;
		}

		if (!$currentcontext = get_context_instance(CONTEXT_COURSE, $COURSE->id))
		{
			$this->content = '';
            		return $this->content;
        	}
	
		$this->content         =  new stdClass;
		$link_type = $this->get_link_type();

		if(empty($CFG->block_mhaairs_base_address)||empty($CFG->block_mhaairs_shared_secret) || empty($CFG->block_mhaairs_customer_number))
		{
			$ic = "; course is not configured ";
			if( !empty( $link_type ) ) { 
				$ic = "; link type:".$link_type;
			} else {
				$ic = "; no link type defiened;";
			}
			$this->content->text   = 'block is not configured'.$ic;
		}
		else
		{
			$mhcampus_text = "McGraw-Hill Campus";
			$type = $link_type;
			$customer = $CFG->block_mhaairs_customer_number;
			$shared_secret = $CFG->block_mhaairs_shared_secret;
			$base = $CFG->block_mhaairs_base_address;
			$token = mhaairs_create_token2($customer, $USER->username, $COURSE->idnumber, $type);
			$querystring = '?token='.mhaairs_encode_token2($token, $shared_secret);
			$this->content->text = '<div style="text-align:center;">'.
									'<a href="'.$base.$querystring.'" target="_blank">'.
									$this->get_text().
									'</a>'.
									'</div>';
		}
		$this->content->footer = '';//'mhaairs Footer';
	 
		return $this->content;
	}
	
	function instance_allow_multiple() {
		return true;
	}
	
	function has_config() {
		return true;
	}
	function hide_header() {
		return true;
	}
	
	function get_text() {
		global $CFG;
		$mhcampus_text = "McGraw-Hill Campus";
		$tegrity_text = "Tegrity Classes";
		$text = "no link";
		$type = $this->get_link_type();
		if(!empty($type)) {
			if($type == "tegrity") {
				$text = $tegrity_text; 
			} else {
				$text = $mhcampus_text; 
			}
		}
		if(empty($this->config) || empty($this->config->block_mhaairs_link_type)){
			$text = $text."";
		}
		return $text;
	}
	
	function get_logo() {
		global $CFG;
		$path = "";
		$type = $this->get_link_type();
		if(!empty($type)) {
			$logo = ""; 
			if($type == "tegrity") {
				$logo = "tegrity_logo.png"; 
			} else {
				$logo = "mhcampus_logo.png"; 
			}
			$path = "".$CFG->wwwroot."/blocks/mhaairs/".$logo;
		}
		return $path;
	}

	function get_link_type() {
		// return 'tegrity';
		global $CFG;
		$blocklevel_type = '';
		if(!empty($this->config)){
			$blocklevel_type = $this->config->block_mhaairs_link_type;
		}
		$default_type = $CFG->block_mhaairs_default_link_type ;
		$type = "";
		if(!empty($blocklevel_type)){
			if($blocklevel_type == "tegrity") {
			       $type = "tegrity"; 
			} else {
			       $type = "mhcampus"; 
			}
		} else { $type = $default_type; }
		return $type;
	}
	
	function specialization() {
	  // Just to make sure that this method exists.
	}
	
} 
?>
