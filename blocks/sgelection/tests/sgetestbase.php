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
 * Local test helpers, generators
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once $CFG->libdir.'/testing/generator/data_generator.php';
require_once 'classes/resolution.php';
require_once $CFG->dirroot.'/blocks/sgelection/lib.php';
sge::require_db_classes();

abstract class block_sgelection_base extends advanced_testcase{

    public function setup(){
        $this->resetAfterTest();
    }

    protected function create_candidate($user = null, election $election = null, office $office = null){

        global $DB;
        if($user == null){
            $user = $this->getDataGenerator()->create_user();
        }

        if($election == null){
            if($DB->count_records(election::$tablename) == 0){
                // create a new election if none exist
                $election = $this->create_election();
            }else{
                // otherwise, choose an existing record at random
                $elections = $DB->get_records(election::$tablename);
                $nokeys = array_values($elections);
                $limit = count($nokeys) - 1;
                $idx = rand(0,$limit);
                $election = new election($nokeys[$idx]);
            }
        }

        if($office == null){
            if($DB->count_records(office::$tablename) == 0){
                // create a new office if none exist
                $office = $this->create_office();
            }else{
                // otherwise, choose an existing record at random
                $offices = $DB->get_records(office::$tablename);
                $nokeys = array_values($offices);
                $limit = count($nokeys);
                $idx = rand(0,$limit-1);
                $office = new office($nokeys[$idx]);
                $office->save();
            }
        }
        $c = new stdClass();
        $c->userid = $user->id;
        $c->office = $office->id;
        $c->affiliation = "nono";
        $c->election_id = $election->id;

        $candidate = new candidate($c);
        $candidate->save();

        return $candidate;
    }

    protected function create_election($params = null, $current = false){

        $startend = function($time, $current){
            $halfinterval  = rand(86400, 31536000);
            $start_date = $time - $halfinterval;
            $end_date   = $current ? $time + $halfinterval : $start_date + $halfinterval;

            return array($start_date, $end_date);
        };

        if(is_object($params) || is_array($params)){
            $params = (array)$params;
            if($current){
                list($start, $end) = $startend(time(), $current);
                $params['start_date'] = $start;
                $params['end_date']   = $end;
            }
            $params['hours_census_start'] = $params['start_date'] - 86400;
            $election = new election($params);
            $election->save();
            return $election;
        }

        $e = new stdClass();
        $semester = $this->create_semester();
        $e->semesterid = $semester->id;
        $e->name       = array_rand(array("Spring", "Summer", "Fall"));

        list($start, $end) = $startend(time(), $current);
        $e->start_date = $start;
        $e->end_date   = $end;
        $e->hours_census_start = $e->start_date - 86400;
        $e->hours_census_complete = $e->hours_census_start + 200;
        $e->thanksforvoting = 'THX';

        $election = new election($e);
        $election->save();

        return $election;
    }

    protected function create_semester(){
        global $DB;
        $s = new stdClass();
        $s->year = rand(2012, 2016);
        $s->name = array_rand(array('Fall', 'Summer', 'Spring'), 1);
        $s->campus = 'Main';
        $s->classes_start = rand(1407000000, 1408000000);
        $s->grade_due     = rand(1408000001, 1409000000);

        $sem = new ues_semester();
        $sem->fill_params((array)$s)->save();
        return $sem;
    }

    protected function create_office($params = null){
        if(is_object($params) || is_array($params)){
            $office = new office($params);
            $office->save();
            return $office;
        }

        $offices = array(
            "Abbess","Admiral","Aesymnetes","Agonothetes","Agoranomos","Air","Aircraftman","Akhoond","Allamah","Amban","Amir","Amphipole","Anax","Apodektai","Apostle","Arahant","Archbishop","Archdeacon","Archduchess","Archimandrite","Archon","Archpriest","Argbadh","Arhat","Asapatish","Aspet","Assistant","Assistant","Assistant","Associate","Aswaran","Augusta","Ayatollah","Baivarapatish","Bapu","Baron","Basileus","Beauty","Bishop","Blessed","Begum","Buddha","Cardinal","Cardinal-nephew","Caesar","Caliph","Captain","Captain","Catholicos","Centurion","Chairman","Chakravartin","Chancellor","Chanyu","Chhatrapati","Chief","Chiliarch","Chorbishop","Choregos","Coiffure","Comes","Commissioner","Concubinus","Consort","Consul","Corporal","Corrector","Councillor","Count","Count","Dàifu","Dalai","Dame","Dathapatish","Deacon","Dean","Decurio","Desai","Despot","Dilochitès","Dikastes","Dimoirites","Distinguished","Divine","Diwan","Don","Duchess","Dux","Earl","Earl","Ecumenical","Elder","Emperor","En","Ephor","Epihipparch","Esquire","Evangelist","Exarch","Fan-bearer on the Right Side of the King","Faqih","Fellow","Fidalgo","Fidei","Field","Foreign","Furén","Fürst","Ganden","Generalissimo","God's Wife","Gong","Goodman","Gothi","Governor","Governor-General","Grand","Grand","Grand","Grand","Grand","Guardian","Hadrat","Handsome","Haty-a","Hazarapatish","Headman","Hegumen","Hekatontarchès","Hellenotamiae","Herald","Your Excellency","Your Grace","Your Highness","Your Illustrious Highness","Your Imperial Highness","Your Imperial Majesty","Your Ladyship","Your Lordship","Your Majesty","Your Royal Highness","Your Serene Highness","Herzog","Hidalgo","Hierodeacon","Hieromonk","Hierophant","High","Hipparchus","His","Hojatoleslam","Ilarchès","Imam","Imperator","Inquisitor","Jagirdar","Jiàoshòu","Junior","Kanstresios","Karo","Khawaja","King","King","Kolakretai","Kumar","Lady","Lady","Lady","Laoshi","Lecturer","Legatus","Leading","Lochagos","Lonko","Lord","Lord","Lord","Lugal","Madam","Magister","Magister","Maha-kshtrapa","Maharaja","Maharana","Maharao","Mahatma","Major","Malik","Mandarin","Marzban","Master","Master","Mawlawi","Mayor","Metropolitan","Mirza","Monsignor","Mullah","Naib","Nakharar","National","Navarch","Nawab","Nawabzada","Nizam","Nobilissimus","Nomarch","Nuncio","Nushi","Optio","Palatine","Pastor","Patriarch","Patroon","Paygan","Peace","Peshwa","Pharaoh","Pir","Polemarch","Pope","Praetor","Presbyter","President","Presiding","Priest","Primate","Prime","Prince","Princeps","Principal","Prithvi-vallabha","Professor","Professor","Propagator","Protodeacon","Proxenos","Prytaneis","Pursuivant","Rabbi","Raja","Rajmata","Reader","Recipient","Recipient","Rector","Reverend","Roju","Sacristan","Saint","Sakellarios","Sahib","Satrap","Savakabuddha","Sayyadina","Sebastokrator","Sebastos","Secretary","Selected","Senior","Senior","Sergeant","Servant","Service","Shah","Shaman","Shifu","Shigong","Shimu","Shofet","Shogun","Sibyl","Somatophylax","Soter","Spahbod","Sparapet","Sri","Starosta","Strategos","Subedar","Sultan","Sunim","Swami","Syntagmatarchis","Tagmatarchis","Taitai","Talented","Tanuter","Taxiarch","Temple","Tenzo","Tetrarch","Thakore","Theorodokoi","Theoroi","The","The","Tirbodh","Tóngzhi","Toqui","Towel","Tribune","Trierarch","Tsar","Unsui","Upasaka","Upajjhaya","Vajracharya","Varma","Venerable","Vicar","Voivode","Weiyuán","Xiaojie","Xiansheng","Xiaozhang","Xry","Yisheng","Yishi","Yuvraj","Zamindar","Zongshi","Zhuxi"
        );

        $colleges = array(
            "College of Agriculture","College of Art & Design","E. J. Ourso College of Business","School of the Coast & Environment","College of Engineering","College of Human Sciences & Education","College of Humanities & Social Sciences","Manship School of Mass Communication","College of Music & Dramatic Arts","College of Science","University College",
        );

        $o = new stdClass();
        $o->name = $offices[rand(0, count($offices)-1)];
        $random = rand(0,999) % 5 == 0;
        $o->college = $random ? $colleges[rand(0, count($colleges)-1)] : '';

        $o->number = $random ? rand(1, 25) : 1;
        $o->weight = rand(0,9999);
        $office = new office($o);
        $office->save();
        return $office;
    }

    protected function create_resolution($params = null, $eid = null){
        if(is_object($params) || is_array($params)){
            $res = new resolution($params);
            $res->save();
            return $res;
        }

        if(!$eid){
            $election = $this->create_election();
            $eid = $election->id;
        }

        $titles = array(
            "A Resolution Regarding Google Apps and Disability Accessibility","A Resolution to Allocate Funds in Support of the Beat MSU Pep Rally","A Resolution to Enact and Codify the Central Student Government Interns Program","A Resolution to Support Campus Wide Event Publicity / A Campus Calendar","A Resolution to Allocate Funds in Support of the Port Huron Statement 50th Anniversary Conference","A Resolution to Add an Election Code to the Compiled Code","A Resolution to Support the Inclusion of Bullying, Cyberbullying, and Cyberharassment as a Violation of the Statement of Student Rights and Responsibilities","A Resolution to Enhance the Central Student Government Chambers","A Resolution to Enhance the Wireless Connectivity Around Campus to Further Serve Student Needs","A Resolution to Amend the Operating Procedures to Update the Attendance Policy","A Resolution to Support the Placement of a Clause in Statement of Student Rights Regarding the Medical Amnesty Policy at the University of Michigan","A Resolution to Update the Statement of Student Rights and Responsibilities to Reflect the \"Michigan Student Assembly's\" Name Change to \"Central Student Government\"","A Resolution to Honor Regent Olivia P. Maynard","A Resolution to Honor Regent S. Martin Taylor","A Resolution to Create the Central Student Government Entrepreneurship Commission","A Resolution to Amend and Update the Compiled Code","A Resolution to Change the Operating Procedures to Improve Guest Speakers","A Resolution to Officially Authorize Jeremy Keeney and Lukas Garske to Negotiate on Behalf of CSG and the Student Body Regarding Ann Arbor's Early Lease Signing Ordinance","A Resolution to Support 'Finals Survival 101'","A Resolution to Host a CSG Diag Day","A Resolution to Recommend an Amendment to the Statement of Student Rights and Responsibilities ","A Resolution to Support the Food Recovery Network","A Resolution to Allocate Funds in Support of Optimize and Social Entrepreneurship","A Resolution to Enact the Winter 2013 CSG Budget","A Resolution to Allocate Funds in Support of the MHacks 2013 Hackathon","A Resolution to Amend the Operating Procedures to Include Contact and Campaign Information ","A Resolution to Provide Adequate Parking for Mopeds on Central Campus","A Resolution to Amend the Complied Code's Finance Section","A Resolution to Update the Election Code","A Resolution to Define Task Force","A Resolution to Co-Sponsor the 2013 Music Matters Sprint Concert and Festivities ","A Resolution to Allocate Funds in Support of Additional Water Refill Stations Purchases","A Resolution Regarding Lecturers' Employee Organization and Teaching Equality","A Resolution Declaring CSG's Support of Medical Amnesty Awareness","A Resolution to Expend Funds for a CSG St. Patrick's Day Tailgate","A Resolution to Support the We Bleed Too Campaign","A Resolution to Create the Student Entrepreneurship Funding Vehicle","A Resolution to Amend The Compiled Code To Give the Chair Of The Disabilities Affairs Commission First Right of Refusal to Sit On The Services for Students with Disabilities Advisory Board And The Council for Disability Concerns","A Resolution to Officially Conclude Jeremy Keeney's and Lukas Garske's Lease Ordinance Negotiations on Behalf of CSG","A Resolution to Limit Real Time Election Results","A Resolution for Student Input in Student Ticketing Policies Set by the University of Michigan Athletic Department","A Resolution Opposing the New Student Seating Policy at Football Games","A Resolution on the Cost of Football Tickets","A Resolution on Roll Call Vote","A Resolution on CSG Mandatory Retreat","A Resolution to Fund Fall Retreat","A Resolution to Preserve the Impartiality of the University Election Commission","A Resolution to Enact the Fall 2013 Central Student Government Budget","A Resolution to Amend the Compiled Code to Remove the Summer Assembly Funding From the Fall Budget","A Resolution to Amend the Compiled Code to Remove the Emergency Executive Fund From the Semesterly Budget","A Resolution to Co-Sponsor the 2013 Mhacks Hackathon","A Resolution to Co-Sponsor the 2014 SAAN Conference","A Resolution to Allocate Funds for the Transportation to the October 15th Supreme Court Case","A Resolution to Amend the Fall 2013 CSG Budget","A Resolution to Co-Sponsor the 2013 Powershift Conference University of Michigan Delegation","A Resolution Amending the Title of the Compiled Code","A Resolution to Aid Students in the Off-Campus Housing Search","A Resolution to Set the CSG Fall Election Dates","A Resolution to Support Male Survivors of Sexual Violence","A Resolution to Reduce Election Complaints and Clarify the Central Student Government Election Code","A Resolution to Replace the Demerit System of the Election Code","A Resolution to Beat State","A Resolution to Reform Article VI: The Election Code","A Resolution to Close A Loophole in Operating Procedures Rule XII.D. Amendments","A Resolution to Modify the Absence Excusal Rules","A Resolution to Allocate Funds From the Legislative Discretionary Account for the Late Night Bus Route","A Resolution to Co-Sponsor the Typhoon Relief Efforts with the Filipino American Student Association","A Resolution Calling for a Special Meeting for the Purpose of Having a Mandatory CSG Retreat","A Resolution to Update the Statement of Rights and Responsibilities","A Resolution to Enact the Winter 2014 CSG Budget","A Resolution to Allocate Funds for the Music Matters 2014 Concert and Springfest","A Resolution to Amend the Statement of Rights and Responsibilities: Part II","A Resolution to Provide Money and Support for the WTF Commission's Water Bottle Refilling Station Project","A Resolution to Welcome and Congratulate Incoming President Mark Schlissel","A Resolution to Increase Minority Student Enrollment","A Resolution to Encourage Assembly Members to organize and Participate in a Day of Service per Semester","A Resolution to Co-Sponsor the Counseling and Psychological Services Year-End Celebration","A Resolution to Amend the Operating Procedures","A Resolution to Update CSG's 501(C)(3) Status","A Resolution to Stand in Solidarity with the #BBUM Movement"
        );

        $paras = rand(0,15);
        $curl = new curl();
        $parameterizedurl = sprintf("http://loripsum.net/api/%s/headers/", $paras);
        $ipsum = $curl->get($parameterizedurl);

        $params = array(
            'election_id' => $eid,
            'title'       => $titles[rand(0, count($titles)-1)],
            'text'        => $ipsum,
            'restrict_fulltime' => 1,
            'link'        => 'http://example.com',
        );

        $resolution = new resolution($params);
        $resolution->save();
        return $resolution;
    }

    public function test_my_generators_basic(){

        $classes = array('office', 'election', 'resolution', 'candidate');

        foreach($classes as $class){
            global $DB;
            $createstring = "create_".$class;
            $instance = $this->$createstring();

            $this->assertInstanceIsValidAndPersisted($instance, $class);
        }
    }

    private function assertInstanceIsValidAndPersisted($instance, $class){
        global $DB;
        $this->assertInstanceOf($class, $instance);

        // We need an instance id for this test.
        $this->assertTrue(isset($instance->id), "Missing attribute 'id' for instance of class {$class}.");
        $row = $DB->get_record($class::$tablename, array('id'=>$instance->id));

        // Did we get a record?
        $this->assertInstanceOf('stdClass', $row, sprintf("Database lookup did not result in an instance of stdClass, as was expected."));

        // Ensure that all the values in the DB for the instance->id row both exist and are equal to the obj attrs.
        foreach((array)$row as $k => $v){
            $errmiising = sprintf("Missing attribute %s::%s", $class, $k);
            $this->assertTrue(isset($instance->$k), $errmiising);

            $errnoteql  = sprintf("%s::%s not equal; DB: %s, instance: %s", $class, $k, $v, $instance->$k);
            $this->assertEquals($v, $instance->$k, $errnoteql);
        }
    }
    public function test_election_generator(){
        $class = 'election';
        $params = array(
            'name'       => 'gruelling',
            'semesterid' => 3,
            'start_date' => time() + 1000,
            'end_date'   => time() + 10000,
            'hours_census_start' => time() + 1000 - 86400,
            'hours_census_complete' => time() + 1000 - 86300,
            'thanksforvoting' => 'Thanks',
        );
        $fn = 'create_'.$class;

        // Test permutation 1.
        $instance = $this->$fn($params);
        $this->assertInstanceIsValidAndPersisted($instance, 'election');
        unset($instance);

        // Test permutation 2.
        $time = time();
        $instance = $this->$fn($params, true);
        $this->assertInstanceIsValidAndPersisted($instance, 'election');
        $this->assertLessThanOrEqual($time, $instance->start_date, sprintf("The time now is %s, ", $time, strftime('%F %T', $time)));
        $this->assertGreaterThanOrEqual($time, $instance->end_date, sprintf("The time now is %s, ", $time, strftime('%F %T', $time)));
        unset($instance);

        $instance = $this->$fn(null, true);
        $this->assertInstanceIsValidAndPersisted($instance, 'election');
        $this->assertLessThanOrEqual($time, $instance->start_date, sprintf("The time now is %s, ", $time, strftime('%F %T', $time)));
        $this->assertGreaterThanOrEqual($time, $instance->end_date, sprintf("The time now is %s, ", $time, strftime('%F %T', $time)));
        unset($instance);
    }

    public function test_office_generator(){
        $params = array(
            'name'  => 'President',
            'college' => '',
            'number'  => 1,
            'weight'  => 4
        );

        $instance = $this->create_office($params);
        $this->assertInstanceOf('office', $instance);
        $this->assertInstanceIsValidAndPersisted($instance, 'office');
    }
}
