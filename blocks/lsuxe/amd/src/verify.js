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

 define(['jquery'],
    function($) {
    // 'use strict';
    // let buttons = {
    //         'name': '#id_verifysource',
    //         'type': 'button'
    //     }
    // };

    return {
        /**
         * Any button or input that we want to show a checkmark or crossmark
         * add them here, the tag to use the type (input or button).
         *
         * @param null
         * @return void
         */
        registerCheckMarkTags: function () {
            // console.log("What are the buttons: ", buttons);
            // var tags = {
            //     {
            //         'name': '#id_verifysource',
            //         'type': 'button'
            //     }
            // };
            /*
            var elements = [$('#blah'), $('#blup'), $('#gaga')];  //basically what you are starting with

            var combiObj = $.map(elements, function(el){return el.get()});
            $(combiObj).on('click', function(ev){
                console.log('EVENT TRIGGERED');
            });
            */
        },

        checkMarkOn: function (tag) {
            $(tag + ' .circle-loader').css('visibility', 'visible');
        },
        checkMarkOff: function (tag) {
            $(tag + ' .circle-loader').css('visibility', 'hidden');
        },
        checkMarkLoading: function (tag) {
            // Make sure it's on
            let cl = tag + ' .circle-loader',
                cm = tag + ' .checkmark';

            this.checkMarkOn(tag);
            if ($(cl).hasClass('load-complete')) {
                $(cl).toggleClass('load-complete');
                $(cm).toggle();
            }

            // $('.xe_confirm_url > .checkmark').toggle();
        },
        checkMarkComplete: function (tag) {
            let cl = tag + ' .circle-loader',
                cm = tag + ' .checkmark';

            if (!$(cl).hasClass('load-complete')) {
                $(cl).toggleClass('load-complete');
                $(cm).toggle();
            }
        },
        crossMarkOn: function (tag) {
            $(tag + ' .circle-cross-loader').css('visibility', 'visible');
        },

        crossMarkOff: function (tag) {
            $(tag + ' .circle-cross-loader').css('visibility', 'hidden');
        },

    };
});