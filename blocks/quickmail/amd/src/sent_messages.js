define(['jquery', 'block_quickmail/notifications'], function($, noti) {
    // 'use strict';

    let stupidFace = function() {
        console.log("Your face is stupid!");
    };

    return {
        init: function() {
            console.log("sent_messages => init() => START");
            $('.qm_sent_msgs').on('click', '.qm_sm_trash', function (ev) {
                ev.preventDefault();
                console.log("Trash this message yo");
                console.log("What is the id: " + $(this).data("msgid"));
                stupidFace();

                // success, warning, info, error
                noti.callNoti({
                    message: "This is a success test",
                    type: "success"
                });
                noti.callNoti({
                    message: "This is an info test",
                    type: "info"
                });
            });
        }
    };
});
