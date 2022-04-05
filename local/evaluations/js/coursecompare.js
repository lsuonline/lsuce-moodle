
var local_evaluation_funcs = {

    course_compare_load: null,
    page_all_checked_true: false,
    global_all_checked_true: false,
    /**
     * Description
        general ajax function to send and recieve data, pretty straight forward
     * @param type array{
        url: http://.......,
        params: {value1:x1, value2:x2, value3:x3},
        request: GET or POST
     } 
     * @return type
     */
    ajax: function(ajax_data) {
        var data_to_pass = {};

        for(var key in ajax_data.params){
            data_to_pass[key] = ajax_data.params[key];
        }
        
        // console.log("What is data to pass in ajax call: ");
        // console.log(data_to_pass);

        $.ajax({
            url: ajax_data.url,
            data: data_to_pass,
            type: ajax_data.request,
            beforeSend: function(){
                // 
            },
            success: function(evt) {
                // console.log("course_eval_lib.ajax => SUCCESS What is evt: ");
                // console.log(evt);
                local_evaluation_funcs[ajax_data.return_func]($.parseJSON(evt));
               
            },
            error: function(evt){
                console.log("course_eval_lib.ajax => FAIL What is evt: ");
                console.log(evt);
            }
        });
    },
    /**
     * Description
        clear this list
     * @params - string - div to clear
     * @return - none
     */
    clear_chunk: function(this_div){
        $('.'+this_div).html("");
    },

    toType: function(obj) {
        return ({}).toString.call(obj).match(/\s([a-zA-Z]+)/)[1].toLowerCase()
    },
    /**
     * Description
        get a list of students without Banner id's or search for one student's record 
     * @return none - ajax func will call returning function
     */
    get_all_groups: function(data){
        
        UofL_Moodle_System.postNotify({
            title: "Fetching......", 
            text: "Grabbing compisons, one sec......", 
            type: "info",
            opacity: 0.9,
            animation: "show",
            // delay: 0,
            icon: "icon-search"
        });

        var params = {
            'dept': data,
        };

        var form_data = {
            url: 'classes/ajax.php',
            params: {'call':'getAllGroups', 'params':params},
            request: 'GET',
            return_func: 'get_all_groups_return',
        };
        console.log("Calling ajax func");
        local_evaluation_funcs.ajax(form_data);

    },
            
    /**
     * Description
        Data from the server containing an array of user results
     * @param array - of students
     */
    get_all_groups_return: function(ajax_info){
        
        console.log("What is ajax_info");
        console.log(ajax_info);

        UofL_Moodle_System.postNotifyRemove();
        // var data = $.parseJSON(ajax_info);
        var this_html = "<style>\
            .table-striped>tbody>tr:nth-child(odd)>td,\
            .table-striped>tbody>tr:nth-child(odd)>th {\
               background-color: #eef7fd;\
             }</style>" + ajax_info.html;

        if(ajax_info.success == "true"){
            UofL_Moodle_System.showModal({"template":null,"content":this_html, "title":"Current Group Comparisons"});
        }else{
            UofL_Moodle_System.postNotify({
                title: "Oooops......", 
                text: "Something is a little broken, please notify admin, sorry.", 
                type: "error",
                opacity: 0.9,
                animation: "show",
                // delay: 0,
                icon: "icon-warning"
            });
        }
    },
    
    update: function(){

        console.log("what is doc ready state: "+document.readyState);
        if( document.readyState != "complete" ){  // don't do unless document loaded
            // console.log("DOC IS NOT READY");
            return ;
        }else{
            clearInterval(local_evaluation_funcs.course_compare_load);
            console.log("Course Compare JS IS READY");

            // check if all are checked
            var all_checked = $('#cc_pageOnlySelect_form :input:checkbox');
            var temp_all_checked = true;
            all_checked.each(function() {
                if(this.name == "evalcheck"){
                     if(this.checked == false){
                        local_evaluation_funcs.page_all_checked_true = false;
                     }
                }
            });
            

            /*
             * print the list of the currently stored selected lists to the php console
            */

            $('#eval_view_current_compares_form').submit(function(e){
                e.preventDefault(); 
                var $inputs = $('#eval_view_current_compares_form :input');
                $inputs.each(function() {
                    console.log("Here is the name: "+this.name);
                    if(this.name == "dept"){
                        console.log("Here is the value: "+this.value);
                        local_evaluation_funcs.get_all_groups(this.value);
                    }

                });
            });
            /*
             * Sent a request to the page to select all items on current page
            */
            $('#cc_pageOnlySelect').submit(function(e) {
                // console.log("cc_pageOnlySelect => what is e: ");
                // console.log(e);

                // get all the inputs into an array.
                var $inputs = $('#cc_pageOnlySelect_form :input:checkbox');

                // not sure if you wanted this, but I thought I'd add it.
                // get an associative array of just the values.
                var values = {},
                    switch_to = false,
                    currentPage = 0,
                    data_to_send = "";

                // toggle the switch
                console.log("What is all checked B: "+local_evaluation_funcs.page_all_checked_true);
                local_evaluation_funcs.page_all_checked_true = !local_evaluation_funcs.page_all_checked_true;
                console.log("What is all checked A: "+local_evaluation_funcs.page_all_checked_true);

                // if(local_evaluation_funcs.page_all_checked_true == true){
                //     local_evaluation_funcs.page_all_checked_true = false;
                // }else{
                //     local_evaluation_funcs.page_all_checked_true = true;
                // }

                $inputs.each(function() {
                    if(this.name == "evalcheck"){
                        // console.log("Here is the first input: "+this.name+" and check: "+this.checked);
                        // console.log("What is checked2: "+this.checked);
                        
                        // change the check box to all or none
                        this.checked = local_evaluation_funcs.page_all_checked_true;

                        // now let's get the course and eval id
                        var bits = this.className.split("_");
                        data_to_send += bits[4]+",";
                        // console.log("data_to_send is now: "+data_to_send);
                    }

                    // console.log("cc_pageOnlySelect => What is this.name: "+this.name+" and check: "+this.checked);
                });
                
                // console.log("data_to_send before: "+data_to_send);
                if (data_to_send.substring(data_to_send.length-1) == ","){
                    data_to_send = data_to_send.substring(0, data_to_send.length-1);
                }
                console.log("data_to_send: "+data_to_send);
                
                var value = $.ajax({
                    url: 'coursecompare.php',
                    data: {'call':'updateSelectedList', 'index':'allOnePage', 'course_ids':data_to_send, 'change_to':local_evaluation_funcs.page_all_checked_true},
                    type: 'GET',
                    success: function(evt) {
                        console.log("coursecomare.js => SUCCESS => What is evt: ");
                        console.log(evt);
                        
                    },
                    error: function(evt){
                        console.log("coursecomare.js => ERROR => What is evt: ");
                        console.log(evt);
                    }
                });

                return false;
            });
            
            

            /*
             * Sent a request to the page to select all items 
            */
            $('#cc_allSelect').submit(function(e) {

                console.log("cc_allSelect => what is e: ");
                console.log(e);

                local_evaluation_funcs.global_all_checked_true = !local_evaluation_funcs.global_all_checked_true;
                var the_dept = null;
                
                // toggle all the check boxes
                var $inputs = $('#cc_pageOnlySelect_form :input');
                $inputs.each(function() {
                    console.log("Here is the input: "+this.name);

                    if(this.name == "evalcheck"){
                        this.checked = local_evaluation_funcs.global_all_checked_true;
                    }

                });
                // get dept
                var $inputs = $('#eval_view_current_compares_form :input');
                $inputs.each(function() {
                    if(this.name == "dept"){
                        console.log("Here is the input: "+this.value);
                        the_dept = this.value;
                    }
                });


                var value = $.ajax({
                    url: 'coursecompare.php',
                    data: {'call':'updateSelectedList', 'index':'allPages', 'dept':the_dept, 'change_to':local_evaluation_funcs.global_all_checked_true},
                    type: 'GET',
                    success: function(evt) {
                        console.log("coursecomare.js => cc_allSelect=> SUCCESS => What is evt: ");
                        console.log(evt);
                    },
                    error: function(evt){
                        console.log("coursecomare.js => cc_allSelect => SUCCESS => What is evt: ");
                        console.log(evt);
                    }
                });

                return false;
            });

            /*
             * Select individual items on current page
            */
                
            $('#course_compare_reporting').delegate('input[type=checkbox]', 'change', function(event, ui){
                var className = event.target.className;
                var the_class = className.split('_');
              
                var page_id = parseInt(the_class[3]);
                var final_index = parseInt(the_class[2]);
                
                var courseid = the_class[4];

                var value = $.ajax({
                    url: 'coursecompare.php',
                    data: {'call':'updateSelectedList', 'index':'toggleOne', 'cid':courseid},
                    type: 'GET',
                    success: function(evt) {
                        console.log("coursecomare.js => SUCCESS => What is evt: ");
                        console.log(evt);
                        
                    },
                    error: function(evt){
                        console.log("coursecomare.js => SUCCESS => What is evt: ");
                        console.log(evt);
                    }
                });
            });

        }
    },
    printCourseSelectedList: function(){

        var value = $.ajax({
            url: 'coursecompare.php',
            data: {'call':'updateSelectedList', 'index':'printAllLists'},
            type: 'GET',
            success: function(evt) {
                console.log("coursecomare.js => printCourseSelectedList=> SUCCESS => What is evt: ");
                console.log(evt);
            },
            error: function(evt){
                console.log("coursecomare.js => cc_allSelect => SUCCESS => What is evt: ");
                console.log(evt);
            }
        });


        return false;
    },
    purgeList: function(){

        var value = $.ajax({
            url: 'coursecompare.php',
            data: {'call':'updateSelectedList', 'index':'purgeList'},
            type: 'GET',
            success: function(evt) {
                console.log("coursecomare.js => printCourseSelectedList=> SUCCESS => What is evt: ");
                console.log(evt);
            },
            error: function(evt){
                console.log("coursecomare.js => cc_allSelect => SUCCESS => What is evt: ");
                console.log(evt);
            }
        });
        return false;
    },

    remove_eval_compare: function(data){
        console.log("What is data: "+data);
        //

        var form_data = {
            url: 'classes/ajax.php',
            params: {
                'call':'removeThisComparison',
                'params': {
                    'remove_comparison': data,
                },
                'class': 'CourseEvalAjax'
            },
            request: 'POST',
            return_func: 'remove_eval_compare_return',
        };
        console.log("Calling ajax func");
        local_evaluation_funcs.ajax(form_data);
    },
    remove_eval_compare_return: function(data){
        console.log("remove_eval_compare_return() -> What is data: " + data);
        
        if(data.success == "false"){

            UofL_Moodle_System.postNotify({
                title: "Oooops", 
                text: "This comparison report was NOT removed.", 
                type: "error",
                opacity: 0.9,
                animation: "show",
                // delay: 0,
                icon: "icon-warning"
            });

        }else{
            // remove the hr tag first, then the row
            $('#eval_report_id_'+data.compare_id).next().remove();
            $('#eval_report_id_'+data.compare_id).remove();

            UofL_Moodle_System.postNotify({
                title: "Success", 
                text: "This comparison report has been removed.", 
                type: "success",
                opacity: 0.9,
                animation: "show",
                // delay: 0,
                icon: "icon-check"
            });
        }

    }

};
local_evaluation_funcs.course_compare_load = setInterval( 'local_evaluation_funcs.update();', 100 ) ;



