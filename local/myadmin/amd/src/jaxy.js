define([
    'jquery',
    'core/ajax',
], function($, Ajax) {
    'use strict';

    return {
        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        myadminAjax: function(data_chunk) {
            var promiseObj = new Promise(function(resolve, reject) {
                // console.log("myadminAjax() -> START, let's Poke the Server");
                var send_this = [{
                    methodname: 'local_myadmin_myadminAjax',
                    args: {
                        datachunk: data_chunk,
                    }
                }];
                Ajax.call(send_this)[0].then(function(results) {
                    // console.log("myadminAjax() -> SUCCESS, what is result: ", results);
                    resolve(JSON.parse(results.data));

                // }).then(function(ev) {
                    // console.log("Ok, have hit then 2");
                //     console.log("is there an ev? ", ev);
                //     return "facker";
                }).catch(function(ev) {
                    // console.log("myadminAjax() -> JAXY Fail :-(");
                    // console.log("myadminAjax() -> JAXY Fail going to reject: ", ev);
                    reject(ev);
                });
                // console.log("myadminAjax() -> AJAX request sent successfully for obj: ", send_this);
            });
            return promiseObj;
        },
        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        makeAJAXCall: function(params, callback) {
            var staticAjax = this;
            staticAjax.myadmin_callback = callback;
            // console.log("makeAJAXCall() -> Going to call ajax now, here's the params: ", params);
            Ajax.call(params)[0].then(function(results) {
                // console.log("makeAJAXCall() -> HAVE RETURNED, what is result: ", results);
                // return staticAjax.myadmin_callback(results);
                return results;

            // }).then(function(ev) {
            //     console.log("Ok, have hit then 2");
            //     console.log("is there an ev? ", ev);
            //     return "facker";

            }).catch(function(ev) {
                console.log("makeAJAXCall() -> JAXY Fail do we have ev: ", ev);
            });

        },


        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        processFreshUsers: function(results) {
            // console.log("processFreshUsers() -> Now processing results: ", results);
            // configgy.setUsers();
            // console.log("processFreshUsers() -> actually, let's just return results");
            return results;
        },

        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        getFreshUsers: function() {
            // console.log("getFreshUsers() -> Going to fetch users from DB.......");
            this.makeAJAXCall([{
                methodname: 'local_myadmin_loadUsers',
                args: {
                    page: '0',
                    pagetotal: '1',
                }
            }], 'processFreshUsers');
        },

        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        getFreshTemplates: function() {
            console.log("getFreshTemplates() -> This function has a DEAD END");
        },

        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        /*
        hellowWorldTest: function() {

            Ajax.call([{
                methodname: 'local_myadmin_hello_world',
                args: {
                    welcomemessage: 'Go Fack Yourself!',
                }
            }])[0].then(function(results) {
                console.log("Ok, have hit then 1, what is result: ", results);
                return "facker";

            // }).then(function(ev) {
            //     console.log("Ok, have hit then 2");
            //     console.log("is there an ev? ", ev);
            //     return "facker";

            }).catch(function(ev) {

                console.log("JAXY Fail for hellowWorldTest :-(");
                console.log("JAXY Fail do we have ev: ", ev);
            });
        },
        */
    };
});

/*
need to store users
need to store pages in localCache

var promises = Ajax.call([request]);
$.when(promises[0]).then(function(data) {
    if (data.result.policy) {
        modalTitle.resolve(data.result.policy.name);
        modalBody.resolve(data.result.policy.content);

        return data;
    } else {
        throw new Error(data.warnings[0].message);
    }
}).catch(function(message) {
    modal.then(function(modal) {
        modal.hide();
        modal.destroy();

        return modal;
    })
    .catch(Notification.exception);

    return Notification.addNotification({
        message: message,
        type: 'error'
    });
});
*/
