define([
    'jquery',
    // 'local_myadmin/renderer',
    'local_myadmin/myadmin_init',
    'local_myadmin/heartbeat',
    // 'theme_uleth_v1/loader'
/* eslint-disable */
], function($, configgy, HB) {
// ], function($, renderer, configgy, HB, loader) {
/* eslint-enable */
    'use strict';

    // console.log(">>>>>>>>>>>>>>>=-=-=-=-=-=-=-=-=-=-=-=<<<<<<<<<<<<<<");
    // console.log("Main.JS -> Starting up App......");
    // console.log(">>>>>>>>>>>>>>>=-=-=-=-=-=-=-=-=-=-=-=<<<<<<<<<<<<<<");

    return {
        /**
         * This is the starting function for the MyAdmin
         * @param {object} extras is data coming from PHP
         */

        init: function() {
            // preloadconfig tackles variables passed from PHP via __INITIAL_STATE__

            // if (window.history.hasOwnProperty("previous")) {
            //     console.log("previous url1 is: " + window.history.previous.href);
            // }

            // load the config for this app
            // var page_to_load = configgy.preLoadConfig();
            // configgy.preLoadConfig();
            // rendy.processPage(page_to_load);

            // is localStorage set?
            // if (!localStorage.stashy) {
            //     localStorage.stashy = "";
            // }
            HB.start(configgy.preLoadConfig());

            // window.onload = function () {
            //     if (window.jQuery) {
            //         // jQuery is loaded
            //         // configgy.postLoadConfig();
            //         // renderer.fetchAndLoad();
            //         console.log("Ready to load postLoadConfig() & fetchAndLoad()");
            //     } else {
            //         // jQuery is not loaded
            //         alert("jQuery DID NOT LOAD");
            //     }
            // };

            // $(document).ready(function() {
            //     $(window).on('load', function() {
            //         //insert all your ajax callback code here.
            //         //Which will run only after page is fully loaded in background.

            //         // this starts the heart beat so ajax calls will be made every (currently hard coded to) 30 seconds
            //         console.log("/\/\/\/\/\/\----------Going to call HB.......");

            //         configgy.postLoadConfig();
            //         renderer.fetchAndLoad();
            //     });
            // });
        }
    };
});
