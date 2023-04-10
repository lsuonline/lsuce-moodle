define([
    'jquery',
    'local_myadmin/myadmin_lib',
    'local_myadmin/myadmin_autocomp_exam',

    'local_myadmin/_bootstrap_libs/bootstrap-table',
    'local_myadmin/_bootstrap_libs/bootstrap-editable',
    'local_myadmin/_bootstrap_libs/bootstrap-table-editable',
    'local_myadmin/_bootstrap_libs/bootstrap-table-toolbar',
    /* eslint-disable */
    // 'local_myadmin/editable',
    // 'local_myadmin/bootstrap-editable',

], function ($, MyAdminLib, myadmin_autocomp, BootstrapTable, editable, bootstrapTableEditable, toolbar) {
    /* eslint-enable */
    'use strict';
    return {
        /** Get Table Data AJAX REQUEST -> PROMISE
         * Description: This will fetch all users
         * the callback will resolve with all the data.
         * @return resolved data
         *
         * FIXME: This is not done
         */
        getUserData: function () {
            return MyAdminLib.jaxyPromise({
                'call': 'getUsers',
                'params': {},
                'class': 'UserAdmin'
            });
        },
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================

        /** START - Initialize The Table
         * Description: Load and show the Student's currently in the exam center.
         * Any binding click events will be registered here as well.
         * AJAX: MAke an ajax call
         * FUNCTION: MAke an ajax call
         */
        initiateUserAdmin: function () {
            // console.log("initiateUserAdmin() -> This SHOULD ONLY BE CALLED ONCE");
            // Fetch the Data and display
            // var that = this;
            this.getUserData().then(function (response) {
                // console.log("What is the response for user admin: ", response);
                // console.log("What is the data: ", response.data);

                // console.log("Initiating User Admin Table........");
                var new_data = [];
                if (response.success === true) {
                    for (var x in response.data) {
                        new_data.push(response.data[x]);
                    }
                }
                // console.log("What is the new_data:", new_data);

                $('#myadmin_user_admin_table').bootstrapTable({
                    undefinedText: '',
                    data: new_data,
                    pageSize: 50,
                    // showRefresh: true,
                    columns: [
                        {title: 'ID', field: 'id'},
                        {title: 'UserID', field: 'userid'},
                        {title: 'Name', field: 'name'},
                        {title: 'Username', field: 'username'},
                        {title: 'Access Level', field: 'access_level'},
                        {/* Exams */
                            title: 'Exams',
                            field: 'operate',
                            align: 'center',
                            valign: 'middle',
                            clickToSelect: false,
                            formatter: function (value, row) {
                                var stuff = "";
                                // console.log("Here in the formatter, what is row: ", row);
                                if (row.exams.length > 1) {
                                    // console.log("How many exams: " + row.exams.length);
                                    // console.log("exams: ", row.exams);
                                    stuff = '<select class="form-control">';
                                    stuff += '<option selected>See My Exams</option>';
                                    // for (var x of row.exams) {
                                    for (var x = 0; x < row.exams.length; x++) {
                                        stuff += '<option>' + row.exams[x] + '</option>';
                                    }
                                    stuff += '</select>';
                                    // console.log("^^^^^^^ What is the final HTML chunk: " + stuff);
                                    return stuff;

                                } else {
                                    return "<p>No Exams</p>";
                                }
                                // console.log("Here in the formatter, what is row: ", row);
                                // if (row.finished == "true") {
                            },
                        },
                        {/* Edit Remove */
                            title: 'Remove',
                            field: 'operate',
                            align: 'center',
                            valign: 'middle',
                            clickToSelect: false,
                            formatter: function (value, row,) {
                                var button = '<div class="btn-group">' +
                                    // '<button class="toast_edit_' + row.id +
                                    // ' btn btn-success btn-lg" id="myadmin_edit_user_admin" data-id="' + row.id + '">' +
                                    // '<i class="fa fa-pencil"></i>' +
                                    // '</button>' +
                                    '<button class="toast_remove_' + row.id +
                                    ' btn btn-danger btn-lg" id="myadmin_remove_user_admin" data-id="' + row.id + '">' +
                                    '<i class="fa fa-trash"></i>' +
                                    '</button>' +
                                    '</div>';
                                // }
                                return button;
                            }
                        }
                    ]
                    //============================================
                });
            });

            // Now let's prep the user search
            myadmin_autocomp.initiateAutoComp(JSON.parse(localStorage.getItem('myadmin_users')));
        }
    };
});
