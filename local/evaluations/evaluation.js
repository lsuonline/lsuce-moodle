/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */

/**
 * javascript that runs on the teachers evaluation setup page. Removes the delete/up/down
 * buttons and locks the types of questions so we will be able to compare them later on 
 * without running into errors.
*/
var jNum = parseInt(document.getElementById('num_default_q').value);

if(jNum != -1){
    var dropdowns = document.getElementsByClassName('fselect');
    
    //If selects are added before the questions then adjust selB4Questions below. (Course and Email Reminders)
    var selB4Questions = 1;
    
    //Disable each standard question so they can't be changed. (This is just for show. No matter how they are changes they will
    //still stay the same as the standard questions when loaded into the database.
    for(var iNum = selB4Questions; iNum < jNum + selB4Questions; iNum++){
        //console.log("What is iNum: "+iNum+" and selB4Questions: "+selB4Questions+" and the value going in: "+(iNum - selB4Questions));
        var isstandard = document.getElementsByName('question_std[' + (iNum - selB4Questions) + ']')[0];

        if(isstandard.getAttribute('value') == 1) {
            var newText = document.createTextNode(" This is a default question");
            var newSPAN = document.createElement("SPAN");
            newSPAN.appendChild(newText);
	    
            dropdowns[iNum].firstChild.disabled = true;
            dropdowns[iNum].appendChild(newSPAN);
        }
	   
    }
}

//Remove the up/down delete buttons.
var questionControl = document.getElementsByClassName('question_controls');

var leng = questionControl.length;

var todel = [];

for(var i = 0; i < leng; i++) {
    todel.push(questionControl[i]);
    
}

for(var i = 0; i < leng; i++){
    todel[i].parentNode.removeChild(todel[i]);
}


// console.log("What the truck is val in option add fields: "+$('#id_option_add_fields').val());

// var currentForm;
// $(function() {
//     $("#dialog-confirm").dialog({
//         resizable: false,
//         height: 140,
//         modal: true,
//         autoOpen: false,
//         buttons: {
//             'Add new row without saving?': function() {
//                 $(this).dialog('close');
//                 currentForm.submit();
//             },
//             'Cancel': function() {
//                 $(this).dialog('close');
//             }
//         }
//     });
//     $("#id_option_add_fields").click(function() {
//       currentForm = $(this).closest('form');
//       $("#dialog-confirm").dialog('open');
//       return false;
//     });
// });



