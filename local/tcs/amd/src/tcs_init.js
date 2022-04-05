define([
    'jquery',
    'local_tcs/jaxy',
    'local_tcs/renderer',
    'local_tcs/tcs_autocomp_stud',
    // 'local_tcs/iziToast',
// ], function ($, jaxy, autocomp, iziToast) {
], function ($, jaxy, rendy, autocomp) {
    'use strict';
    /* eslint-disable */
    var tcs_users = "",
        // hashes
        tcs_user_hash = "",
        tcs_dash_hash = "";
        // tcs_s_table_hash = "";
    /* eslint-enable */

    return {
        // EXAMPLES
        // localStorage["mydatas"] = JSON.stringify(mydatas);
        // var datas = JSON.parse(localStorage["mydatas"]);

        /**
         * Registered Event Listeners
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method fetchSWE
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        loadEventListeners: function () {
            // console.log("------------------------------------------------------");
            // console.log("loadEventListeners() -> START --------(ONLY CALLED ONCE)");
            // console.log("------------------------------------------------------");

            $('[data-toggle=offcanvas]').click(function() {
                $('.row-offcanvas').toggleClass('active');
            });

            function processURL(clicked_link, current_url, title){
                // console.log("processURL() -> Hello from the processURL function");
                // console.log("processURL() -> clicked_link is: " + clicked_link);
                // console.log("processURL() -> current_url is: " + current_url);
                // console.log("processURL() -> title is: " + title);

                var current_url = current_url.substring(0, current_url.indexOf("/tcs/") + 5);
                var clicked_link = clicked_link.replace(/#/g,'');
                // console.log("processURL() -> POST -> What is the url: " + current_url);
                // console.log("processURL() -> POST -> What is the sidebar link: " + clicked_link);

                if (title == "Back To Moodle") {
                    window.location.replace(clicked_link);
                    return;
                }

                window.history.pushState({
                    id: clicked_link
                }, title, current_url + clicked_link + "/");
                // window.history.pushState({
                //     id: clicked_link
                // }, title, current_url + "page/" + clicked_link + "/");
            }

            // console.log("tcs_init -> loadEventListeners() -> What is the window location: " + window.location);
            // console.log("tcs_init -> loadEventListeners() -> What is the window location href: " + window.location.href);

            // this is to switch the page
            $('#tcs_links .nav-link').on("click", function(e) {

                // console.log("tcs_init -> loadEventListeners() -> CLICK");
                e.preventDefault();
                processURL($(this).attr('href'), window.location.href, $(this).data("page_title"));

                // Change the Dashboard title based on the link
                rendy.processPage($(this).data("link"));
            });

            window.addEventListener('popstate', function () {
                // console.log("tcs_init -> loadEventListeners() -> POPSTATE");
                if (history.state && history.state.id === 'Dashboard') {
                    // Render new content for the hompage
                }
                // console.log("tcs_init -> loadEventListeners() -> Popstate has been envoked");
                // console.log("tcs_init -> loadEventListeners() -> what is the event: ", event);
            }, false);
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
        cleanURL: function (this_pager) {
            // The URL could be like this:
            // console.log("tcs_init -> cleanURL()==============================>>>>>>>");
            // console.log("tcs_init -> cleanURL() -> page = " + this_pager);

            var current_url = window.location;
            // console.log("tcs_init -> cleanURL() -> current_url.href: " + current_url.href);

            current_url = current_url.href.replace(/index.php\?page\=/, "");

            // console.log("tcs_init -> cleanURL() -> what is fooker: " + current_url);
            current_url = current_url + "/";

            // state, pageTitle, url
            window.history.replaceState({"page": this_pager}, this_pager, current_url);

            // console.log("tcs_init -> cleanURL() DID WE CHANGE THE URL: " + window.location);
            // console.log("tcs_init -> cleanURL()==============================<<<<<<<");
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
        setUsers: function (users) {
            // console.log("config -> setUsers() -> Storing users in localCache");
            this.tcs_users = users;
            localStorage["tcs_users"] = JSON.stringify(users);
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
        setUserHash: function (hash) {
            // console.log("config -> setUserHash() -> Storing user hash in localCache");
            this.tcs_user_hash = hash;
            localStorage["tcs_user_hash"] = hash;
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
        processUserStore: function(user_hash) {
            // console.log("processUserStore() -> What is the user hash: " + user_hash);
            var stored_user_hash = localStorage.getItem('tcs_user_hash'),
                call_ajax = false,
                stored_users = false,
                that = this;

            if (stored_user_hash) {
                // console.log("processUserStore() -> user hash was stored");
                // TODO: check if this hash is the same as passed in hash
                if (user_hash != stored_user_hash) {
                    // console.log("processUserStore() -> stored user hash DOES NOT EQUAL passed in hash");
                    // Must get new fresh students
                    call_ajax = true;
                } else {
                    // users are stored so let's load them up
                    stored_users = localStorage.getItem('tcs_users');
                    if (stored_users) {
                        // store the users in config
                        this.tcs_users = JSON.parse(stored_users);
                        autocomp.initiateAutoComp(this.tcs_users);
                    } else {
                        // TODO: Make Ajax call to get users
                        call_ajax = true;
                    }
                }
            } else {
                call_ajax = true;
            }

            if (call_ajax) {
                jaxy.tcsAjax(JSON.stringify({
                    'call': 'loadUsers',
                    'params': {
                        'ax': true,
                        // 'ax': false,
                        'page': 0,
                        'total': 0
                    },
                    'class': 'StudentListAjax',
                })).then(function(response) {
                    // console.log("freshProm THEN **************************************************");
                    // console.log("processUserStore() -> returned from ajax AND PROMISE, what is response: ", response);
                    // console.log("freshProm THEN **************************************************");
                    that.setUsers(response.users);
                    that.setUserHash(response.hash);
                    // TODO: this will need to be loaded into the autocomplete library
                    autocomp.initiateAutoComp(response.users);
                });
            }
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
        preLoadConfig: function() {

            // first let's unload the global vars to js from PHP
            var temp_state = {sideBarShow: "in"},
                final_state = {},
                window_stat = {};
                // big_dump = "",
                // stored_templates = "";

            if (window.__SERVER__ === "true" || window.__SERVER__ === true) {
                if (typeof (window.__INITIAL_STATE__) === 'string') {
                    try {
                        // console.log("preLoadConfig() -> What is the __INITIAL_STATE__: ", __INITIAL_STATE__);
                        // console.log("store_general -> What is the __INITIAL_STATE__.table: ", __INITIAL_STATE__.table_data);

                        window_stat = JSON.parse(window.__INITIAL_STATE__);
                        final_state = Object.assign(temp_state, window_stat);
                        // console.log("store_general -> What is the final state here: ", final_state);
                        delete window.__INITIAL_STATE__;
                        window.__SERVER__ = false;
                    } catch (error) {
                        console.log("ERROR, __INITIAL_STATE__ couldn't parse.");
                        console.log(error);
                    }
                }
            } else {
                console.log("WARNING: window.__SERVER__ was not set");
            }


            // ----------- Store Hash Tokens Here ---------------
            // console.log("tcs_init -> going to save the hashes.");
            tcs_user_hash = final_state.user_hash['t_value'];
            tcs_dash_hash = final_state.dash_hash['t_value'];
            // tcs_s_table_hash = final_state.s_table_hash['t_value'];

            localStorage["enter_to_finish"] = final_state.enter_to_finish;
            localStorage["dash_refresh_rate"] = final_state.dash_refresh_rate;
            localStorage["tcs_dash_hash"] = tcs_dash_hash;


            // ----------- Get Admin Status ---------------
            // is the user admin?
            localStorage["tcs_admin_user"] = final_state.is_admin == true ? true : false;
            // console.log("TCS INIT -> Is the user admin: " + localStorage["tcs_admin_user"]);

            // if (final_state.is_admin == true) {
            //     console.log("TCS INIT -> YES, they are admin " + final_state.is_admin);
            //     localStorage["tcs_admin_user"] = true;
            // } else {
            //     console.log("TCS INIT -> NOOOOOOO, they are NOT admin " + final_state.is_admin);
            //     localStorage["tcs_admin_user"] = false;
            // }


            localStorage["tcs_tc_admin_user"] = final_state.is_tc_admin == true ? true : false;
            // console.log("TCS INIT -> Is the user TC admin: " + localStorage["tcs_tc_admin_user"]);


            // console.log("What is admin type: " + typeof localStorage["tcs_admin_user"]);
            // console.log("What is tc admin type: " + typeof localStorage["tcs_tc_admin_user"]);
            // if (final_state.is_tc_admin == true) {
            //     console.log("TCS INIT -> YES, they are admin " + final_state.is_tc_admin);
            // } else {
            //     console.log("TCS INIT -> NOOOOOOO, they are NOT admin " + final_state.is_tc_admin);
            // }

            // localStorage["tcs_s_table_hash"] = tcs_s_table_hash;
            // check user hash, fetch users from local or ajax and store in config
            // console.log("preLoadConfig() -> Going to process User Store Now");

            if (localStorage["tcs_admin_user"] == "true" || localStorage["tcs_tc_admin_user"] == "true") {
                console.log("Going to initiate the auto complete for users");
                this.processUserStore(tcs_user_hash);
            } else {
                console.log("WARNING - User is not admin or tc admin, going to skip fetching users.");
            }

            // check SWE - Students Writing Exams table, fetch from local or ajax and store hash
            // OK, for now we are going to just store if the template has loaded or not
            // by default dashboard is always loaded at start.
            // FIXME: ALSO NOTE this list is in index.php.......can't remember why

            localStorage['page_dashboard'] = false;
            localStorage['page_examlist'] = false;
            localStorage['page_scheduler'] = false;
            localStorage['page_examreqs'] = false;
            localStorage['page_useroverride'] = false;
            localStorage['page_examlogs'] = false;
            localStorage['page_settings'] = false;
            localStorage['page_stats'] = false;
            localStorage['page_printpass'] = false;
            localStorage['page_useradmin'] = false;

            // Any change here must reflect the comp_sidebar.mustache titles
            localStorage['page_dashboard_title'] = "Dashboard";
            localStorage['page_examlist_title'] = "Exam List";
            localStorage['page_scheduler_title'] = "Scheduler";
            localStorage['page_examreqs_title'] = "Exam Requests";
            localStorage['page_useroverride_title'] = "User Overrides";
            localStorage['page_examlogs_title'] = "Exam Logs";
            localStorage['page_settings_title'] = "Settings";
            localStorage['page_stats_title'] = "Stats";
            localStorage['page_printpass_title'] = "Print Pretty Passwords";
            localStorage['page_useradmin_title'] = "User Admins";
            localStorage['page_builder_title'] = "Builder";
            localStorage['page_moodle_title'] = "Back To Moodle";

            var this_pager = window_stat.redirect_page;
            localStorage[this_pager] = true;

            // Here is where the page may NOT be dashboard, show the page and then clean the URL
            // console.log("tcs_init -> preLoadConfig() -> What is the page to load (from PHP): " + this_pager);
            if (this_pager != "page_dashboard") {
                // console.log("tcs_init -> preLoadConfig() -> going to show " + this_pager + " now.");
                rendy.showPage(this_pager);
                this.cleanURL(this_pager);
            // } else {
                // console.log("tcs_init -> preLoadConfig() -> Dashboard is the page, carry on");
            }

            this.loadEventListeners();
            // console.log("preLoadConfig() ========================>>>> FINISHED <<<<========================");
            return this_pager;
        },

        /**
         * Description Here
         *
         * Valid args are:
         * int example 1     Only get events for this course
         * int example 2     Only get events after this time
         *
         * @method postLoadConfig
         * @param {object} args The request arguments
         * @return {promise} Resolved with an array of the calendar events
         */
        postLoadConfig: function() {
            // console.log("postLoadConfig() ========================>>>> START <<<<========================");
            // this.fetchSWE();
        },
    };
});

/*
need to store users
need to store pages in localCache

*/

