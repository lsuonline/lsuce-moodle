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

 // define(['jquery', 'block_lsuxe/xe_lib', 'block_lsuxe/notifications', 'block_lsuxe/verify'],
 define(['jquery', 'block_lsuxe/xe_lib', 'block_lsuxe/notifications'],
    // function($, XELib, Noti, Veri) {
    function($, XELib, Noti) {
    'use strict';
    return {

        /**
         * Fetch the token for the current selected URL. Store in temp sessionStorage
         *
         * @param null
         * @return void
         */
        getTokenReady: function () {
            // Check to see if this is the first time landing or not.
            var url = $('#id_available_moodle_instances option:selected').text();
            XELib.getTokenForURL(url).then(function (response) {
                if (response.success == true) {
                    sessionStorage.setItem("currentToken", response.data);
                    sessionStorage.setItem("currentUrl", url);
                } else {
                    // TODO: Send Notification to user that token is crap
                }
            });
        },

        /**
         * Get group data from a course.
         * @param {object} the json object sent to the server
         * @return resolved data
         */
        getGroupData: function (params) {
            return XELib.jaxyPromise({
                'call': 'getGroupData',
                'params': params,
                'class': 'router'
            });
        },

        /**
         * Moodle removes any changed option/select elements. In order to maintain
         * the data push data into hidden some that are in the form's page.
         *
         * @param {string} tag name of the tag to be changed
         * @param {string} value the value to insert
         * @return {void}
         */
        setHiddenValue: function (tag, value) {
            $('input[name='+tag+']').val(value);
        },

        /**
         * Verify the source course and group
         *
         * @param {object} params the json object sent to the server
         * @return {Object} resolved data
         */
        verifySourceCourse: function (params) {
            return XELib.jaxyPromise({
                'call': 'verifyCourse',
                'params': params,
                'class': 'router'
            });
        },

        verifyDestCourse: function (params) {
            var new_params = {
                'type': 'GET',
                'url': sessionStorage.getItem("currentUrl") + '/webservice/rest/server.php',
                'data': {
                    'wstoken': sessionStorage.getItem("currentToken"),
                    'wsfunction': 'core_course_get_courses_by_field',
                    'moodlewsrestformat': 'json',
                    'field': 'shortname',
                    'value': params.coursename
                }
            };
            return XELib.jaxyRemotePromise(new_params);
         },

        // ==================================================================
        // ==================================================================
        // ==================================================================
        // ==================================================================
        // ==================================================================
        // ==================================================================
        // ==================================================================
        // ==================================================================

        verifyDest: function (jqo) {
            let that = this;
            console.log("verifyDest() -> what is jqo: ", jqo);
            console.log("mappings_form -> verifyDest() -> what is this: ", this);
            var destname = $("#id_destcourseshortname").val();

            that.verifyDestCourse({
                'coursename': destname
            }).then( function (response){
                if (("courses" in response)) {
                    // how many courses were retrieved
                    if (response.courses.length == 1) {
                        that.setHiddenValue('destcourseid', response.courses[0].id);
                        Noti.callNoti({
                            message: "Destination course is there and waiting for you.",
                            type: 'success'
                        });
                    } else {
                        Noti.callNoti({
                            message: "There seems to be more than one course with that shortname.",
                            type: 'warn'
                        });
                    }
                } else {
                    // FALSE
                    Noti.callNoti({
                        message: "The course: " + destname + " was not found on the destination server.",
                        type: 'error'
                    });
                }
            });
        },

        // Verify the Course and Group Names.
        verifySource: function (jqo) {
            let that = this;
            console.log("verifySource() -> what is jqo: ", jqo);
            console.log("mappings_form -> verifySource() -> what is this: ", this);
            var coursename = $("#id_srccourseshortname").val(),
                groupname = "";

            if (sessionStorage.getItem("xes_autocomplete") == "1") {
                groupname = $("#id_srccoursegroupnameselect").val();
            } else {
                groupname = $("#id_srccoursegroupname").val();
            }

            if (coursename.length < 1) {
                // User forgot to enter a course name.
                Noti.callNoti({
                    message: "Ooops, you forgot to enter a course short name",
                    type: 'error'
                });
                return;
            }

            if (groupname.length < 1) {
                // User forgot to enter a course name.
                Noti.callNoti({
                    message: "Ooops, you forgot to enter a group name",
                    type: 'error'
                });
                return;
            }
            that.verifySourceCourse({
                'coursename': coursename,
                'groupname': groupname
            }).then( function (response) {
                if (response.success == false) {
                    Noti.callNoti({
                        message: response.msg,
                        type: 'error'
                    });
                } else {
                    // Populate the hidden fields since we are here.
                    that.setHiddenValue('srccourseid', response.data.id);
                    that.setHiddenValue('srccoursegroupid', response.data.groupid);
                    Noti.callNoti({
                        message: "Everything checks out for the sourse course and group.",
                        type: 'success'
                    });
                }
            });
        },

        // Any changes to the group element, update the hidden.
        updateHidden: function (jqo) {
            let that = this;
            // console.log("updateHidden() -> what is jqo: ", jqo);
            // console.log("mappings_form -> updateHidden() -> what is this: ", this);
            var new_value = $(jqo).find("option:selected").attr('value');
            var new_text = $(jqo).find("option:selected").text();
            that.setHiddenValue('srccoursegroupname', new_text);
            that.setHiddenValue('srccoursegroupid', new_value);
        },

        srcCourse: function (jqo, fuck) {
            var that = this;
            console.log("srcCourse() -> what is jqo: ", jqo);
            console.log("srcCourse() -> what the fuck: ", fuck);
            console.log("mappings_form -> srcCourse() -> what is this: ", this);

            // $(this).children("option:selected").text();
            // $(this).find(":selected").val();
            // $(this).val();

            // if ($(this).val()) {
            if (jqo.val()) {
                // change invokes any change so only make an ajax call if there is value
                that.getGroupData({
                    'courseid': jqo.val(),
                    'coursename': $( "#id_srccourseshortname option:selected" ).text()
                },).then(function (response) {
                    // if the text is disabled then use select
                    if (response.count == 1) {
                        // Single entry so let's update the text field
                        $('#id_srccoursegroupnameselect').val(response.data.groupname);
                        $('#id_srccoursegroupname').val(response.data.groupname);

                    } else if (response.count > 1) {
                        // Multiple groups, so let's unhide the select
                        $('#id_srccoursegroupnameselect').empty();
                        var first_choice = "";
                        for (let i in response.data) {
                            // This is to store the first select and to be used.
                            if (first_choice == "") {
                                first_choice = {
                                    groupid: response.data[i].groupid,
                                    groupname: response.data[i].groupname
                                };
                            }
                            $('#id_srccoursegroupnameselect')
                                .append($("<option></option>")
                                .attr("value", response.data[i].groupid)
                                .text(response.data[i].groupname));
                        }

                        // Now that it's been populated, set the hidden elements to match the first
                        // select option.
                        that.setHiddenValue('srccoursegroupname', first_choice.groupname);
                        that.setHiddenValue('srccoursegroupid', first_choice.groupid);
                    } else {
                        // TODO: The count is neither 1 or greate than 1 so no groups?
                        // display no groups.
                    }

                });
            } else {
                // if there is no value in the course name then clear out the group name.
                $('#id_srccoursegroupnameselect').empty();
                $('#id_srccoursegroupnametext').text();
                $('#id_srccoursegroupnameselect')
                    .append($("<option></option>")
                    .attr("value", 0)
                    .text("Please search for a course first"));
            }
        },

        destCourse: function (jqo) {
            // let that = this;
            console.log("destCourse() -> what is jqo: ", jqo);
            console.log("mappings_form -> destCourse() -> what is this: ", this);
        },
    };
});








































