
define([
    'jquery',
    'local_myadmin/_libs/mermaid'
], function($, mermaid) {
    'use strict';

    // console.log("myadmin_flow.JS -> Starting up App Flow......");
    return {
        /* eslint-disable */
        init: function(extras) {
        /* eslint-enable */
            // console.log("Currently using mermaid.js");
            mermaid.initialize({ startOnLoad: true });
        }
    };
});
