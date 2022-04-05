define([
    'jquery',
    'local_tcs/jaxy',
    'local_tcs/tcs_lib',
    'local_tcs/_libs/jquery.autocomplete',
    'local_tcs/tcs_autocomp',
    'core/modal_factory',
    'core/modal_events',
    'core/templates',
    'local_tcs/tcs_student_table'
    // 'local_tcs/PNotifyButtons'

], function($, jaxy, TCSLib, autocomplete, tcs_autocomp, ModalFactory, ModalEvents, Templates, StudentTable) { // , PNotifyButtons

    'use strict';
        /* eslint-disable */
        var keyStrokeCount = 0,
            isBarCodeReader = 0,
            temp_userid = 0,
            mask = 0,
            stored_idnumber = 0,
            
            $auto_obj = $('.tcs_autocomp_in');
        /* eslint-enable */

    // TODO: Make default select on radio buttons, right now it's last selection
    return {

        auto_comp_modal: 0,
        /**
         * Description: TODO - fill this in
         * @param {object} a list of users to use for searching
         */
        loadUserExams: function(user) {
            // console.log("What is the user data to search: ", user);
            // var promiseObj = new Promise(function(resolve, reject) {
            var promiseObj = new Promise(function(resolve) {
                // return;
                // console.log("What is the attempt value: " + $('#student_attempt_bypass').prop("checked"));

                jaxy.tcsAjax(JSON.stringify({
                    'call': 'loadUserExams',
                    'params': {
                        'userid': user.data.uofl_id,
                        'username': user.data.username,
                        'isnum': true,
                        'attempt_override': $('#student_attempt_bypass').prop("checked"),
                        'ax': true,

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

        /** Find what exams the user has and display in modal
         * Description:
         * @param {object} a list of users to use for searching
         */
        findUserData: function(data) {
            // console.log("findUserData() -> going to FETCH DATA........");
            // console.log("findUserData() -> what is the data: ", data);
            var that = this;
            // Let's find the User's Exam Information
            this.loadUserExams(data).then(function(response) {

                // console.log("findUserData() -> what is the FINAL DATA: ");
                // console.table(response);
                if ('dash_hash' in response) {
                    data.data.exams = response.exams;

                    // console.log("findUserData() -> what is response: " + response);
                    // console.table("findUserData() -> what is response: " + response);
                    // var trigger = $('#enter_student_modal');

                    ModalFactory.create({
                        type: ModalFactory.types.SAVE_CANCEL,
                        title: 'Enter Student For Exam',
                        body: Templates.render('local_tcs/modal_enterstudent', data.data),
                        large: true
                        // can_recieve_focus: button
                        // footer: 'Stuff here Yo',
                    })
                    .then(function(modal) {
                        modal.setSaveButtonText('Enter Student');
                        var root = modal.getRoot();
                        root.on(ModalEvents.save, function() {
                            var exam_type = 0;
                            // Do something to delete item
                            // console.log("Student has entered into the exam arena");
                            // var test1 = $("input[name='tcs_identity_check']:checked").val();
                            // $('#tcs_comments_on_student').val();
                            // Either the form will GO or Fail, either way let's set the modal flag to OFF.
                            // console.log("INSIDE Modal THEN, auto_comp_modal set to zero, currently at: " + that.auto_comp_modal);
                            that.auto_comp_modal = 0;

                            if (that.checkForm()) {
                                // console.log("Form is good to go");
                                if ($("input[name='tcs_exam_check']:checked").data("examname").split("ManualExam-").length === 2) {
                                    exam_type = 1;
                                }

                                StudentTable.addStudent({
                                    'username': data.data.username,
                                    'uofl_id': data.data.uofl_id,
                                    'exam_id': $("input[name='tcs_exam_check']:checked").val(),
                                    'id_type': $("input[name='tcs_identity_check']:checked").val(),
                                    'room': $("input[name='exam_room_check']:checked").val(),
                                    'comments': $('#tcs_comments_on_student').val(),
                                    'exam_type': exam_type
                                });
                            } else {
                                // console.log("Form FAIL");

                                TCSLib.showMessage({
                                    'msg_type': 'error',
                                    'show_msg': {
                                        'title': 'Ooops',
                                        'message': 'Please select an exam for the student',
                                        'position': 'center'
                                    }
                                });

                                // 'show_msg': {
                                //     "title": "Error",
                                //         "message": "Sorry but the Quiz Settings IP Restriction is too short!"
                                // }
                                return false;
                            }
                        });

                        // Handle hidden event.
                        modal.getRoot().on(ModalEvents.hidden, function() {
                            // Destroy when hidden.
                            // console.log("Modal DESTROY, setting auto_comp_modal to zero, currently at: " + that.auto_comp_modal);
                            that.auto_comp_modal = 0;
                            $('.tcs_autocomp_in').val('');
                            $('.tcs_autocomp_in').focus();
                            modal.destroy();
                        });
                        modal.show();
                    // .done(function(modal) {
                        // Do what you want with your new modal.
                    });
                } else {
                    // make sure this is set to OFF since user is being removed.
                    that.auto_comp_modal = 0;
                    // console.table("tcs_autocomp_stud -> findUserData() -> Swipe Remove, what is response (table): " + response);
                    if ('swipe_remove' in response) {
                        // console.log("tcs_autocomp_stud -> findUserData() -> going to call removeStudentFromTable.......");
                        // if ('swipe_remove' in response.extra && response.extra.swipe_remove == true) {
                            // iziToast.show({
                            //     title: 'Success',
                            //     message: response.msg,
                            //     position: 'topRight'
                            // });

                        StudentTable.removeStudentFromTable(response);

                            // ====== OR ======
                            // Ask to remove student
                            /*
                            iziToast.show({
                                theme: 'dark',
                                icon: 'icon-person',
                                title: 'Hey',
                                message: 'Welcome!',
                                position: 'center', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
                                progressBarColor: 'rgb(0, 255, 184)',
                                buttons: [
                                    ['<button>Ok</button>', function (instance, toast) {
                                        alert("Hello world!");
                                    }, true], // true to focus
                                    ['<button>Close</button>', function (instance, toast) {
                                        instance.hide({
                                            transitionOut: 'fadeOutUp',
                                            onClosing: function(instance, toast, closedBy){
                                                console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                                            }
                                        }, toast, 'buttonName');
                                    }]
                                ],
                                onOpening: function(instance, toast){
                                    console.info('callback abriu!');
                                },
                                onClosing: function(instance, toast, closedBy){
                                    console.info('closedBy: ' + closedBy); // tells if it was closed by 'drag' or 'button'
                                }
                            });
                            */
                        // } else {
                            // console.log("=====>>>>>  ERROR 1 -> findUserData  <<<<<=======");
                        // }
                        $('.tcs_autocomp_in').val('');
                        // $('.tcs_autocomp_in').autocomplete().clear();
                        $('.tcs_autocomp_in').autocomplete().hide();
                    } else if (response.success == "false") {

                        TCSLib.showMessage({
                            'msg_type': 'error',
                            'show_msg': {
                                'title': 'Ooops',
                                'message': response.msg,
                                'position': 'center'
                            }
                        });
                    } else {
                        // console.log("Ok, something is funky here");
                        console.log("=====>>>>> ERROR 2 -> findUserData  <<<<<=======");
                    }
                }
            });
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
            // console.log("tcs_autocomp_stud -> onSelect() -> going to call findUserData");
            // console.log("tcs_autocomp_stud -> onSelect() -> what is that.auto_comp_modal: " + this.auto_comp_modal);
            this.findUserData(suggestion);
        },
        onSearchComplete: function (suggestion) {
            // console.log("tcs_autocomp_stud -> onSearchComplete() -> going to call findUserData");
            // console.log("tcs_autocomp_stud -> onSearchComplete() -> what is that.auto_comp_modal: " + this.auto_comp_modal);
            if (this.auto_comp_modal == 0) {
                // console.log("IF -> auto_comp_modal is set to: " + this.auto_comp_modal);
                this.auto_comp_modal = 1;
                this.findUserData(suggestion);
            // } else {
            //     console.log("NOT going to call findUserData");
            //     console.log("ELSE -> auto_comp_modal is set to: " + this.auto_comp_modal);

            }
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
            // console.log("tc_autocomp_stud => initiateAutoComp() ----->>> DO YOU SEE ME ONCE????? <<<-------");

            var that = this;
            // $('#autocomplete').focus();
            // $('#autocomplete').autocomplete({

            // #autocomplete_stud is the id in the search template
            tcs_autocomp.initiateAutoComp(users, that, '#autocomplete_stud');

            $("body").on('click', '#tcs_enterstudent_table > tbody > tr', function (event) {
                // console.log("Clicked inside the row BITCH, what is the type: " + event.target.type);
                if (event.target.type !== 'radio') {
                    $(':radio', this).trigger('click');
                }
            });

            // $('#autocomplete').keyup(function (e) {
            //     console.log("key up: " + e);
            // });

            // $('.tcs_autocomp_in').keypress(function (e) {
            //     console.log('facker test 0' + e);
            // });

            // $('.tcs_autocomp_in').keyup(function (e) {
            //     console.log('facker test 1' + e);
            // });

            // $('#autocomplete_stud').keypress(function (e) {
            //     console.log('facker test 3' + e);
            // });
            // $('#autocomplete_stud').keyup(function (e) {
            //     console.log('facker test 4' + e);
            // });

            $auto_obj = $('.tcs_autocomp_in');

            $auto_obj.keypress(function (e) {
            // $('.tcs_autocomp_in').keypress(function (e) {
                // Here's an example of the card being scanned:
                // % 001028120 ?; 6018190723618365 ? +691606639 ?

                /*  using these variables to store
                    var keyStrokeCount = 0,
                    isBarCodeReader = 0,
                    temp_userid = 0,
                    mask = 0,
                    stored_idnumber = 0,
                    $auto_obj = $('#autocomplete');
                */
                // console.log("-------------------------------");
                // console.log("keypress: " + e);
                // console.log("-------------------------------");
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

                    // console.log("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$");
                    // console.log("keypress -> length is 9 and it's barcode");
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
                    // console.log("What is the id: " + actual_id);
                    that.temp_userid = '';
                    // that.$userid.val(actual_id);
                    //
                    that.keyStrokeCount = 0;
                    that.isBarCodeReader = 0;
                    // that.clear_fields_for_newStudentEntry();
                    // // that.$userid.val(actual_id);
                    // // that.$comments.html('');
                    // that.process_entry(actual_id);
                    // console.log("Whats the final id of this MORON: " + actual_id);
                    that.stored_idnumber = actual_id;
                    // console.log("keypress -> PROCESS ENTRY NOW PART B");
                    // $('#autocomplete').val(actual_id);
                    $auto_obj.val(actual_id);
                    // console.log("444444444 --> What is the final " + that.stored_idnumber);
                    return true;
                }
                // console.log("777777 --> What is the final " + that.stored_idnumber);
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
