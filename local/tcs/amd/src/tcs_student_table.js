define([
    'jquery',
    'local_tcs/jaxy',
    'local_tcs/tcs_lib',
    'core/modal_factory',
    'core/modal_events',
    'core/templates',

    'local_tcs/_bootstrap_libs/bootstrap-table', // this has to be before table-editable
    'local_tcs/_bootstrap_libs/bootstrap-editable',
    'local_tcs/_bootstrap_libs/bootstrap-table-editable',

/* eslint-disable */
], function ($, jaxy, TCSLib, ModalFactory, ModalEvents, Templates,
    BootstrapTable,
    editable,
    bootstrapTableEditable,
    // toolbar
) {

/* eslint-enable */
    'use strict';

    return {
        /** Get Table Data AJAX REQUEST -> PROMISE
         * Description: This will fetch all users currently in the Test Centre room
         * the callback will resolve with all the data.
         * @return resolved data
         */
        getTableData: function(data) {
            var promiseObj = new Promise(function(resolve) {
                jaxy.tcsAjax(JSON.stringify({
                    'call': 'getUsersInExam',
                    'params': data,
                    'class': 'StudentListAjax'
                })).then(function(response) {

                    // console.log("getTableData() -> what is the result: ", response);
                    var new_data = [];

                    for(var x in response.users_in_centre) {
                        new_data.push(response.users_in_centre[x]);
                    }
                    resolve(new_data);
                });
            });
            return promiseObj;
        },

        /** Update Table AJAX REQUEST -> PROMISE
         * Description: This will update a users comment or room#
         * the callback will resolve with a confirmation of success or fail and message.
         * @return resolved data
         */
        updateTableData: function(data) {
            // var promiseObj = new Promise(function(resolve, reject) {
            var promiseObj = new Promise(function(resolve) {
                // var new_params = {
                //     'id': data.id,
                //     'username': data.username,
                // };
                // if (data.hasOwnProperty('room')) {
                //     new_params.room = data.room
                // }
                // 'new_comment': data.comment,
                jaxy.tcsAjax(JSON.stringify({
                    'call': 'updateStudentList',
                    'params': data,
                    'class': 'StudentListAjax'
                })).then(function(response) {
                    // console.log("getTableData() -> what is the result: ", response);
                    resolve(response);
                });
            });
            return promiseObj;
        },

        /** Add Student AJAX REQUEST
         * Description: This is called after the insert has occurred on the DB side. A hash will be generated
         * to trigger a full reload for other devices. But for the user entering the
         * student now they'll see them added. It'll fail otherwise.
         * @param {object} containing users form info
         */
        addStudent: function (user) {

            jaxy.tcsAjax(JSON.stringify({
                'call': 'addStudentToList',
                'params': user,
                'class': 'StudentListAjax',
            })).then(function (response) {
                // console.log("++++++++++ addStudent() -> what is the result: ", response);
                // console.log("StudentTable ---->>>>>> addStudent() -> What is the response: ", response);
                TCSLib.setHash({name: "tcs_dash_hash", value: response.dash_hash});
                // console.log("StudentTable ---->>>>>> addStudent() -> updated hash to: ", response.dash_hash);

                $('#tcs_student_table_wacka').bootstrapTable('insertRow', {
                    index: response.row_id,
                    row: {
                        'id': response.row_id,
                        'user_id': user.uofl_id,
                        'exam_id': response.data.exam_id,
                        'username': response.data.username,
                        'course': response.data.course,
                        'examname': response.data.examname,
                        'room': response.data.room,
                        'signintime': TCSLib.unixToDate(response.data.timesigned),
                        'comments': response.data.comments,
                        'id_type': response.data.id_type,
                        'exam_type': response.data.exam_type,
                        'remove_user': {
                            field: 'operate',
                            title: 'Remove',
                            align: 'center',
                            valign: 'middle',
                            clickToSelect: false,
                            formatter: function (value, row) {
                                // console.log("what is value: ", value);
                                // console.log("what is row: ", row);
                                if (value == "" || row == "") {
                                    console.log("formatter fail");
                                }
                                var button = '<button class="toast_remove_' + response.row_id +
                                    ' btn btn-danger btn-lg" id="tcs_remove_user_from_student_list" data-id="' + response.row_id +
                                    '" data-user_id="' + user.uofl_id +
                                    '" data-username="' + response.data.username +
                                    '" data-exam_id="' + response.data.exam_id +
                                    '" data-room="' + response.data.room +
                                    '">' +
                                    '<i class="fa fa-trash"></i>' +
                                    '</button>';
                                return button;
                            }
                        }
                    }
                });
                TCSLib.showMessage(response);
                TCSLib.updateStats([
                    {'class': 'tcs_student_count','action': 'add'},
                    {'class': 'tcs_room_stat_' + response.data.room,'action': 'add'}
                ]);
            });
        },

        /** Remove Student AJAX REQUEST -> PROMISE
         * Description: This is called after the insert has occurred on the DB side. A hash will be generated
         * to trigger a full reload. This will be helpful for multiple monitors. But for the user being removed
         * the proctor will see them removed.
         * @param {object} containing users form info
         * @return resolved data
         */
        removeStudent: function(data) {
            // var promiseObj = new Promise(function (resolve, reject) {
            // console.log("tc_student_table -> removeStudent() called");
            var promiseObj = new Promise(function (resolve) {
                jaxy.tcsAjax(JSON.stringify({
                    'call': 'removeStudentFromList',
                    'params': {
                        'id': data.id,
                        'user_id': data.user_id,
                        'username': data.username,
                        'exam_id': data.exam_id,
                        'room': data.room
                    },
                    'class': 'StudentListAjax'
                })).then(function (response) {
                    // TCSLib.showMessage(response);
                    // TCSLib.updateStats([
                    //     {'class': 'tcs_student_count','action': 'add'},
                    //     {'class': 'tcs_room_stat_' + response.data.room,'action': 'add'}
                    // ]);
                    // console.log("StudentTable ---->>>>>> removeStudent() -> What is the response: ", response);
                    TCSLib.setHash({name: "tcs_dash_hash", value: response.dash_hash});
                    // console.log("StudentTable ---->>>>>> removeStudent() -> updated hash to: ", response.dash_hash);
                    resolve(response);
                });
            });
            return promiseObj;
        },

        /** Remove Student Confirmation Box
         * Description: This is from the iziToast button function. This needs to be
         * in it's own function so keep this module in scope as the message confirmation
         * is in another module.
         * @param {object} the rows info to be removed: row_id, user_id, username, exam_id
         * @return resolved object and calling removeStudentFromTable() to handle UI change
         */
        removeStudentConfirm: function (data) {
            var that = this;
            // console.log("User has clicked removeStudentConfirm() -> what is data: ", data);
            // console.log("User has clicked removeStudentConfirm() -> now make ajax call....");
            that.removeStudent({
                'id': data.row_id,
                'user_id': data.user_id,
                'username': data.username,
                'exam_id': data.exam_id,
                'room': data.room
            }).then(function (response) {
                // console.log("removeStudent PROMISE has completed, now remove student from UI TABLE");
                that.removeStudentFromTable(response);
            });
        },

        /** Remove Student UI - Removed from Table
         * Description: remove the student from the Table, this is done when ID Card
         * or username is entered again.
         * @param {object} need the have
         *      row_id: x
         *      success: true|false
         *      msg: some message to show....
         */
        removeStudentFromTable: function (data) {
            // console.log("removeStudentFromTable() -> what is the data: ", data);
            TCSLib.showMessage(data);
            TCSLib.updateStats([
                {'class': 'tcs_student_count','action': 'sub'},
                {'class': 'tcs_room_stat_' + data.room, 'action': 'sub'},
                {'class': 'tcs_written_today', 'action': 'add'},
                {'class': 'tcs_written_semester', 'action': 'add'}
            ]);
            $('#tcs_student_table_wacka').bootstrapTable('removeByUniqueId', data.row_id);
        },

        removeConfirmModal: function (data) {

            TCSLib.showMessage({
                msg_type: "show",
                theme: 'light',
                // target: '.fixed-table-container',
                icon: 'fa fa-trash',
                title: 'Confirm',
                titleColor: '#721C24',
                message: 'Are you sure you want to remove ' + data.username,
                messageColor: '#721C24',
                messageSize: '20',
                messageLineHeight: '80',
                color: '#f8d7da',
                iconColor: '#721C24',
                position: 'topCenter', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
                progressBarColor: 'rgb(0, 255, 184)',
                closeOnEscape: true,
                buttons: [
                    ['<button>Ok</button>', function (instance, toast) {
                        instance.hide({
                            transitionOut: 'fadeOutDown',
                            // onClosing: function (instance, toast, closedBy) {
                            //     console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                            // }
                        }, toast, 'buttonName');

                        this.removeStudentConfirm({
                            'row_id': data.row_id,
                            'user_id': data.user_id,
                            'username': data.username,
                            'exam_id': data.exam_id,
                            'room': data.room
                        });
                    }, true], // true to focus
                    ['<button>Close</button>', function (instance, toast) {
                        instance.hide({
                            transitionOut: 'fadeOutDown',
                            // onClosing: function (instance, toast, closedBy) {
                            //     console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                            // }
                        }, toast, 'buttonName');
                    }]
                ],
                onOpening: function () {
                    // console.info('callback abriu!');
                },
                // onClosing: function (instance, toast, closedBy) {
                onClosing: function () {
                    // console.info('closedBy: ' + closedBy); // tells if it was closed by 'drag' or 'button'
                }
            });
        },
        /** Test Function
         * Description: This is just a test function, call this when testing with
         * module's and scope
         */
        student_table_test: function () {
            console.log("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$");
            console.log("student_table_test() -> SUCCESSFULLY CALLED");
            console.log("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$");
        },

        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        /** START - Initialize The Table
         * Description: Load and show the Student's currently in the exam center.
         * Any binding click events will be registered here as well.
         */
        initiateStudentTable: function() {
            // console.log("initiateStudentTable() -> This SHOULD ONLY BE CALLED ONCE");
            // Fetch the Data and display
            var that = this;
            // registerHBEvent()
            this.getTableData({startup: true}).then(function(response) {
                // console.log("initiateStudentTable() -> Returned from AJAX, going to build table, what is data: ", response);
                // console.log("BEFORE: What is b's type: " + typeof (response));
                // console.log("BEFORE: What is b's length: " + response.length);

                // if (typeof (response) == "object" && response.length == 0) {
                //     response = [];
                // }
                // console.log("AFTER: What is b's type: ", typeof (response));
                // console.log("AFTER: What is b's length: " + response.length);
                if (response.length == 0) {
                    $('#tcs_student_table_wacka').bootstrapTable();
                } else {
                    $('#tcs_student_table_wacka').bootstrapTable({
                        data: response,
                        columns: [
                            {title: 'id', field: 'id'},
                            {title: 'Username', field: 'username'},
                            {title: 'Course', field: 'course'},
                            {title: 'Exam Name', field: 'examname'},
                            {title: 'Room #', field: 'room'},
                            {
                                title: 'Time Signed In',
                                field: 'signintime',
                                formatter: function (value) {
                                    if (!isNaN(value)) {
                                        return TCSLib.unixToDate(value);
                                    }
                                    return value;
                                }
                            },
                            {title: 'Comments', field: 'comments'},
                            {
                                field: 'operate',
                                title: 'Remove',
                                align: 'center',
                                valign: 'middle',
                                clickToSelect: false,
                                formatter: function (value, row) {
                                    // console.log("what is value: ", value);
                                    // console.log("what is row: ", row);
                                    if (value == "" || row == "") {
                                        console.log("formatter fail");
                                    }
                                    var button = '<button class="toast_remove_' + row.id +
                                        ' btn btn-danger btn-lg" id="tcs_remove_user_from_student_list" data-id="' + row.id +
                                        '" data-user_id="' + row.user_id +
                                        '" data-username="' + row.username +
                                        '" data-exam_id="' + row.exam_id +
                                        '" data-room="' + row.room +
                                        '">' +
                                        '<i class="fa fa-trash"></i>' +
                                        '</button>';
                                    return button;
                                }
                            },
                            {title: 'User ID', field: 'user_id'},
                            {title: 'Exam ID', field: 'exam_id'},
                            {title: 'ID Type', field: 'id_type'},
                            {title: 'Exam Type', field: 'exam_type'
                        }]
                    // ============================================
                    });
                }
                $('.tcs_autocomp_in').focus();
            });

            /** EDIT Table Cell - AJAX
             * Description: If the user clicks on the Comments column and Room Column then
             * they can edit the cell. Once finished data will be sent to a promised AJAX call.
             */
            $('#tcs_student_table_wacka').on('editable-save.bs.table', function(field, row, newRow){
                // console.log("edit table, here is field: ", field);
                // console.log("Column: " + row);
                // console.log("edit table, here is newRow: ", newRow);
                // console.log("edit table, here is $el: " + oldVal);
                var data = {
                    'id': newRow.id,
                    'username': newRow.username
                };
                if (row == "room") {
                    // check if room is a number
                    if (isNaN(newRow.room)) {
                        // it is NOT a number
                        alert("Not Save, please enter a number");
                        return;
                    }
                    data.room = newRow.room;
                } else if (row == "comments") {
                    data.comment = newRow.comments;
                } else {
                    console.log("Ooooops, this column: " + row + " was edited but is not room or comment.......?");
                }
                that.updateTableData(data).then(function(response) {
                    TCSLib.showMessage(response);
                    // $('#tcs_student_table_wacka').bootstrapTable('load', response);
                });
            });

            /** REMOVE User From Table UI - Confirmation Box
             * Description: If the user clicks on the Trash Can Icon and they hit 'ok' then the
             * removeStudentConfirm() function is called to take care of hte rest (in order to maintain scope)
             */
            $("body").on('click', '#tcs_remove_user_from_student_list', function () {
                // console.log("Going to remove this record now........");
                var row_id = $(this).data('id'),
                    user_id = $(this).data('user_id'),
                    username = $(this).data('username'),
                    exam_id = $(this).data('exam_id'),
                    room = $(this).data('room');
                    // toast_target = ".toast_remove_" + row_id;
                    // console.log("What is the toast target: " + toast_target);

                TCSLib.showMessage({
                    msg_type: "show",
                    theme: 'light',
                    // target: '.fixed-table-container',
                    icon: 'fa fa-trash',
                    title: 'Confirm',
                    titleColor: '#721C24',
                    message: 'Are you sure you want to remove ' + username,
                    messageColor: '#721C24',
                    messageSize: '20',
                    messageLineHeight: '80',
                    color: '#f8d7da',
                    iconColor: '#721C24',
                    position: 'topCenter', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
                    progressBarColor: 'rgb(0, 255, 184)',
                    closeOnEscape: true,
                    buttons: [
                        ['<button>Ok</button>', function (instance, toast) {
                            instance.hide({
                                transitionOut: 'fadeOutDown',
                                // onClosing: function (instance, toast, closedBy) {
                                //     console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                                // }
                            }, toast, 'buttonName');

                            that.removeStudentConfirm({
                                'row_id': row_id,
                                'user_id': user_id,
                                'username': username,
                                'exam_id': exam_id,
                                'room': room
                            });
                        }, true], // true to focus
                        ['<button>Close</button>', function (instance, toast) {
                            instance.hide({
                                transitionOut: 'fadeOutDown',
                                // onClosing: function (instance, toast, closedBy) {
                                //     console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                                // }
                            }, toast, 'buttonName');
                        }]
                    ],
                    onOpening: function () {
                        console.info('callback abriu!');
                    },
                    // onClosing: function (instance, toast, closedBy) {
                    onClosing: function () {
                        // console.info('closedBy: ' + closedBy); // tells if it was closed by 'drag' or 'button'
                    }
                });
            });
        }
    };
});
