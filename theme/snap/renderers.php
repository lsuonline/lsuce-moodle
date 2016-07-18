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
 * Renderer overrides
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/renderers/snap_shared.php');
require_once(__DIR__.'/renderers/core_renderer.php');
require_once(__DIR__.'/renderers/course_renderer.php');
require_once(__DIR__.'/renderers/course_management_renderer.php');
require_once(__DIR__.'/renderers/course_format_topics_renderer.php');
require_once(__DIR__.'/renderers/course_format_weeks_renderer.php');
require_once(__DIR__.'/renderers/core_question_renderer.php');
require_once(__DIR__.'/renderers/mod_quiz_renderer.php');

// Only include folderview renderer if available.
if (file_exists($CFG->dirroot.'/course/format/folderview/renderer.php')) {
    require_once(__DIR__.'/renderers/course_format_folderview_renderer.php');
}

require_once(__DIR__.'/renderers/files_renderer.php');

// Include badge renderer if it should be.
if (file_exists($CFG->dirroot.'/message/output/badge/renderer.php')) {
    require_once(__DIR__.'/renderers/message_badge_renderer.php');
}
