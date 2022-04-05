define([
    'jquery',
    'local_tcs/jaxy',
    'local_tcs/tcs_lib',
    'local_tcs/_libs/jquery.autocomplete',
    'local_tcs/tcs_autocomp',
    'core/modal_factory',
    'core/modal_events',
    'core/templates',
    // 'local_tcs/tcs_student_table'
    // 'local_tcs/PNotifyButtons'
// ], function ($, jaxy, TCSLib, autocomplete, tcs_autocomp, ModalFactory, ModalEvents, Templates, StudentTable) {
], function ($, jaxy, TCSLib, autocomplete, tcs_autocomp, ModalFactory, ModalEvents, Templates) {
    'use strict';
        // var keyStrokeCount = 0,
        //     isBarCodeReader = 0,
        //     temp_userid = 0,
        //     mask = 0,
        //     stored_idnumber = 0,
        //     $auto_obj = $('#autocomplete');

    // TODO: Make default select on radio buttons, right now it's last selection
    return {
        loadUserExams: function(user) {
            // console.log("What is the user data to search: ", user);
            // var promiseObj = new Promise(function(resolve, reject) {
            var promiseObj = new Promise(function(resolve) {
                // return;
                jaxy.tcsAjax(JSON.stringify({
                    'call': 'loadUserExams',
                    'params': {
                        'userid': user.data.uofl_id,
                        'username': user.data.username,
                        'isnum': true,
                        'ax': true
                    },
                    'class': 'StudentListAjax',
                })).then(function(response) {

                    resolve(response);
                    // var result = JSON.parse(response);
                    // console.log("loadUserExams() -> what is the result: ", result);
                    // resolve(JSON.parse(result.data));

                    // TODO: this will need to be loaded into the autocomplete library

                    // }
                });
            });
            return promiseObj;
        },

        addUserAdmin: function(data) {
            return TCSLib.jaxyPromise({
                'call': 'addUser',
                'params': data,
                'class': 'UserAdmin'
            });
        },

        removeUserAdmin: function(data) {
            return TCSLib.jaxyPromise({
                'call': 'removeUser',
                'params': data,
                'class': 'UserAdmin'
            });
        },

        addUserModal: function(data) {
            // console.log("findUserData() -> Do Stuff........");
            // console.log("findUserData() -> what is the data: ", data);
            var that = this;
            // Let's find the User's Exam Information

            // return;

            // this.loadUserExams(data).then(function(response) {

            //     console.log("findUserData() -> what is the FINAL DATA: ", response);
            //     if ('hash' in response) {
            //         data.data.exams = response.exams;

            //         console.log("findUserData() -> FINAL DATA to be sent to template: ", data.data);
            //         var trigger = $('#enter_student_modal');
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: 'Add TCS Admin User',
                body: Templates.render('local_tcs/modal_adduseradmin', data.data),
                large: true
                // can_recieve_focus: button
                // footer: 'Stuff here Yo',
            })
            .then(function(modal) {
                modal.setSaveButtonText('Add User');
                var root = modal.getRoot();
                root.on(ModalEvents.save, function() {
                    // var exam_type = 0;
                    // Do something to delete item
                    // console.log("Student has entered into the exam arena");

                    // var level = $("input[name='tcs_user_admin_form']:checked").val();

                    that.addUserAdmin({
                        id: $('#tcs_uaf_id').val(),
                        username: $('#tcs_uaf_username').val(),
                        access_level: $("input[name='tcs_user_admin_form']:checked").val()
                    }).then(function (response) {
                        // var new_row_id = $('#tcs_user_admin_table').bootstrapTable('getOptions').totalRows;
                        // console.log("ADDING A NEW user admin what is response: ", response);
                        // console.log("and its new row id: " + new_row_id);
                        $('#tcs_user_admin_table').bootstrapTable('insertRow', {
                            index: 0,
                            row: {
                                'id': response.data.id,
                                'userid': response.data.userid,
                                'name': response.data.name,
                                'username': response.data.username,
                                'access_level': response.data.access_level,
                                'exams': response.data.exams
                            }
                        });
                        TCSLib.showMessage(response);
                    });
                    // $('#tcs_comments_on_student').val();
                    // if (that.checkForm()) {
                    //     console.log("Form is good to go");
                    //     if ($("input[name='tcs_exam_check']:checked").data("examname").split("ManualExam-").length === 2) {
                    //         exam_type = 1;
                    //     }

                    //     StudentTable.addStudent({
                    //         'username': data.data.username,
                    //         'uofl_id': data.data.uofl_id,
                    //         'exam_id': $("input[name='tcs_exam_check']:checked").val(),
                    //         'id_type': $("input[name='tcs_identity_check']:checked").val(),
                    //         'room': $("input[name='exam_room_check']:checked").val(),
                    //         'comments': $('#tcs_comments_on_student').val(),
                    //         'exam_type': exam_type
                    //     });
                    // } else {
                    //     console.log("Form FAIL");

                    //     TCSLib.showMessage({
                    //         'msg_type': 'error',
                    //         'show_msg': {
                    //             'title': 'Ooops',
                    //             'message': 'Please select an exam for the student',
                    //             'position': 'center'
                    //         }
                    //     });

                    //     // 'show_msg': {
                    //     //     "title": "Error",
                    //     //         "message": "Sorry but the Quiz Settings IP Restriction is too short!"
                    //     // }
                    //     return false;
                    // }
                });

                // Handle hidden event.
                modal.getRoot().on(ModalEvents.hidden, function() {
                    // Destroy when hidden.
                    console.log("Modal is now going by by bitch");
                    $('.tcs_autocomp_in').val('');
                    $('.tcs_autocomp_in').focus();
                    modal.destroy();
                });
                modal.show();
            // .done(function(modal) {
                // Do what you want with your new modal.
            });

            // TCSLib.showMessage({
            //     'msg_type': 'error',
            //     'show_msg': {
            //         'title': 'Sorry',
            //         'message': response.msg,
            //         'position': 'center'
            //     }
            // });
            // $('.tcs_autocomp_in').val('');
            // $('.tcs_autocomp_in').autocomplete().hide();
        },


        checkForm: function () {

            // console.log("Do we have exam_id: " + $("input[name='tcs_exam_check']:checked").val());
            // console.log("Do we have id_type: " + $("input[name='tcs_identity_check']:checked").val());
            // console.log("Do we have room: " + $("input[name='exam_room_check']:checked").val());
            // console.log("Do we have comments: " + $('#tcs_comments_on_student').val());

            return $("input[name='tcs_exam_check']:checked").val() &&
                $("input[name='tcs_identity_check']:checked").val() &&
                $("input[name='exam_room_check']:checked").val();
        },

        onSelect: function (suggestion) {
            // console.log("made it to autocomp exam onSelect");
            this.addUserModal(suggestion);
        },
        onSearchComplete: function () {
            console.log("onSearchComplete -> This function is dead");
            // this.findUserData(suggestion);
        },

        // ========================================================================================
        // ========================================================================================
        // ========================================================================================

        /** START - Initialize The AutoComplete
         * Description: Initialize The AutoComplete and register any binding events.
         * @param {object} a list of users to use for searching
         */
        initiateAutoComp: function(users) {
            // currently trying this one:
            // https://github.com/devbridge/jQuery-Autocomplete
            // console.log("EXAM - initiateAutoComp() -> going to initiate autocomplete");
            // console.log("EXAM - initiateAutoComp() -> what is users: ", users);
            var that = this;

            // #autocomplete_admin is the id in the search template
            tcs_autocomp.initiateAutoComp(users, that, '#autocomplete_admin');

            /*
            $("body").on('click', '#tcs_enterstudent_table > tbody > tr', function (event) {
                // console.log("Clicked inside the row BITCH, what is the type: " + event.target.type);
                if (event.target.type !== 'radio') {
                    $(':radio', this).trigger('click');
                }
            });

            // $('#autocomplete').keypress(function (e) {
            $auto_obj.keypress(function (e) {
                // Here's an example of the card being scanned:
                // % 001028120 ?; 6018190723618365 ? +691606639 ?
                var actual_id = null,
                    charCode = e.which;
                // keycode 37 = %
                //  13 = return
                //  9 = tab
                that.keyStrokeCount = 0;
                //
                if ((e.keyCode === 37 || charCode === 37) && that.keyStrokeCount === 0) {
                    that.isBarCodeReader = 1;
                    // $('#tcs_std_list_spinner').css("visibility", "visible");
                    that.temp_userid = '';
                    that.mask = '';
                    // return;
                }
                // console.log("keypress -> what is the charcode: " + charCode + " and value: " + this.value);

                //
                that.keyStrokeCount++;
                //
                if (this.value.length === 9 && that.isBarCodeReader === 0) {

                    console.log("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$");
                    console.log("keypress -> length is 9 and it's barcode");
                    that.keyStrokeCount = 0;
                    // TCS.student_list.clear_fields_for_newStudentEntry();
                    // TCS.student_list.process_entry(this.value);
                    return true;
                }
                // do stuff with barcode entry
                if (that.isBarCodeReader) {
                    // store the input
                    that.temp_userid = that.temp_userid + String.fromCharCode(charCode);
                    // console.log("Building id, now its: " + that.temp_userid);
                    that.mask += "*";
                    // $('#autocomplete').val(that.mask);
                    $auto_obj.val(that.mask);
                }
                // if (/[%]\d+[?][;]\d+[?][+]\d+[?]/.test(TCS.student_list.temp_userid) && TCS.student_list.isBarCodeReader === 1) {
                // if the card reader reaches the end the keycode is 13 (return) process now.....
                if (charCode === 13 && that.isBarCodeReader === 1) {
                    // console.log("keypress -> regex and it's barcode");
                    //
                    // $('#tcs_std_list_spinner').css("visibility", "hidden");
                    //
                    // that.$exams.focus();
                    actual_id = that.temp_userid.substring(1, 10);
                    that.temp_userid = '';
                    // that.$userid.val(actual_id);
                    //
                    that.keyStrokeCount = 0;
                    that.isBarCodeReader = 0;
                    // that.clear_fields_for_newStudentEntry();
                    // // that.$userid.val(actual_id);
                    // // that.$comments.html('');
                    // that.process_entry(actual_id);
                    console.log("Whats the final id of this MORON: " + actual_id);
                    that.stored_idnumber = actual_id;
                    console.log("keypress -> PROCESS ENTRY NOW PART B");
                    // $('#autocomplete').val(actual_id);
                    $auto_obj.val(actual_id);
                    return true;
                }
            });
            */

            /** REMOVE User Admin - jQuery EVENT
            * Description: The exam is not actually removed but flips a "finished" switch. These entries
            *       will be at the end of the table.
            * @param {none} -
            * @return {none} -
            */
            $("body").on('click', '#tcs_remove_user_admin', function () {
                var row_data = $('#tcs_user_admin_table').bootstrapTable('getRowByUniqueId', $(this).data("id"));
                // console.log("What s the row data to delete: ", row_data);
                that.removeUserAdmin({
                    userid: row_data.userid,
                    rowid: row_data.id
                }).then(function (response) {
                    // console.log("DONE AJAX to remove user admin, what is the response: ", response);
                    if (response.msg_type == "success") {
                        // console.log("ok, need to update the table now, what is the rowid: " + response.rowid);
                        // remove the user from the list
                        // var this_array = [];
                        // this_array.push(response.rowid);

                        // **** NOTE ****
                        // To REMOVE the values HAVE to be string
                        $('#tcs_user_admin_table').bootstrapTable('remove', {
                            field: 'id',
                            values: response.rowid.toString()
                        });
                        TCSLib.showMessage(response);
                    } else {
                        console.log("ERROR with removing user");
                    }
                });
                // row_data.finished = "true";
                // that.updateTableRow(row_data);

            });
            /*
            $("body").on('keydown', '#tcs_enter_student_form', function (event) {
                console.log("Have hit keydown key");
            });

            // $('#tcs_enter_student_form').keypress(function (e) {
            $("body").on('keypress', '#tcs_enter_student_form', function (event) {
                console.log("Have hit keypress key");
                // tcs_enter_student_form
                if (event.which == 13) {
                    // $('form#login').submit();
                    console.log("Have hit the enter key");
                    if (that.checkForm()) {
                        console.log("Form is good to go");
                    } else {
                        console.log("Form FAIL");
                    }
                    return false;    //<---- Add this line
                }
            });

            $("body").on('submit', 'data-action="save"', function (event) {
            */
        }
    };
});
