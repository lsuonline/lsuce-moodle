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

use enrol_d1\jenzabar\enrollment;

// defined('MOODLE_INTERNAL') || die();
require_once('helpers.php');
require_once('enrollment.php');

class student {
    private $student;
    private $bugfiles;
    public $report;
    public $updstu;

    public function __construct(&$report, $jenzastudent = "", $extras = false) {
        $this->report = $report;
        $this->studenttemp = new \stdClass();
        $this->updstu = false;
        // $this->enrolonly = false;

        if (!empty($extras['updstu'])) {
            $this->updstu = true;
        }
        // if (!empty($extras['enrolonly'])) {
        //     $this->enrolonly = true;
        // }

        // $this->jenzastudent = $jenzastudent;
        // Is the X number present
        !empty($jenzastudent[1]) ? $this->studenttemp->xnumber = $jenzastudent[1] : false;

        // Data for the report, not D1.
        !empty($jenzastudent[7]) ? $this->studenttemp->jenzaid = $jenzastudent[7] : null;

        $this->bugfiles = get_config('enrol_d1', 'extradebug');


        $this->student = new \stdClass();
        // Represent the student object according to D1.
        $this->student->student = new \stdClass();
        
        // Address.
        $this->student->student->addresses = new \stdClass();
        $this->student->student->addresses->address = new \stdClass();

        // Name.
        $this->student->student->lastName = helpers::deepClean($jenzastudent[2]);
        $this->student->student->firstName1 = helpers::alphaStr($jenzastudent[3]);

        if ($this->student->student->firstName1 == "") {
            $this->student->student->firstName1 = "GeauxTigers";
        }

        // Allowed: Mr, Mrs, Ms, Miss, Professor, Dr, Rev, Fr, Hon, Mx
        if (!empty($jenzastudent[5])) {
            $this->student->student->salutationCode = $jenzastudent[5];
            // remove any '.' from the saluation.
            $this->student->student->salutationCode = helpers::salutationMatch($this->student->student->salutationCode);
        }

        // Jenza ID and ALL id's are there so this must exist.
        $this->student->student->preferredName = $jenzastudent[7];

        if (!empty($jenzastudent[11])) {
            $myDateTime = \DateTime::createFromFormat('M/j/Y', $jenzastudent[11]);
            $newDateString = $myDateTime->format('j M Y');
            $this->student->student->birthDate = $newDateString;
        }

        // !empty($jenzastudent[12]) ? $this->student->student->schoolStudentNumber = $jenzastudent[12] : null;
        !empty($jenzastudent[12]) ? $this->student->student->schoolPersonnelNumber = $jenzastudent[12] : null;

        // Have to make loginId Unique.
        if (empty($jenzastudent[13])) {
            // Make a unique loginId.
            $loginId = $this->studenttemp->jenzaid."_".
                $this->student->student->firstName1."_".
                $this->student->student->lastName;

            $this->student->student->loginId = helpers::maxFifty($loginId);
        } else {
            // $this->student->student->loginId = preg_replace('/\s+/', '_', $jenzastudent[13]);
            // if (ctype_alnum($jenzastudent[13])) {
                $this->student->student->loginId = helpers::cleanUsername($jenzastudent[13]);
            // } else {
                // $this->student->student->loginId = preg_replace('/[^a-z_\-0-9]/i', "_", $jenzastudent[13]);
            // }
        }

        // error_log("UsernameLoginID Shenanigans: ". $jenzastudent[13]);
        // error_log("UsernameLoginID converted to: ". $this->student->student->loginId);
        // LMS Person Id.
        !empty($jenzastudent[14]) ? $this->student->student->lmsPersonId = $jenzastudent[14] : null;

        if (!empty($jenzastudent[15])) {
            $this->student->student->studentCategories = new \stdClass();
            $this->student->student->studentCategories->studentCategory = new \stdClass();
            // $this->student->student->studentCategories->studentCategory->dateCreated
            $this->student->student->studentCategories->studentCategory->category = $jenzastudent[15];
        }

        $this->student->student->addresses->address->preferred = "Y";

        // Country.
        if (empty($jenzastudent[22]) || $jenzastudent[22] == "" || strtolower($jenzastudent[22]) == "na") {
            // Country is not specified, let's fill it with USA and LA for State otherwise D1 fails.
            $this->student->student->addresses->address->country = "USA";
            $this->student->student->addresses->address->provinceState = "LA";

        } else if ($jenzastudent[22] == "USA" || $jenzastudent[22] == "U.S." || $jenzastudent[22] == "United States" || $jenzastudent[22] == "US") {
            $this->student->student->addresses->address->country = "USA";
            if (empty($jenzastudent[20]) || $jenzastudent[20] == "" || strtolower($jenzastudent[20]) == "na") {
                $this->student->student->addresses->address->provinceState = "LA";
            } else {
                // Sometimes they still enter the state wrong, so let's check
                if (helpers::validate_state($jenzastudent[20])) {
                    $this->student->student->addresses->address->provinceState = $jenzastudent[20];
                } else {
                    $this->student->student->addresses->address->provinceState = "LA";
                }
            }

        } else {
            if ($jenzastudent[22] != "Canada") {
                // We are out of Canada and USA.
                $this->student->student->addresses->address->foreign = "Y";
                $this->student->student->addresses->address->foreignState = $jenzastudent[20];
            } else {
                $this->student->student->addresses->address->provinceState = $jenzastudent[20];
            }
            $this->student->student->addresses->address->country = $jenzastudent[22];
        }

        // // State - check if Canada or USA or Other.
        //     $this->student->student->addresses->address->country == "USA") {


        //     if (empty($jenzastudent[20]) || $jenzastudent[20] == "" || strtolower($jenzastudent[20]) == "na") {
        //         $this->student->student->addresses->address->provinceState = "LA";
        //     } else {
        //         $this->student->student->addresses->address->provinceState = $jenzastudent[20];
        //     }

        // } else {
        //     // We are out of Canada and USA.
        //     $this->student->student->addresses->address->foreign = "Y";
        //     !empty($jenzastudent[20]) ? $this->student->student->addresses->address->foreignState = $jenzastudent[20] : null;
        // }

        // Address Type Code.
        !empty($jenzastudent[16]) ? $this->student->student->addresses->address->typeCode = $jenzastudent[16] : null;

        // The import has holes for the street, make sure a placeholder is there.
        if (empty($jenzastudent[17]) || $jenzastudent[17] == "" || strtolower($jenzastudent[17]) == "na") {
            $this->student->student->addresses->address->street1 = "123 Placeholder Fake Street";
        } else {
            $this->student->student->addresses->address->street1 = helpers::alphaNumStr($jenzastudent[17]);
        }

        !empty($jenzastudent[18]) ? $this->student->student->addresses->address->street2 = helpers::alphaNumStr($jenzastudent[18]) : null;

        if (empty($jenzastudent[19]) || $jenzastudent[19] == "" || strtolower($jenzastudent[19]) == "na") {
            $this->student->student->addresses->address->city = "Placeholder Fake City";
        } else {
            $this->student->student->addresses->address->city = helpers::alphaNumStr($jenzastudent[19]);
        }

        if (empty($jenzastudent[24]) || $jenzastudent[24] == "" || strtolower($jenzastudent[24]) == "na") {
            $this->student->student->addresses->address->postalZip = "70802";
        } else {
            $this->student->student->addresses->address->postalZip = $jenzastudent[23];
        }

        !empty($jenzastudent[24]) ? $this->student->student->addresses->address->returnMail = $jenzastudent[24] : null;

        $this->student->student->telephones = new \stdClass();
        $this->student->student->telephones->telephone = new \stdClass();
        $this->student->student->telephones->telephone->preferred = "Y";

        // Telephone Area Code.
        !empty($jenzastudent[25]) ? $this->student->student->telephones->telephone->typeCode = $jenzastudent[25] : null;
        if (empty($jenzastudent[26])) {
            // Telephone is REQUIRED so let's use a placeholder.
            $this->student->student->telephones->telephone->areaCode = "555";
        } else {
            if (helpers::validate_areacode($jenzastudent[26])) {
                $this->student->student->telephones->telephone->areaCode = $jenzastudent[26];
            } else {
                $this->student->student->telephones->telephone->areaCode = "555-5555";
            }
        }

        // Telephone Number.
        if (empty($jenzastudent[27])) {
            $this->student->student->telephones->telephone->telephoneNumber = "555-5555";
        } else {
            if (helpers::validate_phone($jenzastudent[27])) {
                $this->student->student->telephones->telephone->telephoneNumber = $jenzastudent[27];
            } else {
                $this->student->student->telephones->telephone->telephoneNumber = "555-5555";
            }
        }
        !empty($jenzastudent[28]) ? $this->student->student->telephones->telephone->telephoneExt = $jenzastudent[28] : null;

        $this->student->student->emails = new \stdClass();
        $this->student->student->emails->email = new \stdClass();

        if (!filter_var($jenzastudent[29], FILTER_VALIDATE_EMAIL)) {
            // invalid emailaddress, fall back to jenzaid
            $this->student->student->emails->email->emailAddress = $jenzastudent[7]."@example.com";
        } else {
            $this->student->student->emails->email->emailAddress = $jenzastudent[29];
        }
        
        // TODO: need a fallback, if no email then phone....?
        $this->student->student->emails->email->preferred = "Y";

        // This is a required field so we have to have something.
        !empty($jenzastudent[33]) ?
            $this->student->student->communicationMethod = $jenzastudent[33] :
            $this->student->student->communicationMethod = "Email";

        // Contact Methods.
        $this->student->student->contactMethods = new \stdClass();
        $this->student->student->contactMethods->contactMethod = "None";

        !empty($jenzastudent[36]) ? $this->student->student->internationalStudent = $jenzastudent[36] : null;
        !empty($jenzastudent[38]) ? $this->student->student->employerName = $jenzastudent[38] : null;
        !empty($jenzastudent[40]) ? $this->student->student->youthParticipant = $jenzastudent[40] : null;
        !empty($jenzastudent[41]) ? $this->student->student->genderCode = $jenzastudent[41] : null;
        !empty($jenzastudent[42]) ? $this->student->student->gradeLevel = $jenzastudent[42] : null;

        // $this->student->student->countryOfOrigin
        !empty($jenzastudent[45]) ? $this->student->student->countyOfResidency = $jenzastudent[45] : null;
        !empty($jenzastudent[46]) ? $this->student->student->nativeLanguage = $jenzastudent[46] : null;

        if (!empty($jenzastudent[48]) || !empty($jenzastudent[50]) || !empty($jenzastudent[51])) {
            $this->student->student->profileHolds = new \stdClass();
            $this->student->student->profileHolds->profileHold = new \stdClass();
            !empty($jenzastudent[48]) ? $this->student->student->profileHolds->profileHold->code = $jenzastudent[48] : null;
            !empty($jenzastudent[50]) ? $this->student->student->profileHolds->profileHold->notes = $jenzastudent[50] : null;
            !empty($jenzastudent[51]) ? $this->student->student->profileHolds->profileHold->expirationDate = $jenzastudent[51] : null;
            // $this->student->student->profileHolds->profileHold->expirationType
            // profileHolds/profileHold/holdReason/
            // $this->student->student->profileHolds->profileHold->objectId
        }

        !empty($jenzastudent[53]) ? $this->student->student->countryOfCitizenship = $jenzastudent[53] : null;

        // $this->student->student->telephones->telephone->countryCallingCode
        // $this->student->student->telephones->telephone->release
        // $this->student->student->telephones->telephone->tcpaConsented
        // $this->student->student->addresses->address->effectiveDate
        // $this->student->student->addresses->address->foreign
        // $this->student->student->addresses->address->preferred
        // $this->student->student->addresses->address->release
        // $this->student->student->addresses->address->street0
        // $this->student->student->addresses->address->terminate

        // $this->student->student->additionalAssociation
        // $this->student->student->alertColorOption
        // $this->student->student->associatedProgramOffice
        // $this->student->student->birthCity

        // $this->student->student->cautionaryRequired
        // $this->student->student->certificateName
        // comments/comment
        // $this->student->student->comments = new \stdClass();
        // $this->student->student->comments->comment
        // $this->student->student->comments->commentDate
        // $this->student->student->comments->commentType

        // $this->student->student->countyOfResidencyDate
        // $this->student->student->department
        // $this->student->student->directBillingAccount
        // emails/email

        // $this->student->student->emails->email->typeCode

        // $this->student->student->emails->email->release
        // $this->student->student->emails->email->return

        // $this->student->student->emergencyContactEmail
        // $this->student->student->emergencyContactName
        // $this->student->student->emergencyContactRelationship
        // $this->student->student->emergencyMedicalInformation
        // $this->student->student->employeeOfSchool

        // $this->student->student->enrolledPreviously
        // $this->student->student->enrollmentEthnicity
        // $this->student->student->enrollmentResident
        // enrollmentTimeframes/
        // $this->student->student->enrollmentTimeframe
        // enrolmentGroups/enrolmentGroup
        if (!empty($jenzastudent[35])) {
            $this->student->student->enrolmentGroups = new \stdClass();
            $this->student->student->enrolmentGroups->enrolmentGroup = new \stdClass();
            $this->student->student->enrolmentGroups->enrolmentGroup->groupNumber = $jenzastudent[35];
            $this->student->student->enrolmentGroups->enrolmentGroup->objectId = 0;
        }

        // $this->student->student->groupNumber
        // $this->student->student->objectId

        // $this->student->student->externalAuthenticationID

        // $this->student->student->firstName2

        // interestAreaAssociations/interestAreaAssociation/interestArea/

        // $this->student->student->interestAreaAssociations = new \stdClass();
        // $this->student->student->interestAreaAssociations->code
        // $this->student->student->interestAreaAssociations->objectId

        // $this->student->student->interests

        // $this->student->student->jobTitle

        // learningGoals
        // $this->student->student->learningGoal

        // $this->student->student->nameTagName

        // $this->student->student->newsletterSubscriptions
        // $this->student->student->octNumber
        // $this->student->student->otherEnrollmentTimeframe
        // $this->student->student->otherInterests
        // $this->student->student->otherLearningGoals
        // $this->student->student->otherNames
        // $this->student->student->parentGuardianGivenName
        // $this->student->student->parentGuardianSurname
        // $this->student->student->passportIssuingCountry
        // $this->student->student->preferredContactTime
        // $this->student->student->preferredName
        // $this->student->student->preferredPronouns
        // privacyQuestions/privacyQuestion
        // $this->student->student->privacyQuestions = new \stdClass();
        // $this->student->student->privacyQuestions->answer
        // $this->student->student->privacyQuestions->question

        // $this->student->student->proficiencyExamScore = new \stdClass();
        // $this->student->student->proficiencyExamScore->effectiveDateRangeEnd
        // $this->student->student->proficiencyExamScore->effectiveDateRangeStart
        // $this->student->student->proficiencyExamScore->gradeCode
        // $this->student->student->proficiencyExamScore->code
        // $this->student->student->proficiencyExamScore->objectId
        // $this->student->student->proficiencyExamScore->score
        // $this->student->student->proficiencyExamScore->testDate

        // $this->student->student->profileStatus
        // $this->student->student->SEVISid
        // $this->student->student->salesforceObjectId

        // $this->student->student->socialSecurityNum
        // studentAssociationDetail
        // studentAssociationDetail/association
        // $this->student->student->studentAssociationDetail = new \stdClass();
        // $this->student->student->studentAssociationDetail->code
        // $this->student->student->studentAssociationDetail->associationobjectId
        // $this->student->student->studentAssociationDetail->certification
        // $this->student->student->studentAssociationDetail->studentState
        // studentCategories/studentCategory/

        // studentCredentials/studentCredential/
        // $this->student->student->studentCredentials = new \stdClass();
        // $this->student->student->studentCredentials->studentCredential = new \stdClass();
        // $this->student->student->studentCredentials->studentCredential->credential

        // telephones/telephone/

        // udfValue/userDefinedFieldSpec/
        // $this->student->student->udfValue = new \stdClass();
        // $this->student->student->udfValue->userDefinedFieldSpec = new \stdClass();
        // $this->student->student->udfValue->userDefinedFieldSpec->className
        // $this->student->student->udfValue->userDefinedFieldSpec->objectId
        // $this->student->student->udfValue->userDefinedFieldSpec->userDefinedFieldSpecName
        // $this->student->student->udfValue->userDefinedFieldSpec->userDefinedFieldValue

        // $this->student->student->visaType
    }

    public function init() {
        return;
    }

    /**
     * Process this student data using D1's web services.
     * @param   @object   The student object from the file
     * @return  @object   return web service result
     */
    public function process() {
        // Hijack to update the student
        if ($this->updstu) {
            
            if ($updateresult = $this->update()) {
                $update_msg = "Student ".$this->student->student->firstName1. " ". $this->student->student->lastName.
                " was updated SUCCESSFULLY!";
                return true;
            } else {
                return false;
            }
        }

        // die();
        // The student may have an "associatedGroup". If they do then we need to obtain the group objectId.
        $gobjectId = "";
        if (isset($this->student->student->enrolmentGroups)) {
            if ($gobjectId = $this->get_group()) {
                $this->student->student->enrolmentGroups->enrolmentGroup->objectId = $gobjectId;
            } else {
                error_log('Failed to get group object Id');
            }
        } else {
            // Are we running a student update?? if so then the field above is absent so abort.
            if ($this->updstu) {
                $update_msg = "Student ".$this->student->student->firstName1. " ". $this->student->student->lastName.
                        " *** NO Group Listed ***";
                error_log($update_msg);
                return;
            }
        }
        $studentobjid = false;

        // If the X number exists, return and continue on with enrollments
        if (isset($this->studenttemp->xnumber) && !$this->updstu) {
            return $this->studenttemp->xnumber;
        }

        $pstart = microtime(true);

        // If we want to run enrollments ONLY as the Xnumber is included in the enrollments
        // file then just return the x number.
        // if ($this->enrolonly) {
        //     return $this->studenttemp->xnumber;
        // }

        // ---------- SEARCH ------------
        $searchresult = $this->search();
        // Hijack to update the student
        /*
        if ($this->updstu) {
            $update_msg = "Student ".$this->student->student->firstName1. " ".
                $this->student->student->lastName. " FAILED TO UPDATE!";

            if (property_exists($this->student->student, "enrolmentGroups")) {
                if ($updateresult = $this->update($searchresult)) {
                    $update_msg = "Student ".$this->student->student->firstName1. " ". $this->student->student->lastName.
                        " was updated SUCCESSFULLY!";
                }
            } else {
                $update_msg = "Student ".$this->student->student->firstName1. " ". $this->student->student->lastName.
                    " *** NO LSU MF ID to Update! ***";
            }
            error_log($update_msg);
            return;
        }
        */
        $pend = microtime(true);
        $this->report->timer("search", $pend - $pstart);

        if ($searchresult["callsuccess"]) {
            if ($searchresult["result"]) {

                // Search found a student.
                error_log(" \e[0;32mStudent ".$this->student->student->firstName1. " ". $this->student->student->lastName.
                    " - ".$searchresult['studentNumber']. " was found.");
                $this->student->objectId = $searchresult["objectId"];
                $this->student->webservice = $searchresult;
                $this->studenttemp->xnumber = $searchresult['studentNumber'];
                return $searchresult["objectId"];
            } else {
                // Successfully searched but student was not found, let's create the user.
                $pstart = microtime(true);
                // ---------- CREATE ------------
                $createresult = $this->create();
                $pend = microtime(true);
                $this->report->timer("search", $pend - $pstart);

                if ($createresult["result"] == false) {
                    error_log(" \e[0;31mPS -->> Student ".$this->student->student->firstName1. " ". $this->student->student->lastName. " was NOT found, student was NOT created.");
                    error_log(" \e[0;31mPS -->> Error: ".$createresult["msg"]);
                    // error_log("PS -->> Student ".$this->student->student->firstName1. " ". $this->student->student->lastName. " was NOT found, student was NOT created.");
                    // error_log("PS -->> Error: ".$createresult["msg"]);
                    return false;
                } else {
                    error_log(" \e[0;32mPS -->> Student ".$this->student->student->firstName1. " ".$this->student->student->lastName. " - ". $createresult['studentNumber']." was NOT found, student has been created.");
                    // error_log("PS -->> Student ".$this->student->student->firstName1. " ".$this->student->student->lastName. " - ". $createresult['studentNumber']." was NOT found, student has been created.");
                    // return $createresult["objectId"];
                    $this->studenttemp->xnumber = $createresult['studentNumber'];
                    return $createresult["studentNumber"];
                }
            }
        } else {
            error_log(" \e[0;31mPS -->> Student ".$this->student->student->firstName1. " ". $this->student->student->lastName." failed for SEARCH ". $searchresult["msg"]);
            // error_log("PS -->> Student ".$this->student->student->firstName1. " ". $this->student->student->lastName." failed for SEARCH ". $searchresult["msg"]);
            return false;
        }
    }

    public function post_process($studidentifier, $rowdata, $extras) {
        // Now for the enrollment.
        // Search the bundle list for the code (**** NOTE **** search by objectId)
        $bundle_code = 0;
        $x = substr($studidentifier, 0, 1);

        // Process the enrollment now
        if (!$extras["studentsonly"]) {
            if ($x != "X") {
                // Using the objectId so we'll need to look up the bundle code.
                $bundle_code = helpers::in_bundle_list1($studidentifier);
                $matchon = "objectId";
            } else {
                // Match on the X studentNumber.
                $matchon = "studentNumber";
                $bundle_code = helpers::in_bundle_list1($studidentifier);
            }

            $enrollment = new enrollment($this->report, $studidentifier, $rowdata, $bundle_code, $matchon);
            return $enrollment->process();
        } else {
            // Not doing enrollment, so return true otherwise this will write to the enroll fail report.
            return true;
        }
    }

    /* Search for a student in D1 to see if they exist or not
     * level can be either: Ignore, Shortest, Short, Medium, Long, Full, Privileged
     * @param   @object   The student object from the file
     * @return  @object   return web service result
     */
    public function search() {
        // Get the data needed.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $level = "Medium";
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/searchStudent?informationLevel='. $level. "&_type=json";

        // Set the POST body.
        // *** NOTE *** A general search for email, example below, performs a LIKE search.
        // $params->body = '{"searchStudentRequestDetail": {"studentSearchCriteria": {'.
            // '"email": '. $this->student->student->emails->email->emailAddress.'}}}';

        $params->body = '{"searchStudentRequestDetail": {"studentSearchCriteria": {'.
            '"searchType": "begin_with",'.
            '"email": "'. $this->student->student->emails->email->emailAddress.'"}}}';

        // error_log("Request body for SEARCH: ");
        // error_log($params->body);

        $results = helpers::curly($params);

        if (empty($results)) {
            return array(
                "callsuccess" => false,
                "result" => false,
                "msg" => "Failed to communicate with D1, result set is EMPTY",
                "errorCode" => "Error",
                "sce" => "search"
            );
        }

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Student_Search_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        // Depending on the type of search there are multiple result sets.
        if (property_exists($results, "SRSException")) {
            return array(
                "callsuccess" => false,
                "result" => $results,
                "msg" => "Uh Oh - SRSException",
                "requestdata" => $this->student->student,
                "sce" => "search"
            );

        } else if (property_exists($results, "student")) {
            // Student has been found.
            return array(
                "callsuccess" => true,
                "result" => true,
                "objectId" => $results->student->objectId,
                "studentNumber" => $results->student->studentNumber,
                "sce" => "search"
            );
        
        } else if (property_exists($results, "searchStudentResult")) {
            if ($results->searchStudentResult->paginationResponse->totalCount == 0) {
                return array(
                    "callsuccess" => true,
                    "result" => false,
                    "msg" => "The student does NOT exist or was not found.",
                    "errorCode" => "Error",
                    "sce" => "search"
                );
            }

            if ($results->searchStudentResult->paginationResponse->totalCount == 1) {
                return array(
                    "callsuccess" => true,
                    "result" => $results,
                    "objectId" => $results->searchStudentResult->students->student->objectId,
                    "studentNumber" => $results->searchStudentResult->students->student->studentNumber
                );
            }

            if ($results->searchStudentResult->paginationResponse->totalCount > 1) {
                // Does this student have matching loginId's??
                foreach ($results->searchStudentResult->students->student as $shmuck) {
                    if ($this->student->student->loginId == $shmuck->loginId) {
                        return array(
                            "callsuccess" => true,
                            "result" => $results,
                            "objectId" => $shmuck->objectId,
                            "studentNumber" => $shmuck->studentNumber
                        );
                    }
                }
                // If we get HERE then there is no matching loginId but the email is the same.
                // ....WTF to do?
                // I guess return as false?....it'll create another user account with this
                // email address so........
                error_log("***** WARNING ***** multiple users exist for this email account with NO matching loginId");
                return array(
                    "callsuccess" => false,
                    "result" => false,
                    "errorCode" => "Error",
                    "sce" => "search"
                );
            }
        } else {
            error_log("SS -->> WARNING WARNING D1 has changed their API and has broken everything.  <<--||");
        }
    }

    /**
     * Create the student if they do not exist
     * @param   @object   The student object from the file
     * @return  @object   return web service result
     */
    public function create() {
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewRESTV2/createStudent?sendUsernameAndPasswordEmails=N&_type=json';
        // Set the POST body.
        $params->body = json_encode($this->student);
        // error_log("Request body for CREATE: ");
        // error_log($params->body);

        $results = helpers::curly($params);

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Student_Create_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        if (property_exists($results, "createStudentResult")) {
            $temperror = "Error";
            $results->createStudentResult->responseCode == "Success" ? $temperror = "" : null;

            return array(
                "callsuccess" => true,
                "result" => true,
                "objectId" => $results->createStudentResult->objectId,
                "studentNumber" => $results->createStudentResult->studentNumber,
                "msg" => "",
                "errorCode" => $temperror,
                "sce" => "create"
            );
        } else {
            return array(
                "callsuccess" => true,
                "result" => false,
                "objectId" => "",
                "msg" => $results->SRSException->message,
                "errorCode" => isset($results->SRSException->errorCode) ? $results->SRSException->errorCode : $results->SRSException->cause,
                "sce" => "create"
            );
        }
    }

    /**
     * Update the student record
     * @param   @object   The student object from the file
     * @return  @object   return web service result
     */
    public function get_group() {

        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        $level = "Medium";

        // Set the URL for the post command to get a list of the courses matching the parms.
        $params->url = $s->wsurl.'/webservice/InternalViewREST/getGroup?_type=json';
        // Set the POST body.
        $params->body = '{"getGroupRequestDetail": {"attributeValue": "'.
            $this->student->student->enrolmentGroups->enrolmentGroup->groupNumber.
            '","matchOn": "groupNumber"}}';

        $results = helpers::curly($params);

        if (empty($results)) {
            return array(
                "callsuccess" => false,
                "result" => false,
                "msg" => "Failed to communicate with D1",
                "errorCode" => "Error",
                "sce" => "search"
            );
        }

        // Write the results to a logging file.
        $header = helpers::log_header();

        if (property_exists($results, "SRSException")) {
            $path_to_save = $this->report->reportspath. "/importer/logs/Group_search_FAIL.txt";
            file_put_contents(
                $path_to_save,
                $header.print_r($results, 1).PHP_EOL."Data Used: ".PHP_EOL.$params->body,
                FILE_APPEND
            );
        }

        if (property_exists($results, "SRSException")) {
            return false;
        } else if (property_exists($results, "getGroupResult")) {
            return $results->getGroupResult->group->objectId;
        }
    }

    /**
     * Update the student record
     * @param   @object   The student object from the file
     * @return  @object   return web service result
     */
    public function update($searchresult) {

        $student_objectId = "";
        // $this->student->student->schoolStudentNumber
        // $this->student->student->schoolPersonnelNumber
        
        if ($searchresult["callsuccess"]) {
            if ($searchresult["result"]) {
                // Search found a student.
                $student_objectId = $searchresult["objectId"];
            }
        }

        // The LSU MF ID needs to be updated HOWEVER some students don't have it.
        // So if the field below is not set then.......abort.
        $s = helpers::get_d1_settings();

        $params = new \stdClass();
        // Set the URL for the post command to get a list of the courses matching the parms.
        // $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=objectId&_type=json';
        $params->url = $s->wsurl.'/webservice/InternalViewREST/updateStudent?matchOn=studentNumber&_type=json';
        // Set the POST body.
        /*
        $params->body = '{'.
            '"student": {'.
                '"schoolPersonnelNumber": '. $this->student->student->schoolPersonnelNumber.','.
                '"studentNumber": "'. $this->studenttemp->xnumber.'",'.
                '"objectId": '.$student_objectId.','.
                '"enrolmentGroups": {'.
                    '"enrolmentGroup": {'.
                        '"groupNumber": "'.$this->student->student->enrolmentGroups->enrolmentGroup->groupNumber.'",'.
                        '"objectId": "'.$this->student->student->enrolmentGroups->enrolmentGroup->objectId.'",'.
                        '"associationMode": "create"'.
                    '}'.
                '}'.
            '}'.
        '}';
        */
        $params->body = '{'.
            '"student": {'.
                '"schoolPersonnelNumber": '. $this->student->student->schoolPersonnelNumber.','.
                '"studentNumber": "'. $this->studenttemp->xnumber.'"'.
            '}'.
        '}';

        $results = helpers::curly($params);
        $header = helpers::log_header();

        // This will write the results to a logging file.
        /* Sample result below.
        {
            "updateStudentResult": {
                "status": "OK",
                "responseCode": "Success",
                "objectId": 1431752,
                "studentNumber": "X000044",
                "version": 2
            }
        }
        */
        if (property_exists($results, "updateStudentResult")) {
            $temperror = "Error";
            if ($results->updateStudentResult->responseCode == "Success") {
                return true;
            }
        } else {
            return false;
        }
    }
}
