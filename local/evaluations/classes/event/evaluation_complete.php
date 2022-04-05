<?php
/**
 * ************************************************************************
 *             The report_inactivity report viewed event class.
 * ************************************************************************
 * @package     Inactivity Reports
 * @author      David Lowe
 * ************************************************************************
 * ************************************************************************
 * The report_participation report viewed event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int course id: Id of the course.
 *      - string url: URL of this inactivity report.
 * }
 */

namespace local_evaluations\event;

defined('MOODLE_INTERNAL') || die();


class evaluation_complete extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventCourseEvaluations', 'local_evaluations');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Course Evaluations";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        // return array($this->courseid, "course", "report inactivity", "report/inactivity/index.php?id=" . $this->courseid,
        //         $this->courseid);
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        error_log("\nEvents for local -> evaluations -> wtf.......what to do here?");
        
        return new \moodle_url('/report/inactivity/index.php', array('id' => $this->courseid,
            'courseid' => $this->other['courseid'], 'url' => $this->other['url']));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        // if (empty($this->other['courseid'])) {
        //     throw new \coding_exception('The \'courseid\' value must be set in other.');
        // }

        // if (empty($this->other['url'])) {
        //     throw new \coding_exception('The \'url\' value must be set in other.');
        // }
    }
}
