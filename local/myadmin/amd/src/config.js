// M.cfg.wwwroot is a java-script function provided by Moodle that returns the url of your site.
// Note: You must enter the paths and shim in the order that the files must load.
// In this case, moment.js must load before fullcalendar.js

define([], function() {

    window.requirejs.config({
        paths: {

            // *** NOTE *** using M.cfg.wwwroot works with and without locally but not on PROD
            // Must use cfg for prod.
            // For example:
            // "myadminapp": M.cfg.wwwroot + '/local/myadmin/js/some_file',

            // THIS WORKS - regular module test with fullcalendar
            // "editable": M.cfg.wwwroot + '/local/myadmin/js/bootstrap-editable-lazy',

            // NO, DOES NOT WORK - Direct import of vue does NOT work
            // "myadminapp": '/local/myadmin/myadmin/dist/js/app.8b61f4a8',

            // THIS WORKS - simple module template
            // "myadminapp": '/local/myadmin/js/import_template',

            // NO, DOES NOT WORK - can see html but css and images are broken
            // "myadminapp": '/local/myadmin/vue_dist/vue_import_shell',
        },
     shim: {
            // 'editable': {
            //     exports: 'editable',
            //     // enforceDefine: false
            // }
        }
    });
});
