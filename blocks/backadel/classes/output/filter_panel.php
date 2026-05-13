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

namespace block_backadel\output;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use renderable;
use templatable;

/**
 * Right-side offcanvas filter drawer wrapping arbitrary form HTML.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_panel implements renderable, templatable {

    /**
     * @param string $collapseid HTML id for the offcanvas element (no leading #).
     * @param string $title      Label shown on the trigger button and drawer header.
     * @param int    $activecount Number of active filters (0 = outlined button).
     * @param string $formhtml   Trusted server-rendered moodleform HTML.
     */
    public function __construct(
        private string $collapseid,
        private string $title,
        private int $activecount,
        private string $formhtml,
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        return [
            'collapseid'  => $this->collapseid,
            'title'       => $this->title,
            'activecount' => $this->activecount,
            'formhtml'    => $this->formhtml,
        ];
    }
}
