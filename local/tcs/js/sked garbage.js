initiateScheduler2: function(users) {
            console.log("initiateScheduler() ========================>>>> START <<<<========================");
            // var calendarEl = document.getElementById('tcs_scheduler');
            
            // helpers
            // function getWeekEnd() {
                // var curr = new Date; // get current date
            function getWeek() {
                var curr = new Date; // get current date
                var first = curr.getDate() - curr.getDay(); // First day is the day of the month - the day of the week
                var last = first + 6; // last day is the first day + 6

                // var firstday = new Date(curr.setDate(first)).toUTCString();
                var firstday = new Date(curr.setDate(first));
                // var lastday = new Date(curr.setDate(last)).toUTCString();
                var lastday = new Date(curr.setDate(last));
                return {
                    'first': firstday,
                    'last': lastday
                }
                // firstday
                // "Sun, 06 Mar 2011 12:25:40 GMT"
                // lastday
                // "Sat, 12 Mar 2011 12:25:40 GMT"
            }
            function semesterStart(year, month, day) {
                var date = new Date(year, month, day);
                // date.setHours(hours, minutes, 0, 0);
                return date;
            }
            function semesterEnd(year, month, day) {
                var date = new Date(year, month, day);
                // date.setHours(hours, minutes, 0, 0);
                return date;
            }
            function today(hours, minutes) {
                var date = new Date();
                date.setHours(hours, minutes, 0, 0);
                console.log("initiateScheduler -> today() -> what is date: " + date);
                
                return date;
            }
            function yesterday(hours, minutes) {
                var date = today(hours, minutes);
                date.setTime(date.getTime() - 24 * 60 * 60 * 1000);
                return date;
            }
            function tomorrow(hours, minutes) {
                var date = today(hours, minutes);
                date.setTime(date.getTime() + 24 * 60 * 60 * 1000);
                return date;
            }
            var events = [{
                name: 'Meeting 1',
                location: '1',
                start: today(4, 15),
                end: today(7, 30),
                url: null,
                class: '', // extra class
                disabled: false, // is disabled?
                data: {}, // data to set with $.data() method
                userData: {} // custom data
            },
            {
                name: 'Meeting 2',
                location: '2',
                start: today(7, 30),
                end: today(9, 15)
            },
            {
                name: 'Meeting',
                location: '3',
                start: today(10, 0),
                end: today(11, 30)
            },
                // more events here
            ];

            var locations = [
                { id: '1', name: 'biol 3400 A', tzOffset: 7 * 60 },
                { id: '2', name: 'anth 2410 A', tzOffset: -10 * 60 },
                { id: '3', name: 'GEOG 2300 A', tzOffset: 4 * 60 },
                { id: 'london', name: 'mgt 3205 A', tzOffset: -1 * 60 },
                { id: '5', name: 'BCHM 2300 A', tzOffset: -2 * 60 },
                { id: '6', name: 'mgt 2100 A,B,&C', tzOffset: -2 * 60 },
            ];
            
            var weekSE = getWeek();
            var mySchedule = $('#tcs_scheduler').skedTape({
                caption: 'Exams',
                // start: semesterStart(2019, 05, 01), // yesterday(22, 0),
                start: weekSE.first, // yesterday(22, 0),
                // end: semesterEnd(2019, 08, 31), // today(12, 0),
                end: weekSE.last, // today(12, 0),
                showEventTime: true,
                showEventDuration: true,
                scrollWithYWheel: true,
                locations: locations,
                events: events,
                showDates: true,
                maxTimeGapHi: 60 * 1000, // 1 minute
                minGapTimeBetween: 1 * 60 * 1000, 
                // minGapTimeBetween: 24 * 60 * 60 * 1000,
                // snapToMins: 1,
                // editMode: true,
                timeIndicatorSerifs: true,
                // formatters: {
                //     date: function (date) {
                //         return $.fn.skedTape.format.date(date, 'l', '.');
                //     },
                //     duration: function (start, end, opts) {
                //         return $.fn.skedTape.format.duration(start, end, {
                //             hrs: 'ч.',
                //             min: 'мин.'
                //         });;
                //     },
                // },
                canAddIntoLocation: function (location, event) {
                    console.log("initiateScheduler() -> canAddIntoLocation");
                    return location.id !== 'london';
                },
                postRenderLocation: function ($el, location, canAdd) {
                    console.log("initiateScheduler() -> postRenderLocation");
                    this.constructor.prototype.postRenderLocation($el, location, canAdd);
                    $el.prepend('<i class="fas fa-thumbtack text-muted"/> ');
                }
            });

            mySchedule.on('skedtape:event:click', function (e) {
                // on click
                console.log("initiateScheduler() skedtape:event:click");
                
            });
            
            mySchedule.on('skedtape:event:contextmenu', function (e) {
                // on right click
                console.log("initiateScheduler() skedtape:event:contextmenu");
            });
            
            mySchedule.on('skedtape:timeline:click', function (e) {
                // on timeline click
                console.log("initiateScheduler() skedtape:timeline:click");
            });


            mySchedule.on('event:dragEnded.skedtape', function (e) {
                console.log("initiateScheduler() zzzzz -> event:dragEnded.skedtape");
                console.log(e.detail.event);
            });
            mySchedule.on('event:click.skedtape', function (e) {
                console.log("initiateScheduler() xxxxx -> event:click.skedtape");
                mySchedule.skedTape('removeEvent', e.detail.event.id);
            });
            mySchedule.on('timeline:click.skedtape', function (e, api) {
                console.log("initiateScheduler() yyyyy -> timeline:click.skedtape");
                try {
                    mySchedule.skedTape('startAdding', {
                        name: 'New meeting',
                        duration: 60 * 60 * 1000
                    });
                }
                catch (e) {
                    if (e.name !== 'SkedTape.CollisionError') throw e;
                    //alert('Already exists');
                }
            });
            // console.log("initiateScheduler() -> What is FC: ", Calendar);
            // console.log("initiateScheduler() -> What is FC_T: ", FC_T);
            // console.log("initiateScheduler() -> What is FC_RC: ", FC_RC);
            // console.log("initiateScheduler() -> What is FC_RT: ", FC_RT);
            // document.addEventListener('DOMContentLoaded', function () {
                // console.log("initiateScheduler() -> **********************%%%%%%%%%%%%%%%%$$$$$$$$##############");
                // var calendar = new Calendar.calendar(calendarEl, {
                // var calendar = new FC(calendarEl, {
                    // plugins: [FC_RT], FAIL
                    // plugins: [FC_T], FAIL
                    // plugins: ['resourceTimeline'],
                    // plugins: [dayGridPlugin],
                    // header: {
                    //     left: 'prev,next today',
                    //     center: 'title',
                    //     right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'   
                    // },
                    // navLinks: true, // can click day/week names to navigate views
                    // defaultView: 'resourceTimelineWeek',
                    // defaultView: 'resourceTimeline',
                    // schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                    // resources: [
                        // your resource list
                    // ]
                // });
                
                // calendar.render();
            // });

            /*
            var calendar = new Calendar(calendarEl, {
                schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source'
            });
        

            document.addEventListener('DOMContentLoaded', function () {
                var calendarEl = document.getElementById('calendar');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    plugins: ['dayGrid']
                });

                calendar.render();
            });
            */


            console.log("initiateScheduler() ========================>>>> FINISHED <<<<========================");
        }