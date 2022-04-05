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

namespace local_utools\task;

use \Datetime;
use \UtoolsLib;

/**
 * Simple task to call Piwik and get some data
 *
 * @copyright  2014 Universite de Montreal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class userstat_task extends \core\task\scheduled_task
{
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */

    private $piwik_call1 = null;

    /*
        var piwik_call1 = {
            'module': 'API',
            'idSite': '1',
            'method': 'Live.getCounters',
            'lastMinutes': 30,
            'format': 'JSON',
            'token_auth': '0c1c48ff5d224469767df3ec5264e479',
        }, piwik_call2 = {
            'module': 'API',
            'idSite': '1',
            'method': 'VisitTime.getVisitInformationPerLocalTime',
            'format': 'JSON',
            'period': 'day',
            'date': 'today',
            'token_auth': '0c1c48ff5d224469767df3ec5264e479',
        };

        var piwik_data = [{
            method: 'GET',
            url: 'https://analytics.uleth.ca/index.php',
            data: piwik_call1,
            dataType: "jsonp",
            to_dispatch: "updatePiwikUserStat",
            block_name: "piwik_user_count"
        }, {
            method: 'GET',
            url: 'https://analytics.uleth.ca/index.php',
            data: piwik_call2,
            dataType: "jsonp",
            to_dispatch: "updatePiwikMaxUserStat",
            block_name: "piwik_max_users_stat"

        }];
    */

    public function get_name() {
        return get_string('user_stat_name', 'local_utools');
    }

    public function call_carl($method, $the_date, $segment = null) {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_HEADER         => false,  // don't return headers
            CURLOPT_FOLLOWLOCATION => true,   // follow redirects
            CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
            CURLOPT_ENCODING       => "",     // handle compressed
            CURLOPT_USERAGENT      => "test", // name of client
            CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
            CURLOPT_TIMEOUT        => 120,    // time-out on response
        );

        $url = "https://analytics.uleth.ca/index.php?module=API".
            "&idSite=1".
            "&token_auth=0c1c48ff5d224469767df3ec5264e479".
            "&format=JSON".
            "&period=day".
            "&segment=".$segment.
            "&date=".$the_date.
            "&method=".$method;
            

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        // error_log("\n----------Executing API CALL NOW-----------\n");
        $content  = curl_exec($ch);

        curl_close($ch);

        return $content;
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        date_default_timezone_set('America/Edmonton');

        global $DB, $CFG;
        require_once(dirname(dirname(dirname(__FILE__))) .'/lib/UtoolsLib.php');

        $format_piwik = 'Y-m-d';
        $methods = [
            "VisitTime.getVisitInformationPerLocalTime",
            "VisitsSummary.getUniqueVisitors",
        ];
        $today_obj = new \DateTime();
        // error_log("\nHere is today_obj: ". print_r($today_obj, 1));

        // modify will alter the today_obj
        $yesterday = $today_obj->modify('-1 day')->format('Y-m-d');

        // =================================================================================
        // =================================================================================
        // We must make sure that we aren't entering another chunk of data for the same day.
        $last_timestamp = $DB->get_record_sql(
            'SELECT sdate 
            FROM mdl_utools_user_stat
            ORDER BY sdate DESC
            LIMIT 1'
        );
        // error_log("\nWhat is last_timestamp: ". print_r($last_timestamp, 1));
        if ($last_timestamp) {
            $stamp_date = new \DateTime();
            $stamp_date->format($format_piwik);
            // error_log("\nThe timestamp is: ". $last_timestamp->sdate);
            $stamp_date->setTimestamp($last_timestamp->sdate);

            if ($stamp_date->format($format_piwik) == $yesterday) {
                // the date's are equal, so let's abort this as we don't need duplicate dates.
                error_log("\nuserstat_task - the dates are equal, aborting todays run.");
                return;
            }
        }
        // =================================================================================
        // =================================================================================

        if (isset($CFG->local_utools_semester) && strlen($CFG->local_utools_semester) > 1) {
            $semester = $CFG->local_utools_semester;
        } else {
            $semester = substr(dirname(dirname(dirname(dirname(dirname(__FILE__))))), -6);
        }

        // send yesterday.
        try {
            $meth0 = $this->call_carl($methods[1], $yesterday);
            $total_max_logins = json_decode($meth0)->value;

            // VisitTime.getVisitInformationPerLocalTime
            $meth1 = json_decode($this->call_carl($methods[0], $yesterday));
            for ($x = 0; $x < 24; $x++) {

                $this_insert = new \stdClass();
                $this_insert->semester = $semester;
                $this_insert->hour = str_replace("h", "", $meth1[$x]->label);
                $this_insert->uniq_visitors = $meth1[$x]->nb_uniq_visitors;
                $this_insert->visits = $meth1[$x]->nb_visits;
                $this_insert->actions = $meth1[$x]->nb_actions;
                $this_insert->max_actions = $meth1[$x]->max_actions;
                $this_insert->sum_visit_length = $meth1[$x]->sum_visit_length;
                $this_insert->bounce_count = $meth1[$x]->bounce_count;
                $this_insert->visits_converted = $meth1[$x]->nb_visits_converted;
                $this_insert->total_logins = $total_max_logins;
                $this_insert->sdate = $today_obj->getTimestamp();

                if (!$did_it_work = $DB->insert_record('utools_user_stat', $this_insert, true)) {
                    error_log("\nWARNING: The record failed to insert into utools_user_stat");
                }
            }
        } catch (Exception $e) {
            error_log("\nERROR: Something failed when trying to talk to cURL AND OR failed to insert into utools_user_stat");
        }
    }
}
