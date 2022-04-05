<?php

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
 * Form for editing HTML block instances.
 *
 * @package   moodlecore
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing Random glossary entry block instances.
 *
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



//require_once($CFG->libdir . '/datalib.php');




class block_landing_block_edit_form extends block_edit_form {
 
    
    function definition() {
        $mform =& $this->_form;

        // First show fields specific to this type of block.
        $this->specific_definition($mform);

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'whereheader', get_string('wherethisblockappears', 'block'));

        // If the current weight of the block is out-of-range, add that option in.
        $blockweight = $this->block->instance->weight;
        $weightoptions = array();
        if ($blockweight < -block_manager::MAX_WEIGHT) {
            $weightoptions[$blockweight] = $blockweight;
        }
        for ($i = -block_manager::MAX_WEIGHT; $i <= block_manager::MAX_WEIGHT; $i++) {
            $weightoptions[$i] = $i;
        }
        if ($blockweight > block_manager::MAX_WEIGHT) {
            $weightoptions[$blockweight] = $blockweight;
        }
        $first = reset($weightoptions);
        $weightoptions[$first] = get_string('bracketfirst', 'block', $first);
        $last = end($weightoptions);
        $weightoptions[$last] = get_string('bracketlast', 'block', $last);

        $regionoptions = $this->page->theme->get_all_block_regions();
        // $context = context_course::instance($course->id);
        $parentcontext = context::instance_by_id($this->block->instance->parentcontextid);
        $mform->addElement('hidden', 'bui_parentcontextid', $parentcontext->id);
        
        $mform->addElement('static', 'bui_homecontext', get_string('createdat', 'block'), $parentcontext->get_context_name());
        $mform->addHelpButton('bui_homecontext', 'createdat', 'block');

        // For pre-calculated (fixed) pagetype lists
        $pagetypelist = array();

        // parse pagetype patterns
        $bits = explode('-', $this->page->pagetype);

        // First of all, check if we are editing blocks @ front-page or no and
        // make some dark magic if so (MDL-30340) because each page context
        // implies one (and only one) harcoded page-type that will be set later
        // when processing the form data at {@link block_manager::process_url_edit()}

        // There are some conditions to check related to contexts
        $ctxconditions = $this->page->context->contextlevel == CONTEXT_COURSE &&
                         $this->page->context->instanceid == get_site()->id;
        // And also some pagetype conditions
        $pageconditions = isset($bits[0]) && isset($bits[1]) && $bits[0] == 'site' && $bits[1] == 'index';
        // So now we can be 100% sure if edition is happening at frontpage
        $editingatfrontpage = $ctxconditions && $pageconditions;

        // Let the form to know about that, can be useful later
        $mform->addElement('hidden', 'bui_editingatfrontpage', (int)$editingatfrontpage);

        // Front page, show the page-contexts element and set $pagetypelist to 'any page' (*)
        // as unique option. Processign the form will do any change if needed
        if ($editingatfrontpage) {
            $contextoptions = array();
            $contextoptions[BUI_CONTEXTS_FRONTPAGE_ONLY] = get_string('showonfrontpageonly', 'block');
            $contextoptions[BUI_CONTEXTS_FRONTPAGE_SUBS] = get_string('showonfrontpageandsubs', 'block');
            $contextoptions[BUI_CONTEXTS_ENTIRE_SITE]    = get_string('showonentiresite', 'block');
            $mform->addElement('select', 'bui_contexts', get_string('contexts', 'block'), $contextoptions);
            $mform->addHelpButton('bui_contexts', 'contexts', 'block');
            $pagetypelist['*'] = '*'; // This is not going to be shown ever, it's an unique option

        // Any other system context block, hide the page-contexts element,
        // it's always system-wide BUI_CONTEXTS_ENTIRE_SITE
        } elseif ($parentcontext->contextlevel == CONTEXT_SYSTEM) {
            $mform->addElement('hidden', 'bui_contexts', BUI_CONTEXTS_ENTIRE_SITE);

        } elseif ($parentcontext->contextlevel == CONTEXT_COURSE) {
            // 0 means display on current context only, not child contexts
            // but if course managers select mod-* as pagetype patterns, block system will overwrite this option
            // to 1 (display on current context and child contexts)
            $mform->addElement('hidden', 'bui_contexts', BUI_CONTEXTS_CURRENT);
        } elseif ($parentcontext->contextlevel == CONTEXT_MODULE or $parentcontext->contextlevel == CONTEXT_USER) {
            // module context doesn't have child contexts, so display in current context only
            $mform->addElement('hidden', 'bui_contexts', BUI_CONTEXTS_CURRENT);
        } else {
            $parentcontextname = $context->get_context_name();
            $contextoptions[BUI_CONTEXTS_CURRENT]      = get_string('showoncontextonly', 'block', $parentcontextname);
            $contextoptions[BUI_CONTEXTS_CURRENT_SUBS] = get_string('showoncontextandsubs', 'block', $parentcontextname);
            $mform->addElement('select', 'bui_contexts', get_string('contexts', 'block'), $contextoptions);
        }

        // Generate pagetype patterns by callbacks if necessary (has not been set specifically)
        if (empty($pagetypelist)) {
            $pagetypelist = generate_page_type_patterns($this->page->pagetype, $parentcontext, $this->page->context);
            $displaypagetypewarning = false;
            if (!array_key_exists($this->block->instance->pagetypepattern, $pagetypelist)) {
                // Pushing block's existing page type pattern
                $pagetypestringname = 'page-'.str_replace('*', 'x', $this->block->instance->pagetypepattern);
                if (get_string_manager()->string_exists($pagetypestringname, 'pagetype')) {
                    $pagetypelist[$this->block->instance->pagetypepattern] = get_string($pagetypestringname, 'pagetype');
                } else {
                    //as a last resort we could put the page type pattern in the select box
                    //however this causes mod-data-view to be added if the only option available is mod-data-*
                    // so we are just showing a warning to users about their prev setting being reset
                    $displaypagetypewarning = true;
                }
            }
        }

        // hide page type pattern select box if there is only one choice
        if (count($pagetypelist) > 1) {
            if ($displaypagetypewarning) {
                $mform->addElement('static', 'pagetypewarning', '', get_string('pagetypewarning', 'block'));
            }

            $mform->addElement('select', 'bui_pagetypepattern', get_string('restrictpagetypes', 'block'), $pagetypelist);
        } else {
            $values = array_keys($pagetypelist);
            $value = array_pop($values);
            $mform->addElement('hidden', 'bui_pagetypepattern', $value);
            // Now we are really hiding a lot (both page-contexts and page-type-patterns),
            // specially in some systemcontext pages having only one option (my/user...)
            // so, until it's decided if we are going to add the 'bring-back' pattern to
            // all those pages or no (see MDL-30574), we are going to show the unique
            // element statically
            // TODO: Revisit this once MDL-30574 has been decided and implemented, although
            // perhaps it's not bad to always show this statically when only one pattern is
            // available.
            if (!$editingatfrontpage) {
                // Try to beautify it
                $strvalue = $value;
                $strkey = 'page-'.str_replace('*', 'x', $strvalue);
                if (get_string_manager()->string_exists($strkey, 'pagetype')) {
                    $strvalue = get_string($strkey, 'pagetype');
                }
                // Show as static (hidden has been set already)
                $mform->addElement(
                    'static',
                    'bui_staticpagetypepattern',
                    get_string('restrictpagetypes', 'block'),
                    $strvalue
                );
            }
        }

        if ($this->page->subpage) {
            if ($parentcontext->contextlevel == CONTEXT_USER) {
                $mform->addElement('hidden', 'bui_subpagepattern', '%@NULL@%');
            } else {
                $subpageoptions = array(
                    '%@NULL@%' => get_string('anypagematchingtheabove', 'block'),
                    $this->page->subpage => get_string('thisspecificpage', 'block', $this->page->subpage),
                );
                $mform->addElement('select', 'bui_subpagepattern', get_string('subpages', 'block'), $subpageoptions);
            }
        }

        $defaultregionoptions = $regionoptions;
        $defaultregion = $this->block->instance->defaultregion;
        if (!array_key_exists($defaultregion, $defaultregionoptions)) {
            $defaultregionoptions[$defaultregion] = $defaultregion;
        }
        $mform->addElement('select', 'bui_defaultregion', get_string('defaultregion', 'block'), $defaultregionoptions);
        $mform->addHelpButton('bui_defaultregion', 'defaultregion', 'block');

        $mform->addElement('select', 'bui_defaultweight', get_string('defaultweight', 'block'), $weightoptions);
        $mform->addHelpButton('bui_defaultweight', 'defaultweight', 'block');

        // Where this block is positioned on this page.
        $mform->addElement('header', 'onthispage', get_string('onthispage', 'block'));
        
        //Check if the user is admin or not, if admin show the visibility item in edit form, otherwise not.
        
        global $USER;
        
        $admins = get_admins();
        $isadmin = false;
        foreach ($admins as $admin) {
            if ($USER->id == $admin->id) {
                $isadmin = true;
                break;
            }
        }
        if ($isadmin) {
            
            $mform->addElement('selectyesno', 'bui_visible', get_string('visible', 'block'));
        // Show all
        } else {
            $mform->addElement('hidden', 'bui_visible', get_string('visible', 'block'));
            
        }
 
        $blockregion = $this->block->instance->region;
        if (!array_key_exists($blockregion, $regionoptions)) {
            $regionoptions[$blockregion] = $blockregion;
        }
        $mform->addElement('select', 'bui_region', get_string('region', 'block'), $regionoptions);

        $mform->addElement('select', 'bui_weight', get_string('weight', 'block'), $weightoptions);

        $pagefields = array('bui_visible', 'bui_region', 'bui_weight');
        if (!$this->block->user_can_edit()) {
            $mform->hardFreezeAllVisibleExcept($pagefields);
        }
        if (!$this->page->user_can_edit_blocks()) {
            $mform->hardFreeze($pagefields);
        }

        $this->add_action_buttons();
    }
}
