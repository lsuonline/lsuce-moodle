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
 * External API to fetch rendered help content from Backadel markdown docs.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

/**
 * Web service: markdown help for admin pages.
 */
class get_help_content extends external_api {

    /** @var string[] */
    private const VALID_TOPICS = [
        'overview',
        'search',
        'catalogue',
        'failed',
        'delete',
        'migrate',
        'restore',
        'simple_restore_list',
    ];

    /**
     * Parameters for {@see execute()}.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'topic' => new external_value(PARAM_ALPHANUMEXT,
                'Help topic key (matches a docs/*.md filename)'),
        ]);
    }

    /**
     * Load topic markdown and return HTML.
     *
     * @param string $topic Topic key.
     * @return array{success: bool, content: string}
     */
    public static function execute(string $topic): array {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['topic' => $topic]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('block/backadel:viewresults', $context);

        if (!in_array($params['topic'], self::VALID_TOPICS, true)) {
            return [
                'success' => false,
                'content' => get_string('help_not_found', 'block_backadel'),
            ];
        }

        $docsdir = $CFG->dirroot . '/blocks/backadel/docs';
        $filepath = $docsdir . '/' . $params['topic'] . '.md';

        // Defence in depth: ensure the resolved path stays within docs/.
        $realdocs = realpath($docsdir);
        $realfile = realpath($filepath);
        if ($realdocs === false || $realfile === false || strpos($realfile, $realdocs . DIRECTORY_SEPARATOR) !== 0) {
            return [
                'success' => false,
                'content' => get_string('help_not_found', 'block_backadel'),
            ];
        }

        if (!is_readable($filepath)) {
            return [
                'success' => false,
                'content' => get_string('help_not_found', 'block_backadel'),
            ];
        }

        $raw = file_get_contents($filepath);
        if ($raw === false) {
            return [
                'success' => false,
                'content' => get_string('help_not_found', 'block_backadel'),
            ];
        }

        return [
            'success' => true,
            'content' => markdown_to_html($raw),
        ];
    }

    /**
     * Return structure for {@see execute()}.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the content was found'),
            'content' => new external_value(PARAM_RAW, 'Rendered HTML help content'),
        ]);
    }
}
