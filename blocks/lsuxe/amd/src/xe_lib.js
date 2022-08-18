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

define(['jquery', 'block_lsuxe/jaxy'],
    function($, jaxy) {
    'use strict';
    return {

        /**
         * Store data in sessionStorage so it's available throughout
         *
         * @param {object} the json object to save
         * @return null
         */
        preLoadConfig: function() {
            var window_stat = {};

            if (window.__SERVER__ === "true" || window.__SERVER__ === true) {
                if (typeof (window.__INITIAL_STATE__) === 'string') {
                    try {
                        window_stat = JSON.parse(window.__INITIAL_STATE__);
                        delete window.__INITIAL_STATE__;
                        window.__SERVER__ = false;
                    } catch (error) {
                        console.log(error);
                    }
                }
            } else {
                console.log("WARNING: window.__SERVER__ was not set");
            }
            for (var key in window_stat) {
                sessionStorage.setItem(key, window_stat[key]);
            }
        },

        /**
         * Post data to a URL. This is a form submit and will cause a page reload POSTing data.
         *
         * @param {string} redirectUrl to post to, self if nothing passed.
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
        },

        /**
         * Build the select on a form. (INCOMPLETE)
         *
         * @param {string} token
         * @return {Promise}
         *
        buildSelect: function (data) {

            var options = [];
            $.each(results, function(index, data) {
                options.push({
                    value: data.id + '__' + data.shortname,
                    label: data.shortname
                });
            });
            return options;
        }/

        /**
         * Get the token for the current selected URL
         *
         * @param {string} token
         * @return {Promise}
         */
        testWebServices: function (params) {
            // lsut/ 333cbddcab2ada64d9e8ca0ec49c6414
            return this.jaxyRemotePromise(params);
        },

        /**
         * Get the token for the current selected URL
         *
         * @param {string} token
         * @return {Promise}
         */
        getTokenForURL: function (url) {
            return this.jaxyPromise({
                'call': 'getToken',
                'params': {
                    'url': url
                },
                'class': 'router'
            });
        },

        isValidUrl: function (urlString) {
            var urlPattern = new RegExp('^(https?:\\/\\/)?'+ // validate protocol
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // validate domain name
            '((\\d{1,3}\\.){3}\\d{1,3}))'+ // validate OR ip (v4) address
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // validate port and path
            '(\\?[;&a-z\\d%_.~+=-]*)?'+ // validate query string
            '(\\#[-a-z\\d_]*)?$','i'); // validate fragment locator
            return !!urlPattern.test(urlString);
        },

        /* ====================================================================== */
        /* ===================      AJAX Functions      ========================= */
        /* ====================================================================== */
        /** Make an AJAX call and return a json object, from server,
         *  wrapping the AJAX call in a promise.
         * @param - {object} the details of the ajax call
         * @return {Promise}
         */
        jaxyPromise: function (data) {
            var promiseObj = new Promise(function (resolve) {
                jaxy.XEAjax(JSON.stringify(data)).then(function (response) {
                    resolve(response);
                });
            });
            return promiseObj;
        },

        /** Make an AJAX call and return a json object, from REMOTE server,
         *  wrapping the AJAX call in a promise.
         * @param - {object} the details of the ajax call
         * @return {Promise}
         */
        jaxyRemotePromise: function (data) {
            var promiseObj = new Promise(function (resolve) {
                jaxy.XERemoteAjax(data).then(function (response) {
                    console.log("jaxyRemotePromise returned, response is: ", results);
                    resolve(response);
                });
            });
            return promiseObj;
        },
    };
});