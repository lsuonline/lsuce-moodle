<?php
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

class JMeterAjax
{
    private $ulethlib = null;
    private $range = null;

    public function __construct()
    {
        include_once('UtoolsLib.php');
        $this->ulethlib = new UtoolsLib();
        $this->range = '30 seconds';
    }

    /**
     * Description - function to sort a multi dimensional associative array. This
     * is called by php's usort function so we don't really literally call it.
     * @param type string - string to compare with b
     * @param type string - string to compare with a
     * @return something sorted
     */
    // static function jmCompare($a, $b)
    private function jmCompareTs($a, $b)
    {
        return strcmp($a["attributes"]["ts"], $b["attributes"]["ts"]);
    }

    private function jmCompareStack($a, $b)
    {
        return strcmp($a->server->last_reported_at, $b->server->last_reported_at);
    }

    // static function jmCompare($a, $b)
    private function jmCompareEc($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return ($a > $b) ? -1 : 1;
    }
    
    /**
    * Description - take the data and chunk the data into time ranges.
    *
    * @param struct - all the jmeter data
    * @param string - the time range
    * @return struct - array of data that's in time ranges
    */
    private function breakIntoTimeRange($jmeter_obj)
    {
        $this->ulethlib->printToLog("\n JMeterAjax -> breakIntoTimeRange() -> START", "jmeter");
        
        $arrayKeys = array_keys($jmeter_obj);
        // the first element of your array is:
        $starting_time = intval(intval($jmeter_obj[$arrayKeys[0]]['attributes']['ts']) / 1000);
        $ending_time = strtotime("+".$this->range, $starting_time);
        // both times should be int now

        // one array to rule them all
        $ranged_array = array();
        
        // array to hold objects within 30 seconds
        $this_range = array();

        // what is the size of the jm_obj array?
        $jmeter_obj_size = count($jmeter_obj);
        $jmeter_obj_counter = 0;

        // what is the last timestamp recorded? if we go over that we must abort loop
        $fail_safe = intval(intval($jmeter_obj[$arrayKeys[$jmeter_obj_size - 1]]['attributes']['ts']) / 1000);
        
        while ($jmeter_obj_counter < $jmeter_obj_size) {
            $current_obj = intval(intval($jmeter_obj[$arrayKeys[$jmeter_obj_counter]]['attributes']['ts']) / 1000);
            if ($current_obj >= $starting_time && $current_obj < $ending_time) {
                $this_range[] = $jmeter_obj[$arrayKeys[$jmeter_obj_counter]];
                $jmeter_obj_counter++;
            } else {
                $starting_time = $ending_time;
                $ending_time = strtotime("+".$this->range, $starting_time);
                $ranged_array[] = $this_range;
                unset($this_range);
                $this_range = array();
            }

            if ($starting_time > $fail_safe) {
                $this->ulethlib->printToLog("\n JMeterAjax -> breakIntoTimeRange() -> We have surpassed the last timestamp, must break", "jmeter");
                break;
            }
        }
        // error_log("\n JMeterAjax -> breakIntoTimeRange() -> What is the array
        // size of the time range chunk ".count($ranged_array));
        // foreach($ranged_array as $this_plop) {
        //     $temp_tt = intval(intval($this_plop[0]['attributes']['ts']) / 1000);
        //     error_log(" \n ". date('h:i:s', $temp_tt));
            // error_log(print_r($this_plop, true));
        // }
        $this->ulethlib->printToLog("\n JMeterAjax -> breakIntoTimeRange() -> RETURNING", "jmeter");
        return $ranged_array;
    }

    /**
     * Description - Compile the stack reporting into a final result.
     *
     * @param struct - an array of classes for each app server.
     * @return struct - compiled results of the stack reports.
     */
    private function buildStackReports($stack_report_objs, $combine = false) // , time_set_length)
    {
        $this->ulethlib->printToLog("\n JMeterAjax -> buildStackReports() -> START", "jmeter");

        $cpu = array();
        $memory = array();
        $time = array();
        $index_counter = 0;
        $index_total = count($stack_report_objs);
        // if ($time_set_length < $index_total) {
        //     $index_total = $time_set_length;
        // }
        
        // in case there's more than one app server
        if ($combine) {
            $num_of_reports = count($stack_report_objs);
            $index_total = count($stack_report_objs[0]);
            for ($i = 0; $i < $index_total; $i++) {
                $cpu = 0;
                $memory = 0;
                $time = 0;

                for ($j = 0; $j < $num_of_reports; $j++) {
                    $cpu += $stack_report_objs[$j][$i]->server->summary->cpu;
                    $memory += $stack_report_objs[$j][$i]->server->summary->memory;
                }
                $cpu_a[] = $cpu / $num_of_reports;
                $memory_a[] = $memory / $num_of_reports;
                // grab the first report's time as they will all slightly be off
                // from each other but they should be very close to each by index
                $time_a[] = $stack_report_objs[0][$i]->server->last_reported_at;

            }
        } else {
            for ($i = 0; $i < $index_total; $i++) {
                $cpu_a[] = $stack_report_objs[$i]->server->summary->cpu;
                $memory_a[] = $stack_report_objs[$i]->server->summary->memory;
                $time_a[] = $stack_report_objs[$i]->server->last_reported_at;
            }
        }
        $this->ulethlib->printToLog("\n JMeterAjax -> buildStackReports() -> RETURNING", "jmeter");

        return array("cpu" => $cpu_a, "memory" => $memory_a, "time" => $time_a);
    }

    /**
    * Description - Compile all the chunked data into a final result for that particular time.
    *
    * @param struct - the time ranged chunked data
    * @return struct - compiled results of the chunked data
    */
    private function buildStats($jmeter_obj)
    {
        $this->ulethlib->printToLog("\n JMeterAjax -> buildStats() -> START", "jmeter");

        $results = array();
        $finished_data_set = array();

        $time_frame_set = array();
        $data_transfer_set = array();
        $error_count_set = array();
        $total_threads_set = array();
        $avg_latency_set = array();
        $error_list_set = array();
        $hostnames = array();
        $error_list = array();
        $time_frame_last = null;

        foreach ($jmeter_obj as $jobi) {
            $time_frame = null;
            $first_obj = true;
            $chunk_size = count($jobi);
            $data_transfer = 0;
            $error_count = 0;
            $total_threads = 0;
            $avg_latency = 0;
            
            // keep track of the load machines and it's user count
            unset($hostnames);
            $hostnames = array();

            foreach ($jobi as $j) {
                // this is the one httpSample now
                // get the first object's time as our timeline marker.
                if ($first_obj) {
                    $first_obj = false;

                    $time_frame = intval(intval($j['attributes']['ts']) / 1000);
                    $time_frame_last = $time_frame;
                }
                // let's add the bytes up
                $data_transfer += intval($j['attributes']['by']);
                $error_count += intval($j['attributes']['ec']);
                $avg_latency += intval($j['attributes']['lt']);
                // get the hostnames thread count,
                if (isset($hostnames[$j['attributes']['hn']])) {
                    // this machine is in the list, do we need to bump up the number?
                    if ($hostnames[$j['attributes']['hn']] < intval($j['attributes']['na'])) {
                        // update the value
                        $hostnames[$j['attributes']['hn']] = intval($j['attributes']['na']);
                    }
                } else {
                    $hostnames[$j['attributes']['hn']] = intval($j['attributes']['na']);
                }

                if ($j['attributes']['ec'] == "1") {
                    if (isset($error_list[$j['attributes']['lb']])) {
                        $error_list[$j['attributes']['lb']]++;
                    } else {
                        $error_list[$j['attributes']['lb']] = 1;
                    }
                }
            }
            // get averages
            if (isset($chunk_size) && $chunk_size > 0) {
                $avg_latency = $avg_latency / $chunk_size;
                $error_count = ($error_count / $chunk_size) * 100;
            }
            
            // get the number of threads (users)
            foreach ($hostnames as $hn => $key) {
                // error_log("What is hn: ".$hn);
                $total_threads += intval($key);
            }
            
            // If null then this set of data has nothing in it, must push the time ahead
            if ($time_frame == null) {
                // put this set 30 seconds ahead
                $time_frame = strtotime("+".$this->range, $time_frame_last);
                // now make sure our last recorded time is this time.
                $time_frame_last = $time_frame;
            }

            $time_frame_set[] = date('h:i:s', $time_frame); // need to have last time here
            $data_transfer_set[] = $data_transfer;
            $error_count_set[] = round($error_count, 4);
            $total_threads_set[] = $total_threads;
            $avg_latency_set[] = round($avg_latency, 2);
        }

        if (count($error_list) > 1) {
            uasort($error_list, array($this,'jmCompareEc'));
        }

        // error_log("\n JMeterAjax -> buildStats() -> what is the time set: ". print_r($time_frame_set, 1));
        $this->ulethlib->printToLog("\n JMeterAjax -> buildStats() -> RETURNING", "jmeter");

        return array(
            'time_set' => $time_frame_set,
            'data_transfer_set' => $data_transfer_set,
            'error_count_set' => $error_count_set,
            'total_threads_set' => $total_threads_set,
            'avg_latency_set' => $avg_latency_set,
            'error_list_set' => $error_list,
        );
    }

    /**
     * Description - pass in the filename to open, parse and turn into an array of objects
     * @param type string - name of file
     * @return array of structs with jmeter data.
     */
    private function xmlToObject($source, $stack = false)
    {
        $this->ulethlib->printToLog("\n JMeterAjax -> xml_to_object() -> START", "jmeter");

        include_once('UtoolsLib.php');
        $ulethlib = new UtoolsLib();

        // ini_set('memory_limit', '2048M');
        ini_set('memory_limit', '1024M');

        $xml = $ulethlib->utoolsFileManager(array("file_action" => "get_file", "file_id" => $source));
        // $xml = file_get_contents($source);

        if ($xml) {
            $this->ulethlib->printToLog("\n JMeterAjax -> xml_to_object() -> yay, we have a file obj", "jmeter");
        } else {
            $this->ulethlib->printToLog("\n JMeterAjax -> xml_to_object() -> doh :-p we DONT have a file obj", "jmeter");
            return false;
        }

        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        $unset_counter = 0;
        foreach ($tags as $tag) {
            if ($stack) {
                if ($tag['tag'] == 'responseData' && $tag['value']) {
                    $json_response[] = $tag['value'];
                }
            } else {
                if ($tag['tag'] != 'httpSample') {
                    unset($tags[$unset_counter]);
                } else {
                    unset($tags[$unset_counter]['type']);
                    unset($tags[$unset_counter]['level']);
                }
            }
            $unset_counter++;
        }

        $json_array = array();

        if ($stack) {
            foreach ($json_response as $jr) {
                $temp_dec = json_decode($jr);
                // convert to timestamp so we can sort it
                if (isset($temp_dec->error)) {
                    return array("Error" => $temp_dec->error->title);
                }
                $temp_dec->server->last_reported_at = strtotime($temp_dec->server->last_reported_at);
                $json_array[] = $temp_dec;
            }
            // now sort the array
            usort($json_array, array($this, 'jmCompareStack'));
            return $json_array;
        }

        // sort based on the timestamp
        usort($tags, array($this,'jmCompareTs'));
        $this->ulethlib->printToLog("\n JMeterAjax -> xml_to_object() -> RETURNING", "jmeter");


        return $tags;
    }

    private function writeToFile($data, $filename)
    {
        include_once('ToXML2.php');
        $xml = ArrayToXML::toXml($data, 'JMeter_Load_Test', $filename);
    }
    /**
     * Description - Get the JMeter file and process it returning data for highcharts.
     * @param type string - file_id - need the file name.
     * @param type string - r_time - do we want the hours/minutes/seconds returned? (true).
     *                               or the timestamp? (false)
     * @param type string - range - how to show the time intervals.
     * @return json msg with the url.
     */
    public function getResultSet($params = null)
    {
        $this->ulethlib->printToLog("\n JMeterAjax -> getResultSet() -> START", "jmeter");

        $file_id = isset($params['file_id']) ? $params['file_id'] : null;
        $filename = isset($params['filename']) ? $params['filename'] : null;
        $r_time = isset($params['r_time']) ? $params['r_time'] : false;
        $this->range = isset($params['range']) ? $params['range'] : "30 seconds";

        $this->ulethlib->printToLog("\n JMeterAjax -> getResultSet() -> what is file_id: ", "jmeter", $file_id);

        $jmeter_obj = $this->xmlToObject($file_id, false);

        // let's check to see if there are any stack files
        // $jmeter_obj = $this->xmlToObject($file_id, $filename);
        // Summary_Report_2015-05-29-10-54.xml
        // Stack_Report_2015-05-29-10-54.xml
        
        $ranged = $this->breakIntoTimeRange($jmeter_obj);
        // $this->writeToFile($ranged, 'ranges.xml');
        $load_data = isset($params['load_data']) ? $params['load_data'] : null;
        if ($load_data) {
            // if we have stack results......
            $load_data = explode("_", $load_data);
            
            // error_log("\nWhat is load_data: ". print_r($load_data, 1));

            $load_data_size = intval(count($load_data) / 2);
            $reporting_results = array();
            $sql_loop_num = 0;
            for ($lds = 0; $lds < $load_data_size; $lds++) {

                // let's assume we are going to fetch some SQL type DB, let's add that data set last
                // as the javascript always assumes 0 and 1 are the App Servers.
                $filestring = $load_data[($lds * 2)];
                $findme   = 'SQL';
                $pos = strpos($filestring, $findme);

                if ($pos != false) {
                    
                    // error_log("YES - SQL found in the filename on loop: ". $lds);
                    $sql_loop_num = $lds;
                    continue;
                    // echo "The string '$findme' was found in the string '$mystring'";
                    // echo " and exists at position $pos";
                }

                $reporting_obj = $this->xmlToObject($load_data[($lds * 2) + 1], true);
                
                // error_log("\nWhat is reporting_obj: ". print_r($reporting_obj, 1));

                if (count($reporting_obj) == 1) {
                    continue;
                }
                
                $reporting_results[] = array_merge(
                    array("name" => $load_data[($lds * 2)]),
                    $this->buildStackReports($reporting_obj, false)
                );
            }

            // now add the SQL data
            $reporting_obj_sql = $this->xmlToObject($load_data[($sql_loop_num * 2) + 1], true);
            $reporting_results[] = array_merge(
                array("name" => $load_data[($sql_loop_num * 2)]),
                $this->buildStackReports($reporting_obj_sql, false)
            );


            $ranged_count = count($ranged);
            $reporting_results_count = count($reporting_results[0]['cpu']);

            if ($ranged_count > $reporting_results_count) {
                $ranged = array_slice($ranged, 0, $reporting_results_count);   // returns "a", "b", and "c"
            }
        }

        $result_set = $this->buildStats($ranged);
        // what is the result set size? we need the timestamp length
        // $time_set_length = count($result_set['time_set']);
        if ($load_data) {
            $result_set['stack'] = $reporting_results;
        }
        // $this->writeToFile($result_set, 'result_sets.xml');
        
        $this->ulethlib->printToLog("\n JMeterAjax -> getResultSet() -> RETURNING", "jmeter");

        die(json_encode(array("success" => true, "data" => $result_set)));
    }

    /**
     * Description - Get the list of files that are currently stored in the file repo.
     * @return json msg with the file list.
     */
    public function getFileList($params = null)
    {
        global $CFG, $DB;
        // include_once($CFG->dirroot . '/lib/filestorage/file-storage.php');
        include_once($CFG->dirroot . '/lib/moodlelib.php');
        $this->ulethlib->printToLog("\n JMeterAjax -> getFileList() -> START", "jmeter");

        $sql = "SELECT * FROM mdl_files WHERE component like '%utools%'";
        $file_list = $DB->get_records_sql($sql);
        
        $this->ulethlib->printToLog("\n JMeterAjax -> getFileList() -> FINISHED", "jmeter");
        if (count($file_list) > 0) {
            die(json_encode(array("success" => true, "callback" => "list", "data" => $file_list)));
        } else {
            die(json_encode(array("success" => true, "callback" => "no_files", "msg" => "There are no files", "data" => array())));
        }
    }

    /**
     * Description - Upload a file to the utools repo stored in Moodle
     * @return json msg with the file list.
     */
    public function uploadFile($params = null)
    {
        global $DB, $CFG;
        // error_log("\nuploadFile -> START");
        require('UploadHandler.php');
        
        $files = isset($params['files']) ? $params['files'] : null;
        $this->ulethlib->printToLog("\n JMeterAjax -> uploadFile() -> START", "jmeter");
        $this->ulethlib->printToLog("\n JMeterAjax -> uploadFile() -> are there any files: ", "jmeter", count($files));

        $upload_handler = new UploadHandler(
            array(
                // 'moodle_component' => 'local_utools_JMeter',
                'moodle_component' => 'local_utools',
                'moodle_filearea' => 'testfiles',
                'moodle_filepath' => '/'
            )
        );

        // clean up the mysterious '.' file that get's created
        $DB->delete_records_select(
            "files",
            "component = :component AND filename = :filename",
            array(
                'component' => 'local_utools',
                'filename' => '.'
            )
        );
        $this->ulethlib->printToLog("\n JMeterAjax -> uploadFile() -> RETURNING", "jmeter");

        // let the fileupload library take care of the call back messages.
        // die(json_encode(array("success" => true, "callback" => "list", "msg" => "I think it's done........?")));
        // error_log("\nuploadFile -> END");
    }

    /**
     * Description - Delete a file
     * @return json msg with success or fail.
     */
    public function deleteFile($params = null)
    {
        // require('UploadHandler.php');
        global $CFG;
        include_once($CFG->dirroot . '/lib/moodlelib.php');
        include_once($CFG->dirroot . '/lib/externallib.php');

        $context = context_system::instance();
        $fs = get_file_storage();
        // MOODLE E ==============================

        $fileid = isset($params['fileid']) ? $params['fileid'] : null;
        $found_file = $fs->get_file_by_id($fileid);

        if ($found_file) {
            $found_file->delete();
            die(json_encode(
                array(
                    "success" => true,
                    "callback" => "delete",
                    "msg" => "File ".$fileid." has been deleted"
                )
            ));
        } else {
            die(json_encode(
                array(
                    "success" => false,
                    "callback" => "delete",
                    "msg" => "File ".$fileid." was not found....?"
                )
            ));
        }
        
        $this->ulethlib->printToLog("\n JMeterAjax -> deleteFile() -> going to delete this file: ", "jmeter", $fileid);
    }
}