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

declare(strict_types=1);

namespace block_backadel\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Runs the catalogue filesystem migration logic on-demand (scheduled task runner).
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_filesystem_adhoc extends \core\task\adhoc_task {

    public function execute(): void {
        (new migrate_filesystem())->execute();
    }

    public function get_name(): string {
        return get_string('task_migrate_filesystem_adhoc', 'block_backadel');
    }
}
