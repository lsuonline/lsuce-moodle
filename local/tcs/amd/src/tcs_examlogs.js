define([
    'jquery',
    'local_tcs/jaxy',
    'local_tcs/tcs_lib',
    // 'local_tcs/iziToast',
    // 'core/modal_factory',
    // 'core/modal_events',
    // 'core/templates',
    // 'local_tcs/jquery.autocomplete',
    // 'local_tcs/tcs_autocomp_exam',
    'local_tcs/_bootstrap_libs/bootstrap-table'
    // 'local_tcs/editable',
    // 'local_tcs/bootstrap-table-editable',
    // 'local_tcs/bootstrap-table-toolbar',
    // 'local_tcs/PNotifyButtons'
    /* eslint-disable */
// ], function ($, TCSLib, jaxy, BootstrapTable, bootstrapTableEditable, toolbar) {
], function ($, jaxy, TCSLib, BootstrapTable) {
// ], function ($, TCSLib, jaxy, bootstrapTable) {
    /* eslint-enable */
    'use strict';

    return {
        /** Get Table Data AJAX REQUEST -> PROMISE
         * Description: This will fetch all Exam Logs
         * the callback will resolve with all the data.
         * @return resolved data
         */
        getTableData: function () {
            var promiseObj = new Promise(function (resolve) {
                jaxy.tcsAjax(JSON.stringify({
                    'call': 'getExamLogs',
                    'params': {},
                    'class': 'Stats'
                })).then(function (response) {
                    // console.log("EXAMLOGS -> getTableData() -> what is the result: ", response);
                    // var new_data = [];
                    // for (var x in response.users_in_centre) {
                    //     new_data.push(response.users_in_centre[x]);
                    // }
                    resolve(response);
                    // resolve(new_data);
                });
            });
            return promiseObj;
        },

        // ========================================================================================
        // ========================================================================================
        // ========================================================================================

        /** START - Initialize The Exam Log Table
         * Description: Entries of when students wrote their exams
         * Any binding click events will be registered here as well.
         * AJAX: MAke an ajax call
         * FUNCTION: MAke an ajax call
         */
        initiateLogTable: function () {
            // console.log("initiateLogTable() -> This SHOULD ONLY BE CALLED ONCE");
            // Fetch the Data and display
            // var that = this;
            this.getTableData().then(function (response) {

                if (response.length == 0) {
                    $('#tcs_examlogs_table').bootstrapTable();
                } else {
                    $('#tcs_examlogs_table').bootstrapTable({
                        data: response,
                    });
                }
            });
        }
    };
});
