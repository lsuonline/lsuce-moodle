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

namespace enrol_d1\jenzabar;

require_once('helpers.php');

class cert {

    private $bugfiles;
    public $pagenumber;
    public $pagesize;
    public $totalcount;

    public function __construct(&$report, $cert = "") {
        $this->pagenumber = "1";
        $this->pagesize = "25";
        $this->totalcount = 0;
        $this->restcalled = false;
        $this->report = $report;
        $this->bugfiles = get_config('enrol_d1', 'extradebug');

        $this->cert = new \stdClass();
        $this->cert->enrollStudentInCertificateRequestDetail = new \stdClass();

        $this->cert->enrollStudentInCertificateRequestDetail->matchOn = $cert[1];
        $this->cert->enrollStudentInCertificateRequestDetail->attributeValue = $cert[2];
        $this->cert->enrollStudentInCertificateRequestDetail->certificateCode = $cert[3];
        // D1 requires dd MMM yyyy
        // CSV has MMM/dd/yyyy
        $this->cert->enrollStudentInCertificateRequestDetail->enrollmentDate = helpers::fix_for_d1_date($cert[4]);
    }

    /**
     * Intitialize dodads here.
     * @param   @object   Data from the CSV file
     * @param   @object   Any extra tidbits
     * @return  @bool   return success or fail
     */
    public function init($rowdata = "", $extras = array()) {
        return;
    }

    /**
     * Process the enrollment data using D1's web services.
     * @return  @bool   return success or fail
     */
    public function process() {

        $pstart = microtime(true);
        $results = $this->enroll_cert();
        $pend = microtime(true);
        $this->report->timer("addup", $pend - $pstart);

        // For course section search use.
        $success = false;
        if (property_exists($results, "enrollStudentInCertificateResult")) {
            if ($results->enrollStudentInCertificateResult->responseCode == "Success") {
                $update_msg = "User: ". $this->cert->enrollStudentInCertificateRequestDetail->attributeValue.
                    " was successfully added to the ". $this->cert->enrollStudentInCertificateRequestDetail->certificateCode.
                    " certificate.";
                $success = true;
            }
        } else if (property_exists($results, "SRSException")) {
            $update_msg = "** FAIL, User ". $this->cert->enrollStudentInCertificateRequestDetail->attributeValue.
            " FAILED to be enrolled in ". $this->cert->enrollStudentInCertificateRequestDetail->certificateCode;
            $update_msg .= "\n\nCode: ". $results->SRSException->errorCode. "\nmsg: ".$results->SRSException->message;
        }
        error_log($update_msg);
    }

    public function post_process($result, $rowdata, $extras) {
        return;
    }

    /**
     * Stuff here
     * more stuff.......
     * @param   @object   Something
     * @return  @object   return result
     */
    public function enroll_cert() {

        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewREST/enrollStudentInCertificate?_type=json';

        $params->body = '{'.
            '"enrollStudentInCertificateRequestDetail": {'.
                '"attributeValue": "'.$this->cert->enrollStudentInCertificateRequestDetail->attributeValue.'",'.
                '"certificateCode": "'.$this->cert->enrollStudentInCertificateRequestDetail->certificateCode.'",'.
                '"matchOn": "studentNumber",'.
                '"enrollmentDate": "'.$this->cert->enrollStudentInCertificateRequestDetail->enrollmentDate.'"'.
            '}'.
        '}';

        $results = helpers::curly($params);
        /* Sample of Return
        {
            "enrollStudentInCertificateResult": {
                "status": "OK",
                "responseCode": "Success",
                "certificateCode": "POPMP",
                "studentInfo": {
                    "firstName": "John",
                    "lastName": "Mayeaux",
                    "objectId": 1430412,
                    "personNumber": "X000025",
                    "preferredEmail": "119291@example.com",
                    "schoolPersonnelNumber": "",
                    "status": "Active",
                    "userName": "Colbymayeaux09"
                }
            }
        }
        */

        $header = helpers::log_header();

        // This will write the results to a logging file.
        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Cert_Enroll_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        return $results;
    }
}
