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

namespace report_content2fix\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Malformed content entity for reportbuilder.
 *
 * @package   report_content2fix
 * @copyright 2026 LSU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class malformed_content extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'report_content2fix',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('pluginname', 'report_content2fix');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('report_content2fix');

        $columns = [];

        // Component column.
        $columns[] = (new column(
            'component',
            new lang_string('column_component', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.component")
            ->set_is_sortable(true);

        // Course ID column.
        $columns[] = (new column(
            'courseid',
            new lang_string('courseid', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$tablealias}.courseid")
            ->set_is_sortable(true);

        // Course module ID column.
        $columns[] = (new column(
            'cmid',
            new lang_string('cmid', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$tablealias}.cmid")
            ->set_is_sortable(true);

        // Activity column (human-readable module name from component).
        $columns[] = (new column(
            'activity',
            new lang_string('activity', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.component, {$tablealias}.rowid")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $component, stdClass $row): string {
                if (empty($row->component)) {
                    return '';
                }
                $modname = str_replace('mod_', '', $row->component);
                $name = get_string('pluginname', $row->component);
                if (strpos($name, '[[') !== false) {
                    return $row->component . ' (id ' . $row->rowid . ')';
                }
                return $name;
            });

        // Field column.
        $columns[] = (new column(
            'field',
            new lang_string('column_field', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.compfield")
            ->set_is_sortable(true);

        // Summary column.
        $columns[] = (new column(
            'summary',
            new lang_string('summary', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$tablealias}.summary")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $summary): string {
                return s($summary ?? '');
            });

        // Time checked column.
        $columns[] = (new column(
            'timechecked',
            new lang_string('timechecked', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$tablealias}.timechecked")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], get_string('strftimedatetimeshort', 'core_langconfig'));

        // Link column (view activity) - requires cmid and component.
        $columns[] = (new column(
            'link',
            new lang_string('link', 'report_content2fix'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.id, {$tablealias}.cmid, {$tablealias}.component")
            ->set_is_sortable(false)
            ->add_callback(static function($value, stdClass $row): string {
                if (empty($row->cmid)) {
                    return '-';
                }
                $url = new \moodle_url(
                    '/mod/' . str_replace('mod_', '', $row->component) . '/view.php',
                    ['id' => $row->cmid]
                );
                return \html_writer::link($url, get_string('view'));
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;

        $tablealias = $this->get_table_alias('report_content2fix');

        $filters = [];

        // Component (activity type) filter.
        $filters[] = (new filter(
            select::class,
            'component',
            new lang_string('column_component', 'report_content2fix'),
            $this->get_entity_name(),
            "{$tablealias}.component"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                global $DB;
                $components = $DB->get_fieldset_sql(
                    'SELECT DISTINCT component FROM {report_content2fix} ORDER BY component',
                    []
                );
                $options = [];
                foreach ($components as $component) {
                    $name = get_string('pluginname', $component);
                    if (strpos($name, '[[') !== false) {
                        $name = $component;
                    }
                    $options[$component] = $name;
                }
                return $options;
            });

        // Time checked filter.
        $filters[] = (new filter(
            date::class,
            'timechecked',
            new lang_string('timechecked', 'report_content2fix'),
            $this->get_entity_name(),
            "{$tablealias}.timechecked"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_RANGE,
                date::DATE_PREVIOUS,
                date::DATE_CURRENT,
            ]);

        return $filters;
    }
}
