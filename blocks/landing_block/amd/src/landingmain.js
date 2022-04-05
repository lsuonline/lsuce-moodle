define(['jquery'], function ($) {
    'use strict';

    return {


        // ==========================================================
        // variabls for this object
        // ==========================================================
        username: null,
        lbversion: "2",
        instances: [
            'current_title',
            'long_term_title',
            'past_title',
            'future_title'
        ],

        // ==========================================================
        // methods for this object
        // ==========================================================
        runAJAX: function(ajax_data) {
            var data_to_pass = {},
                key = null;

            // we are accessing data within Moodle
            // let's gather the params into a bundle
            for (key in ajax_data.params) {
                if (ajax_data.params.hasOwnProperty(key)) {
                    data_to_pass[key] = ajax_data.params[key];
                }
            }
            // console.log("going to call ajax, what we sending:");
            // console.log("url: " + ajax_data.url);
            // console.log("data_to_pass: ", data_to_pass);
            // console.log("storedData: ", ajax_data.storedData);

            return $.ajax({
                url: ajax_data.url,
                context: this,
                data: data_to_pass,
                type: (ajax_data.request === undefined) ? 'GET' : ajax_data.request,
                storedData: ajax_data.storedData,

            }).promise();
        },

        buildTermBlock: function (results) {

            var result = JSON.parse(results),
                instance = result.instance,
                html = "",
                assign_length = 0,
                href = "",
                instance_name = "",
                this_url = "",
                count = 0,
                add_show_hide = "",
                collapse = "",
                key = "",
                this_assign = "",
                i = 0,
                instance_tag = "";

            instance_name = instance;

            instance_tag = '#lb_expand_term_details_' + instance;
            this_url = $(instance_tag).data('url');

            // The result should either have data or html, process accordingly
            // console.log("Do we have data.....", result);
            if (result.hasOwnProperty("data") && result.data.length > 0) {

                for (i = 0; i < result.data.length; i++) {
                // for (let key of result.data) {
                    key = result.data[i];

                    // if (key.overview.is_ta === true) {
                    add_show_hide = '<span class="pull-right"><a data-toggle="collapse" data-item-id="' +
                        instance_name + '" data-parent="#lb_my_courses_accord_' +
                        instance_name + '" href="#collapse_' + instance + '_' + count +
                        '" aria-expanded="true" id="lb_show_hide_toggle">Show/Hide</a></span>';
                    // } else {
                        // add_show_hide = "";
                    // }

                    if (instance == "current_title") {
                        collapse = "show";
                    }

                    // meh.....let's just expand all courses by default
                    collapse = " show";

                    if (typeof (key.overview) === "string") {

                        html += '<div class="row-fluid">' +
                            '<div class="accordion uleth_accordion_snip" role="tablist" ' +
                            'aria-multiselectable="true" id="lb_my_courses_accord_'
                            + instance + '">' +
                            '<div class="card">' +
                                '<div class="card-header" role="tab" id="' + instance_name + '_heading">' +
                                    '<a data-parent="#lb_my_courses_accord_' + instance + '" href="' + key.this_url + '">' +
                                        '' + key.title_msg + '' +
                                    '</a>' +
                                '</div>' +
                                '<div id="collapse_' + instance + '_' + count + '" class="' + collapse +
                                    ' collapse in" role="tabpanel" aria-labelledby="' +
                                    instance_name + '_heading">' +
                                    '<div class="card-body">';

                        html += '<div class="box coursebox"><a href="' + this_url +
                            '">You are currently not registered in any course OR your Instructor ' +
                            'has not opened your course for access yet.</a></div>';
                        html += '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

                    } else {
                        html += '<div class="row-fluid">' +
                        '<div class="accordion uleth_accordion_snip" role="tablist" aria-multiselectable="true" ' +
                        'id="lb_my_courses_accord_' + instance + '">' +
                            '<div class="card">' +
                                '<div class="card-header" role="tab" id="' + instance_name +'_heading">' + add_show_hide +
                                    '<a data-parent="#lb_my_courses_accord_' + instance + '" href="' + key.this_url + '">' +
                                        '' + key.title_msg + '' +
                                    '</a>' +
                                '</div>' +
                                '<div id="collapse_' + instance + '_' + count + '" class="' + collapse +
                                    ' collapse in" role="tabpanel" aria-labelledby="'+ instance_name +'_heading">' +
                                    '<div class="card-body">';
                        // Now the Course Teachers, Assignments and Quizzes
                        // =========================================================
                        // display the teachers
                        // =========================================================
                        html += '<div class="teachers">';
                        // for (let teacher of key.overview.teachers) {

                        for (var x = 0; x < key.overview.teachers.length; x++) {
                        // for (let teacher of key.overview.teachers) {

                            html += '<h5>Teacher: ' + key.overview.teachers[x].fullname + '</h5>';
                            // html += '<h5>Teacher: ' + teacher.fullname + '</h5>';
                        }
                        html += '</div>';

                        // =========================================================
                        // display the assignments
                        // =========================================================
                        assign_length = key.overview.assign.length;


                        html += '<div class="lb_assignments_container">' +
                            '<div class="row lb_assign_quiz_header">' +
                                '<div class="col col-sm-6 lb_assign_quiz_header_title">' +
                                    '<h4>Course Assignments</h4>' +
                                '</div>' +
                                '<div class="col col-sm-6">' +
                                    '<span class="lb_assignments_toggle pull-right">Show/Hide</span>' +
                                '</div>' +
                            '</div>' +

                            '<div class="lb_assignment_panel">';

                        if (assign_length > 0) {
                            for (var z = 0; z < key.overview.assign.length; z++) {

                                this_assign = key.overview.assign[z];
                            // for (let this_assign of key.overview.assign) {

                                href = this_assign.wwwroot + '/mod/assign/view.php?id=' + this_assign.coursemodule;
                                html += '<div class="assign overview">' +
                                   '<div class="name">' + this_assign.strassignment + ': ' +
                                   '<a ' + this_assign.dimmedclass +
                               'title="' + this_assign.strassignment + '" ' +
                               'href="' + href + '">' + this_assign.assign_name +
                               '</a></div>';

                                //---------------------
                                if (this_assign.duedate) {
                                    html += '<div class="info">' + this_assign.strduedate + ': ' +
                                    this_assign.userdate + '</div>';
                                } else {
                                    html += '<div class="info">' + this_assign.strduedateno + '</div>';
                                }
                                if (this_assign.cutoffdate) {
                                    if (this_assign.cutoffdate == this_assign.duedate) {
                                        html += '<div class="info">' + this_assign.strnolatesubmissions + '</div>';
                                    } else {
                                        html += '<div class="info">' + this_assign.strcutoffdate + ': ' +
                                        this_assign.userdate + '</div>';
                                    }
                                }

                                // Show only relevant information+
                                if (this_assign.submitdetails !== false) {
                                    html += this_assign.submitdetails;
                                }

                                if (this_assign.gradedetails !== false) {
                                    html += this_assign.gradedetails;
                                }
                                html += '</div>';
                            }
                            // end of for

                            html += '</div>'; // closes the assignment panel
                            // html += '</div>';

                        } else {
                            html += '<div class="lb_no_data_to_show">Currently there are no assignments with a due date</div>';
                        }

                        html += '</div>'; // closes the assignment container
                        // =========================================================
                        // display the quizzes
                        // =========================================================

                        html += '<div class="lb_quiz_container">' +
                            '<div class="row lb_assign_quiz_header">' +
                                '<div class="col col-sm-6 lb_assign_quiz_header_title">' +
                                    '<h4>Course Exams</h4>' +
                                '</div>' +
                                '<div class="col col-sm-6">' +
                                    '<span class="lb_quiz_toggle pull-right">Show/Hide</span>' +
                                '</div>' +
                            '</div>';
                        html += '<div class="lb_quiz_panel">';

                        if (typeof (key.overview.quiz) == "object") {
                            // do we have data?
                            if (key.overview.quiz.hasOwnProperty(key.courseid)) {
                                html += key.overview.quiz[key.courseid].quiz;
                            } else {
                                html += '<div class="lb_no_data_to_show">Currently there are no quizzes.</div>';
                            }

                        } else {
                            html += '<div class="lb_no_data_to_show">Currently there are no quizzes.</div>';
                        }

                        html += '</div></div>';

                        // end of quizzes ------------------------------------------
                        html += '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>';

                        // display the quizzes
                        count++;

                    } // end of displaying teachers, quizzes, etc.
                }
            } else {
                // console.log("FAIL, we DO NOT have data, what is result: ", result);
                if (result.hasOwnProperty("html") && result.html !== 'no courses found') {
                    html = result.html;
                } else {

                    if (result.html === 'no courses found') {
                        // ok, there is no content, do we have a custom message from the server?
                        if (result.no_course.length > 1) {
                            // make the custom message linkable to the server
                            html = '<div class="box coursebox"><a href="' + this_url + '">' + result.no_course + '</a></div>';
                        } else {
                            // just in case
                            html = '<div class="box coursebox"><a href="' + this_url +
                                '">You are currently not registered in any course OR your Instructor has ' +
                                'not opened your course for access yet.</a></div>';
                        }
                    } else {
                        // console.log("ERROR - Admin, user did not have any results for courses, need to fix ASAP");
                        html = '<div class="box coursebox"><a href="' + this_url +
                            '">You are currently not registered in any course OR your Instructor has not opened ' +
                            'your course for access yet.</a></div>';
                    }
                }
            }

            // make sure the current term is expanded
            if (instance == "current_title" && !$('#collapse_current_title').hasClass("in")) {
                $('#collapse_current_title').addClass("in");
            }

            $(instance_tag).html("");
            $(instance_tag).html(html);
        },

        callAndReturnAJAX: function (data) {

            this.runAJAX({
                url: data.url,
                params: data.params,
                request: data.request,
                storedData: data.storedData

            }).then(function (results) {

                // get which instance we are on, storedData is persitant in the ajax object
                this.buildTermBlock(results);
            });
        },

        getInstanceCourses: function () {

            var arrayLength = null,
                site_url = null,
                going_to = null,
                the_request = 'GET',
                div_id = null,
                params = {},
                all_params = {},
                // found_201403 = null,
                i = null;
                // old_fart = false;

            arrayLength = this.instances.length;
            for (i = 0; i < arrayLength; i++) {

                // reset these variables:
                params = {};
                all_params = {};
                // old_fart = false;

                // skip the instance that already pulled data from the local db
                if ($('#lb_expand_term_details_' + this.instances[i]).data("local_info")) {
                    continue;
                }

                div_id = "#lb_expand_term_details_" + this.instances[i];
                site_url = $(div_id).data("url");

                // site was not found so it's not active, skip it
                if (site_url === undefined) {
                    continue;
                }
                // found_201403 = site_url.indexOf("201403");
                params = {
                    'instance_name': this.instances[i],
                    'username': this.username,
                    'rawdata': false,
                    'caller': this.lbversion
                };

                // if we are using a newer code base use this section
                // else call legacy page
                // if (found_201403 === -1) {
                // if (found_201402 == -1 && found_201403 == -1 && found_long_term == -1) {
                going_to = site_url + 'blocks/landing_block/lib/ajax.php';

                // } else {
                //     old_fart = true;
                //     going_to = site_url + 'blocks/landing_block/return.php';
                //     the_request = 'POST';
                //     params.username = this.username;
                // }

                all_params = {
                    'call': 'getCourseTitle',
                    'params': params,
                    'class': 'LBAjax',
                    'username': this.username
                };

                if ($(div_id).is(':empty') || $(div_id).find('i').hasClass('fa-spinner')) {

                    this.callAndReturnAJAX({
                        'url': going_to,
                        'params': all_params,
                        'request': the_request,
                        'storedData': {
                            'instance': this.instances[i]
                        }
                    });
                }
            }
        },

        callStartups: function () {

            $(".block.block_landing_block").on("click", '[class^="lb_course_term_"]', function(e) {

                var oTarget = $(e.target).children();
                oTarget.toggleClass('fa-chevron-right fa-chevron-down ', 200);
            });

            $(".block.block_landing_block").on("click", ".lb_assign_quiz_header", function() {
                var $header = "",
                    $content = "";

                $header = $(this);
                // getting the next element
                $content = $header.next();
                // open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
                $content.slideToggle(200, function () {
                    // do something with the toggle if you want here
                });
            });
        },

        init: function () {
            // console.log("landing_block -> main.js -> init() -> START");

            this.username = $('.main_landing_block_notifications_elem').data("internal_username");
            this.lbversion = $('.main_landing_block_notifications_elem').data("lbversion");
            this.getInstanceCourses();
            this.callStartups();
        }
    };
});
