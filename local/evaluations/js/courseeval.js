/*global $:false */

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course Evaluations Tool
 * @package   local
 * @subpackage  Evaluations
 * @author      Modified and Updated By David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
var CourseEval = {

    // Utools object variables can be placed here.
    // initialized: false,
    // widgets_enabled: {},
    // widgets: {},
    // utools_page_load: null,
    // tcms_url: null,
    // general: {},
    // debug_ajax: [],
    // widget_intervals: {},
    // refresh_timer: {},
    // logger: {},
    // settings: {},
    // complexWidgetCounter: 0,
    // startup: null,
    administration: null,

    /** ************************************************************************************
     * Description - Build and send of ajax request, the .then handler in jQuery will handle
     *               the callback for the ajax response.
     * @param - ajax data.
     * @return nothing, promise will handle results.
     */
    runAJAX: function (ajax_data) {
        // console.log("input is: ", ajax_data);
        var data_to_pass = {},
            key = null,
            ajax_obj = {},
            outside = false;

        if (this.getObjectSize(ajax_data.params) === 1) {
            // we are accessing data outside of Moodle, like piwik
            outside = true;
            for (key in ajax_data.params.params) {
                if (ajax_data.params.params.hasOwnProperty(key)) {
                    data_to_pass[key] = ajax_data.params.params[key];
                }
            }
        } else {
            // we are accessing data within Moodle
            // let's gather the params into a bundle
            for (key in ajax_data.params) {
                if (ajax_data.params.hasOwnProperty(key)) {
                    data_to_pass[key] = ajax_data.params[key];
                }
            }
        }

        // let's now build the ajax object
        for (key in ajax_data) {
            if (ajax_data.hasOwnProperty(key)) {
                if (key === 'params') {
                    ajax_obj.data = data_to_pass;
                } else {
                    ajax_obj[key] = ajax_data[key];
                }
            }
        }

        // temporarily storing this so we can play with it in the console.
        // this.debug_ajax.push(ajax_obj);

        return $.ajax(ajax_obj).promise();
    },

    /** ************************************************************************************
     * Description - Fetch a script from the server, a way to lazy load for widgets
     * @param - ajax data
     * @return nothing, this will either pass or fail..
     */
    getScript: function (ajax_data) {
        $.getScript(ajax_data.url);
    },

    /** ************************************************************************************
     * Description - Because we can have logging turned on or off console.log will print here.
     * @param - object: {
                    msg: ["some message"],
                    extra: [no text, just print object]
                }
     * @return nothing, just print.
     */
    // printConsoleMsg: function (data) {

    //     if (CourseEval.logger.hasOwnProperty(data.widget)) {
    //         // console.log("CourseEval.printConsoleMsg -> yes this key exists: " + data.widget + " what is the logger: " + CourseEval.logger[data.widget]);
    //         if (this.logger[data.widget] === "2" || this.logger[data.widget] === "3") {
    //             // console.log(data.msg);
    //             if (data.extra !== undefined) {
    //                 console.log(data.extra);
    //             }
    //         }

    //     } else {
    //         // console.log("this.printConsoleMsg -> NO key does not exist, falling back on this debug level");
    //         if (this.logger.general === "2" || this.logger.general === "3") {
    //             // console.log(data.msg);
    //             if (data.extra !== undefined) {
    //                 console.log(data.extra);
    //             }
    //         }
    //     }
    // },

    /** ************************************************************************************
     * Description - Get the size of a javascript object
     * @param - the object to get size of
     * @return int - the size of the object.
     */
    getObjectSize: function (obj) {

        var size = 0,
            key = null;

        for (key in obj) {
            if (obj.hasOwnProperty(key)) {
                size++;
            }
        }
        return size;
    },
}

// ************************************************************************************
// ************************************************************************************
// ************************************************************************************
// ************************************************************************************
// end of Course Eval Page
// ************************************************************************************
// ************************************************************************************
// ************************************************************************************
// ************************************************************************************
// ************************************************************************************
// ************************************************************************************