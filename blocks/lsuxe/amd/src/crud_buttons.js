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
 * @package    block_lsuxe Cross Enrollment
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'block_lsuxe/notifications', 'block_lsuxe/xe_lib'], function($, noti, XELib) {
    'use strict';

    return {

        registerEvents: function () {

            $('.block_lsuxe_container .mview_update').on('click', function() {
                console.log("MVIEW_UPDATE");
                // var record = $(this).closest("tr").data("rowid");
                // TODO: finish this.
            });

            $('.block_lsuxe_container .mview_edit').on('click', function(ev) {
                ev.preventDefault();
                console.log("MVIEW_EDIT");
                var record = $(this).closest("tr").data("rowid"),
                    send_this = {
                        "sentaction": "update",
                        "sentdata": record,
                        "vform": "1"
                    },
                    url = localStorage["wwwroot"] + "/blocks/lsuxe/" + localStorage["xe_form"] + ".php";
                XELib.pushPost(url, send_this);
                // TODO: finish this.
            });

            $('.block_lsuxe_container .mview_delete').on('click', function(ev) {
                ev.preventDefault();

                var row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    "this_form": $(this).closest("form")
                };

                noti.callRemoveModi(row_data).then(function (response) {
                    if (response.status == true) {
                        console.log("YAY the thingy is true");
                        var this_form = $('#map_form_'+response.data.record);
                        // Convert all the form elements values to a serialised string.
                        // var formData = $(ev).closest("form").serialize();
                        // var formData = row_data.this_form.serialize();
                        this_form.append('<input type="hidden" name="sentaction" value="delete" />');
                        this_form.submit();

                        console.log("does row_data exist BITCH: ", response.data.record);
                        // console.log("What is the formData BITCH: ", formData);
                        // console.log("What is the response formData BITCH: ", response.data);

                    } else {
                        console.log("NOPE the thingy is false");

                    }
                });
            });
        },
        /**
         * Some description here.......
         * @param {obj} A simple object with the 'message' and 'type' of notification.
         * @return void
         */
        init: function() {
            var that = this;

            // register events
            that.registerEvents();
        },

    };
});