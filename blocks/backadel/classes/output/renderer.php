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

use plugin_renderer_base;

/**
 * Renderer for Backadel block template output.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the catalogue list using the block_backadel/catalogue template.
     */
    public function render_catalogue(catalogue_renderable $renderable): string {
        return $this->render_from_template('block_backadel/catalogue', $renderable->export_for_template($this));
    }

    /**
     * Render a collapsible filter panel around form HTML.
     */
    public function render_filter_panel(\block_backadel\output\filter_panel $panel): string {
        return $this->render_from_template('block_backadel/local/filter_panel', $panel->export_for_template($this));
    }
}
