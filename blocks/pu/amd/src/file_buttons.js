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

 define(['jquery', 'block_pu/notifications', 'block_pu/pu_lib'],
    function($, noti, PULib) {
    'use strict';
    return {
        /**
         * Check if the file exists
         * @param {object} the json object sent to the server
         * @return resolved data
         */
        // check_file_exists: function (params) {
        //     return PULib.jaxyPromise({
        //         'call': 'check_file_exists',
        //         'params': params,
        //         'class': 'pufile'
        //     });
        // },

        frackYou: function () {
            console.log("Frack you snitch");
        },

        confirmCheckExecute: function (params) {
            // First let's confirm
            console.log("What are the params: ", params);
            noti.callYesNoModi(params).then(function (response) {

                console.log("Chaning 2");
                if (response.status == true) {
                    // console.log("Copy button CLICKED -> TRUE");

                    // console.log("Do we have this: ", this);
                    // console.log("Do we have that: ", that);
                    return response;
                }
            // }).then(fuckYou)
            }).then(function (response) {
                // We need to check if the file exists.
                console.log("Inside chain before second call-> What is the response: ", response);
                PULib.check_file_exists(response).then(function (response) {
                    console.log("Inside check_file_exists -> What is the response: ", response);

                    if (response.success == false) {
                        console.log(" ==>> FUCK WOD 1: ", response);
                        noti.callNoti({
                            message: response.msg,
                            type: "fail"
                        });
                    // } else if (response.success == "false") {
                        // console.log(" ==>> FUCK WOD 2:" , response);

                    } else {

                        console.log(" ==>> GOTCHA BITCH!!!!!");
                    }
                    // var this_form = $('#pu_file_form_'+response.data.record);
                    // // Convert all the form elements values to a serialised string.
                    // this_form.append('<input type="hidden" name="action" value="copy" />');
                    // this_form.submit();
                });

                // return new Promise((resolve, reject) => { // (*)
                //     setTimeout(() => resolve(result * 2), 1000);
                // });

                // that.check_file_exists({
                //     // 'mdl_file_id': $("input[name=nameGoesHere]").val();
                //     'mdl_file_id': $(this).find('input[name=mdl_file_id]').val()

                // }).then(function (response) {
                //     // if the text is disabled then use select
                //     console.log("What is the response: ", response);
                // var this_form = $('#pu_file_form_'+response.data.record);
                // // Convert all the form elements values to a serialised string.
                // this_form.append('<input type="hidden" name="action" value="copy" />');
                // this_form.submit();

                // });
            });
        },
    };
});