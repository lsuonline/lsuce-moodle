<?php
/*

	Custom date control for Settings pages in Moodle.
	2011 Jeff King / Future Shock Software
	
	TODO: YUI Calendar control support

*/

class admin_setting_configdate extends admin_setting {
    public $name2;
    public $name3;

    /**
     * Constructor
     * @param string $labelname
     * @param string $monthname setting for month
     * @param string $dayname label for the field
     * @param string $yearname setting for year
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param array $defaultsetting array representing default time 'h'=>hours, 'm'=>minutes
     */

/*

    public function __construct($name, $visiblename, $description, $defaultsetting, $lowercase=false) {
        $this->lowercase = $lowercase;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

*/

/*
public    function admin_setting_configdate($labelname, $monthname, $dayname, $yearname, $visiblename, $description, $defaultsetting) {
        $this->name2 = $dayname;
        $this->name3 = $yearname;
        parent::admin_setting($monthname, $visiblename, $description, $defaultsetting);
    }
  */
    
  public  function get_setting() {

       $result = $this->config_read($this->name);
       if (is_null($result)) {
            return NULL;
        }
       return $result;
    }

 public   function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $result = $this->config_write($this->name,$data['y']."/".$data['m']."/".$data['d']." ".$data['h'].":".$data['i']);
        return ($result ? '' : get_string('errorsetting', 'admin'));
    }

 public   function output_html($data, $query='') {
        $default = $this->get_defaultsetting();

        if (is_array($default)) {
            $defaultinfo = $default['y'].'/'.$default['m'].'/'.$default['d']." ".$data['h'].":".$data['i'];
        } else {
            $defaultinfo = NULL;
        }
              	if (!$data){
			$data =  date('Y/n/j H:i');
                }
                list($date,$time) = explode(' ', $data); 
                list($year,$month, $day) = explode('/', $date); 
                list($hour,$minute) = explode(':', $time); 

		//<select id="'.$this->get_id().'h" name="'.$this->get_full_name().'[h]">'
		
		$return = '<fieldset class="felement fdate_selector">
		<label class="accesshide" for="'.$this->get_id().'d">Day</label>
		<select name="'.$this->get_full_name().'[d]" id="'.$this->get_id().'d">';
		
		for ($i = 1; $i < 32; $i++) {
		            $return .= '<option value="'.$i.'"'.($i == $day ? ' selected="selected"' : '').'>'.$i.'</option>';
		        }
		$months=array('January','February','March','April','May','June','July','August','September','October','November','December');
		
		$return .='
		</select>&nbsp;<label class="accesshide" for="'.$this->get_id().'m">Month</label>
		<select name="'.$this->get_full_name().'[m]" id="'.$this->get_id().'m">';
		
		for ($i = 0; $i < 12; $i++) {
		            $return .= '<option value="'.($i+1).'"'.(($i+1) == $month ? ' selected="selected"' : '').'>'.$months[$i].'</option>';
		        }
		
		
		$return.='
		</select>&nbsp;<label class="accesshide" for="'.$this->get_id().'d">Year</label>
		<select name="'.$this->get_full_name().'[y]" id="'.$this->get_id().'y">';
		
		
		for ($i = 2012; $i < 2050; $i++) {
		            $return .= '<option value="'.$i.'"'.($i == $year ? ' selected="selected"' : '').'>'.$i.'</option>';
		        }
		
                $return.='</select><label class="accesshide" for="'.$this->get_id().'h">Hour</label>
		<select name="'.$this->get_full_name().'[h]" id="'.$this->get_id().'h">';
		
		for ($i = 0; $i < 24; $i++) {
                    $temp = str_pad($i, 2, "0", STR_PAD_LEFT);
		            $return .= '<option value="'.$temp.'"'.($temp == $hour ? ' selected="selected"' : '').'>'.$temp.'</option>';
		        }
                $return.='</select><label class="accesshide" for="'.$this->get_id().'i">Minute</label>
		<select name="'.$this->get_full_name().'[i]" id="'.$this->get_id().'i">';
		$minTemp = array("00","30","59");
		for ($i = 0; $i < count($minTemp); $i++) {
		            $return .= '<option value="'.$minTemp[$i].'"'.($minTemp[$i] == $minute ? ' selected="selected"' : '').'>'.$minTemp[$i].'</option>';
		        }
		$return .='
		</select></fieldset>';

        return format_admin_setting($this, $this->visiblename, $return, $this->description, false, '', $defaultinfo, $query);
    }

}
