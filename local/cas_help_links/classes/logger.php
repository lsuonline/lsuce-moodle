<?php
 
class local_cas_help_links_logger {

    /**
     * Persists a log record for a "link clicked" activity
     * 
     * @param  int  $user_id  moodle user id
     * @param  int  $link_id  'help links' table record id
     * @return boid
     */
    public static function log_link_click($user_id, $course_id, $link_id = 0)
    {
        global $DB;

        $log_record = new stdClass;
        $log_record->user_id = $user_id;
        $log_record->course_id = $course_id;
        $log_record->link_id = $link_id;
        $log_record->time_clicked = time();

        $DB->insert_record(self::get_log_table_name(), $log_record);
    }

    /**
     * Returns the name of the 'help links log' table
     * 
     * @return string
     */
    private static function get_log_table_name()
    {
        return 'local_cas_help_links_log';
    }
    
}
