<?php

interface online_semester_codes {
    const FALL1 = '1L';
    const FALL2 = '1P';
    const SPRING1 = '2D';
    const SPRING2 = '2L';
    const SUMMER1 = '3D';
    const SUMMER2 = '1D';
}

interface online_institution_codes {
    const ONLINE_SEM = 'CLSB';
    const LAW_SEM = 'LAWB';

    const ONLINE_FINAL = 'CLSE';
    const LAW_FINAL = 'LAWE';

    const ONLINE_CAMPUS = '01';
    const LAW_CAMPUS = '08';

    const ONLINE_INST = '1590';
    const LAW_INST = '1595';
}

abstract class online_source implements online_institution_codes, online_semester_codes {
    /**
     * An ONLINE source requires these
     */
    var $serviceId;
    var $username;
    var $password;
    var $wsdl;

    function __construct($username, $password, $wsdl, $serviceId) {
        $this->username = $username;
        $this->password = $password;
        $this->wsdl = $wsdl;
        $this->serviceId = $serviceId;
    }

    private function build_parameters(array $params) {
        return array (
            'widget1' => $this->username,
            'widget2' => $this->password,
            'serviceId' => $this->serviceId,
            'parameters' => $params
        );
    }

    private function escape_illegals($response) {
        $convertables = array(
            '/s?&s?/' => ' &amp; ',
        );
        foreach ($convertables as $pattern => $replaced) {
            $response = preg_replace($pattern, $replaced, $response);
        }
        return $response;
    }

    private function clean_response($response) {
        $clean = $this->escape_illegals($response);

        $contents = <<<XML
<?xml version='1.0'?>
<rows>
    $clean
</rows>
XML;
        return $contents;
    }

    public function invoke($params) {
        $client = new SoapClient($this->wsdl, array('connection_timeout' => 3600));

        $invoke_params = $this->build_parameters($params);

        $response = $client->invoke($invoke_params)->invokeReturn;

        return new SimpleXmlElement($this->clean_response($response));
    }

    public function parse_date($date) {
        $parts = explode('-', $date);
        return mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
    }

    public function parse_name($fullname) {
        list($lastname, $fm) = explode(',', $fullname);
        $other = explode(' ', trim($fm));

        $first = $other[0];

        if (strlen($first) == 1) {
            $first = $first . ' ' . $other[1];
        }

        return array($first, $lastname);
    }

    public function encode_semester($semester_year, $semester_name) {

        $partial = function ($year, $name) {
            return sprintf('%d%s', $year, $name);
        };

        switch ($semester_name) {
            case 'First Fall': return $partial($semester_year + 1, self::FALL1);
            case 'Second Fall': return $partial($semester_year + 1, self::FALL2);
            case 'First Spring': return $partial($semester_year, self::SPRING1);
            case 'Second Spring': return $partial($semester_year, self::SPRING2);
            case 'First Summer': return $partial($semester_year, self::SUMMER1);
            case 'Second Summer': return $partial($semester_year + 1, self::SUMMER2);
        }
    }
}

abstract class online_teacher_format extends online_source {
    public function format_teacher($xml_teacher) {
        $primary_flag = trim($xml_teacher->PRIMARY_INSTRUCTOR);

        list($first, $last) = $this->parse_name($xml_teacher->INDIV_NAME);

        $teacher = new stdClass;

        $teacher->idnumber = (string) $xml_teacher->LSU_ID;
        $teacher->primary_flag = (string) $primary_flag == 'Y' ? 1 : 0;

        $teacher->firstname = $first;
        $teacher->lastname = $last;
        $teacher->username = (string) $xml_teacher->PRIMARY_ACCESS_ID;

        return $teacher;
    }
}

abstract class online_student_format extends online_source {
    const AUDIT = 'AU';

    public function format_student($xml_student) {
        $student = new stdClass;

        $student->idnumber = (string) $xml_student->LSU_ID;
        $student->credit_hours = (string) $xml_student->CREDIT_HRS;

        if (trim((string) $xml_student->GRADING_CODE) == self::AUDIT) {
            $student->student_audit = 1;
        }

        list($first, $last) = $this->parse_name($xml_student->INDIV_NAME);

        $student->username = (string) $xml_student->PRIMARY_ACCESS_ID;
        $student->firstname = $first;
        $student->lastname = $last;
        $student->user_ferpa = trim((string)$xml_student->WITHHOLD_DIR_FLG) == 'P' ? 1 : 0;

        return $student;
    }
}
