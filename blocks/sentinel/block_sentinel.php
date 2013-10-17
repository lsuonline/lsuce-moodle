<?php
global $CFG;

class block_sentinel extends block_base {

    public $field;
    
    
    public function has_config() {
        return true;
    }

    public function init() {

        $this->title = get_string('pluginname', 'block_sentinel');
    }

    public function applicable_formats() {
        return array('site' => true, 'my' => false, 'course' => true, 'mod' => true);
    }

    public function get_content() {
        global $COURSE, $USER, $CFG;
        if ($this->content !== null) {
            return $this->content;
        }
//        mtrace("Sentinel called");
        $this->content    = new stdClass();
        
        $public   = explode(',',get_config('block_sentinel','excluded_courses'));
        $excluded = in_array($COURSE->id,$public);

        $configClients = get_config('block_sentinel','clients');
        if(!empty($configClients)){
            $clients  = explode(',',get_config('block_sentinel','clients'));
            $result   = 0;

            foreach($clients as $c){
                $path = preg_split('/_/', $c);
                $fullPath = implode('/', $path);
                require_once($CFG->dirroot.'/'.$fullPath.'/sentinel.php');

                $test = 0;

                if(class_exists($c)){
                    $test = $c::allowUser($USER);
    //                mtrace(sprintf("test returned %s", (int)$test));
                }else{
                    throw new Exception(sprintf("Tried calling %s::allowUser(), but the class does not exist at %s", $c, $fullPath));
                }

                $result += (int)$test;
            }

            if($result == count($clients)){
                return $this->content;
            } elseif(!$excluded){
                header("Location: " . $CFG->wwwroot . "/blocks/sentinel/index.php");
            } else {

    //            throw new Exception("All tests did not pass AND the course in question is NOT in the excluded set. Unknown error");
            }
        }else{
            return $this->content;
        }
    }
}

?>
