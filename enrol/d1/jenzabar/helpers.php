<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    enrol_d1 - Jenzabar/CSV Import Tool
 * @copyright  2022 onwards Louisiana State University
 * @copyright  2022 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// defined('MOODLE_INTERNAL') || die();
namespace enrol_d1\jenzabar;
require_once(dirname(__DIR__, 3). '/config.php');

class helpers {
    private static $token;
    public static $tokentimestart;
    public static $bundlelist;
    public static $bundlelist2;
    public static $sigcalled;

    public function __construct() {
        // self::$sigcalled = true;
    }

    public static function get_help() {
        $help = <<<EOL
        No flags will run the default importer to import students and enrollments.

        The importer will process any file in the 'unprocessed' folder.
        Reports will be generated for successfully created students and enrolments as well 
        as the failed attempts to create a student or enrolment. Those reports will be in the importer/reports
        folder.

        Here are your options:

        ----------------------------------------------------------------------------------------
        *** NOTE *** Default use of "php base.php" will import/create students and enrollments.
        ----------------------------------------------------------------------------------------

        General
          -b=[x],        Optional: Row begin when processing a large file
          -e=[x],        Optional: Row end when processing a large file
          -n,            Optional: Name the output files (currently uses timestamp which can change every time process starts)
                            Files are appended too. So if name exists then data will be appended rather than multiple files.
          -h,    --help  Display's the list of commands for this script

        Student/Enrollment
          -a,            Required: To trigger student import/create and do enrollments (folder: /importer/student)
          -s,            Optional: Import students only. (folder: /importer/student)
          -g,            Optional: When creating the student write student row to CSV BUT with new XNumber.
          -u,            Optional: Find and update student (temp hack to update LSU MF ID)

        Bundle
          -p,            Required: To trigger bundle enrollments import (folder: /importer/bundle)
          -q,            Optional: Convert Bundle enrollments Jenza ID's to ObjectIds.

        Fees
          -f,            Required: To trigger Import more than 4 fees file. (folder: /importer/fees)
          -d,            Optional: Find duplicate fees.
          -i,            Purge Fees and add temp fee
          -r,            Purge temp fee and add fees from csv

        Certificates
          -t,            Required: Enroll students in certificates. (folder: /importer/cert)

        Course
          -v,            Required: Update courses, default action is to update to original data. (in folder: /importer/course/)
          -w,            Optional: Set to true if you want everything to be "Active" and in "Final Approval".
        
        Course Sections
          -c,            Required: Update course sections, default is to set dates to original (folder: /importer/coursesection/)
          -x,            Optional: When updating course sections set dates to far in the future.
          -o,            Optional: Set this flag to include the grade template code.
          -y,            Optional: Set this flag to run a custom function (WARNING: contents may change).
          -z,            Optional: Set this flag to unenroll all students in the course section.
          -j,            Optional: Set to true if you want to count how many sections are NOT in Final Approval.

        File Processing
          -m=[x],        Required: Extract enrollments from the main enrollment file given a list of custom sections (look in pfile.php)
          --lf=[x],      Optional: name of the file to open and process in pfile folder (currently only ready for option 4)
          --f1cm=[x]     Optional: In this file what column to use to match with file 2? (if f1 has email and f2 has email then match it)
          --f2cm=[x]     Optional: In this file what column to use to match with file 1?
          --f1cv=[x]     Optional: Which column's value we wanting 
          --f2cd=[x]     Optional: Which column we inserting that data??
                                    
        ******************************************************************************************************************\n\n
        EOL;
        return $help;
    }

    public static function get_d1_settings() {
        global $CFG;

        // Build the object.
        $s = new \stdClass();
        // Get the debug file storage location.
        $s->debugfiles = get_config('enrol_d1', 'debugfiles');
        // Get the Moodle data root.
        $s->dataroot = $CFG->dataroot;
        // Get the DestinyOne webservice url prefix.
        $s->wsurl = $CFG->d1_wsurl;
        // Determine if we should do our extra debugging.
        $s->debugging = get_config('enrol_d1', 'debuextra') == 1 && $CFG->debugdisplay == 1 ? 1 : 0;

        return $s;
    }

    /**
     * Grabs D1 Webseervice credentials.
     *
     * @return @object $c
     */
    public static function get_d1_creds() {
        // Build the object.
        $c = new \stdClass();
        // Get the username from the config settings.
        $c->username   = get_config('enrol_d1', 'username');
        // Get the webservice password.
        $c->password   = get_config('enrol_d1', 'password');
        // Get the debug file storage location.

        return $c;
    }

    /**
     * Grabs the token from the D1 web services.
     *
     * @return @string $token
     */
    // public function set_token($token = "") {
    //     self::$token = $token;
    // }
    /**
     * Grabs the token from the D1 web services.
     *
     * @return @string $token
     */
    public static function set_token_start() {
        self::$tokentimestart = microtime(true);
    }

    public static function get_token_time() {
        $timeend = microtime(true);
        return $timeend - self::$tokentimestart;
    }

    public static function set_sig($setto) {
        self::$sigcalled = $setto;
    }

    public static function get_sig() {
        return self::$sigcalled;
    }
    /**
     * Grabs the token from the D1 web services.
     *
     * @return @string $token
     */
    public static function get_token() {
        // Get the data needed.
        $s = self::get_d1_settings();
        $c = self::get_d1_creds();

        // Set the URL for the REST command to get our token.
        $url = "$s->wsurl/webservice/InternalViewREST/login?_type=json&username=$c->username&password=$c->password";

        // Set up the CURL handler.
        $curl = curl_init($url);

        // Set the CURL options.
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, false);

        // Grab the response.
        $response = curl_exec($curl);

        // Set the HTTP code for debugging.
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close the curl handler.
        curl_close($curl);

        if ($s->debugging == 1) {
            // If there is a location set, use it.
            $loc = isset($s->debugfiles) ? $s->debugfiles : $s->dataroot;

            // Write this out to a file for debugging.
            $fp = fopen($loc . '/token.txt', 'w');
            fwrite($fp, $response);
            fclose($fp);
        }

        // Set the token.
        self::$tokentimestart = microtime(true);
        self::$token = $response;

        // Return the response.
        return($response);
    }



    /**
     * A Date/Time header for a log entry when doing D1 requests.
     * @return @string
     */
    public static function log_header() {

        $myDateTime = (new \DateTime())->format('M d Y h:i:s A');
        $line = PHP_EOL."***************************************************".PHP_EOL;
        $line .= $myDateTime.PHP_EOL;
        $line .= "***************************************************".PHP_EOL;
        return $line;
    }
    /**
     * A general curl function for Destiny.
     * Pass in the body and url and get the results.
     * @param  @object $params
     * @return @object $s
     */
    public static function curly($params) {
        // Get the data needed.
        // $s = self::get_d1_settings();

        // Set the URL for the post command to get a list of the courses matching the parms.
        // $url = $s->wsurl . $params->url;

        // Attempt to get a new token if the result gives us an "Access denied."" error.
        $callcheck = 0;
        $results = "";
        while ($callcheck < 5) {

            // Set the POST body.
            // $body = $params->body;

            // Set the POST header.
            $header = array('Content-Type: application/json', 'sessionId: ' . self::$token);

            // Set up the CURL handler.
            $curl = curl_init($params->url);

            // Se the CURL options.
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params->body);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            // Grab the response.
            $json_response = curl_exec($curl);

            // Set the HTTP code for debugging.
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // Close the CURL handler.
            curl_close($curl);

            // Decode the response.
            $response = json_decode($json_response);

            if (property_exists($response, "SRSException")) {
                if ($response->SRSException->message == "Access denied.") {
                    error_log("\n*******************   WARNING   *******************");
                    error_log("\nThe token has EXPIRED after ". helpers::get_token_time(). " seconds. Attempting to renew.....");
                    error_log("\n***************************************************");
                    self::get_token();
                } else {
                    // It's a different fail so exit while
                    break;
                }
            } else {
                // It's a different fail so exit
                break;
            }
            $callcheck++;
        }
        // Return the response.
        return $response;
    }

    public static function fix_for_d1_date($csvdate) {
        // From Scott's file: May/29/2019
        $newdatetime = \DateTime::createFromFormat('M/j/Y', trim($csvdate));
        return $newdatetime->format('j M Y');
    }

    public static function cleanUsername($dirty) {
        // $muddy = self::removeAccents($dirty);
        // $better = self::stripCommas($muddy);
        // $ding = self::stripPeriods($better);
        $ding = self::alphaNumStr($dirty);
        $dong = self::maxFifty($ding);
        return $dong;
    }

    public static function removeAccents($str) {

        // Names to test with: $name1 = "Renée","Noël","Sørina","Adrián","François","Mónica","Jokūbas","John-Paul",
        // "Siân","Maël","Mike O'Leary","Thomas O'Calloway","Janet Smith-Johnson","Mary-Ann Johnson"
        $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U',
            'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o',
            'ó'=>'o', 'ô'=>'o', 'õ'=>'o','ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'ū' => 'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b',
            'ÿ'=>'y', '\'' => "", '`'=> "", " " => "", '\"' => "");
            // 'ÿ'=>'y', '\'' => "", '`'=> "", " " => "", "-" => "");
        return strtr($str, $unwanted_array);
    }

    public static function alphaNumStr($ding) {
        // $ding = str_replace(' ', '-', $dirty); // Replaces all spaces with hyphens.
        // preg_match('/^[\w-]+$/', $ding)
        $dong = preg_replace('/[^A-Za-z0-9\-]/', '', $ding); // Removes special chars.
        return preg_replace('/-+/', '-', $dong); // Replaces multiple hyphens with single one.
    }

    public static function alphaStr($str) {
        // D1 allows: @~`-'.(),]].
        // Let's just use alpha numberic
        return preg_replace('/[^A-Za-z\-]/', '', $str);
    }

    public static function stripSpace($dirty) {
        $clean = str_replace(' ', '_', $dirty);
        return $clean;
    }
    public static function stripCommas($dirty, $replace = false) {
        $clean = $replace ? str_replace(',', ' ', $dirty) : str_replace(',', '', $dirty);
        return $clean;
    }

    public static function stripPeriods($dirty, $replace = false) {
        $clean = $replace ? str_replace('.', ' ', $dirty) : str_replace('.', '', $dirty);
        return $clean;
    }

    public static function stripLineBreaks($dirty, $replace = false) {
        // $clean = trim(preg_replace('/\s\s+/', ' ', $dirty));
        $clean = preg_replace("/\r|\n/", "", $dirty);
        return $clean;
    }

    public static function salutationMatch($sal) {
        $accepted = array("mr", "mrs", "ms", "miss", "professor", "dr", "rev", "fr", "hon", "mx");
        $possible = self::stripPeriods($sal);
        $sal = strtolower($possible);

        if (in_array($sal, $accepted)) {
            return $possible;
        } else {
            return "";
        }
    }


    public static function validate_state($this_state) {

        $stateAbbreviations = array('AL','AK','AS','AZ','AR','CA','CO','CT','DE','DC','FM','FL','GA','GU','HI','ID','IL','IN','IA','KS','KY','LA','ME','MH','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','MP','OH','OK','OR','PW','PA','PR','RI','SC','SD','TN','TX','UT','VT','VI','VA','WA','WV','WI','WY');
        // NOT INCLUDING.
        // 'AE'=>'ARMED FORCES AFRICA \ CANADA \ EUROPE \ MIDDLE EAST',
        // 'AA'=>'ARMED FORCES AMERICA (EXCEPT CANADA)',
        // 'AP'=>'ARMED FORCES PACIFIC'

        foreach($stateAbbreviations as $state) {
            if(preg_match("/\b($state)\b/", $this_state, $match)) {                    
                return true;
            }
        }
        return false;
    }
    

    public static function validate_phone($dirty) {

        if(preg_match("/^[0-9]{3}-[0-9]{4}/", $dirty)) {
            return true;
        } else {
            return false;
        }
    }

    public static function validate_areacode($dirty) {

        if(preg_match("/^[0-9]{3}/", $dirty)) {
            return true;
        } else {
            return false;
        }
    }


    public static function deepClean($dirty) {

        $mirky = self::removeAccents($dirty);
        $clear = self::alphaStr($mirky);
        if ($clear == "") {
            return "GeauxTigers";
        } else {
            return $clear;
        }
    }

    public static function maxFifty($string) {
        return (strlen($string) > 50) ? substr($string,0,50) : $string;
    }

    public static function printMem() {
        /* Currently used memory */
        $mem_usage = memory_get_usage();

        /* Peak memory usage */
        $mem_peak = memory_get_peak_usage();
        error_log('The script is now using: ' . round($mem_usage / 1024) . 'KB of memory.');
        error_log('Peak usage: ' . round($mem_peak / 1024) . 'KB of memory.');
    }


    public static function load_bundle_list($bundledata) {

        $new_list = array();
        foreach ($bundledata as $key => $val) {
            $new_list[$val[0]] = $val[1];
        }
        self::$bundlelist = $new_list;

        self::$bundlelist2 = $bundledata;

        // The method above takes anywhere from.....3-8 seconds to find.
        // isset appears to be a little quicker than array_key_exists.
        // Going to break this array down into 10 arrays

        // $newlist = array();
        // foreach ($bundledata as $key => $val) {
        //     $newlist[$val[0]] = $val[1];
        // }
        // $size = count($newlist);


    }

    /**
     * Use binary search to find a key of a value in an array.
     *
     * @param array $array
     *   The array to search for the value.
     * @param int $value
     *   A value to be searched.
     *
     * @return int|null
     *   Returns the key of the value in the array, or null if the value is not found.
     */
    public static function in_bundle_list1($objid) {
        // Set the left pointer to 0.
        $left = 0;
        // Set the right pointer to the length of the array -1.
        $right = count(self::$bundlelist2) - 1;

        while ($left <= $right) {
            // Set the initial midpoint to the rounded down value of half the length of the array.
            $midpoint = (int) floor(($left + $right) / 2);

            // error_log("bundlelist: ".self::$bundlelist2[$midpoint][0]." objid: ". $objid);
            // error_log("Is bundlelist obj less than objid: ".self::$bundlelist2[$midpoint][0]." < ". $objid);
            if (self::$bundlelist2[$midpoint][0] < $objid) {
                // The midpoint value is less than the value.
                $left = $midpoint + 1;
            } elseif (self::$bundlelist2[$midpoint][0] > $objid) {
                // The midpoint value is greater than the value.
                $right = $midpoint - 1;
            } else {
                // This is the key we are looking for.
                // return $midpoint;
                // error_log("HUZZZZAAAAAHHHHHH - FOUND IT.");
                // error_log("HUZZZZAAAAAHHHHHH - returning this: ". self::$bundlelist2[$midpoint][1]);

                return self::$bundlelist2[$midpoint][1];
            }
            // error_log("Loop.");
        }
        
        // die();
        // The value was not found.
        return false;

    }

    public static function in_bundle_list2($objid) {

        // $id = array_search($objid, array_column(self::$bundlelist, 'score'));
    }

    public static function in_bundle_list($objid) {
        // 2416440
        $hack11 = microtime(true);
        $result11 = isset(self::$bundlelist[$objid]);
        $hack22 = microtime(true);
        error_log("Time for isset: ". ($hack22 - $hack11));
        error_log("result is: ". $result11);

        // $hack1 = microtime(true);
        // $result22 = array_key_exists($objid, self::$bundlelist);
        // $hack2 = microtime(true);
        // error_log("Time for array_key_exists: ". ($hack2 - $hack1));
        // error_log("result is: ". $result22);

        if (array_key_exists($objid, self::$bundlelist)) {
            return self::$bundlelist[$objid];
        } else {
            return false;
        }
    }
}
