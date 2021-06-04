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
 * English strings for biosigid
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage biosigid
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['already_verified'] = 'Your identity has already been verified, thank you.';
$string['biosigid'] = 'Biometric Signature ID';
$string['biosigid:addinstance'] = 'Add BioSig-ID™ Instance';
$string['biosigid:privacy:grading'] = 'This plugin uses the grading subsystem to add grades for a user.';
$string['biosigiddomain'] = 'BioSight Domain';
$string['biosigiddomain_desc'] = 'Domain for BioSight-ID™ feature, staging-sandbox.verifyexpress.com used if nothing is configured';
$string['biosigidfieldset'] = 'Custom example fieldset';
$string['biosigidname'] = 'Name';
$string['biosigidname_help'] = 'Name of content item';
$string['bsi_error'] = 'Sorry an error occurred: "<strong><em>%s</em></strong>".  If you are unable to resolve this issue please contact your support.';
$string['configcustom1'] = 'Custom Parameter 1';
$string['configcustomerid'] = 'Unique identifier assigned to the customer of the primary system';
$string['configencdefaults'] = 'Enter values used to generate the encryption key applied by the Rijndael symmetric key algorithm.';
$string['configlocale'] = 'Default locale code specifying the language and locale';
$string['configpassphrase'] = 'Pass phrase to be used to generate the encryption key (8-36 ASCII characters)';
$string['configsalt'] = 'Salt value to be used to generate the encryption key (8-36 ASCII characters)';
$string['configsettings_custom'] = 'Custom Parameters';
$string['configsharedcode'] = 'Shared secret code which authenticates the primary system to BSI';
$string['configsysiddefaults'] = 'Enter settings for making a connection to the Biometric Signature ID server.';
$string['configsystemid'] = 'Unique identifier assigned to the primary system';
$string['configurl'] = 'URL for single sign-on (SSO) connections to Biometric Signature ID server';
$string['configvector'] = 'Initialization vector (must be 16 ASCII characters)';
$string['custom1'] = 'Custom Parameter 1';
$string['customerid'] = 'Customer ID';
$string['encdefaults'] = 'Encryption settings';
$string['error'] = 'An error occurred...';
$string['error100'] = 'None';
$string['error110'] = 'Unknown error';
$string['error120'] = 'Shared Code not authorized';
$string['error130'] = 'License error';
$string['error140'] = 'System ID not found';
$string['error150'] = 'Customer ID not found';
$string['error160'] = 'Locale Code not found';
$string['error170'] = 'New Window not found';
$string['error180'] = 'Login ID not found';
$string['error190'] = 'First Name not found';
$string['error200'] = 'Last Name not found';
$string['error210'] = 'Email address not found';
$string['error220'] = 'Internal server error';
$string['error230'] = 'User profile frozen';
$string['error240'] = 'User profile locked';
$string['error250'] = 'Timestamp not accepted';
$string['instructor'] = 'This page will redirect students to validate their identity.</p><p>Overriding a student\'s grade will prevent them from accessing this facility.  Use the form below to force a student to re-validate themselves (the form also includes the names of students whose grades have been overridden).';
$string['locale'] = 'Default locale';
$string['modulename'] = 'Biometric Signature ID';
$string['modulename_help'] = 'Use the Biometric Signature ID module for... | The Biometric Signature ID module allows...';
$string['modulenameplural'] = 'Biometric Signature IDs';
$string['nobiosigids'] = 'There are no instances of the Biometric Signature ID module';
$string['overridden'] = 'Please contact your instructor for access to this option.';
$string['passphrase'] = 'Pass phrase';
$string['pluginadministration'] = 'Biometric Signature ID administration';
$string['pluginname'] = 'Biometric Signature ID';
$string['privacy:metadata:mod_biosigid:biometricdata'] = 'This is the BioSig-ID™ profile which is used to authenticate the user.';
$string['privacy:metadata:mod_biosigid:email'] = 'In order to facilitate self resets, an email address is required.';
$string['privacy:metadata:mod_biosigid:externalpurpose'] = 'The BioSig-ID™ Moodle module creates a connection to the BioSig-ID™ interface server, which then upon server-to-server credential exchange, returns the BioSig-ID™ application server URL for the user to be presented with the BioSig-ID™ drawing pad inside a Moodle iframe construction. Returned results is a Boolean value true/false to indicate back to the BioSig-ID™ Moodle module to store 100% grade or not when used as a tool-instance and allow adaptive release conditions to open gated-items, or in quiz-instance usage to allow quiz to open or not';
$string['privacy:metadata:mod_biosigid:firstname'] = 'Providing human readable reports for the institution.';
$string['privacy:metadata:mod_biosigid:lastname'] = 'Providing human readable reports for the institution.';
$string['privacy:metadata:mod_biosigid:userid'] = 'BioSig-ID™ requires a unique idenfier to function in authenticating the end user.';
$string['reset_button'] = 'Reset';
$string['reset_empty'] = 'There are no students requiring to be reset.';
$string['reset_error'] = 'There was an error resetting the student, please try again.';
$string['reset_label'] = 'Student ID:';
$string['reset_ok'] = 'The student has been reset.';
$string['reset_option'] = 'Select student...';
$string['reset_title'] = 'Reset Student';
$string['return_to_course'] = 'Click <a href="{$a->link}" target="_top">here</a> to return to the course.';
$string['salt'] = 'Salt';
$string['settings_custom'] = 'Custom Parameters';
$string['sharedcode'] = 'Shared Code';
$string['sysiddefaults'] = 'System identification';
$string['systemid'] = 'System ID';
$string['url'] = 'SSO URL';
$string['vector'] = 'Vector';
