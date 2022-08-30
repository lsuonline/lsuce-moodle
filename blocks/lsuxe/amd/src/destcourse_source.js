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

define(['jquery', 'core/ajax', 'core/notification', 'block_lsuxe/xe_lib'],
    function($, Ajax, Notification, XELib) {
    'use strict';
    return {

        /**
         * Make an ajax call to the destination server and get courses.
         *
         * @param {Object} all the options to use jquery's $.ajax function (not moodle's)
         * @return {Promise}
         */
         getCourses: function (params) {

            var params = {
                'type': 'GET',
                'url': sessionStorage.getItem("currentUrl") + '/webservice/rest/server.php',
                'data': {
                    'wstoken': sessionStorage.getItem("currentToken"),
                    'wsfunction': 'core_course_get_courses',
                    'moodlewsrestformat': 'json'
                }
            };
            return XELib.jaxyRemotePromise(params);
         },

        /**
         * Process the results for auto complete elements. To keep the course id
         * and name they have been concatenated as the value.
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {Array} results An array or results.
         * @return {Array} New array of results.
         */
        processResults: function(selector, results) {
            var options = [];
            $.each(results, function(index, data) {
                options.push({
                    value: data.id + '__' + data.shortname,
                    label: data.shortname
                });
            });
            return options;
        },

        /**
         * This is using a subclass of Moodle's autocomplete function in:
         * classes/form/groupform_autocomplete.php
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {String} query The query string.
         * @param {Function} callback A callback function receiving an array of results.
         */
        /* eslint-disable promise/no-callback-in-promise */
        transport: function(selector, query, callback) {
            this.getCourses().then(callback).catch(Notification.exception);
        }
    };

});