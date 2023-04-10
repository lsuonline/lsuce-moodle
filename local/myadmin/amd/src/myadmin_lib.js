define([
    'jquery',
    'local_myadmin/jaxy',
    'local_myadmin/_libs/iziToast',
    'local_myadmin/_libs/moment',
], function ($, jaxy, iziToast, moment) {
    'use strict';

    // Set the format for Moment JS
    moment().format('dddd, MMMM Do, YYYY - h:mma');

    return {

        /* ====================================================================== */
        /* ================      Local Storage Functions      =================== */
        /* ====================================================================== */

        /**
         * Get the hash from local storage
         * Hash could be user hash, student table hash, stat card update hash, etc.
         * @method getHash
         * @param string - name of the hash you want
         * @return string - the hash
         */
        getHash: function(hash) {
            if (typeof localStorage[hash] !== 'undefined') {
                return localStorage.getItem(hash);
            } else {
                return "no_hash";
            }
        },

        /**
         * Set the hash in local storage
         * @method setHash
         * @param obj - will have name of hash and new value {name:xx, value:xx}
         * @return
         */
        setHash: function(obj) {
            // console.log("Going to set this item: " + obj.name + " and it's value: " + obj.value);
            localStorage.setItem(obj.name, obj.value);
        },

        /**
         * This will get the hash, check if it's the same and if NOT update it
         * @method processHash
         * @param obj - will have name of hash and new value {name:xx, value:xx}
         * @return
         */
        processHash: function(obj) {
            if (this.getHash(obj.hash) == obj.value) {
                return false;
            } else {
                localStorage.setItem(obj.name, obj.value);
                return true;
            }
        },

        /**
         * Check if the user is admin or not. This is used for certain AJAX calls
         * @method isAdmin
         * @param none
         * @return bool
         */
        isAdmin: function() {

            if (localStorage.getItem("myadmin_admin_user") == "true" || localStorage.getItem("myadmin_tc_admin_user") == "true") {
                return true;
            } else {
                return false;
            }
        },

        /* ====================================================================== */
        /* ==================      Message Functions      ======================= */
        /* ====================================================================== */

        /** Description: All message requests for iziToastk come here to be displayed.
         * @param {object} all the settings for the message to be shown
         * @return nothing
         */
        showMessage: function (data) {
            // console.log("showMessage() -> What is the data to show: ", data);
                if (data.msg_type == "success") {
                    iziToast.success(data.show_msg);
                } else if (data.msg_type == "error") {
                    iziToast.error(data.show_msg);
                } else if (data.msg_type == "show") {
                    iziToast.show(data);
                } else if (data.msg_type == "info") {
                    iziToast.info(data.show_msg);
                } else if (data.msg_type == "warning") {
                    iziToast.warning(data.show_msg);
                }
        },

        /* ====================================================================== */
        /* =================      UI Update Functions      ====================== */
        /* ====================================================================== */

        /** Description: Update one of the stat cards immediately
         * @param {object} has 3 keys
         *      1. class - name of class to update
         *      2. action - one of add/sub/over
         *      3. over - override the number with this value
         * @return nothing
         */
        updateStats: function (data) {
            // console.log("MyAdminLib -> updateStats() -> START, what is data: ", data);
            data.forEach(function (msg) {

                // console.log("MyAdminLib -> updateStats() -> what is msg: ", msg);
                if (msg.action == "add") {
                    var temp = parseInt($('.' + msg.class).text());
                    temp = temp + 1;

                    $('.' + msg.class).text(temp);
                } else if (msg.action == "sub") {
                    var temp = parseInt($('.' + msg.class).text());
                    // make sure we don't go below zero
                    if (temp === 0) {
                        return;
                    } else {
                        temp = temp - 1;
                        $('.' + msg.class).text(temp);
                    }
                } else if (msg.action == "over") {
                    $('.' + msg.class).text(msg.over);
                }

            });
        },

        /* ====================================================================== */
        /* ===================      AJAX Functions      ========================= */
        /* ====================================================================== */

        /** Description: Get the current date and return a readable format.
         * @param - None
         * @return Date
         */
        jaxyPromise: function (data) {
            // console.log("jaxyPromise -> Going to pass this to the jaxy function: ", data);
            // var promiseObj = new Promise(function (resolve, reject) {
            var promiseObj = new Promise(function (resolve) {
                jaxy.myadminAjax(JSON.stringify(data)).then(function (response) {
                    resolve(response);
                });
            });
            return promiseObj;
        },

        /* ====================================================================== */
        /* ===================      DATE Functions      ========================= */
        /* ====================================================================== */

        /** Date Modifier UNIX to DATE
         * Description: This function uses moment.js to change the epoch unix timestamp to a
         * human readable date. According to American English the date will be as follows:
         * Monday, September 6, 2019 - 4:30pm
         * @param {int} - unix timestamp
         * @return {string} - ex: Monday, September 6, 2019 - 4:30pm
         */
        unixToDate: function (unix_time) {
            return moment.unix(unix_time).format('dddd, MMMM Do, YYYY - h:mma');
        },

        /** Get Date
         * Description: This function get's the date in a human readable format.
         *      According to American English the date will be as follows:
         *      Monday, September 6, 2019 - 4:30pm
         * @param {int} - unix timestamp
         * @return {string} - ex: Monday, September 6, 2019 - 4:30pm
         */
        getCurrentDate: function (with_clock) {

            var add_clock = "";
            if (with_clock) {
                add_clock = ' - h:mma';
            }
            return moment().format('dddd, MMMM Do, YYYY' + add_clock);
        },
        /** Date Modifier DATE to UNIX
         * Description: This function uses moment.js to change the human readable date to a
         *      epoch unix timestamp .
         * @param {string} - unix timestamp
         * @return {int} - 123412341
         */
        dateToUnix: function (date_time) {
            return moment(date_time).format('X');
        }

        /** Description: Get the current date and return a readable format.
         * @param - None
         * @return Date
        getCurrentDate2: function () {
            var now = new Date();
            var day = ("0" + now.getDate()).slice(-2);
            var month = ("0" + (now.getMonth() + 1)).slice(-2);
            var today = now.getFullYear() + "-" + (month) + "-" + (day);
            return today;
        },
         */
    };
});