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
 * Unit tests for report_editdates_mod_forum_date_extractor.
 *
 * Tests that saving forum dates via the edit dates report correctly updates
 * Moodle calendar events — the root cause of MD-1661.
 *
 * @package   report_editdates
 * @copyright 2026 LSU Online & Continuing Education
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/report/editdates/lib.php');
require_once($CFG->dirroot . '/report/editdates/mod/forumdates.php');

/**
 * Unit tests for the forum date extractor's calendar update behaviour.
 *
 * @covers report_editdates_mod_forum_date_extractor
 */
class report_editdates_mod_forum_date_extractor_test extends advanced_testcase {

    /**
     * Verify that save_dates() on the forum extractor updates the calendar event.
     *
     * MD-1661: Before the fix, save_dates() only called DB::update_record() on the
     * forum table and never called forum_update_calendar(). As a result, the calendar
     * event timestamp was never refreshed, so the old date remained visible on the
     * activity card and in the calendar block.
     *
     * After the fix (PR #40 / commit b5508f9), save_dates() calls forum_update_calendar()
     * to refresh the calendar event. This test verifies that the calendar event's
     * timestart matches the newly saved duedate.
     */
    public function test_save_dates_updates_calendar_event(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $originaltime = mktime(9, 0, 0, 7, 1, 2025);
        $forum = $this->getDataGenerator()->create_module('forum', [
            'course'  => $course->id,
            'duedate' => $originaltime,
        ]);

        // Confirm the calendar event exists with the original duedate.
        $events = $DB->get_records('event', ['modulename' => 'forum', 'instance' => $forum->id]);
        $this->assertCount(1, $events, 'Expected one calendar event for the forum duedate.');
        $event = reset($events);
        $this->assertEquals($originaltime, $event->timestart,
            'Calendar event should initially reflect the original duedate.');

        // Obtain cm_info for the forum.
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->instances['forum'][$forum->id];

        // Build the date extractor for forums.
        $extractor = report_editdates_mod_date_extractor::make('forum', $course);
        $this->assertNotNull($extractor, 'Forum date extractor should be available.');

        // Save a new duedate via the extractor (simulating what index.php does).
        $newtime = mktime(9, 0, 0, 8, 1, 2025);
        $extractor->save_dates($cm, [
            'duedate'    => $newtime,
            'cutoffdate' => 0,
        ]);

        // Assert: forum table row has been updated.
        $updatedforum = $DB->get_record('forum', ['id' => $forum->id]);
        $this->assertEquals($newtime, $updatedforum->duedate,
            'Forum duedate in DB should reflect the new value.');

        // Assert: calendar event timestart must also be updated (the actual fix for MD-1661).
        $updatedevent = $DB->get_record('event', ['modulename' => 'forum', 'instance' => $forum->id]);
        $this->assertNotFalse($updatedevent, 'Calendar event should still exist after date update.');
        $this->assertEquals($newtime, $updatedevent->timestart,
            'Calendar event timestart must be updated by forum_update_calendar() — MD-1661 regression check.');
    }

    /**
     * Verify that save_new_dates() triggers the course_module_updated event.
     *
     * MD-1661 / PR #50: The save_new_dates() wrapper ensures that calendar-aware
     * subsystems receive the course_module_updated event after any date change, not
     * just for forum activities.
     */
    public function test_save_new_dates_triggers_course_module_updated_event(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $forum  = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->instances['forum'][$forum->id];

        $extractor = report_editdates_mod_date_extractor::make('forum', $course);

        // Capture events fired during save_new_dates().
        $eventsink = $this->redirectEvents();

        $newtime = mktime(10, 0, 0, 8, 15, 2025);
        $extractor->save_new_dates($cm, [
            'duedate'    => $newtime,
            'cutoffdate' => 0,
        ]);

        $events = $eventsink->get_events();
        $eventsink->close();

        $eventtypes = array_map(function($e) {
            return $e->eventname;
        }, $events);

        $this->assertContains('\core\event\course_module_updated', $eventtypes,
            'save_new_dates() must trigger course_module_updated so calendar observers are notified.');
    }

    /**
     * Verify that setting duedate to 0 (disabled) removes the calendar event.
     */
    public function test_save_dates_clears_calendar_event_when_duedate_disabled(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $time = mktime(9, 0, 0, 7, 1, 2025);
        $forum = $this->getDataGenerator()->create_module('forum', [
            'course'  => $course->id,
            'duedate' => $time,
        ]);

        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->instances['forum'][$forum->id];

        $extractor = report_editdates_mod_date_extractor::make('forum', $course);

        // Disable the duedate.
        $extractor->save_dates($cm, [
            'duedate'    => 0,
            'cutoffdate' => 0,
        ]);

        $updatedforum = $DB->get_record('forum', ['id' => $forum->id]);
        $this->assertEquals(0, $updatedforum->duedate,
            'Duedate should be 0 (disabled) after save.');

        $eventcount = $DB->count_records('event', ['modulename' => 'forum', 'instance' => $forum->id]);
        $this->assertEquals(0, $eventcount,
            'Calendar event should be removed when duedate is set to 0.');
    }
}
