<?php
/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  University of Lethbridge Custom Tools                    **
 * @name        utools
 * @author      David Lowe                                               **
 * ************************************************************************
 * ********************************************************************** */

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/utools/lib/UserSelectorExisting.php');
require_once($CFG->dirroot . '/local/utools/lib/UserSelectorPotential.php');

class AdminForm extends moodleform
{

    private $current_admins, $potential_admins;
        // $ac = null;

    public function __construct()
    {

        // $this->ac = array("autocomplete" => "on");

        // parent::__construct(null, null, "post", '', $this->ac, true);

        // $attributes = array('autocomplete'=>'off');
        $this->current_admins = new UserSelectorExisting();
        $this->current_admins->set_extra_fields(array('username', 'email'));
        
        $this->potential_admins = new UserSelectorPotential();
        $this->potential_admins->set_extra_fields(array('username', 'email'));
        
        parent::__construct();
    }
  
    public function definition()
    {
        global $DB, $OUTPUT;
        $mform = & $this->_form;
        // $mform->setType('username', PARAM_NOTAGS);

        // Can add any extra checks here.......
        
        $mform->addElement('html', '<div id="addadminsform">');
        $mform->addElement('html', '<h3 class="main">Utools Administrators</h3>');
        $mform->addElement('html', '<div>');

        // $mform->addElement('hidden', 'dept', $this->dept);

        // <input type="text" name="addselect_searchtext" id="addselect_searchtext" size="15" value="test.load1">
        
        //We're going to format into 3 columns using a table. First column is
        //the current admins, second we will put the add/remove buttons
        //and in the last we will put all the users in the system.
        $mform->addElement('html', '<table class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">');
        $mform->addElement('html', '<tr>');

        $mform->addElement('html', '<td id="existingcell">');
        $mform->addElement('html', '<p><label for="remove_user">Current Administrators</label></p>');
        $mform->addElement('html', $this->current_admins->display(true));
        $mform->addElement('html', '</td>');

        $mform->addElement('html', '<td id="buttonscell">');
        $mform->addElement('html', '<p class="arrow_button">');
        $mform->addElement('html', '<input name="add" id="add" type="submit" value="'
            . $OUTPUT->larrow() . '&nbsp;' . get_string('add') . '" title="'
            . get_string('add') . '"/>' . '</br>');
        $mform->addElement('html', '<input name="remove" id="remove" type="submit" value="'
            . $OUTPUT->rarrow() . '&nbsp;' . get_string('remove') . '" title="'
            . get_string('remove') . '"/>');



        $mform->addElement('html', '</br>');

        $mform->addElement('html', '<div class="radio">');
            $mform->addElement('html', '<label>');
                $mform->addElement('html', '<input type="radio" name="optionsRadios" id="utools_admin_level_1" value="Administrator" checked>');
                $mform->addElement('html', 'Administrator');
            $mform->addElement('html', '</label>');
            $mform->addElement('html', '<label>');
                $mform->addElement('html', '<input type="radio" name="optionsRadios" id="utools_admin_level_2" value="Watcher">');
                $mform->addElement('html', 'Stack & Stats Watcher');
            $mform->addElement('html', '</label>');
            $mform->addElement('html', '<label>');
                $mform->addElement('html', '<input type="radio" name="optionsRadios" id="utools_admin_level_3" value="Instructor">');
                $mform->addElement('html', 'Instructor');
            $mform->addElement('html', '</label>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '</br>');
        
        $mform->addElement('html', '</p>');
        $mform->addElement('html', '</td>');

        $mform->addElement('html', '<td id="potentialcell">');
        $mform->addElement('html', '<p><label for="add_user">Users</label></p>');
        $mform->addElement('html', $this->potential_admins->display(true));
        $mform->addElement('html', '</td>');

        $mform->addElement('html', '</tr>');
        $mform->addElement('html', '</table>');
        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');
    
    }
}
