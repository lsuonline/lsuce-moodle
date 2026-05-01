<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace tiny_accordion\admin;

use tiny_accordion\preset_parser;

/**
 * Custom admin_setting subclass for the accordion style presets widget.
 *
 * Extends admin_setting (Open/Closed principle) without modifying core.
 * Delegates all parsing/sanitising to preset_parser (Dependency Inversion).
 * Renders via a Mustache template — no inline HTML.
 *
 * @package     tiny_accordion
 * @copyright   2026 LSU Online & Continuing Education
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_style_presets extends \admin_setting {

    /**
     * Constructor.
     *
     * @param string $name    Setting name, e.g. 'tiny_accordion/stylepresets'.
     * @param string|\lang_string $visiblename Display name.
     * @param string|\lang_string $description Description shown under the widget.
     * @param string $defaultsetting Default value (use '' for no presets).
     */
    public function __construct(
        string $name,
        $visiblename,
        $description,
        string $defaultsetting = ''
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Return the current stored setting value.
     *
     * @return string|false Raw JSON string, empty string, or false when not yet set.
     */
    public function get_setting(): string|false {
        return $this->config_read($this->name) ?? '';
    }

    /**
     * Return the default setting value.
     *
     * @return string Empty string (no presets by default).
     */
    public function get_defaultsetting(): string {
        return $this->defaultsetting;
    }

    /**
     * Validate, sanitise, and store the posted JSON value.
     *
     * Delegates parsing and sanitising entirely to preset_parser so this method
     * remains a thin adapter between the POST data and storage.
     *
     * @param string $data Raw JSON string from the hidden input (POST value).
     * @return string Empty string on success; a lang string describing the error on failure.
     */
    public function write_setting($data): string {
        $trimmed = trim((string) $data);

        if ($trimmed === '' || $trimmed === '[]') {
            $this->config_write($this->name, '[]');
            return '';
        }

        $parser = new preset_parser();
        $decoded = $parser->decode($trimmed);

        if ($decoded === null) {
            return get_string('setting_stylepresets_invalidjson', 'tiny_accordion');
        }

        $clean = $parser->sanitise($decoded);
        $this->config_write($this->name, $parser->serialise($clean));
        return '';
    }

    /**
     * Render the admin widget HTML via Mustache template.
     *
     * @param string $data   Current setting value (raw JSON string).
     * @param string $query  Admin search query (for highlighting).
     * @return string HTML for the setting widget.
     */
    public function output_html($data, $query = ''): string {
        global $OUTPUT;

        $parser = new preset_parser();
        $presets = [];

        if (!empty($data)) {
            $decoded = $parser->decode($data);
            if ($decoded !== null) {
                foreach ($decoded as $i => $row) {
                    $presets[] = [
                        'rowindex'     => $i,
                        'label'        => $row['label']        ?? '',
                        'detailsclass' => $row['detailsclass'] ?? '',
                        'detailsstyle' => $row['detailsstyle'] ?? '',
                        'summaryclass' => $row['summaryclass'] ?? '',
                        'summarystyle' => $row['summarystyle'] ?? '',
                    ];
                }
            }
        }

        $context = [
            'fullname'    => $this->get_full_name(),
            'id'          => $this->get_id(),
            'presets'     => $presets,
            'haspresets'  => count($presets) > 0,
            'currentjson' => $data ?: '[]',
        ];

        $html = $OUTPUT->render_from_template('tiny_accordion/admin_style_presets', $context);
        return format_admin_setting($this, $this->visiblename, $html, $this->description, true, '', null, $query);
    }
}
