<?php
class Base {

    public function __construct($paramz = array()) {
        $this->publicfields = array_keys(get_class_vars(get_class($this)));

        foreach($paramz as $pa => $ram){
            if(in_array($pa, $this->publicfields)){
                $this->$pa = $ram;
            }
        }
    }
}
class XMLBase extends Base {

    public function toXML(){
        $xml  = new DOMDOcument();
        $xml->formatOutput = true ;

        $row = $xml->createElement('ROW');

        foreach($this->publicfields as $field){
            $fieldnode = $xml->createElement(strtoupper($field), $this->$field);
            $row->appendChild($fieldnode);
        }

        return $row;
    }

    public static function writeXML($params){
        $obj  = new static($params);
        $file = $obj->file;
        $xml  = new DOMDOcument();
        $xml->formatOutput = true ;
        $xml->preserveWhiteSpace = true;

        if(file_exists($file) && strlen(file_get_contents($file)) > 0){

            $xml->load($file);
            $root = $xml->getElementsByTagName('rows');
            $root = $root->item(0);
        }else{

            touch($file);
            $root = $xml->createElement('rows');
            $xml->appendChild($root);

            file_put_contents($file, $xml->saveXML());
        }
        $new = $xml->importNode($obj->toXML(), true);
        $root->appendChild($new);

        file_put_contents($file, $xml->saveXML());
    }
}


class StudentData extends XMLBase{

    public $idnumber,
            $registration_date,
            $year_class,
            $curric_code,
            $college_code,
            $keypad_id;

    public function __construct($paramz = array()) {
        parent::__construct($paramz);
        $this->file = 'STUDENT_DATA.xml';
    }
}

class Student extends XMLBase{
    public $idnumber,
           $dept_code,
           $course_nbr,
           $section_nbr,
           $credit_hrs,
           $indiv_name,
           $primary_access_id,
           $withhold_dir_flag;

    public function __construct($paramz = array()) {
        $paramz['course_nbr'] = '1001';
        parent::__construct($paramz);
        $this->file = 'STUDENTS.xml';
    }
}

class Datasource {

    public static function getCSV($file){
        $datas = array();
        if(($handle = fopen($file, 'r')) !== false){
            $keys = fgetcsv($handle);
            while(($data = fgetcsv($handle)) !== false){
                $datas[] = array_combine($keys, $data);
            }
            fclose($handle);
        }

        return $datas;
    }
}

class BehatInteraction extends Base {

    public $cand, $res1, $res2, $primary_access_id, $college_code, $credit_hrs;

    static $overallCount = 0;
    static $candVotes = array();
    static $resoVotes = array();
    static $createString = '';
    static $voteString   = '';
    static $resultString = '';

    const NO      = 1;
    const YES     = 2;
    const ABSTAIN = 3;

    public function __construct($params){
        parent::__construct($params);
        $this->res1 = $this->int2ResVote($this->res1);
        $this->res2 = $this->int2ResVote($this->res2);

        $resoptions  = array('No' => 0, 'Yes' => 0, 'Abstain' => 0);
        if(empty(self::$resoVotes['res1'])){
            self::$resoVotes['res1'] = $resoptions;
        }
        if(empty(self::$resoVotes['res2'])){
            self::$resoVotes['res2'] = $resoptions;
        }

    }

    public function int2ResVote($i){

        if($i == self::NO){
            return 'No';
        }elseif($i == self::YES){
            return 'Yes';
        }else{
            return 'Abstain';
        }
    }

    private function candStr($college, $cand){
        return strtolower(sprintf('%s candidate%d', $college, $cand));
    }

    public function getResExistsAndVoteStr(){
        $fullTime      = $this->credit_hrs >= 12;

        $res1VoteStr = $res2VoteStr = $res1ExistsStr = '';
        if($fullTime){
            if($this->res1 != 'Abstain'){
                $res1VoteStr = sprintf('And I click on "%s" "text" in the "//div[@class=\'resolution\' and .//h1[text()=\'res1\']]" "xpath_element"', $this->res1);
            }
            $res1ExistsStr = 'And I should see "Yes" in the "//div[@class=\'resolution\' and .//h1[text()=\'res1\']]" "xpath_element"';
        }else{
            $res1ExistsStr = 'And I should not see "res1"';
        }

        if($this->res2 != 'Abstain'){
            $res2VoteStr = sprintf('And I click on "%s" "text" in the "//div[@class=\'resolution\' and .//h1[text()=\'res2\']]" "xpath_element"', $this->res2);
        }

        $res2ExistsStr = 'And I should see "Yes" in the "//div[@class=\'resolution\' and .//h1[text()=\'res2\']]" "xpath_element"';

        return sprintf("%s\n%s\n%s\n%s\n", $res1ExistsStr, $res2ExistsStr, $res1VoteStr, $res2VoteStr);
    }

    public function getCandExistsAndVoteStr($candStr){
        $college_code = strtolower($this->college_code);

        return sprintf('And I should see "%s candidate1"
            And I should see "%s candidate2"
            And I should see "%s candidate3"
            And I click on "%s" "text" in the "//div[@class=\'candidate\' and .//label[text()=\'%s\']]" "xpath_element"',
                $college_code,
                $college_code,
                $college_code,
                $candStr,
                $candStr
                );
    }

    public function getResVoteConfirmation(){
        $res1     = '';
        $fullTime = $this->credit_hrs >= 12;

        if($fullTime){
            $res1 = sprintf('Then I should see "You voted %s on resolution: res1"', $this->res1);
            self::$resoVotes['res1'][$this->res1]++;
        }

        $res2 = sprintf('Then I should see "You voted %s on resolution: res2"', $this->res2);
        self::$resoVotes['res2'][$this->res2]++;
        return sprintf("%s\n%s\n", $res1, $res2);
    }

    public function updateCandidateCounts(){
        $name = strtolower($this->college_code);
        if(empty(self::$candVotes[$this->college_code])){

            self::$candVotes[$this->college_code] = array(
                $name.'1' => 0,
                $name.'2' => 0,
                $name.'3' => 0
            );
        }
        self::$candVotes[$this->college_code][$name.$this->cand]++;
    }

    public function writeBehat(){
        $candStr = $this->candStr($this->college_code, $this->cand);

        if($this->primary_access_id == 'facadv001' || $this->primary_access_id == 'commissioner001'){
            return;
        }
        self::$overallCount++;
        $this->updateCandidateCounts();

        $voteSteps = sprintf('
            And I log in as "%s"
            And I follow "My home"
            And I click on "Ballot for Fall [General Election]" "link"
            %s

            %s

            And I press "Review Choices"
            Then I should see "You voted for %s"
            %s

            And I click on "Vote" "text"
            Then I should see "Thanks!"
            Then I should see "Number of votes cast so far %d"
            And I log out%s',
                $this->primary_access_id,
                $this->getCandExistsAndVoteStr($candStr),
                $this->getResExistsAndVoteStr(),
                $candStr,
                $this->getResVoteConfirmation(),
                self::$overallCount,
                "\n\n"
                );

        self::$voteString .= $voteSteps;
        return;
    }

    public function createCandidate(){
        if(empty(self::$createString)){
            self::$createString .= $this->resText();
        }

        self::$createString .= "\n\n";

        self::$createString .= sprintf(
                'And I set the following fields to these values:
                    | paws ID of Candidate | %s |
                    | Office the Candidate is running for | College Council President [%s] |
                    And I press "save_candidate"', $this->primary_access_id, $this->college_code);
    }

    private function resText(){
        return 'And I set the following fields to these values:
         | Title of Resolution | res1 |
         | Resolution Text | This resolution is open only to Full-time students |
      And I click on "Restrict to Full Time?" "text"
      And I press "save_resolution"

      And I set the following fields to these values:
         | Title of Resolution | res2 |
         | Resolution Text | This resolution is open to all students |
      And I press "save_resolution"';
    }

    public function buildCandResultStr(){

        foreach(self::$candVotes as $college => $cands){
            foreach($cands as $cand => $votes){
                self::$resultString .= "\n";
                self::$resultString .= sprintf('Then I should see "%d" in the "%s" "table_row"', $votes, $this->candStr($college, substr($cand, -1)));
            }
        }
    }

    public function buildResResultStr(){
        print("got here");
        $thenIshouldSee = function ($vote, $r){
            if($vote == 'Yes'){
                $key = 2;
            }elseif($vote == 'No'){
                $key = 3;
            }else{
                $key = 4;
            }
            $xpath = sprintf("//tr[td[text() = 'res%s']]/td[%s]", $r, $key);
            return sprintf("Then I should see \"%s\" in the \"%s\" \"xpath_element\"\n", self::$resoVotes['res'.$r][$vote], $xpath);
        };

        self::$resultString .= "\n\n";

        foreach(range(1,2) as $r){
            foreach(array('Yes', 'No', 'Abstain') as $vote){
                self::$resultString .= $thenIshouldSee($vote, $r);
            }
        }
    }
}


$datas = Datasource::getCSV('datasource.csv');
foreach($datas as $data){
    Student::writeXML($data);
    StudentData::writeXML($data);
    $behat = new BehatInteraction($data);
    if(!empty($data['cand'])){
        $behat->writeBehat();
    }else{
        $behat->createCandidate();
    }
}
$behat->buildResResultStr();
$behat->buildCandResultStr();
BehatInteraction::$createString .= "\n\nAnd I log out";

file_put_contents('vote.behat', BehatInteraction::$voteString);
file_put_contents('create.behat', BehatInteraction::$createString);
file_put_contents('result.behat', BehatInteraction::$resultString);



