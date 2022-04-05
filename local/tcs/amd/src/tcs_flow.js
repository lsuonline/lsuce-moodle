
define([
    'jquery',
    'local_tcs/_libs/mermaid'
], function($, mermaid) {
    'use strict';

    // console.log("tcs_flow.JS -> Starting up App Flow......");
    return {
        /* eslint-disable */
        init: function(extras) {
        /* eslint-enable */
            // console.log("Currently using mermaid.js");
            mermaid.initialize({ startOnLoad: true });
        }
    };
});
