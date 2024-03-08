/* **************************************************************************** */
/* **************************************************************************** */
/* This is the uncompressed version. The compressed version is in all_js_min.js */
/* **************************************************************************** */
/* **************************************************************************** */

// handy line for chrome console to list all required files: 
// require.s.contexts._.defined

var UofL_Moodle_System = {
    // global variables to this system
    modal_callbacks: null,
    modal_selectall: false,
    required_notify: false,
    peepee: false,
    grade_scroll_initiated: false,

    simple_test_var: {
        title: "Hello World",
        text: "This is a sample message for testing.",
        type: "success",
        opacity: 0.9,
        animation: "show",
        // delay: 0,
        icon: "fa fa-thumbs-up"
    },
    // grade_scroll_ : function () {
    grade_scroll_init : function () {

        // $('#triggerDiv').on('mousewheel DOMMouseScroll', function (e){
        //     // console.log("Scroll was triggered...", e);

        //     // 1. 
        //     $('#scrollableTooltip').scrollTop(e.originalEvent.deltaY + $('#scrollableTooltip').scrollTop());

        //     // 2. 
        //     // $('#scrollableTooltip').trigger('scroll', e);

        //     e.preventDefault();
        // });
    },
    getRootUrl: function () {
        var defaultPorts = {"http:":80,"https:":443};

        return window.location.protocol + "//" + window.location.hostname
        + (((window.location.port)
        && (window.location.port !== defaultPorts[window.location.protocol]))
        ? (":"+window.location.port) : "");
    },

    current_notify_msg: null,

    getDate: function () {
        var date = new Date(),
            days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            months = ["January", "February", "March", "April", "May",
            "June", "July", "August", "September", "October", "November", "December"],
            pad = function(str) { str = String(str); return (str.length < 2) ? "0" + str : str; },

            meridian = (parseInt(date.getHours() / 12) === 1) ? 'PM' : 'AM',
            hours = date.getHours() > 12 ? date.getHours() - 12 : date.getHours();  
        
        return days[date.getDay()] + ' ' + months[date.getMonth()] + ' ' + date.getDate() + ' '
            + date.getFullYear() + ' ' + hours + ':' + pad(date.getMinutes()) + ':'
            + pad(date.getSeconds()) + ' ' + meridian;
    },
    postNotify: function (data) {

        console.log("What is the data to use for PNotify: ", data);
        UofL_Moodle_System.current_notify_msg = new PNotify(data);

        // if (this.required_notify === false) {
            // require(['core/event', 'jquery'], function(event, $) {
            //     event.notifyFilterContentUpdated($(result.fullcontent));
            // });

            // require(['theme_uleth/pnotify', 'jquery'], function(PNotify, $) {
            //     // event.notifyFilterContentUpdated("Fart");
            //     UofL_Moodle_System.peepee = PNotify;
            //     console.log("Inside require func, what is this: ", this);
            //     console.log("Inside require func, do we see object's var: " + UofL_Moodle_System.simple_test_var);
            //     console.log("Let's try to create a PNotify msg INSIDE require");
            //     new PNotify(UofL_Moodle_System.simple_test_var);
            // });

            // this.required_notify = require('pnotify');
        //     this.required_notify = true;
        // } else {
        //     console.log("Do we have a PNotify obj??");
            // console.log(this.peepee);
            // console.log("Let's try to create a PNotify msg");

            // new this.peepee(this.simple_test_var);

        // }

        // UofL_Moodle_System.current_notify_msg = new PNotify(data);

    },

    postNotifyRemove : function() {
        UofL_Moodle_System.current_notify_msg.remove();
        UofL_Moodle_System.current_notify_msg.remove();

    },

    selectAllJSON_Text : function (el) {
        if (typeof window.getSelection !== "undefined" && typeof document.createRange !== "undefined" && UofL_Moodle_System.modal_selectall === true) {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } else if (typeof document.selection !== "undefined" && typeof document.body.createTextRange !== "undefined"  && UofL_Moodle_System.modal_selectall === true) {
            var textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.select();
        }
    },
    
    showModal : function (data) {
        var template = null;
        data.title = (typeof data.title !== 'undefined') ? data.title : "University of Lethbridge";
        data.content = (typeof data.content !== 'undefined') ? data.content : "Oooops, content was not sent.....?";
        data.callbacks = (typeof data.callbacks !== 'undefined') ? data.callbacks : null;
        
        if (typeof data.callbacks !== 'undefined') {
            UofL_Moodle_System.modal_callbacks = data.callbacks;
        } else {
            UofL_Moodle_System.modal_callbacks = false;
        } 
        // data.template = (typeof data.template !== 'undefined') ? data.template : "Oooops, content was not sent.....?";
        
        if (!data.template) {
 
            // var d = new Date();
                
            // console.log("Going to build template");
            // by default this should be false.
            if (typeof data.extras.selectall !== 'undefined') {
                UofL_Moodle_System.modal_selectall = data.extras.selectall;
            } else {
                UofL_Moodle_System.modal_selectall = false;
            } 

            template = '<div id="itemViewModal" class="white-popup">' +
                '<div class="container-fluid">' +
                    '<div class="row-fluid">' +
                        '<div class="span12">' +
                            '<div class="media">' +
                                '<a href="#" class="pull-left ns_sim_profile_pic">' +
                                '<img src="https://moodle.uleth.ca/theme/uofl_boot/pix/UofL_Horizontal_RGB.png" class="media-object" /></a>' +
                                '<div class="media-body">' +
                                    '<h4 class="media-heading ns_sim_main_heading">' +
                                        data.title +
                                    '</h4> <span class="ns_sim_sub_heading">' + this.getDate() + ' </span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<hr>' +
                    '<div class="row-fluid">';
                    
                        if(data.extras !== undefined && UofL_Moodle_System.modal_selectall === true){
                            template = template + '<div class="span12" onclick="UofL_Moodle_System.selectAllJSON_Text(this);">'+data.content+'</div>';
                        }else{
                            template = template + '<div class="span12">'+data.content+'</div>';
                        }

                    template = template + '</div>' +
                '</div>' +
            '</div>';
        } else {
            // console.log("showing template that's been sent");
            template = data.template;
        }

        $.magnificPopup.open({
            items: {
                src: template, // '#moodle_test_modal',
                type: 'inline',
                removalDelay: 300,
                // Class that is added to popup wrapper and background
                // make it unique to apply your CSS animations just to this exact popup
                mainClass: 'mfp-fade',
            },
            callbacks: {
                beforeOpen: function() {
                    // console.log('Start of popup initialization');
                },
                elementParse: function() {
                    // Function will fire for each target element
                    // "item.el" is a target DOM element (if present)
                    // "item.src" is a source that you may modify

                    // console.log('Parsing content. Item object that is being parsed:', item);
                },
                change: function() {
                    // console.log('Content changed');
                    // console.log(this.content); // Direct reference to your popup element
                },
                resize: function() {
                    // console.log('Popup resized');
                    // resize event triggers only when height is changed or layout forced
                },
                open: function() {

                    // if(UofL_Moodle_System.modal_callbacks !== undefined || UofL_Moodle_System.modal_callbacks !== null){
                        // console.log('Going to make this a jquery object: '+modal_callback_data.jquery_obj);
                        // var temp_obj = modal_callback_data.jquery_obj;
                        // console.log('Now going to add this library to the object: '+modal_callback_data.attach_this);
                        // temp_obj[attach_this]();
                    // }
                },

                beforeClose: function() {
                    // Callback available since v0.9.0
                    // console.log('Popup close has been initiated');
                },
                close: function() {
                    // console.log('Popup removal initiated (after removalDelay timer finished)');
                },
                afterClose: function() {
                    // console.log('Popup is completely closed');
                    UofL_Moodle_System.modal_callbacks = null;
                    UofL_Moodle_System.modal_selectall = false;
                },
            },
            closeOnContentClick: false,
            showCloseBtn: true
        });
    },
    closeModal : function(){
        $.magnificPopup.close();
    }
};