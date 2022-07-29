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

define(['jquery'], function($) {
    'use strict';
    return {

        /**
         * Store data in localStorage so it's available throughout
         *
         * @param {object} the json object to save
         * @return null
         */
        preLoadConfig: function() {
            // var final_state = {},
            var window_stat = {};

            if (window.__SERVER__ === "true" || window.__SERVER__ === true) {
                if (typeof (window.__INITIAL_STATE__) === 'string') {
                    try {
                        window_stat = JSON.parse(window.__INITIAL_STATE__);
                        console.log("store_general -> What is the final state here: ", window_stat);
                        delete window.__INITIAL_STATE__;
                        window.__SERVER__ = false;
                    } catch (error) {
                        console.log("ERROR, __INITIAL_STATE__ couldn't parse.");
                        console.log(error);
                    }
                }
            } else {
                console.log("WARNING: window.__SERVER__ was not set");
            }

            console.log("What is the data: ", window_stat);

            for (var key in window_stat) {
                console.log("Going to store this value: " + window_stat[key] + " in this key: " + key);
                localStorage[key] = window_stat[key];
            }
        },

        /**
         * Post data to a URL.
         *
         * @param {string} url to post to
         * @param {object} the json object to post
         * @return {Promise}
         */
        pushPost: function(redirectUrl, data) {

            var input_part = '',
                form_part = '',
                form;

            for (var key in data) {
                var value = data[key];
                input_part += '<input type="hidden" value="' + value + '" name="' + key + '"></input>';
            }

            form_part = '<form method="POST" style="display: none;">' + input_part + '</form>';
            form = $(form_part);
            $('body').append(form);
            $(form).submit();
        }
    };
});