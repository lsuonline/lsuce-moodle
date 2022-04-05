define([
    'jquery',
    'local_tcs/jaxy',
    'local_tcs/tcs_lib',
    'local_tcs/_libs/jquery.autocomplete'
    // 'core/modal_factory',
    // 'core/modal_events',
    // 'core/templates',

    // 'local_tcs/tcs_student_table'
    // 'local_tcs/PNotifyButtons'
// ], function ($, jaxy, TCSLib, autocomplete, ModalFactory, ModalEvents, Templates, StudentTable) {
    /* eslint-disable */
], function ($, jaxy, TCSLib, autocomplete) {
    'use strict';
    /* eslint-enable */
    // var keyStrokeCount = 0,
    //     isBarCodeReader = 0,
    //     temp_userid = 0,
    //     mask = 0,
    //     stored_idnumber = 0,

    var $auto_obj = null;

    // TODO: Make default select on radio buttons, right now it's last selection
    return {
        /*
        checkForm: function () {

            console.log("Do we have exam_id: " + $("input[name='tcs_exam_check']:checked").val());
            console.log("Do we have id_type: " + $("input[name='tcs_identity_check']:checked").val());
            console.log("Do we have room: " + $("input[name='exam_room_check']:checked").val());
            console.log("Do we have comments: " + $('#tcs_comments_on_student').val());

            return $("input[name='tcs_exam_check']:checked").val() &&
                $("input[name='tcs_identity_check']:checked").val() &&
                $("input[name='exam_room_check']:checked").val();
        },
        */
        // ========================================================================================
        // ========================================================================================
        // ========================================================================================
        /** START - Initialize The AutoComplete
         * Description: Initialize The AutoComplete and register any binding events.
         * @param {object} a list of users to use for searching
         */
        initiateAutoComp: function (users, calling_obj, tag) {
            // currently trying this one:
            // https://github.com/devbridge/jQuery-Autocomplete
            // console.log("initiateAutoComp() -> going to initiate autocomplete");
            // console.log("initiateAutoComp() -> what is users: ", users);
            // $('#autocomplete').focus();
            // $('#autocomplete').autocomplete({

            $auto_obj = $(tag);
            $auto_obj.focus();
            $auto_obj.autocomplete({

                lookup: users,
                lookupLimit: 20,
                // lookupFilter: function(suggestion, originalQuery, queryLowerCase) {
                // var re = new RegExp('\\b' + $.Autocomplete.utils.escapeRegExChars(queryLowerCase), 'gi');
                // var re = new RegExp(originalQuery, 'gi');
                // return re.test(suggestion.data.uofl_id);
                // },
                triggerSelectOnValidInput: function () {
                    // console.log("triggerSelectOnValidInput() -> Do Stuff........");

                },
                onSelect: function (suggestion) {
                    // console.log("initiateAutoComp -> onSelect() -> Do Stuff........");
                    // console.log("initiateAutoComp -> onSelect() -> going to call onSelect ");
                    calling_obj.onSelect(suggestion);
                },
                onHint: function () {
                    // console.log("onHint() -> Do Stuff........");
                    // $('#autocomplete-ajax-x').val(hint);
                },
                onSearchComplete: function (param1, param2) {
                    // console.log("initiateAutoComp -> onSearchComplete() -> Typing........");
                    // console.log("initiateAutoComp -> onSearchComplete() -> is there a param1 passed in: ", param1);
                    // console.log("initiateAutoComp -> onSearchComplete() -> is there a param2 passed in: ", param2);
                    if (param2.length == 1) {
                        if (localStorage["enter_to_finish"] == "1") {
                            // console.log("@@@@@@@@@@@ ===>>>> initiateAutoComp -> onSearchComplete()");
                            calling_obj.onSearchComplete(param2[0]);
                        }
                    }

                },
                onInvalidateSelection: function () {
                    // console.log("onInvalidateSelection() -> Do Stuff........");
                    // $('#selction-ajax').html('You selected: none');
                }
            });
            /*
            $("body").on('click', '#tcs_enterstudent_table > tbody > tr', function (event) {
                // console.log("Clicked inside the row BITCH, what is the type: " + event.target.type);
                if (event.target.type !== 'radio') {
                    $(':radio', this).trigger('click');
                }
            });
            // $('#autocomplete').keypress(function (e) {
            $auto_obj.keypress(function (e) {
                // Here's an example of the card being scanned:
                // % 001028120 ?; 6018190723618365 ? +691606639 ?
                var actual_id = null,
                    charCode = e.which;
                // keycode 37 = %
                //  13 = return
                //  9 = tab
                that.keyStrokeCount = 0;
                //
                if ((e.keyCode === 37 || charCode === 37) && that.keyStrokeCount === 0) {
                    that.isBarCodeReader = 1;
                    // $('#tcs_std_list_spinner').css("visibility", "visible");
                    that.temp_userid = '';
                    that.mask = '';
                    // return;
                }
                // console.log("keypress -> what is the charcode: " + charCode + " and value: " + this.value);

                //
                that.keyStrokeCount++;
                //
                if (this.value.length === 9 && that.isBarCodeReader === 0) {

                    console.log("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$");
                    console.log("keypress -> length is 9 and it's barcode");
                    that.keyStrokeCount = 0;
                    // TCS.student_list.clear_fields_for_newStudentEntry();
                    // TCS.student_list.process_entry(this.value);
                    return true;
                }
                // do stuff with barcode entry
                if (that.isBarCodeReader) {
                    // store the input
                    that.temp_userid = that.temp_userid + String.fromCharCode(charCode);
                    // console.log("Building id, now its: " + that.temp_userid);
                    that.mask += "*";
                    // $('#autocomplete').val(that.mask);
                    $auto_obj.val(that.mask);
                }
                // if (/[%]\d+[?][;]\d+[?][+]\d+[?]/.test(TCS.student_list.temp_userid) && TCS.student_list.isBarCodeReader === 1) {
                // if the card reader reaches the end the keycode is 13 (return) process now.....
                if (charCode === 13 && that.isBarCodeReader === 1) {
                    // console.log("keypress -> regex and it's barcode");
                    //
                    // $('#tcs_std_list_spinner').css("visibility", "hidden");
                    //
                    // that.$exams.focus();
                    actual_id = that.temp_userid.substring(1, 10);
                    that.temp_userid = '';
                    // that.$userid.val(actual_id);
                    //
                    that.keyStrokeCount = 0;
                    that.isBarCodeReader = 0;
                    // that.clear_fields_for_newStudentEntry();
                    // // that.$userid.val(actual_id);
                    // // that.$comments.html('');
                    // that.process_entry(actual_id);
                    console.log("Whats the final id of this MORON: " + actual_id);
                    that.stored_idnumber = actual_id;
                    console.log("keypress -> PROCESS ENTRY NOW PART B");
                    // $('#autocomplete').val(actual_id);
                    $auto_obj.val(actual_id);
                    return true;
                }
            });
            /*
            $("body").on('keydown', '#tcs_enter_student_form', function (event) {
                console.log("Have hit keydown key");
            });

            // $('#tcs_enter_student_form').keypress(function (e) {
            $("body").on('keypress', '#tcs_enter_student_form', function (event) {
                console.log("Have hit keypress key");
                // tcs_enter_student_form
                if (event.which == 13) {
                    // $('form#login').submit();
                    console.log("Have hit the enter key");
                    if (that.checkForm()) {
                        console.log("Form is good to go");
                    } else {
                        console.log("Form FAIL");
                    }
                    return false;    //<---- Add this line
                }
            });

            $("body").on('submit', 'data-action="save"', function (event) {
            */
        }
    };
});
