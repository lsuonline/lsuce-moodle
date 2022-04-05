define([
    'jquery',
    'local_tcs/tcs_lib',
    'core/modal_factory',
    'core/modal_events',
    'core/templates',

    'local_tcs/_libs/moment',
    'local_tcs/_libs/jquery.autocomplete',

    'local_tcs/_bootstrap_libs/bootstrap-table',
    'local_tcs/_bootstrap_libs/bootstrap-editable',
    'local_tcs/_bootstrap_libs/bootstrap-table-editable',
    'local_tcs/_bootstrap_libs/bootstrap-table-toolbar',

/* eslint-disable */
], function ($, TCSLib, ModalFactory, ModalEvents, Templates, moment, autocomplete,
    BootstrapTable, editable, bootstrapTableEditable, toolbar) {

    'use strict';
    var stored_edit_obj = "";
    /* eslint-enable */

    // // Set the format for Moment JS
    // moment().format('dddd, MMMM Do, YYYY - h:mma');

    return {
        /** Get Table Data AJAX REQUEST -> PROMISE
         * Description: This will fetch all users currently in the Test Centre room
         * the callback will resolve with all the data.
         * @return resolved data
         */
        getExamData: function () {
            return TCSLib.jaxyPromise({
                'call': 'getAllOpenExams',
                'params': { },
                'class': 'Exams'
            });
        },

        /** Update Visible/Hidden AJAX REQUEST -> PROMISE
         * Description: This will update a users comment or room#
         * the callback will resolve with a confirmation of success or fail and message.
         * @return resolved data
         */
        updateTableData: function(data) {
            return TCSLib.jaxyPromise({
                'call': data.call,
                'params': data.data,
                'class': 'Exams'
            });
        },

        /** Add Manual Exam AJAX REQUEST -> PROMISE
         * Description: Show modal to add manual exam
         * The callback will resolve with a confirmation of success or fail and message.
         * @return resolved data
         */
        addManualExam: function(data) {
            return TCSLib.jaxyPromise({
                'call': 'addManualExams',
                'params': data,
                'class': 'Exams'
            });
        },

        /** Edit Manual Exam AJAX REQUEST -> PROMISE
         * Description: Show modal to edit manual exam
         * The callback will resolve with a confirmation of success or fail and message.
         * @return resolved data
         */
        editManualExam: function(data) {
            return TCSLib.jaxyPromise({
                'call': 'updateExam',
                'params': data,
                'class': 'Exams'
            });
        },

        /** Check the Manual Form
         * Description: Check the Manual Form to make sure the following ARE Filled
         * @param none - using jQuery to grab and check the data.
         * @return bool
         */
        checkForm: function () {
            return $("#tc_manual_exam_course_name").val() &&
                $("#tc_manual_exam_exam_name").val() &&
                $("#tc_manual_exam_opening_date").val() &&
                $("#tc_manual_exam_closing_date").val() &&
                $("#tc_manual_exam_password").val() &&
                $("#tc_manual_exam_student_list").val();
        },

        /** Find changes in the Form
         * Description: When performing updates to the manual form let's find those changes
         *      and return that data.
         * @param none - using jQuery to grab and check the data.
         * @return {object} - returns all the updated changes.
         */
        findeUpdatedData: function () {
            // find the updated data
            var return_obj = {};
            if (this.stored_edit_obj.course_name != $("#tc_manual_exam_course_name").val()) {
                return_obj.course_name = $("#tc_manual_exam_course_name").val();
            }
            if (this.stored_edit_obj.exam_name != $("#tc_manual_exam_exam_name").val()) {
                return_obj.exam_name = $("#tc_manual_exam_exam_name").val();
            }

            if (this.stored_edit_obj.opening_date != $("#tc_manual_exam_opening_date").val()) {
                return_obj.opening_date = $("#tc_manual_exam_opening_date").val();
            }

            if (this.stored_edit_obj.closing_date != $("#tc_manual_exam_closing_date").val()) {
                return_obj.closing_date = $("#tc_manual_exam_closing_date").val();
            }

            if (this.stored_edit_obj.password != $("#tc_manual_exam_password").val()) {
                return_obj.password = $("#tc_manual_exam_password").val();
            }

            if (this.stored_edit_obj.password != $("#tc_manual_exam_student_list").val()) {
                return_obj.student_list = $("#tc_manual_exam_student_list").val();
            }

            if (this.stored_edit_obj.notes != $("#tc_manual_exam_notes").val() ||
                $("#tc_manual_exam_notes").val() != "") {
                return_obj.notes = $("#tc_manual_exam_notes").val();
            }

            return_obj.row_id = this.stored_edit_obj.id;
            return_obj.exam_id = this.stored_edit_obj.exam_id;
            return_obj.manual = "true";
            return return_obj;
        },

        /** Update the Exam Table Row
         * Description: Various parts of an entry can be changed, notes, visibility, and edit/remove
         *      Those functions to handle the changes funnel here and sends data to the server.
         * @param {object} - changed_row is the row with all it's data and new changes.
         * @return none - Shows message at end of AJAX call.
         */
        updateTableRow: function (changed_row) {
            // Need to include row_id
            changed_row.row_id = changed_row.id;

            // Manual Exams will have all the data already and only need to send the updated data
            // Moodle exams will need all the data
            var what_to_call = "updateExam",
                data_to_send = "";

            if (changed_row.manual == "false") {
                // ok, we are working with a Moodle exam so let's send all the data
                what_to_call = "addUpdateMoodleExam";
                data_to_send = changed_row;
            } else {
                // Need to send row_id, exam_id and the new changes
                data_to_send = {
                    'row_id': changed_row.row_id,
                    'exam_id': changed_row.exam_id,
                    'notes': changed_row.notes,
                    'visible': changed_row.visible,
                    'manual': changed_row.manual,
                    'finished': changed_row.finished
                };
            }

            var data = {
                'call': what_to_call,
                'data': data_to_send
            };

            // Perform the AJAX and promise
            this.updateTableData(data).then(function (response) {
                TCSLib.showMessage(response);
            });
        },

        /** Modal Component: Add or Edit Manual Exam
         * Description: Show the UI add manual exam component from templates/modal_entermanualexam
         * @param {obj} -
         * @return {int} - 123412341
         */
        modalAddEditManualExam: function (modal_data, add_or_edit) {
            var modal_title = "Manual Exam",
                modal_save_btn = "Save",
                that = this;

            if (add_or_edit == "add") {
                modal_title = 'Add Manual Exam';
                modal_save_btn = 'Add Exam';
            } else {
                modal_title = 'Edit Manual Exam';
                modal_save_btn = 'Update Exam';
            }
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: modal_title,
                body: Templates.render('local_tcs/modal_entermanualexam', modal_data),
                large: true
                // can_recieve_focus: button
                // footer: 'Stuff here Yo',
            }).then(function (modal) {
                modal.setSaveButtonText(modal_save_btn);
                var root = modal.getRoot();
                root.on(ModalEvents.save, function () {
                    // Need to check the form and make sure all required fields are populated
                    if (that.checkForm()) {
                        // Ok, now going to grab the data and send\

                        if (add_or_edit == "add") {
                            // ---- ADD MODAL ----
                            that.addManualExam({
                                "course_name": $("#tc_manual_exam_course_name").val(),
                                "exam_name": $("#tc_manual_exam_exam_name").val(),
                                "opening_date": $("#tc_manual_exam_opening_date").val(),
                                "closing_date": $("#tc_manual_exam_closing_date").val(),
                                "password": $("#tc_manual_exam_password").val(),
                                "student_list": $("#tc_manual_exam_student_list").val(),
                                "notes": $("#tc_manual_exam_notes").val()
                            }).then(function (response) {
                                var new_row_id = $('#tcs_exam_table').bootstrapTable('getOptions').totalRows;
                                $('#tcs_exam_table').bootstrapTable('insertRow', {
                                    index: 0,
                                    row: {
                                        'id': new_row_id + 1,
                                        'exam_id': response.data.exam_id,
                                        'course_id': response.data.course_id,
                                        'manual': response.data.manual,
                                        'course_name': response.data.course_name,
                                        'exam_name': response.data.exam_name,
                                        'opening_date': response.data.opening_date,
                                        'closing_date': response.data.closing_date,
                                        'password': response.data.password,
                                        'student_list': response.data.student_list,
                                        'notes': response.data.notes,
                                        'visible': response.data.visible
                                    }
                                });
                                TCSLib.showMessage(response);
                            });
                        } else {
                            // ---- EDIT MODAL ----
                            var send_this = that.findeUpdatedData();
                            that.editManualExam(send_this).then(function (response) {
                                // console.log("What is the response AFTER updating the row: ", response);
                                $('#tcs_exam_table').bootstrapTable('updateRow', {
                                    index: response.data.id,
                                    row: response.data
                                });
                                TCSLib.showMessage(response);
                            });
                        }
                    } else {
                        TCSLib.showMessage({
                            'msg_type': 'error',
                            'show_msg': {
                                'title': 'Ooops',
                                'message': 'Sorry, you have missed something in the form',
                                'position': 'center'
                            }
                        });
                        return false;
                    }
                });

                // Handle hidden event.
                modal.getRoot().on(ModalEvents.hidden, function () {
                    // Destroy when hidden.
                    modal.destroy();
                });
                modal.show();
            });
        },

        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================

        /** START - Initialize The Table
         * Description: Load and show ALL the exams that are currently OPEN.
         * Any binding click events will be registered here as well.
         */
        initiateExamTable: function() {
            // Fetch the Data and display
            var that = this;
            this.getExamData().then(function (response) {
                //-------
                var new_data = [];
                if (response.success === true) {
                    for (var x in response.data) {
                        new_data.push(response.data[x]);
                    }
                }
                // if (response.length == 0) {
                //     console.log("There are no exams......apparently");
                // }
                // console.log("Initiating Exam Table........");
                // console.log("What is exam list data pre load table: ", new_data);
                $('#tcs_exam_table').bootstrapTable({
                    undefinedText: '',
                    data: new_data,
                    pageSize: 50,
                    // showRefresh: true,
                    columns: [
                        {title: 'ID', field: 'id'},
                        {title: 'Exam ID', field: 'exam_id'},
                        {title: 'Course ID', field: 'course_id'},
                        {title: 'Manual', field: 'manual'},
                        {title: 'Course', field: 'course_name'},
                        {title: 'Exam', field: 'exam_name'},
                        {/* opening_date */
                            title: 'Opening Date', field: 'opening_date',
                            formatter: function (value) {
                                // check if value is a number
                                if (!isNaN(value)) {
                                    return TCSLib.unixToDate(value);
                                }
                                return value;
                            }
                        },
                        {/* closing_date */
                            title: 'Closing Date', field: 'closing_date',
                            formatter: function (value) {
                                if (!isNaN(value)) {
                                    return TCSLib.unixToDate(value);
                                }
                                return value;
                            }
                        },
                        {title: 'Passowrd', field: 'password'},
                        {title: 'Notes', field: 'notes'},
                        {title: 'Student List', field: 'student_list'},
                        { /* Visibility */
                            title: 'Visibility',
                            field: 'operate',
                            align: 'center',
                            valign: 'middle',
                            clickToSelect: false,
                            formatter: function (value, row) {
                                // #f8d7da no
                                // #d4edda yes
                                var button = "";
                                if (row.visible == "true") {
                                    button = '<select class="form-control select tcs_manual_exam_status_changed" ' +
                                        'style="min-width:75px;" data-exam_id="' + row.id +
                                        '"><option value="1">Hidden</option><option value="2" selected>Visible</option></select>';
                                } else {
                                    button = '<select class="form-control select tcs_manual_exam_status_changed" ' +
                                        'style="min-width:75px;" data-exam_id="' + row.id +
                                        '"><option value="1" selected>Hidden</option><option value="2">Visible</option></select>';
                                }
                                return button;
                            }
                        },
                        { title: 'Subnet', field: 'subnet' },
                        {/* Finished */
                            title: 'Finished',
                            field: 'operate',
                            align: 'center',
                            valign: 'middle',
                            clickToSelect: false,
                            formatter: function (value, row) {
                                // var button = "";
                                if (row.finished == "true") {
                                    return "EXAM IS DONE";
                                }
                                return "Exam is Active";
                            }
                        },
                        { /* Edit/Remove */
                            title: 'Edit/Remove',
                            field: 'operate',
                            align: 'center',
                            valign: 'middle',
                            clickToSelect: false,
                            formatter: function (value, row) {
                                var button = "";
                                if (row.finished == "true") {
                                    // we need to make this button
                                    button = '<div class="btn-group">' +
                                        '<button class="toast_bring_back_' + row.id +
                                        ' btn btn-warning btn-lg" id="tcs_bring_back_manual_exam" data-id="' + row.id + '">' +
                                        '<i class="fa fa-mail-reply"></i>' +
                                        '</button>' +
                                        '</div>';
                                    return button;
                                }
                                if (row.manual != "false") {
                                    button = '<div class="btn-group">' +
                                    '<button class="toast_edit_' + row.id +
                                    ' btn btn-success btn-lg" id="tcs_edit_manual_exam" data-id="' + row.id + '">' +
                                    '<i class="fa fa-pencil"></i>' +
                                    '</button>' +
                                    '<button class="toast_remove_' + row.id +
                                    ' btn btn-danger btn-lg" id="tcs_remove_exam" data-id="' + row.id + '">' +
                                    '<i class="fa fa-trash"></i>' +
                                    '</button>' +
                                    '</div>';
                                }
                                return button;
                            }
                        }
                    ]
                // ============================================
                });
            });

            // console.log("Table should be loaded........");
            /** PRE TABLE LOAD
             * Description: BEFORE the table loads you can do stuff here
             * @param {none} -
             * @return {none} -
             *
            $('#tcs_exam_table').on('pre-body.bs.table', function(field, row, newRow, oldVal){
                // console.log("pre-body.bs.table - Do Awesome Stuff Here");
            });
            */

            /** POST TABLE LOAD
             * Description: AFTER the table loads you can do stuff here
             * @param {none} -
             * @return {none} -
             *
            $('#tcs_exam_table').on('post-body.bs.table', function(field, row, newRow, oldVal){
                // console.log("post-body.bs.table - Do Awesome Stuff Here");
            });
            */
            /* ON LOAD SUCCESS EXAMPLE
            $('#tcs_exam_table').on('load-success.bs.table', function(field, row, newRow, oldVal){
                // console.log("Do Awesome Stuff Here");
            });
            */

            /** Exam Status Change - jQuery EVENT
             * Description: Obtains the exam id which then get's the the row and it's data. This
             *      data is then sent to the server to be updated.
             * @param {none} -
             * @return {none} -
             */
            $('#tcs_exam_table').on('change', '.tcs_manual_exam_status_changed', function() {
                var row_data = $('#tcs_exam_table').bootstrapTable('getRowByUniqueId', $(this).data("exam_id"));
                // console.log("tc_exam_table.js -> Going to update the visibility it's currently: " + row_data.visible);
                row_data.visible = row_data.visible == "true" ? "false" : "true";
                // console.log("tc_exam_table.js -> update, what is visible now: " + row_data.visible);
                // console.log("tc_exam_table.js -> what is the row_data: ", row_data);
                that.updateTableRow(row_data);
            });

            /** Edit NOTES - jQuery EVENT
             * Description: Obtains the exam id which then get's the the row and it's data. This
             *      data is then sent to the server to be updated.
             * @param {none} -
             * @return {none} -
             */
            $('#tcs_exam_table').on('editable-save.bs.table', function(field, row, newRow) {
                that.updateTableRow(newRow);
            });

            /** Edit Manual Exam - jQuery EVENT
            * Description: Modal is pre-populated with current existing data and activated
            *       to make changes to the manual exam.
            * @param {none} -
            * @return {none} -
            */
            $("body").on('click', '#tcs_edit_manual_exam', function () {
                var row_data = $('#tcs_exam_table').bootstrapTable('getRowByUniqueId', $(this).data("id"));
                // let's clone the row so it doesn't get altered and mess up the table data
                var cloned_row = JSON.parse(JSON.stringify(row_data));

                // modify our dates to be stored on the server
                cloned_row.opening_date = TCSLib.unixToDate(cloned_row.opening_date);
                cloned_row.closing_date = TCSLib.unixToDate(cloned_row.closing_date);
                that.stored_edit_obj = cloned_row;
                that.modalAddEditManualExam(cloned_row, "edit");
            });

            /** REMOVE Exam - jQuery EVENT
            * Description: The exam is not actually removed but flips a "finished" switch. These entries
            *       will be at the end of the table.
            * @param {none} -
            * @return {none} -
            */
            $("body").on('click', '#tcs_remove_exam', function () {
                var row_data = $('#tcs_exam_table').bootstrapTable('getRowByUniqueId', $(this).data("id"));
                row_data.finished = "true";
                that.updateTableRow(row_data);

                $('#tcs_exam_table').bootstrapTable('updateRow', {
                    row: row_data.id
                });
            });

            /** UNDO REMOVE Exam - jQuery EVENT
            * Description: The odd time an exam might have to be revived for a student, this will allow the
            *       exam to be active again
            * @param {none} -
            * @return {none} -
            */
            $("body").on('click', '#tcs_bring_back_manual_exam', function () {
                var row_data = $('#tcs_exam_table').bootstrapTable('getRowByUniqueId', $(this).data("id"));
                row_data.finished = "false";
                that.updateTableRow(row_data);
                $('#tcs_exam_table').bootstrapTable('updateRow', {
                    row: row_data.id
                });
            });

            /** FILTERS - jQuery EVENT
            * Description: Buttons at the top of the table provide filtering in for various columns:
            *   manual, visible, hidden, finished, closed and no_filter
            * @param {none} -
            * @return {none} -
            */
            $('.exam_filter_btn').on('click', function () {
                var filterAlgorithm = $(this).data("filterby"),
                    filter_this = "";

                if (filterAlgorithm == "manual") {
                    filter_this = {
                        manual: "true"
                    };
                } else if (filterAlgorithm == "visible") {
                    filter_this = {
                        visible: "true"
                    };
                } else if (filterAlgorithm == "hidden") {
                    filter_this = {
                        visible: "false"
                    };
                } else if (filterAlgorithm == "subnet") {
                    filter_this = {
                        subnet: "true"
                    };
                } else if (filterAlgorithm == "finished") {
                    filter_this = {
                        finished: "true"
                    };
                } else {
                    // filterAlgorithm == no_filter
                    filter_this = {};
                }
                $('#tcs_exam_table').bootstrapTable('filterBy', filter_this);
            });

            /** CREATE New Manual Exam - jQuery EVENT
            * Description: Modal Form to create a new manual exam
            * @param {none} -
            * @return {none} -
            */
            $('#add_manual_exam_btn').on('click', function () {
                var current_date = TCSLib.getCurrentDate(),
                    opening_date = current_date + " 09:00:00",
                    closing_date = current_date + " 21:00:00";

                // Pass this data to the form
                var data = {
                    'current_date': current_date,
                    'opening_date': opening_date,
                    'closing_date': closing_date
                };
                that.modalAddEditManualExam(data, "add");
            });
            // console.log("--------------------- End of initiateExamTable() ---------------------");
        } // --- END initiateExamTable()
    }; // --- END return
});
