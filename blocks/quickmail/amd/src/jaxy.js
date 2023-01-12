define([
    'jquery',
    'core/ajax',
], function($, Ajax) {
    'use strict';

    return {
        /**
         * A Javascript Promise Wrapper to make AJAX calls.
         *
         * Valid args are:
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        qmAjax: function(data_chunk) {
            var promiseObj = new Promise(function(resolve, reject) {
                console.log("qmAjax() -> START, let's Poke the Server");
                var send_this = [{
                    methodname: 'block_quickmail_qmAjax',
                    args: {
                        datachunk: data_chunk,
                    }
                }];
                Ajax.call(send_this)[0].then(function(results) {
                    console.log("qmAjax() -> SUCCESS, what is result: ", results);
                    resolve(JSON.parse(results.data));
                }).catch(function(ev) {
                    reject(ev);
                });
            });
            return promiseObj;
        },
    };
});
