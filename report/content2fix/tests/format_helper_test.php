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

namespace report_content2fix;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for format helper queue logic.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_helper_test extends \advanced_testcase {

    /**
     * Data provider: all available content item types (component, table, field).
     * Dynamically fetches sources from html_scanner.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function content_source_provider(): array {
        $sources = local\html_scanner::get_html_content_sources();
        $cases = [];
        foreach ($sources as $source) {
            $key = sprintf('%s / %s / %s', $source['component'], $source['table'], $source['field']);
            $cases[$key] = [$source['component'], $source['table'], $source['field']];
        }
        return $cases;
    }

    /**
     * Data provider for queue gating conditions.
     *
     * @return array[]
     */
    public static function can_queue_filtered_format_provider(): array {
        return [
            'allowed for admin with capability even multiple filtered courses' => [
                true,
                true,
                2,
                true,
            ],
            'blocked for non admin' => [
                false,
                true,
                1,
                false,
            ],
            'allowed for admin without capability when filtered course is single' => [
                true,
                false,
                1,
                true,
            ],
            'blocked for admin without capability when no filtered courses' => [
                true,
                false,
                0,
                false,
            ],
            'blocked for admin without capability when filtered spans multiple courses' => [
                true,
                false,
                2,
                false,
            ],
        ];
    }

    /**
     * Test queue gating condition helper.
     *
     * @dataProvider can_queue_filtered_format_provider
     * @param bool $isadmin
     * @param bool $canfixfiltered
     * @param int $filtereddistinctcoursecount
     * @param bool $expected
     */
    public function test_can_queue_filtered_format(
        bool $isadmin,
        bool $canfixfiltered,
        int $filtereddistinctcoursecount,
        bool $expected
    ): void {
        $actual = local\format_helper::can_queue_filtered_format(
            $isadmin,
            $canfixfiltered,
            $filtereddistinctcoursecount
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * Test helper queues adhoc task with filter values in custom data.
     */
    public function test_queue_filtered_format_task(): void {
        $this->resetAfterTest();

        $filtervalues = [
            'course:fullname_operator' => '2',
            'course:fullname_value' => 'History 101',
        ];

        local\format_helper::queue_filtered_format_task($filtervalues);

        $tasks = \core\task\manager::get_adhoc_tasks(task\format_all_html_task::class);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertNotFalse($task);
        $this->assertEquals((object) ['filtervalues' => (object) $filtervalues], $task->get_custom_data());
    }

    /**
     * Create test data for a content source and return the entry plus expected content.
     *
     * @param string $component e.g. mod_page, core_course
     * @param string $table e.g. page, course
     * @param string $field e.g. content, intro, summary
     * @param string $initialcontent HTML to store
     * @return array{0: \stdClass, 1: int|string, 2: \stdClass|null} [entry, rowid, instance or null]
     */
    protected function create_test_data_for_source(
        string $component,
        string $table,
        string $field,
        string $initialcontent
    ): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['numsections' => 1], ['createsections' => true]);

        if ($component === 'core_course') {
            $DB->set_field('course', 'summary', $initialcontent, ['id' => $course->id]);
            $DB->set_field('course', 'summaryformat', FORMAT_HTML, ['id' => $course->id]);
            $entry = (object) [
                'component' => $component,
                'comptable' => $table,
                'compfield' => $field,
                'rowid' => (int) $course->id,
                'courseid' => (int) $course->id,
                'cmid' => null,
            ];
            return [$entry, (int) $course->id, $course];
        }

        if ($component === 'core_section') {
            $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
            $DB->set_field('course_sections', 'summary', $initialcontent, ['id' => $section->id]);
            $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['id' => $section->id]);
            $entry = (object) [
                'component' => $component,
                'comptable' => $table,
                'compfield' => $field,
                'rowid' => (int) $section->id,
                'courseid' => (int) $course->id,
                'cmid' => null,
            ];
            return [$entry, (int) $section->id, $section];
        }

        $modname = str_replace('mod_', '', $component);
        $params = ['course' => $course->id];
        if ($field === 'intro') {
            $params['intro'] = '';
            $params['introformat'] = FORMAT_HTML;
        } elseif ($field === 'content' && $modname === 'page') {
            $params['content'] = '';
            $params['contentformat'] = FORMAT_HTML;
        }
        $instance = $this->getDataGenerator()->create_module($modname, $params);
        $DB->set_field($table, $field, $initialcontent, ['id' => $instance->id]);
        $DB->set_field($table, $field . 'format', FORMAT_HTML, ['id' => $instance->id]);

        $cm = get_coursemodule_from_instance($modname, $instance->id);
        $entry = (object) [
            'component' => $component,
            'comptable' => $table,
            'compfield' => $field,
            'rowid' => (int) $instance->id,
            'courseid' => (int) $course->id,
            'cmid' => (int) $cm->id,
        ];
        return [$entry, (int) $instance->id, $instance];
    }

    /**
     * Test get_content_for_entry returns content from source table.
     *
     * @dataProvider content_source_provider
     * @param string $component
     * @param string $table
     * @param string $field
     */
    public function test_get_content_for_entry(string $component, string $table, string $field): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $expected = '<p>Test content for ' . $component . '</p>';
        [$entry, , ] = $this->create_test_data_for_source($component, $table, $field, $expected);

        $content = local\format_helper::get_content_for_entry($entry);
        $this->assertSame($expected, $content);
    }

    /**
     * Test persist_tinymce_content saves content and triggers event.
     *
     * @dataProvider content_source_provider
     * @param string $component
     * @param string $table
     * @param string $field
     */
    public function test_persist_tinymce_content(string $component, string $table, string $field): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $initial = '<p>Old content</p>';
        $newcontent = '<p>New content from TinyMCE</p>';
        [$entry, $rowid, ] = $this->create_test_data_for_source($component, $table, $field, $initial);

        $entry->summary = 'Test';
        $entry->timechecked = time();
        $entry->id = $DB->insert_record('report_content2fix', $entry);

        $result = local\format_helper::persist_tinymce_content($entry->id, [
            'text' => $newcontent,
            'format' => FORMAT_HTML,
        ]);

        $this->assertTrue($result['success']);

        $updated = $DB->get_record($table, ['id' => $rowid]);
        $this->assertNotFalse($updated);
        $this->assertSame($newcontent, $updated->$field);
    }
}
