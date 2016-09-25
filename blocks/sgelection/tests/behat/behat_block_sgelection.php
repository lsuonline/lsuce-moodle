<?php
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode;

class behat_block_sgelection extends behat_base{
   /**
     * @Given /^the following elections exist:$/
     */
    public function theFollowingElectionsExist(TableNode $table)
    {

        $rows = $table->getRows();

        // Anchor date object for all derivatives.
        $date = new DateTime('now');

        // helper fn to compute date offsets [from munutes]
        $offset = function($minutes, $unit) {
            $now      = new DateTime('now');
            $add      = $minutes < 0 ? false : true;
            $prefix   = $unit == 'D' ? 'P' : 'PT';
            $interval = new DateInterval(sprintf("%s%d%s",$prefix, abs($minutes), $unit));
            return $add ? $now->add($interval) : $now->sub($interval);
        };

        // Setup default dates.
        $id_start_date_day = $date;
        $id_hours_census_start_minute = $offset(1, 'M');
        $id_end_date_day = $offset(1, 'D');

        // Override default start, census and end dates, if provided.
        foreach($rows as $k => $row){
            $options = array('id_hours_census_start_minute', 'id_start_date_day', 'id_end_date_day');
            if(in_array($row[0], $options)){
                $elements = explode('_', $row[0]);
                $tail     = array_pop($elements);
                $unit     = strtoupper(substr($tail, 0, 1));
                $$row[0]  = $offset($row[1], $unit);
                unset($rows[$k]);
            }
        }

        $rows[] = array('id_hours_census_start_minute', intval($id_hours_census_start_minute->format('i')));
        $rows[] = array('id_start_date_day', intval($id_start_date_day->format('d')));
        $rows[] = array('id_end_date_day', intval($id_end_date_day->format('d')));

        $table->setRows($rows);

        $this->configure_block();
        return array(
            new Given('I follow "' . get_string('create_election', 'block_sgelection') . '"'),
            new Given('I set the following fields to these values:', $table),
            new Given('I press "' . get_string('savechanges') . '"')
        );
    }

    /**
     * @Given /^I open the election$/
     */
    public function iOpenTheElection()
    {
        global $DB;
        $election = $DB->get_record('block_sgelection_election', array('id'=>1));
        $election->start_date = time();
        $DB->update_record('block_sgelection_election', $election);
    }


    public function configure_block(){
        set_config('census_window', 0, 'block_sgelection');
    }

    /**
     * @Given /^I configure ues$/
     */
    public function iConfigureUes(){
        // Config provider.
        $xml = new ProviderConfigBase();
        $xml->setConfigs();

        // Configure UES.
        $ues = new UesConfig();
        $ues->setConfigs();
    }


    /**
     * @Given /^I initialize ues users$/
     */
    public function iInitializeUesUsers(){
        $basepath = get_config('local_xml', 'xmldir');
        var_dump($basepath);
        $xml = new DOMDocument();
        $xml->loadXML(file_get_contents($basepath.'STUDENTS.xml'));
        $usernames = $xml->getElementsByTagName('PRIMARY_ACCESS_ID');
        $saved = array('username');

        foreach($usernames as $username){
            $name = $username->nodeValue;
            if(in_array($name, $saved)){
                continue;
            }
            $saved[] = $username->nodeValue;
        }
        $gen = new behat_data_generators();
        $table = new TableNode(implode("\n", $saved));
        $gen->the_following_exist('users', $table);
    }

    /**
     * @Given /^I run cron$/
     */
    public function iRunCron(){
        // Cron dependencies.
        require_once(__DIR__ . '/../../../../lib/cronlib.php');
        global $CFG;
        $CFG->local_mr_redis_server = 'localhost';
        mtrace("RUNNING CRON from behat -------------------------------\n");
        cron_run();
    }

    /**
     * @When /^I go to "([^"]*)"$/
     */
    public function iGoTo($arg1)
    {
        $this->getSession()->visit($this->locate_path($arg1));
    }
}




class UesConfig {

    //enrol/ues settings
    private $config = array(
        array('course_form_replace',       1, 'enrol_ues'),
        array('course_fullname',           '{year} {name} {department} {session}{course_number} for {fullname}', 'enrol_ues'),
        array('course_restricted_fields',  'groupmode,groupmodeforce,lang ', 'enrol_ues'),
        array('course_shortname',          '{year} {name} {department} {session}{course_number} for {fullname}', 'enrol_ues'),
        array('cron_hour',                 2, 'enrol_ues'),
        array('cron_run',                  0, 'enrol_ues'),
        array('editingteacher_role',       3, 'enrol_ues'),
        array('email_report',              0, 'enrol_ues'),
        array('enrollment_provider',       'xml', 'enrol_ues'),
        array('error_threshold',           100, 'enrol_ues'),
        array('grace_period',              3600, 'enrol_ues'),
        //array('lastcron',                  0, 'enrol_ues'),
        array('process_by_department',     1, 'enrol_ues'),
        array('recover_grades',            1, 'enrol_ues'),
        array('running',                   0, 'enrol_ues'),
        //array('starttime',                 0, 'enrol_ues'),
        array('student_role',              5, 'enrol_ues'),
        array('sub_days',                  60, 'enrol_ues'),
        array('teacher_role',              4, 'enrol_ues'),
        array('user_auth',                 'cas', 'enrol_ues'),
        array('user_city',                 'anywhere', 'enrol_ues'),
        array('user_confirm',              1, 'enrol_ues'),
        array('user_country',              'NA', 'enrol_ues'),
        array('user_email',                '@example.com', 'enrol_ues'),
        array('user_lang',                 'en', 'enrol_ues'),
        array('version',                   2013081007, 'enrol_ues'),
    );

    public function getConfigs(){
        return $this->config;
    }

    public function setConfigs(){
        foreach($this->config as $conf){
            set_config($conf[0], $conf[1], $conf[2]);
        }
    }
}

class ProviderConfigBase {

    //local provider settings
    private $config = array(
        array('anonymous_numbers',       1, 'local_xml'),
        array('degree_candidates',           1, 'local_xml'),
        array('sports_information',  1, 'local_xml'),
        array('student_data',          1, 'local_xml'),
        array('version',                  2013081000, 'local_xml'),
    );

    public function __construct(){
        global $CFG;
        $relativepath = '/blocks/sgelection/tests/behat/enrolments/';
        $this-> config[] = array('xmldir', $CFG->dirroot.$relativepath, 'local_xml');
    }

    public function setConfigs(){
        foreach($this->config as $conf){
            set_config($conf[0], $conf[1], $conf[2]);
        }
    }
}