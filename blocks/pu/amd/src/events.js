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
 * @package   block_pu
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe, Robert Russo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 define(['jquery', 'block_pu/notifications', 'block_pu/pu_lib', 'block_pu/file_buttons'],
    function($, noti, PULib, FB) {
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
            // Copy File.
            // --------------------------------
            $('.block_pu_container .pu_file_copy').on('click', function(ev) {
                ev.preventDefault();

                var row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    "this_form": $(this).closest("form"),
                    "title": 'Copy File',
                    "body": 'Copy and move file to location in settings?',
                    "save_button": "Copy",
                    "mfileid": $(this).closest("tr").find('input[name=mdl_file_id]').val()
                };

                FB.confirmCheckExecute(row_data);
            });

            // --------------------------------
            // Delete Non Moodle File.
            // --------------------------------
            $('.block_pu_container .nonmood_file_delete').on('click', function(ev) {
                ev.preventDefault();

                // var links = $(this).closest("tr").data("mlinks"),
                var row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    // "this_form": $(this).closest("form"),
                    "title": 'Delete File',
                    "body": 'Are you sure you want to delete this file?',
                    "save_button": "Hell Ya"
                };
                noti.callYesNoModi(row_data).then(function (response) {
                    if (response.status == true) {
                        var this_form = $('#nonmood_file_form_' + response.data.record);
                        console.log("What is response when deleting: ", response);
                        console.log("What is data when deleting: ", response.data);
                        console.log("What is the record: " + response.data.record);
                        console.log("What is this_form: ", this_form);
                        // Convert all the form elements values to a serialised string.
                        this_form.append('<input type="hidden" name="action" value="delete" />');
                        this_form.submit();
                    }
                });
            });
            // --------------------------------
            // Delete Moodle File.
            // --------------------------------
            $('.block_pu_container .pu_file_delete').on('click', function(ev) {
                ev.preventDefault();

                // var links = $(this).closest("tr").data("mlinks"),
                var row_data = {
                    "record": $(this).closest("tr").data("rowid"),
                    // "this_form": $(this).closest("form"),
                    "title": 'Delete File',
                    "body": 'Are you sure you want to delete this file?',
                    "save_button": "Hell Ya"
                };

                noti.callYesNoModi(row_data).then(function (response) {
                    if (response.status == true) {
                        var this_form = $('#pu_file_form_' + response.data.record);
                        console.log("What is response when deleting: ", response);
                        console.log("What is data when deleting: ", response.data);
                        // Convert all the form elements values to a serialised string.
                        this_form.append('<input type="hidden" name="action" value="delete" />');
                        this_form.submit();
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
            // Register events.
            that.registerEvents();
        },
    };
});