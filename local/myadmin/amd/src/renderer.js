
define([
    'jquery',
    'core/templates',
    'local_myadmin/heartbeat'
], function($, templates, HB) {

        'use strict';
        return {

            fetchAndLoad: function() {
                // console.log("fetchAndLoad() -> START -------------");

                // ok, the main page has loaded and now we have other sub-pages that
                // could be in localCache. If not then fetch and store.
                /*
                localStorage.stashy.foreach(function(chunk) {
                    big_dump += chunk;
                });
                console.log("What is big_dump: " + big_dump);
                // now let's append all the cached pages.
                $('#myadmin_page_switcher').append(big_dump);
                */
            },

            /**
             * Process the page that has been clicked
             * @method fetchSWE
             * @param {object} args The request arguments
             * @return {promise} Resolved with an array of the calendar events
             */
            processPage: function(data) {
                // in blocks/myoverview/amd/src/tab_preferences has an example of saving
                // the pages state and preferences
                // console.log("What is data: " + data);
                // data will be the page, page_dashboard, page_examlist
                this.getSnippet(data);
                HB.changePage(data);
            },

            showPage: function (page) {
                // console.log("renderer -> showPage() -> what is the page: " + page);
                $('.myadmin_page_loader').each( function() {
                    var temp_that = $(this);

                    if (temp_that.attr('id') == page) {
                        temp_that.show();
                        // make sure the title of the page is updated.
                        var page_title = page + "_title";
                        $('.myadmin_db_title').text(localStorage[page_title]);
                    } else {
                        temp_that.hide();
                    }
                });
            },

            getSnippet: function(page) {
                // let's check to see if this template is stored
                var context = {},
                that = this;

                if (localStorage[page] === "false") {
                    // This will call the function to load and render our template.
                    templates.render('local_myadmin/' + page, context)

                    // It returns a promise that needs to be resoved.
                    .then(function(html, js) {
                        // Here eventually I have my compiled template, and any javascript that it generated.
                        // The templates object has append, prepend and replace functions.

                        // templates.replaceNodeContents('#myadmin_page_switcher', html, js);
                        templates.appendNodeContents('#myadmin_page_switcher', html, js);

                        // Save the state of the store to true to avoid re-appending
                        localStorage[page] = true;
                        that.showPage(page);
                    }).fail(function() {
                        // Deal with this exception (I recommend core/notify exception function for this).
                        console.log("myadmin/renderer.js -> getSnippet() - FAIL, the ajax failed to get template");
                    });

                } else {
                    this.showPage(page);
                }
            },

            setSnippet: function(data) {
                localStorage[data.page] = true;
            },
        };
    }
);