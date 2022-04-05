define(['jquery', 'core/notification'], function($, notification) {
    'use strict';

    return {

        /**
         * A simple way to call the Moodle core notification system.
         * @param {obj} A simple object with the 'message' and 'type' of notification.
         * Type can be either: success, warning, info, error
         *
         * @return void
         */
        callNoti: function(data) {
            if (!data.hasOwnProperty('message')) {
                console.log("ERROR -> Notification was called but with no message, aborting.");
            }
            if (!data.hasOwnProperty('type')) {
                // default to info
                data.type = "info";
            }
            notification.addNotification(data);
        }
    };
});