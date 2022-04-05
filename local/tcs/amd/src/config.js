// M.cfg.wwwroot is a java-script function provided by Moodle that returns the url of your site.
// Note: You must enter the paths and shim in the order that the files must load.
// In this case, moment.js must load before fullcalendar.js

define([], function() {

    window.requirejs.config({
        paths: {

            // *** NOTE *** using M.cfg.wwwroot works with and without locally but not on PROD
            // Must use cfg for prod.
            // For example:
            // "tcsapp": M.cfg.wwwroot + '/local/tcs/js/some_file',

            // THIS WORKS - regular module test with fullcalendar
            // "editable": M.cfg.wwwroot + '/local/tcs/js/bootstrap-editable-lazy',

            // NO, DOES NOT WORK - Direct import of vue does NOT work
            // "tcsapp": '/local/tcs/tcs/dist/js/app.8b61f4a8',

            // THIS WORKS - simple module template
            // "tcsapp": '/local/tcs/js/import_template',

            // NO, DOES NOT WORK - can see html but css and images are broken
            // "tcsapp": '/local/tcs/vue_dist/vue_import_shell',
        },
     shim: {
            // 'editable': {
            //     exports: 'editable',
            //     // enforceDefine: false
            // }
        }
    });
});
