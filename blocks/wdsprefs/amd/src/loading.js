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
            var params;
            params = {};
            $('.wds_loading').ajaxError(function() {
                 // console.log('LOADING: wacka wacka 1');
                return $('.wds_loading').html($('.network_failure').html());
            });
            $('.passed').each(function(i, elem) {
                 // console.log('LOADING: wacka wacka 2');
                params[$(elem).attr('name')] = $(elem).val();
                return params[$(elem).attr('name')];
            });
            return $.post(window.location.pathname, params, function(html) {
            // $.post(window.location.pathname, params, function(html) {
                 // console.log('LOADING: wacka wacka 3');
                return $('.wds_loading').html(html);
            });
        }
    };
});
