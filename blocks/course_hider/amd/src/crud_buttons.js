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
 * Course Hider Tool
 *
 * @package   block_course_hider
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'block_course_hider/notifications', 'block_course_hider/ch_lib'],
    function($, noti, CHLib) {
    'use strict';
    return {
        /**
         * Register click events for the page.
         *
         * @param null
         * @return void
         */
        registerEvents: function () {

            // --------------------------------
            // Recover Mapping.
            // --------------------------------
            $('.block_course_hider_container .mview_recover').on('click', function(ev) {
                ev.preventDefault();
                var row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    "this_form": $(this).closest("form"),
                    "title": 'Recover Mapping',
                    "body": 'Do you really want to recover this?',
                    "save_button": "Recover"
                };

                noti.callYesNoModi(row_data).then(function (response) {
                    if (response.status == true) {
                        var this_form = $('#map_form_'+response.data.record);
                        // Convert all the form elements values to a serialised string.
                        this_form.append('<input type="hidden" name="sentaction" value="recovered" />');
                        this_form.submit();
                    // } else {
                        // console.log("NOPE the thingy is false");
                    }
                });
            });

            // --------------------------------
            // Edit Mapping.
            // --------------------------------
            $('.block_course_hider_container .mview_edit').on('click', function(ev) {
                ev.preventDefault();
                var record = $(this).closest("tr").data("rowid"),
                    send_this = {
                        "sentaction": "update",
                        "sentdata": record,
                        "vform": "1"
                    },
                    url = sessionStorage.getItem("wwwroot") + "/blocks/course_hider/" + sessionStorage.getItem("sample_form") + ".php";
                XELib.pushPost(url, send_this);
            });

            // --------------------------------
            // Delete Mapping.
            // --------------------------------
            $('.block_course_hider_container .mview_delete').on('click', function(ev) {
                ev.preventDefault();

                var links = $(this).closest("tr").data("mlinks"),
                row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    "this_form": $(this).closest("form"),
                    "title": 'Delete item',
                    "body": 'Do you really want to delete?',
                    "save_button": "Delete"
                };

                if (links > 1) {
                    noti.callAlert({
                        "title": "Aborted",
                        "message": "Cannot delete this while mappings are linked."
                    });
                } else {
                    noti.callYesNoModi(row_data).then(function (response) {
                        if (response.status == true) {
                            var this_form = $('#map_form_' + response.data.record);
                            // Convert all the form elements values to a serialised string.
                            this_form.append('<input type="hidden" name="sentaction" value="delete" />');
                            this_form.submit();
                        }
                    });
                }
            });
        },

        /**
         * Currently this is being called from the mustache templates when viewing lists.
         * @param null
         * @return void
         */
        init: function() {
            var that = this;
            // Register events.
            that.registerEvents();
        },
    };
});