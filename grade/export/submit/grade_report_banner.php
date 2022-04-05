<?php
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->dirroot . '/grade/report/grader/lib.php');
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class providing an API for the grader report building and displaying.
 * @uses grade_report
 * @package gradebook
 */

class grade_report_banner extends grade_report_grader
{

    public function __construct($courseid, $gpr, $context, $page = null, $sortitemid = null)
    {
        global $CFG, $FinalGradeColumnName, $isEditing, $gradesMissing, $USER;
    
        $FinalGradeColumnName = $CFG->gradeexport_final_grades_column;//'[Final Grades]'; //This is the column name for final grades
        if (property_exists($USER, 'gradeediting') && isset($USER->gradeediting)) {
            $isEditing = false;
            if (isset($USER->gradeediting[$this->courseid])) {
                $isEditing = (bool)$USER->gradeediting[$this->courseid];
            }
            if (isset($USER->gradeediting[$courseid])) {
                $isEditing = (bool)$USER->gradeediting[$courseid];
            }
        } else {
            // error_log("\n\n");
            // error_log("\n---------------->>>> GRADE EDITING IS NOT SET <<<<------------------\n");
            $isEditing = false;
        }

        parent::__construct($courseid, $gpr, $context, $page);

        //Process the grade tree and remove any column that isn't our banner final grade column.
        $removeItems=array();

        foreach ($this->gtree->items as $gradeItem) {
            if ($gradeItem->itemname ==  $FinalGradeColumnName) {    //This the hardcoded column name!
            // echo "Found the banner column!";
            // print_r($gradeItem);
            } else {
                $removeItems[]=$gradeItem->id;
            }
        }

        foreach ($removeItems as $removeId) {
            unset($this->gtree->items[$removeId]);
        }

        foreach (array_keys($this->gtree->{'top_element'}['children']) as $key) {
            $row=$this->gtree->{'top_element'}['children'][$key];
            if (isset($row['object']->id)) {
                if (in_array($row['object']->id, $removeItems)) {
                    unset($this->gtree->{'top_element'}['children'][$key]);
                }
            }
        }

        foreach (array_keys($this->gtree->levels[1]) as $key) {
            $row=$this->gtree->levels[1][$key];
            if (isset($row['object']->id)) {
                if (in_array($row['object']->id, $removeItems)) {
                    unset($this->gtree->levels[1][$key]);
                }
            }
        }
    }
    
    public function getFinalGradeColumnName()
    {
        global $FinalGradeColumnName;
        return $FinalGradeColumnName;
    }
    
    public function hasFinalGrades()
    {
        global $CFG, $FinalGradeColumnName;

        //Process the grade tree and remove any column that isn't our banner final grade column.
        $found=0;
        foreach ($this->gtree->items as $gradeItem) {
            //This the hardcoded column name!
            if ($gradeItem->itemname == $FinalGradeColumnName) {
                $found = 1;
                // echo "Found the banner column!";
                // print_r($gradeItem);
            }
        }
        return $found;
    }
    
    //kill some of the links
    protected function get_collapsing_icon($element)
    {
        return '';
    }
    
    //kill some of the links
    public function get_sort_arrows(array $extrafields = array())
    {
        global $OUTPUT;
        $arrows = array();
        
        $strsortasc   = $this->get_lang_string('sortasc', 'grades');
        $strsortdesc  = $this->get_lang_string('sortdesc', 'grades');
        $strfirstname = $this->get_lang_string('firstname');
        $strlastname  = $this->get_lang_string('lastname');

        // $firstlink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid'=>'firstname')), $strfirstname);
        $firstlink = $strfirstname;
        // $lastlink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid'=>'lastname')), $strlastname);
        $lastlink = $strlastname;
        
        $idnumberlink = html_writer::link(new moodle_url($this->baseurl, array('sortitemid'=>'idnumber')), get_string('idnumber'));

        $arrows['studentname'] = '';
        /*
        if ($this->sortitemid === 'lastname') {
            if ($this->sortorder == 'ASC') {
                $arrows['studentname'] .= print_arrow('up', $strsortasc, true);
            } else {
                $arrows['studentname'] .= print_arrow('down', $strsortdesc, true);
            }
        }
        */
        $arrows['studentname'] .= ' ' . $firstlink;

        if ($this->sortitemid === 'firstname') {
            if ($this->sortorder == 'ASC') {
                $arrows['studentname'] .= '';//print_arrow('up', $strsortasc, true);
            } else {
                $arrows['studentname'] .= '';//print_arrow('down', $strsortdesc, true);
            }
        }

        $arrows['studentname'] .= ' '  .$lastlink;


        $arrows['idnumber'] = $idnumberlink;

        if ('idnumber' == $this->sortitemid) {
            if ($this->sortorder == 'ASC') {
                $arrows['idnumber'] .= '';//print_arrow('up', $strsortasc, true);
            } else {
                $arrows['idnumber'] .= '';//print_arrow('down', $strsortdesc, true);
            }
        }

        return $arrows;
    }

    /*

    public function load_final_grades() {
        global $CFG, $DB;

        // please note that we must fetch all grade_grades fields if we want to construct grade_grade object from it!
        $params = array_merge(array('courseid'=>$this->courseid), $this->userselect_params);
        $sql = "SELECT g.*
                  FROM {grade_items} gi,
                       {grade_grades} g
                 WHERE g.itemid = gi.id 
                 AND gi.itemname = 'Banner Final Grades'
                 AND gi.courseid = :courseid {$this->userselect}";

        $userids = array_keys($this->users);


        if ($grades = $DB->get_records_sql($sql, $params)) {
            foreach ($grades as $graderec) {
                if (in_array($graderec->userid, $userids) and array_key_exists($graderec->itemid, $this->gtree->get_items())) { // some items may not be present!!
                    $this->grades[$graderec->userid][$graderec->itemid] = new grade_grade($graderec, false);
                    $this->grades[$graderec->userid][$graderec->itemid]->grade_item =& $this->gtree->get_item($graderec->itemid); // db caching
                }
            }
        }

        // prefil grades that do not exist yet
        foreach ($userids as $userid) {
            foreach ($this->gtree->get_items() as $itemid=>$unused) {
                if (!isset($this->grades[$userid][$itemid])) {
                    $this->grades[$userid][$itemid] = new grade_grade();
                    $this->grades[$userid][$itemid]->itemid = $itemid;
                    $this->grades[$userid][$itemid]->userid = $userid;
                    $this->grades[$userid][$itemid]->grade_item =& $this->gtree->get_item($itemid); // db caching
                }
            }
        }
    }
    */


    /*
    
    
    Ovverride the code that prints out the actual grades. 
        -added decimal grade in brackets after the letter grade.
        -highlights missing grades
        -...anything else we want to customize? ?
    */

    public function get_right_rows($displayaverages = false) {
        global $CFG, $USER, $OUTPUT, $DB, $PAGE, $isEditing, $gradesMissing;

        $gradesMissing = 0;
        $rows = array();
        $this->rowcount = 0;
        $numrows = count($this->gtree->get_levels());
        $numusers = count($this->users);
        $gradetabindex = 1;
        $columnstounset = array();
        $strgrade = $this->get_lang_string('grade');
        $strfeedback  = $this->get_lang_string("feedback");
        $arrows = $this->get_sort_arrows();

        // error_log("\n");
        // error_log("\ngrade_report_banner -> get_right_rows() -> course id??: ". $this->course->id);
        // error_log("\ngrade_report_banner -> get_right_rows() -> course obj: ". print_r($this->course, 1));

        // get the Final Grades row id
        $finalgrades_result = $DB->get_record("grade_items", array("itemname" => '[Final Grades]', 'courseid' => $this->course->id));
        // error_log("\ngrade_report_banner -> get_right_rows() -> What is finalgrades_result obj: ". print_r($finalgrades_result, 1));
        $finalgrades_id = $finalgrades_result->id;

        $jsarguments = array(
            // 'id'        => '#fixed_column',
            'cfg'       => array('ajaxenabled'=>false),
            'items'     => array(),
            'users'     => array(),
            'feedback'  => array()
        );
        $jsscales = array();

        // Get preferences once.
        $showactivityicons = $this->get_pref('showactivityicons');
        $quickgrading = $this->get_pref('quickgrading');
        $showquickfeedback = $this->get_pref('showquickfeedback');
        $enableajax = $this->get_pref('enableajax');
        $showanalysisicon = $this->get_pref('showanalysisicon');

        // Get strings which are re-used inside the loop.
        $strftimedatetimeshort = get_string('strftimedatetimeshort');
        $strexcludedgrades = get_string('excluded', 'grades');
        $strerror = get_string('error');

        foreach ($this->gtree->get_levels() as $key => $row) {
            if ($key == 0) {
                // do not display course grade category
                // continue;
            }

            $headingrow = new html_table_row();
            $headingrow->attributes['class'] = 'heading_name_row';

            foreach ($row as $columnkey => $element) {
                $sortlink = clone($this->baseurl);
                if (isset($element['object']->id)) {
                    $sortlink->param('sortitemid', $element['object']->id);
                }

                $eid    = $element['eid'];
                $object = $element['object'];
                $type   = $element['type'];
                $categorystate = @$element['categorystate'];

                if (!empty($element['colspan'])) {
                    $colspan = $element['colspan'];
                } else {
                    $colspan = 1;
                }

                if (!empty($element['depth'])) {
                    $catlevel = 'catlevel'.$element['depth'];
                } else {
                    $catlevel = '';
                }

                if ($type == 'filler' or $type == 'fillerfirst' or $type == 'fillerlast') {
                // Element is a filler
                    $fillercell = new html_table_cell();
                    $fillercell->attributes['class'] = $type . ' ' . $catlevel;
                    $fillercell->colspan = $colspan;
                    $fillercell->text = '&nbsp;';
                    $fillercell->header = true;
                    $fillercell->scope = 'col';
                    $headingrow->cells[] = $fillercell;
                
                } elseif ($type == 'category') {
                // Element is a category
                    $categorycell = new html_table_cell();
                    $categorycell->attributes['class'] = 'category ' . $catlevel;
                    $categorycell->colspan = $colspan;
                    $categorycell->text = shorten_text($element['object']->get_name());
                    $categorycell->text .= $this->get_collapsing_icon($element);
                    $categorycell->header = true;
                    $categorycell->scope = 'col';

                    // Print icons
                    if ($isEditing) {
                        $categorycell->text .= $this->get_icons($element);
                    }

                    $headingrow->cells[] = $categorycell;
                
                } else {
                // Element is a grade_item
               
                    //$itemmodule = $element['object']->itemmodule;
                    //$iteminstance = $element['object']->iteminstance;

                    if ($element['object']->id == $this->sortitemid) {
                        if ($this->sortorder == 'ASC') {
                            $arrow = $this->get_sort_arrow('up', $sortlink);
                        } else {
                            $arrow = $this->get_sort_arrow('down', $sortlink);
                        }
                    } else {
                        $arrow = $this->get_sort_arrow('move', $sortlink);
                    }

                    $headerlink = $this->gtree->get_element_header($element, true, $showactivityicons, false, true);

                    $itemcell = new html_table_cell();
                    $itemcell->attributes['class'] = $type . ' ' . $catlevel . 'highlightable';
                    $itemcell->attributes['data-itemid'] = $element['object']->id;
                    if ($element['object']->is_hidden()) {
                        $itemcell->attributes['class'] .= ' hidden';
                    }

                    $singleview = '';
                    if (has_capability('gradereport/singleview:view', $this->context)) {
                        $url = new moodle_url('/grade/report/singleview/index.php', array(
                            'id' => $this->course->id,
                            'item' => 'grade',
                            'itemid' => $element['object']->id
                        ));
                        $singleview = $OUTPUT->action_icon(
                            $url,
                            new pix_icon('t/editstring', get_string('singleview', 'grades', $element['object']->get_name()))
                        );
                    }

                    $itemcell->colspan = $colspan;
                    $itemcell->text = shorten_text($headerlink) . $arrow . $singleview;
                    $itemcell->header = true;
                    $itemcell->scope = 'col';

                    $headingrow->cells[] = $itemcell;
                }
            }

            // if (count($headingrow->cells) 
            // loop 1 has HRM
            // if ($headingrow->cells[0]->text == "HRM") {
                // continue;
            // }
            // loop 2 has category titles
            $header_counter = 0;
            foreach($headingrow->cells as $chop) {
                if (isset($chop->attributes['data-itemid'])) {

                    if ($chop->attributes['data-itemid'] != $finalgrades_id) {
                        unset($headingrow->cells[$header_counter]);
                    }
                } else {
                    unset($headingrow->cells[$header_counter]);
                }
                $header_counter++;
            }
            // loop 3 has quiz titles
            // if(count($headingrow->cells) > 0){
                $rows[] = $headingrow;
            // }
        }

        $rows = $this->get_right_icons_row($rows);

        // Preload scale objects for items with a scaleid
        $scaleslist = array();
        $tabindices = array();

        foreach ($this->gtree->get_items() as $itemid => $item) {
            $scale = null;
            if (!empty($item->scaleid)) {
                $scaleslist[] = $item->scaleid;
                $jsarguments['items'][$itemid] = array('id'=>$itemid, 'name'=>$item->get_name(true), 'type'=>'scale', 'scale'=>$item->scaleid, 'decimals'=>$item->get_decimals());
            } else {
                $jsarguments['items'][$itemid] = array('id'=>$itemid, 'name'=>$item->get_name(true), 'type'=>'value', 'scale'=>false, 'decimals'=>$item->get_decimals());
            }
            $tabindices[$item->id]['grade'] = $gradetabindex;
            $tabindices[$item->id]['feedback'] = $gradetabindex + $numusers;
            $gradetabindex += $numusers * 2;
        }
        $scalesarray = array();

        if (!empty($scaleslist)) {
            $scalesarray = $DB->get_records_list('scale', 'id', $scaleslist);
        }
        $jsscales = $scalesarray;

        $rowclasses = array('even', 'odd');

        foreach ($this->users as $userid => $user) {
            if ($this->canviewhidden) {
                $altered = array();
                $unknown = array();
            } else {
                $hidingaffected = grade_grade::get_hiding_affected($this->grades[$userid], $this->gtree->get_items());
                $altered = $hidingaffected['altered'];
                $unknown = $hidingaffected['unknown'];
                unset($hidingaffected);
            }


            $itemrow = new html_table_row();
            $itemrow->id = 'user_'.$userid;
            $itemrow->attributes['class'] = $rowclasses[$this->rowcount % 2];

            // $jsarguments['users'][$userid] = fullname($user);
            $fullname = fullname($user);
            $jsarguments['users'][$userid] = $fullname;

            foreach ($this->gtree->items as $itemid => $unused) {
                $item =& $this->gtree->items[$itemid];
                $grade = $this->grades[$userid][$item->id];

                $itemcell = new html_table_cell();

                $itemcell->id = 'u'.$userid.'i'.$itemid;
                $itemcell->attributes['data-itemid'] = $itemid;

                // Get the decimal points preference for this item
                $decimalpoints = $item->get_decimals();

                if (in_array($itemid, $unknown)) {
                    $gradeval = null;
                } elseif (array_key_exists($itemid, $altered)) {
                    $gradeval = $altered[$itemid];
                } elseif ($grade->grade_item->itemname == "[Final Grades]") {

                    if (isset($grade->rawgrade)) {
                        $gradeval = $grade->rawgrade;
                    } else {
                        $gradeval = $grade->finalgrade;
                    }
                }

                // MDL-11274
                // Hide grades in the grader report if the current grader doesn't have 'moodle/grade:viewhidden'
                if (!$this->canviewhidden and $grade->is_hidden()) {
                    if (!empty($CFG->grade_hiddenasdate) and $grade->get_datesubmitted() and !$item->is_category_item() and !$item->is_course_item()) {
                        // the problem here is that we do not have the time when grade value was modified, 'timemodified' is general modification date for grade_grades records
                        // $itemcell->text = html_writer::tag('span', userdate($grade->get_datesubmitted(), get_string('strftimedatetimeshort')), array('class'=>'datesubmitted'));
                        $itemcell->text = "<span class='datesubmitted'>" . userdate($grade->get_datesubmitted(), $strftimedatetimeshort) . "</span>";
                    } else {
                        $itemcell->text = '-';
                    }
                    $itemrow->cells[] = $itemcell;
                    continue;
                }

                // emulate grade element
                $eid = $this->gtree->get_grade_eid($grade);
                $element = array('eid'=>$eid, 'object'=>$grade, 'type'=>'grade');

                $itemcell->attributes['class'] .= ' grade';
                if ($item->is_category_item()) {
                    $itemcell->attributes['class'] .= ' cat';
                }
                if ($item->is_course_item()) {
                    $itemcell->attributes['class'] .= ' course';
                }
                if ($grade->is_overridden()) {
                    $itemcell->attributes['class'] .= ' overridden';
                    $itemcell->attributes['aria-label'] = get_string('overriddengrade', 'gradereport_grader');
                }

                if ($grade->is_excluded()) {
                    // $itemcell->attributes['class'] .= ' excluded';
                    // Adding white spaces before and after to prevent a screenreader from
                    // thinking that the words are attached to the next/previous <span> or text.
                    // $itemcell->text .= " <span class='excludedfloater'>" . $strexcludedgrades . "</span> ";
                }

                if (!empty($grade->feedback)) {
                    //should we be truncating feedback? ie $short_feedback = shorten_text($feedback, $this->feedback_trunc_length);
                    // $jsarguments['feedback'][] = array('user'=>$userid, 'item'=>$itemid, 'content'=>wordwrap(trim(format_string($grade->feedback, $grade->feedbackformat)), 34, '<br/ >'));
                    $feedback = wordwrap(trim(format_string($grade->feedback, $grade->feedbackformat)), 34, '<br>');
                    $itemcell->attributes['data-feedback'] = $feedback;
                }

                if ($grade->is_excluded()) {
                    $itemcell->text .= html_writer::tag('span', get_string('excluded', 'grades'), array('class'=>'excludedfloater'));
                }

                // Do not show any icons if no grade (no record in DB to match)
                if (!$item->needsupdate and $isEditing) {
                    $itemcell->text .= $this->get_icons($element);
                }

                $hidden = '';
                if ($grade->is_hidden()) {
                    $hidden = ' hidden ';
                }

                $gradepass = ' gradefail ';
                if ($grade->is_passed($item)) {
                    $gradepass = ' gradepass ';
                } elseif (is_null($grade->is_passed($item))) {
                    $gradepass = '';
                }

                // if in editing mode, we need to print either a text box
                // or a drop down (for scales)
                // grades in item of type grade category or course are not directly editable
                if ($item->needsupdate) {
                    // $itemcell->text .= html_writer::tag('span', get_string('error'), array('class'=>"gradingerror$hidden"));
                    $itemcell->text .= "<span class='gradingerror{$hidden}'>" . $strerror . "</span>";

                } elseif ($isEditing) {

                    if ($item->scaleid && !empty($scalesarray[$item->scaleid])) {
                        $scale = $scalesarray[$item->scaleid];
                        $gradeval = (int)$gradeval; // scales use only integers
                        $scales = explode(",", $scale->scale);
                        // reindex because scale is off 1

                        // MDL-12104 some previous scales might have taken up part of the array
                        // so this needs to be reset
                        $scaleopt = array();
                        $i = 0;
                        foreach ($scales as $scaleoption) {
                            $i++;
                            $scaleopt[$i] = $scaleoption;
                        }

                        // if ($this->get_pref('quickgrading') and $grade->is_editable()) {
                        if ($quickgrading and $grade->is_editable()) {
                            $oldval = empty($gradeval) ? -1 : $gradeval;
                            if (empty($item->outcomeid)) {
                                $nogradestr = $this->get_lang_string('nograde');
                            } else {
                                $nogradestr = $this->get_lang_string('nooutcome', 'grades');
                            }
                            $itemcell->text .= '<input type="hidden" id="oldgrade_'.$userid.'_'.$item->id.'" name="oldgrade_'.$userid.'_'.$item->id.'" value="'.$oldval.'"/>';
                            $attributes = array('tabindex' => $tabindices[$item->id]['grade'], 'id'=>'grade_'.$userid.'_'.$item->id);
                            $itemcell->text .= html_writer::select($scaleopt, 'grade_'.$userid.'_'.$item->id, $gradeval, array(-1=>$nogradestr), $attributes);
                        } elseif (!empty($scale)) {
                            $scales = explode(",", $scale->scale);

                            // invalid grade if gradeval < 1
                            if ($gradeval < 1) {
                                $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>-</span>";
                            } else {
                                $gradeval = $grade->grade_item->bounded_grade($gradeval); //just in case somebody changes scale
                                $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>{$scales[$gradeval - 1]}</span>";
                            }
                        } else {
                            // no such scale, throw error?
                        }

                    } elseif ($item->gradetype != GRADE_TYPE_TEXT) { // Value type
                        // if ($this->get_pref('quickgrading') and $grade->is_editable()) {
                        if ($quickgrading and $grade->is_editable()) {
                            $value = format_float($gradeval, $decimalpoints);
                            $itemcell->text .= '<input type="hidden" id="oldgrade_'.$userid.'_'.$item->id.'" name="oldgrade_'.$userid.'_'.$item->id.'" value="'.$value.'" />';
                            $itemcell->text .= '<input size="6" tabindex="' . $tabindices[$item->id]['grade']
                                          . '" type="text" class="text" title="'. $strgrade .'" name="grade_'
                                          .$userid.'_' .$item->id.'" id="grade_'.$userid.'_'.$item->id.'" value="'.$value.'" />';
                        } else {
                            // $itemcell->text .= html_writer::tag('span', format_float($gradeval, $decimalpoints), array('class'=>"gradevalue$hidden$gradepass"));
                            $itemcell->text .= "<span class='gradevalue{$hidden}{$gradepass}'>" .
                                format_float($gradeval, $decimalpoints) . "</span>";
                        }
                    }


                    // If quickfeedback is on, print an input element
                    if ($this->get_pref('showquickfeedback') and $grade->is_editable()) {
                        $itemcell->text .= '<input type="hidden" id="oldfeedback_'.$userid.'_'.$item->id.'" name="oldfeedback_'.$userid.'_'.$item->id.'" value="' . s($grade->feedback) . '" />';
                        $itemcell->text .= '<input class="quickfeedback" tabindex="' . $tabindices[$item->id]['feedback'].'" id="feedback_'.$userid.'_'.$item->id
                                      . '" size="6" title="' . $strfeedback . '" type="text" name="feedback_'.$userid.'_'.$item->id.'" value="' . s($grade->feedback) . '" />';
                    }

                } else { // Not editing
                    // UofL: Force it to be LETTER
                    $gradedisplaytype = GRADE_DISPLAY_TYPE_LETTER; //$item->get_displaytype();
                    if ($item->scaleid && !empty($scalesarray[$item->scaleid])) {
                        $itemcell->attributes['class'] .= ' grade_type_scale';
                    } elseif ($item->gradetype != GRADE_TYPE_TEXT) {
                        $itemcell->attributes['class'] .= ' grade_type_text';
                    }

                    // if ($this->get_pref('enableajax')) {
                    if ($enableajax) {
                        $canoverride = true;
                        if ($item->is_category_item() || $item->is_course_item()) {
                            $canoverride = (bool) get_config('moodle', 'grade_overridecat');
                        }
                        if ($canoverride) {
                            $itemcell->attributes['class'] .= ' clickable';
                        }
                    }

                    if ($item->needsupdate) {
                        // $itemcell->text .= html_writer::tag('span', get_string('error'), array('class'=>"gradingerror$hidden$gradepass"));
                        $itemcell->text .= "<span class='gradingerror{$hidden}{$gradepass}'>" . $error . "</span>";
                    } else {
                    // UofL - ADD PERCENTAGE...
                        $highlight = '';
                        $percentageGrade = " (".grade_format_gradevalue_percentage($gradeval, $item, $item->get_decimals(), null).")";
                        // $letterGrade = grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null);
                        $letterGrade = grade_format_gradevalue_letter($gradeval, $item);

                        if ($grade->finalgrade === null) {
                            $highlight = " style='background:red; color:white' ";
                            $letterGrade = "NOT ASSIGNED";
                            $percentageGrade = "";
                            $gradesMissing = 1;
                        }
    
                        $itemcell->text .= html_writer::tag('span', '<span '.$highlight.'>'.$letterGrade.$percentageGrade."</span>", array('class'=>"gradevalue$hidden$gradepass"));
                    }
                }

                if (!empty($this->gradeserror[$item->id][$userid])) {
                    $itemcell->text .= $this->gradeserror[$item->id][$userid];
                }

                $itemrow->cells[] = $itemcell;
            }
            $rows[] = $itemrow;
        }

        if ($enableajax) {
            $jsarguments['cfg']['ajaxenabled'] = true;
            $jsarguments['cfg']['scales'] = array();
            foreach ($jsscales as $scale) {
                $jsarguments['cfg']['scales'][$scale->id] = explode(',', $scale->scale);
            }
            $jsarguments['cfg']['feedbacktrunclength'] =  $this->feedback_trunc_length;

            //feedbacks are now being stored in $jsarguments['feedback'] in get_right_rows()
            //$jsarguments['cfg']['feedback'] =  $this->feedbacks;
        }
        $jsarguments['cfg']['isediting'] = $isEditing;
        $jsarguments['cfg']['courseid'] =  $this->courseid;
        $jsarguments['cfg']['studentsperpage'] =  $this->get_pref('studentsperpage');
        $jsarguments['cfg']['showquickfeedback'] =  (bool)$showquickfeedback;

        $module = array(
            'name'      => 'gradereport_grader',
            'fullpath'  => '/grade/report/grader/module.js',
            'requires'  => array('base', 'dom', 'event', 'event-mouseenter', 'event-key', 'io', 'json-parse', 'overlay')
        );
        $PAGE->requires->js_init_call('M.gradereport_grader.init_report', $jsarguments, false, $module);
        $PAGE->requires->strings_for_js(array('addfeedback','feedback', 'grade'), 'grades');
        $PAGE->requires->strings_for_js(array('ajaxchoosescale','ajaxclicktoclose','ajaxerror','ajaxfailedupdate', 'ajaxfieldchanged'), 'gradereport_grader');

        $rows = $this->get_right_range_row($rows);
        $rows = $this->get_right_avg_row($rows, true);
        $rows = $this->get_right_avg_row($rows);

        return $rows;
    }

    function hasMissingGrades()
    {
        global $gradesMissing;
        return $gradesMissing;
    }

    public function load_users()
    {
        global $CFG, $DB;

        //limit to users with a gradeable role
        list($gradebookrolessql, $gradebookrolesparams) = $DB->get_in_or_equal(explode(',', $this->gradebookroles), SQL_PARAMS_NAMED, 'grbr0');

        //limit to users with an active enrollment
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($this->context);

        //fields we need from the user table
        $userfields = user_picture::fields('u', get_extra_user_fields($this->context));
        $userfields.=",u.idnumber";

        $sortjoin = $sort = $params = null;

        //if the user has clicked one of the sort asc/desc arrows
        if (is_numeric($this->sortitemid)) {
            $params = array_merge(array('gitemid'=>$this->sortitemid), $gradebookrolesparams, $this->groupwheresql_params, $enrolledparams);

            $sortjoin = "LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = $this->sortitemid";
            $sort = "g.finalgrade $this->sortorder";

        } else {
            $sortjoin = '';
            switch($this->sortitemid) {
                case 'lastname':
                    $sort = "u.lastname $this->sortorder, u.firstname $this->sortorder";
                    break;
                case 'firstname':
                    $sort = "u.firstname $this->sortorder, u.lastname $this->sortorder";
                    break;
                case 'email':
                    $sort = "u.email $this->sortorder";
                    break;
                case 'idnumber':
                default:
                    $sort = "u.idnumber $this->sortorder";
                    break;
            }

            $params = array_merge($gradebookrolesparams, $this->groupwheresql_params, $enrolledparams);
        }

        $cxid = context_course::instance($this->courseid);
        $sql = "SELECT $userfields
                  FROM {user} u
                  JOIN ($enrolledsql) je ON je.id = u.id
                       $this->groupsql
                       $sortjoin
                  JOIN (
                           SELECT DISTINCT ra.userid
                             FROM {role_assignments} ra
                            WHERE ra.roleid IN ($this->gradebookroles)
                              AND ra.contextid = " . $cxid->id . " 
                       ) rainner ON rainner.userid = u.id
                   AND u.deleted = 0
                   $this->groupwheresql
              ORDER BY $sort";


        $this->users = $DB->get_records_sql($sql, $params, $this->get_pref('studentsperpage') * $this->page, $this->get_pref('studentsperpage'));

        if (empty($this->users)) {
            $this->userselect = '';
            $this->users = array();
            $this->userselect_params = array();
        } else {
            list($usql, $uparams) = $DB->get_in_or_equal(array_keys($this->users), SQL_PARAMS_NAMED, 'usid0');
            $this->userselect = "AND g.userid $usql";
            $this->userselect_params = $uparams;

            //add a flag to each user indicating whether their enrolment is active
            $sql = "SELECT ue.userid
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE ue.userid $usql
                           AND ue.status = :uestatus
                           AND e.status = :estatus
                           AND e.courseid = :courseid
                  GROUP BY ue.userid";
            
            $coursecontext = context_course::instance($this->courseid);
            // $coursecontext = get_course_context($this->context);

            $params = array_merge($uparams, array('estatus'=>ENROL_INSTANCE_ENABLED, 'uestatus'=>ENROL_USER_ACTIVE, 'courseid'=>$coursecontext->instanceid));
            $useractiveenrolments = $DB->get_records_sql($sql, $params);

            foreach ($this->users as $user) {
                $this->users[$user->id]->suspendedenrolment = !array_key_exists($user->id, $useractiveenrolments);
            }
        }

        return $this->users;
    }
}
