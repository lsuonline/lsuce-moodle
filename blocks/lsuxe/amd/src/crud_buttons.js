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
 * Cross Enrollment Tool
 *
 * @package    block_lsuxe
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'block_lsuxe/notifications', 'block_lsuxe/xe_lib'],
    function($, noti, XELib) {
    'use strict';
    return {

        /**
         * Register click events for the page.
         *
         * @param null
         * @return void
         */
        registerEvents: function () {

            $('.block_lsuxe_container .mview_update').on('click', function() {
                // record will be the id of the record in the db
                // var record = $(this).closest("tr").data("rowid");
                // TODO: finish this by calling some scheduled task to run NOW.
            });

            $('.block_lsuxe_container .mview_edit').on('click', function(ev) {
                ev.preventDefault();
                var record = $(this).closest("tr").data("rowid"),
                    send_this = {
                        "sentaction": "update",
                        "sentdata": record,
                        "vform": "1"
                    },
                    url = sessionStorage.getItem("wwwroot") + "/blocks/lsuxe/" + sessionStorage.getItem("xe_form") + ".php";
                XELib.pushPost(url, send_this);
            });

            $('.block_lsuxe_container .mview_delete').on('click', function(ev) {
                ev.preventDefault();

                var row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    "this_form": $(this).closest("form")
                };

                noti.callRemoveModi(row_data).then(function (response) {
                    if (response.status == true) {
                        var this_form = $('#map_form_'+response.data.record);
                        // Convert all the form elements values to a serialised string.
                        this_form.append('<input type="hidden" name="sentaction" value="delete" />');
                        this_form.submit();
                    } else {
                        console.log("NOPE the thingy is false");
                    }
                });
            });
        },

        /**
         * Currently this is being called from the mustache templates when viewing lists.
         * @param null
         * @return void
         */
        init: function() {
            var that = this;

            // register events
            that.registerEvents();
        },
    };
});