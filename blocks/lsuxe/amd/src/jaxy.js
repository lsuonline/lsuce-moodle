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

 define([
    'jquery',
    'core/ajax',
], function($, Ajax) {
    'use strict';

    return {
        /**
         * A Javascript Promise Wrapper to make AJAX calls.
         *
         * Valid args are:
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        xeAjax: function(data_chunk) {
            var promiseObj = new Promise(function(resolve, reject) {
                console.log("xeAjax() -> START, let's Poke the Server");
                var send_this = [{
                    methodname: 'block_quickmail_xeAjax',
                    args: {
                        datachunk: data_chunk,
                    }
                }];
                Ajax.call(send_this)[0].then(function(results) {
                    console.log("xeAjax() -> SUCCESS, what is result: ", results);
                    resolve(JSON.parse(results.data));
                }).catch(function(ev) {
                    reject(ev);
                });
            });
            return promiseObj;
        },
    };
});
