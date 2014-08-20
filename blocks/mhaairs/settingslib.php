<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 * @author     Darko Miletic
 */

defined('MOODLE_INTERNAL') or die();

global $CFG;
require_once($CFG->dirroot.'/blocks/mhaairs/lib.php');

class admin_setting_configmulticheckbox_mhaairs extends admin_setting_configmulticheckbox {

    public function __construct($name, $heading, $description) {
        parent::__construct($name, $heading, $description, null, null);
    }

    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }
        $result = false;
        $services = block_mhaairs_getlinks('services', true);
        if (is_array($services) && isset($services['Tools'])) {
            foreach ($services['Tools'] as $item) {
                $choices[$item['ServiceID']] = '&nbsp;&nbsp;'.$item['ServiceName'];
            }
            asort($choices);
            $this->choices = $choices;
            $result = true;
        }
        return $result;
    }

    public function output_html($data, $query='') {
        if ($this->load_choices()) {
            return parent::output_html($data, $query);
        }

        $visiblename = get_string('services_displaylabel', 'block_mhaairs');
        $description = get_string('service_down_msg'     , 'block_mhaairs');
        return format_admin_setting($this, $visiblename, '', $description, false, '', '');
    }
}

/**
 * Class admin_setting_configcheckbox_mhaairs
 */
class admin_setting_configcheckbox_mhaairs extends admin_setting_configcheckbox {
    /**
     * @var moodle_url|null
     */
    private $url = null;

    /**
     * @return bool
     */
    protected function disablewrite() {
        $result = false;
        if (!during_initial_install()) {
            global $PAGE;
            $url = new moodle_url('/admin/settings.php', array('section' => 'blocksettingmhaairs'));
            if ($PAGE->url->compare($url)) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $defaultsetting
     * @param string $yes
     * @param string $no
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $yes='1', $no='0') {
        parent::__construct($name, $visiblename, $description, $defaultsetting, $yes, $no);
        $this->nosave = $this->disablewrite();
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function write_setting($data) {
        global $CFG;
        if ($this->nosave && ((string)$data === $this->yes)) {
            require_once($CFG->dirroot.'/blocks/mhaairs/loglib.php');
            block_mhaairs_log::instance()->deleteall();
        }
        return parent::write_setting($data);
    }
}
