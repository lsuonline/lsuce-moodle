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
 * myoverview block renderer
 *
 * @package    block_uleth_library
 * @copyright  2021 David Lowe <david.lowe@uleth.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uleth_library\output;
defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;

class renderer extends plugin_renderer_base {

    /**
     * Return the main content for the block uleth_library
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(main $main) {
        return $this->render_from_template('block_uleth_library/main', $main->export_for_template($this));
    }
}
