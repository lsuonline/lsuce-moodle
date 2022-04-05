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
 * A scheduled task.
 *
 * @package    course_stat
 * @copyright  2014 Universite de Montreal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_utools\task;

/**
 * Simple task to run the IMS Enterprise enrolment cron.
 *
 * @copyright  2014 Universite de Montreal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task
{
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('course_stat_name', 'local_utools');
    }

    // public function setCourseInfo() {
    public function execute() {
    
        require_once(dirname(dirname(dirname(__FILE__))) .'/lib/UtoolsLib.php');

        global $DB;
        $get_visible_course = $DB->get_record_sql(
            'SELECT COUNT(shortname) from mdl_course WHERE visible = 1'
        );
        $get_invisible_course = $DB->get_record_sql(
            'SELECT COUNT(shortname) from mdl_course WHERE visible = 0'
        );
        $get_outcome = $DB->get_record_sql(
            'SELECT DISTINCT COUNT(o.courseid)
            FROM mdl_course AS c,mdl_grade_outcomes_courses as o
            where c.id = o.courseid '
        );
        $get_gradebook = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT gi.courseid
            FROM mdl_grade_grades AS gg, mdl_grade_items AS gi
            WHERE gi.id = gg.itemid ) G '
        );
        $get_file_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_resource AS r
            WHERE c.id = r.course ) F '
        );
        $get_page_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_page AS p
            WHERE c.id = p.course ) P '
        );
        $get_url_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_url AS u
            WHERE c.id = u.course ) U '
        );
        $get_book_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_book AS b
            WHERE c.id = b.course ) BOOK'
        );
        $get_imscp_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_imscp AS ims
            WHERE c.id = ims.course) IMSCP'
        );
        $get_quiz_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_quiz AS q
            WHERE c.id = q.course) QUIZ '
        );
        $get_forum_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c,  mdl_forum AS f
            WHERE c.id = f.course
            GROUP BY c.id
            HAVING COUNT (c.id) > 1) FORUM'
        );
            //AND f.type != ?', array ("news") FORUM'
        $get_feed_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_feedback AS feed
            WHERE c.id = feed.course) FEED '
        );
        $get_wiki_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_wiki AS wiki
            WHERE c.id = wiki.course) WIKI'
        );
        $get_glossary_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_glossary AS glos
            WHERE c.id = glos.course ) GLOSS'
        );
        $get_chat_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_chat AS chat
            WHERE c.id = chat.course ) CHAT '
        );
        $get_lesson_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_lesson AS lesson
            WHERE c.id = lesson.course ) LESSON '
        );
        $get_assignment_used = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_assignment AS assign
            WHERE c.id = assign.course ) ASSIGN '
        );
        $get_time = $DB->get_record_sql(
            'SELECT COUNT(*)
            FROM (SELECT c.id from mdl_course AS c WHERE c.visible = 1
            INTERSECT
            SELECT c.id
            FROM mdl_course AS c, mdl_assignment AS assign
            WHERE c.id = assign.course ) ASSIGN '
        );
        $course_combine->term =  substr(dirname(dirname(dirname(dirname(dirname(__FILE__))))), -6);
        $course_combine->visible_course =  (int) $get_visible_course->count;
        $course_combine->invisible_course =  (int) $get_invisible_course->count;
        $course_combine->outcome =  (int) $get_outcome->count;
        $course_combine->gradebook =  (int) $get_gradebook->count;
        $course_combine->page_used =  (int) $get_page_used->count;
        $course_combine->url_used =  (int) $get_url_used->count;
        $course_combine->book_used =  (int) $get_book_used->count;
        $course_combine->imscp_used =  (int) $get_imscp_used->count;
        $course_combine->file_used =  (int) $get_file_used->count;
        $course_combine->quiz_used =  (int) $get_quiz_used->count;
        $course_combine->forum_used =  (int) $get_forum_used->count;
        $course_combine->feed_used =  (int) $get_feed_used->count;
        $course_combine->wiki_used =  (int) $get_wiki_used->count;
        $course_combine->glossary_used =  (int) $get_glossary_used->count;
        $course_combine->chat_used =  (int) $get_chat_used->count;
        $course_combine->lesson_used =  (int) $get_lesson_used->count;
        $course_combine->assignment_used =  (int) $get_assignment_used->count;
        $course_combine->date =  (int) time();

        $course_insert = $DB->insert_record('utools_course_stat', $course_combine, true);
    }
    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    // public function execute()
    // {
    //     $this->setCourseInfo();
    // }
}
