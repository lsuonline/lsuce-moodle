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
            var available, bucket, changed, move_selected, move_to_available, move_to_bucket, selected;
            $("input[name^='shell_name_']").keyup(function() {
                // console.log("SELECTION: input shell_name_");
                var id, value;
                value = $(this).val();
                id = $(this).attr("name");
                $("#" + id).text(value);
                return $("input[name='" + id + "_hidden']").val(value);
            });

            $("a[href^='shell_']").click(function() {
                // console.log("SELECTION: a shell_name_");
                var display, id, name;
                id = $(this).attr("href").split("_")[1];
                name = $("input[name='shell_name_" + id + "']");
                display = $(name).css("display");
                if (display === "none") {
                    $(name).css("display", "block");
                    $(name).focus();
                    $(name).select();
                } else {
                    $(name).css("display", "none");
                }
                return false;
            });

            selected = function() {
                // console.log("SELECTION: selected - ");
                return $("input:checked[name='selected_shell']").attr("value");
            };

            available = $("select[name='before[]']");

            bucket = function() {
                // console.log("SELECTION: bucket");
                return $("select[name='shell_" + selected() + "[]']");
            };

            changed = function() {
                // console.log("SELECTION: changed");
                var compressed, id, toValue, values;
                id = selected();
                values = $("input[name='shell_values_" + id + "']");
                toValue = function(i, child) {
                    return $(child).val();
                };
                compressed = $(bucket()).children().map(toValue);
                return values.val($(compressed).toArray().join(","));
            };

            move_selected = function(from, to) {
                // console.log("SELECTION: move_selected");
                var children;
                children = $(from).children(":selected");
                $(to).append(children);
                return changed();
            };

            move_to_bucket = function() {
                // console.log("SELECTION: move_to_buckete");
                return move_selected(available, bucket());
            };

            move_to_available = function() {
                // console.log("SELECTION: move_to_available");
                return move_selected(bucket(), available);
            };

            $("input[name='move_right']").click(move_to_bucket);
            return $("input[name='move_left']").click(move_to_available);
            // $("input[name='move_left']").click(move_to_available);
        }
    };
});
