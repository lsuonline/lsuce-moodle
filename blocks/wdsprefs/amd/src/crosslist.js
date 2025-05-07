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
            return $("#id_save").click(function() {
            // $("#id_save").click(function() {
                var validated, value;
                value = true;
                validated = [];
                $("input[name^='shell_name_']").each(function(index, name) {
                    if ($(name).attr('type' === 'hidden')) {

                        $(validated).each(function(i, n) {
                            if (n === $(name).val()) {
                                value = false;
                                return value;
                            }
                        });
                    }
                    return validated.push($(name).val());
                });
                if(!value) {
                    $("#split_error").text("Each shell should have a unique name.");
                }
                return value;
            });
        }
    };
});
