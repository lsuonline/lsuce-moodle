define([
    'jquery',
    'local_myadmin/myadmin_lib',

], function($, MyAdminLib) {
        'use strict';
        // let's keep the scope of this module available async functions.
        /** Dash Stats - AJAX REQUEST
         * Description: Get the data for any dash stat and display it.
         * The callback will resolve with all the data.
         * return resolved data
         */
        return {
            /** Get Dash Stats AJAX REQUEST -> PROMISE
             * Description: This will fetch all dash card stats
             * the callback will resolve with all the data.
             * @return resolved data
             */
            changePage: function (page) {
                // console.log("HB -> changePage() -> START");
                // console.log("HB -> changePage() -> what is the page passed in: " + page);
                if (page != "page_dashboard") {
                    this.stop();
                } else {
                    this.start();
                }
            },

            // registerEvent: function (data) {
            //     console.log("registerEvent() -> ============= START =============");
            //     console.log("registerEvent() -> what is data: " + data);
            // },


             /** Get Dash Stats AJAX REQUEST -> PROMISE
             * Description: This will fetch all dash card stats
             * the callback will resolve with all the data.
             * @return resolved data
             */
            getDashStats: function () {

                return MyAdminLib.jaxyPromise({
                    'call': 'getDashStats',
                    'params': {
                        'hash': MyAdminLib.getHash("myadmin_dash_hash")
                    },
                    'class': 'Stats'
                });
            },

            /** Get User List in Centre AJAX REQUEST -> PROMISE
             * Description: This will fetch all users
             * the callback will resolve with all the data.
             * @return resolved data
             */
            // getTableData: function () {
            //     return MyAdminLib.jaxyPromise({
            //         'call': 'getUsersInExam',
            //         'params': {
            //             // 'hash': MyAdminLib.getHash("myadmin_s_table_hash")
            //             'hash': MyAdminLib.getHash("myadmin_dash_hash")
            //         },
            //         'class': 'StudentListAjax'
            //     });
            // },

            /** Update all Dashboard Stat Cards
             * Description:
             *
             * @return nothing, just update the DOM
             */
            dashUpdate: function (data) {

                if (MyAdminLib.getHash("myadmin_dash_hash") != data.dash_hash) {

                    try {
                        // =====================================================
                        // =============== Update The Stat Cards ===============
                        for (var chunk in data.data) {
                            $('.' + data.data[chunk]['stat_name']).text(data.data[chunk]['stat_data']);
                        }

                        if (MyAdminLib.isAdmin() && data.is_admin) {
                            $('#myadmin_student_table_wacka').bootstrapTable('load', data.student_table_list);
                        }

                        // update the hash in the local storage
                        // var d = new Date(),
                            // n = d.toLocaleTimeString();

                        // =====================================================
                        // =============== Update The Student List ===============
                        MyAdminLib.setHash({name: "myadmin_dash_hash", value: data.dash_hash});
                    } catch (err) {
                        // stop the interval
                        clearInterval(window.DALO);
                    }
                }
            },

            /** Update all the list of students if necessary
             * Description:
             *
             * @return nothing, just update the DOM
             */
            // sTableUpdate: function (data) {

            //     if (MyAdminLib.getHash("myadmin_dash_hash") != data.dash_hash) {

            //         MyAdminLib.setHash({name: "myadmin_dash_hash", value: data.dash_hash});

            //         var d = new Date();
            //         var n = d.toLocaleTimeString();

            //         $('#myadmin_student_table_wacka').bootstrapTable('load', data.users_in_centre);
            //     }
            // },

            start: function(page) {
                // tc_init will have the starting page and will be passed to here. Only run
                // interval if the dashboard is the current page.
                if (page != "page_dashboard") {
                    return;
                }

                console.log("HB -> STARTING..................");
                var that = this;
                if (typeof window.DALO == 'undefined') {
                    console.log("HB -> Initiating HeartBeat.");
                    console.log("HB -> You can stop the HB by typing this into the console: clearInterval(window.DALO);");
                    // the variable is defined
                    if (MyAdminLib.getHash("dash_refresh_rate") == 'undefined') {
                        // console.log("heartbeat.js -> ERROR the data_refresh_rate was not set.");
                        return;
                    }
                    // *** NOTE *** To stop the heartbeat simple clear the interval
                    // clearInterval(window.DALO);
                    window.DALO = setInterval(function () {
                        console.log("-------/\-----/\-----ba boomp------/\-------/\------/\----");

                        // TODO: this needs to be changed to a registration system so any page
                        // can register a heartbeat
                        that.getDashStats().then(function (response) {
                            // console.log("Interval -> going to call update function dashUpdate");
                            that.dashUpdate(response);
                        });

                    }, (localStorage["dash_refresh_rate"] * 1000));
                }
            },

            stop: function() {
                clearInterval(window.DALO);
            }
        };
    }
);