define([
    'jquery',
    // 'local_tcs/jaxy',
    'local_tcs/tcs_lib',
    'local_tcs/_libs/moment',
    'local_tcs/skezzy_calendar',

    // 'local_tcs/_libs/tui-calendar'

    // 'local_tcs/_libs/tui-code-snippet',
    // 'local_tcs/_libs/tui-date-picker',
    // 'local_tcs/_libs/tui-time-picker',
    // 'local_tcs/_libs/tui-dom',
    // 'local_tcs/fullcalendar_core',
    // 'local_tcs/fullcalendar_timeline',
    // 'local_tcs/fullcalendar_daygrid',
    // 'local_tcs/fullcalendar_resource_common',
    // 'local_tcs/fullcalendar_resource_timeline',

    // 'local_tcs/tcs_lib',
    // 'local_tcs/jquery.autocomplete',
    // 'core/modal_factory',
    // 'core/modal_events',
    // 'core/templates',
    // 'local_tcs/tcs_student_table_init'
    // 'local_tcs/PNotifyButtons'
// ], function ($, jaxy, Calendar, FC_T, dayGridPlugin, FC_RC, FC_RT) {
], function ($, TCSLib, moment, Calendar) {
    // 'use strict';
    return {
        calendar_obj: null,

        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        getExamData: function (params) {
            return TCSLib.jaxyPromise({
                'call': 'getExams',
                'params': params,
                'class': 'Exams'
            });
        },

        // getExamData: function () {
        //     var promiseObj = new Promise(function (resolve) {
        //         jaxy.tcsAjax(JSON.stringify({
        //             'call': 'getAllExams',
        //             'params': {},
        //             'class': 'Exams'
        //         })).then(function (response) {
        //             // console.log("getExamData-> What is the response: ", response);
        //             if (response.success === true) {
        //                 var new_data = [];
        //                 for (var x in response.data) {
        //                     new_data.push(response.data[x]);
        //                 }
        //                 resolve(new_data);
        //             } else {
        //                 // TODO: Show alert or popup
        //                 console.log("ajax returned false, meaning no data or query failed.....");
        //             }
        //         });
        //     });
        //     return promiseObj;
        // },
        /** buildItems - Build the items to display
         * Description: Each item is an event in the timeline. This will be the exam with it's
         * start and end date.
         * @param {object} - AJAX data from the server
         * @return array - the list of items to see
         */
        buildItems: function (data) {
            // var ts = moment("10/15/2014 9:00", "M/D/YYYY H:mm").valueOf();
            // var m = moment(ts);
            // var s = m.format("M/D/YYYY H:mm");
            // alert("Values are: ts = " + ts + ", s = " + s);

            var items_list = [],
                opening_date = "",
                closing_date = "",
                // fixed_opening_date = false,
                // fixed_closing_date = false,
                sectionID = 1,
                date_diff = 0,
                today = moment().startOf('day');
                // today = moment('08/06/2020 09:00:00');
                // 19th Jun 2020 09:10
                // log showing: 19th Jan 019 3:10
                // custom_format = "Do MMM YYYY HH:mm";
                // custom_format = 'Do MMM YYYY',
            // console.log("Going to start building the items now........");
            // console.log("how many items are there: " + data.data.length);
            for (var i of data.data) {
                // console.log("What is the i obj: ", i);
                // reset these......
                sectionID = 1;
                opening_date = closing_date = 0;

                i.id = parseInt(i.id);
                i.opening_date = parseInt(i.opening_date);
                i.closing_date = parseInt(i.closing_date);

                //=======================
                // Process Opening Date
                // some dates may not be set......might just have ending date
                if (i.opening_date == 0 || i.opening_date == "" || i.opening_date == "0" || i.opening_date == undefined) {
                    // console.log("*******ATTENTION******* opening_date is 0, empty or undefined: " + i.opening_date);
                    // let's just say it started last week and maybe mark it??
                    opening_date = moment(today).add('days', -50);
                    // fixed_opening_date = true;
                    sectionID = 3;
                } else {
                    // console.log("What is the opening_date: " + opening_date);
                    var temp = moment.unix(i.opening_date);
                    // temp = temp.format(custom_format);
                    // console.log("What is the temp obj: ", temp);
                    opening_date = temp;
                    // sectionID = 1;
                }

                //=======================
                // Process Opening Date
                if (i.closing_date == 0 || i.closing_date == "") {
                    // console.log("*******ATTENTION******* closing_date is 0, empty or undefined: " + i.closing_date);
                    // let's just say it started last week and maybe mark it??
                    closing_date = moment(today).add('days', +50);
                    // fixed_closing_date = true;
                    sectionID = 3;
                } else {
                    // console.log("What is the closing_date: " + closing_date);
                    var temp = moment.unix(i.closing_date);
                    // temp = temp.format(custom_format);
                    // console.log("What is the temp obj: ", temp);
                    closing_date = temp;
                    // sectionID = 1;
                }

                //=======================
                // Process Dates that are longer than 2 weeks

                // var d1 = "2019-01-10";
                // var d2 = "2019-01-20";

                date_diff = moment(closing_date).diff(opening_date, 'days');
                // console.log("What is the date diff: " + date_diff);
                if (date_diff > 14 && sectionID != 3) {
                    // if it equals 3 then it was just set because the start/end was 0
                    sectionID = 2;
                }

                var obj_to_push = {
                    'id': i.id,
                    'name': '<div>' + i.course_name + '</div>',
                    'sectionID': sectionID,
                    'start': opening_date,
                    'end': closing_date,
                    'classes': 'item-status-none',
                    'events': [{
                        'label': 'string to show in tooltip',
                        'at': moment(today).add('hours', 11),
                        'classes': 'item-event-one'
                    }],
                    'time_diff': date_diff
                };
                // console.log("Storing this obj:  ", obj_to_push);
                items_list.push(obj_to_push);
            }
            /*
            response is an array of exams containing:
                id: 0
                course_id: "525"
                course_name: "FUNC-Test-Course"
                exam_id: "11"
                exam_name: "New Quiz Demo"
                opening_date: "1445276460"
                closing_date: "2248362000"
                finished: "false"
                manual: "false"
                notes: ""
                password: ""
                student_list: ""
                visible: "true"
            */
            return items_list;
        },

        /** buildSections - This will be the sections to display
         * Description:
         *
         * @param {object} - AJAX data from the server
         * @return array - the list of items to see
         */
        buildSections: function () {

        },

        /** START - Initialize The Scheduler
         * Description: Initialize The AutoComplete and register any binding events.
         * @param {object} a list of users to use for searching
         */
        initiateScheduler: function() {

            // ****** NOTE *******
            // Moodle sais:
            // JQuery is available via $
            // JQuery UI is available via $.ui

            // ****** NOTE *******
            /*
                When changing the date via buttons(3 days, 1 week, 1 month) the following is called

                skezzy_timeline.js -> Period_Clicked() -> START
                skezzy_timeline.js -> SelectPeriod() -> START
                skezzy_timeline.js -> Init() -> START

                When <- Left and Right -> arrows are clicked:
                skezzy_timeline.js -> TimeShift_Clicked() -> START
                skezzy_timeline.js -> GetSelectedPeriod() -> START
                skezzy_timeline.js -> Init() -> START

                When Date Picker is used: (not very functional atm)
                skezzy_timeline.js -> GotoTimeShift_Clicked() -> START
                skezzy_timeline.js -> GotoTimeShift_Clicked() -> onSelect() -> START
                skezzy_timeline.js -> Init() -> START

                When "Today" button is clicked:
                skezzy_timeline.js -> TimeShift_Clicked() -> START
                skezzy_timeline.js -> GetSelectedPeriod() -> START
                skezzy_timeline.js -> Init() -> START

                // ************* NOTE *******************************
                // The above 4 functions are followed by the 3 calls below

                skezzy_timeline.js -> CreateCalendar() -> START
                skezzy_timeline.js -> GetSelectedPeriod() -> START
                skezzy_timeline.js -> GetEndOfPeriod() -> START
            */

            var that = this;
            // $(document).ready(Calendar.Init);
            this.getExamData().then(function (response) {
                // console.log("initiateScheduler() -> AJAX HAS RETURNED");
                // console.log("initiateScheduler() -> what is the response length: ", response.length);

                // var custom_format = "Do MMM YYYY HH:mm";
                // 19th Jun 2020 09:10
                // var st1 = moment(new Date('Mon Jun 8 2020 11:31:08'));
                // var st1 = moment('Mon Jun 8 2020 11:31:08', custom_format);
                // var st1 = moment().startOf('day');

                var st1 = moment("11/25/2019");
                // console.log("MOMENT TEST 1: What is st: ", st1);
                // var st2 = moment().startOf('day');
                // console.log("MOMENT TEST 2: What is st: ", st2);
                // var today = moment('8th Jun 2020 09:00', 'Do MMM YYYY HH:mm');
                // var today = moment('08-06-2020', 'DD-MM-YYYY');
                // var this_day = '08-06-2020';
                // console.log("What is the response: ");
                // console.table(response);

                Calendar.SetStartDate(st1);
                Calendar.SetSelectedPeriod('1 week');

                Calendar.SetItems(that.buildItems(response));
                Calendar.SetSections([{
                    id: 1,
                    name: 'Exams'
                }, {
                    id: 2,
                    name: 'Longer than 2 weeks'
                }, {
                    id: 3,
                    name: 'No Start/End Date'
                }]);
                Calendar.Init();


                /*
                that.calendar_obj = new Calendar('#tui-calendar', {
                    defaultView: 'week',
                    isReadOnly: true,
                    taskView: true
                    // template: {
                    //     monthDayname: function(dayname) {
                    //         return '<span class="calendar-week-dayname-name">Wacka Wacka Ding Dong</span>';
                    //     }
                    // }
                });

                // that.calendar_obj.createSchedules([
                // {
                //     id: '1',
                //     calendarId: '1',
                //     title: 'my schedule',
                //     category: 'time',
                //     dueDateClass: '',
                //     start: '2018-01-18T22:30:00+09:00',
                //     end: '2018-01-19T02:30:00+09:00'
                // },
                // {
                //     id: '2',
                //     calendarId: '1',
                //     title: 'second schedule',
                //     category: 'time',
                //     dueDateClass: '',
                //     start: '2018-01-18T17:30:00+09:00',
                //     end: '2018-01-19T17:31:00+09:00',
                //     isReadOnly: true    // schedule is read-only
                // }
                // ]);
                */
            });
        },
    };
});
