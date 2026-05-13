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
 * Shared SQL fragments for Backadel catalogue queries.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Catalogue SQL snippets reused across filters and listings.
 */
final class sql_helpers {

    /**
     * Effective course type: admin override when set, else latest warm-path row for the filename.
     *
     * @param string $cataloguealias Alias for {@see block_backadel_catalogue} (e.g. c or cat).
     * @return string SQL expression (no AS clause).
     */
    public static function effective_coursetype_sql(string $cataloguealias = 'c'): string {
        return 'COALESCE(' . $cataloguealias . '.coursetype_override, (SELECT bc_ct.coursetype FROM {block_backadel_courses} bc_ct '
            . 'WHERE bc_ct.filename = ' . $cataloguealias . '.filename ORDER BY bc_ct.id DESC LIMIT 1))';
    }
}
