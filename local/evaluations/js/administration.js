/*global $:false */
/**
 * ************************************************************************
 * *                     Tools for Course Eval                           **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge                                 **
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */
//

CourseEval.administration = CourseEval.administration || {

    // CourseEval object variables can be placed here.
    /** ************************************************************************************
     * Description - The start of the semester will have an empty set of course eval courses, run this function
     *               in the console to auto generate. (only should be run once by an admin).
     * @param - none
     * @return nothing.
     */
    // CourseEval.administration.runPopulateDeptartments()
    runPopulateDeptartments: function () {
        
        CourseEval.runAJAX({
            url: 'classes/ajax.php',
            params: {
                'call': 'runPopulateDeptartments',
                'params': {
                },
                'class': 'CourseEvalAjax',
                'storage': {}
            },
            request: 'POST',

        }).then(function (results) {
            var result = $.parseJSON(results);
            console.log("runPopulateDeptartments -> what is the result: ", result);
        });
    },

    prepAddDepartment: function () {

        var dept = $('#local_eval_admin_add_dept').val();
        var code = $('#local_eval_admin_add_code').val();

        if (dept.length < 1) {
            CourseEval.administration.printError("Oops", "Looks like you don't have a department name.");
            return;
        }

        if (code.length < 1) {
            CourseEval.administration.printError("Oops", "Looks like you don't have a department code.");
            return;
        }

        this.addDepartment(dept, code);
    },

    addDepartment: function (dept, code) {

        if (dept.length < 1) {
            CourseEval.administration.printError("Oops", "Looks like you don't have a department name.");
            return;
        }

        if (code.length < 1) {
            CourseEval.administration.printError("Oops", "Looks like you don't have a department code.");
            return;
        }
        // local_eval_admin_add_dept
        // local_eval_admin_add_code
        // console.log("addDepartment() -> What is the dept_name: " + dept);
        // console.log("addDepartment() -> What is the dept_code: " + code);
        code = code.toUpperCase();

        CourseEval.runAJAX({
            url: 'classes/ajax.php',
            params: {
                'call': 'addDepartment',
                'params': {
                    'dept': dept,
                    'code': code,
                },
                'class': 'CourseEvalAjax',
                'storage': {}
            },
            request: 'POST',

        }).then(function (results) {
            var result = $.parseJSON(results);

            // console.log("addDepartment -> what is the result: ", result);

            if (result.success === "true") {
                CourseEval.administration.printSuccess("Success", result.msg);
                $('#local_eval_admin_dept_container').empty();
                $('#local_eval_admin_dept_container').html(result.html);

                // clear the fields
                $('#local_eval_admin_add_dept').val('').focus();
                $('#local_eval_admin_add_code').val('');

            } else {
                CourseEval.administration.printError("Oops", result.msg);
            }

        });
    },

    removeDepartment: function (params) {

        if (params == undefined) {
            CourseEval.administration.printError("Oops", "There was no id attached to this department......uh oh.");
            return;
        }
        // will be splitting this: local_eval_admin_code_[CODE]
        var chunks = params.split('_'),
            code = null;
        code = chunks[4];

        CourseEval.runAJAX({
            url: 'classes/ajax.php',
            params: {
                'call': 'deleteDepartment',
                'params': {
                    // 'dept': dept,
                    'code': code,
                },
                'class': 'CourseEvalAjax',
            },
            storage: {
                'id': params
            },
            request: 'POST',

        }).then(function (results) {
            var result = $.parseJSON(results);

            // console.log("addDepartment -> what is the result: ", result);

            if (result.success === "true") {
                CourseEval.administration.printSuccess("Success", result.msg);
                // console.log("What is storage: " + this.storage);
                // console.log("What is storage.id: " + this.storage.id);

                $('#' + this.storage.id).parent().remove();
            } else {
                CourseEval.administration.printError("Oops", result.msg);
            }

        });
    },


    printSuccess: function (msg_title, msg) {

        UofL_Moodle_System.postNotify({
            title: msg_title,
            text: msg,
            type: "success",
            opacity: 0.9,
            animation: "show",
            // delay: 0,
            icon: "icon-thumbs-up"
        });
    },

    printError: function (msg_title, msg) {
        UofL_Moodle_System.postNotify({
            title: msg_title,
            text: msg,
            type: "error",
            opacity: 0.9,
            animation: "show",
            // delay: 0,
            icon: "icon-thumbs-down"
        });
    }
};
//
//=============================================================================
//===================       Load on Dom Ready       ===========================
//=============================================================================
// CourseEval.printConsoleMsg({"msg": "Utools administration -> document.ready now invoked."});
//
// ADMIN FORM 
// works in console if exec'd there, $('.mform').on('submit', function(e) {

document.onreadystatechange = function () {
    /*jslint browser:true */
    var state = document.readyState;
    // if (state === 'interactive') {
        // console.log("Loading.........");
    // } else 
    if (state === 'complete') {
        // console.log('Dom is ready!');

        $('#local_eval_admin_add_btn').click(function(event) {
            // console.log("does this obj exist: ", CourseEval.administration);
            CourseEval.administration.prepAddDepartment();
        });

        // $('#local_eval_admin_code_delete_btn').click(function(event) {
        $('#local_eval_admin_dept_container').on('click', '#local_eval_admin_code_delete_btn', function () {
            // console.log("Shizzzzz clicked on the trash button");
            var parent_id = $(this).parent().attr('id');
            CourseEval.administration.removeDepartment(parent_id);

        });

        $(document).keypress(function(e) {
            if(e.which == 13) {
                CourseEval.administration.prepAddDepartment();
            }
        });
    }
};

// Utools.administration.init();