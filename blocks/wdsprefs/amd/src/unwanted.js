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
 * @package
 * @copyright  2025 onwards Louisiana State University
 * @copyright  2025 onwards Robert Russo & David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'],
    function($) {
    'use strict';
    return {
        /**
         * This is the starting function for the splitter.
         */
        init: function() {
            var apply_event, make_selected;
            make_selected = function(courseid, checked) {
                return function() {
                    $("input[id*='course" + courseid + "_']").attr('checked', checked);
                    return false;
                };
            };
            apply_event = function(checked) {
                return function(index, elem) {
                    var id;
                    id = $(elem).attr('id').split('_')[1];
                    return $(elem).click(make_selected(id, checked));
                };
            };
            $("a[id^='all_']").each(apply_event(true));
            return $("a[id^='none_']").each(apply_event(false));
            // $("a[id^='none_']").each(apply_event(false));
        }
    };
});
