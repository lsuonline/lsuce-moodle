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
 * Strings for the quizaccess_biosigid plugin.
 *
 * @package    quizaccess_biosigid
 * @author     Nathan Hoogstraat <nathan.hoogstraat@biosig-id.com>
 * @copyright  2011-2020 Biometric Signature Identification, Inc. <info@biosig-id.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'BioSig-ID™ Quiz Intercepter';

$string['biosig_required'] = 'BioSig-ID™ Quiz Intercept';
$string['biosig_required_help'] = 'This will enable the BioSig-ID™ Quiz Intercept for this quiz.';
$string['biosig_enabled'] = 'Enabled';

$string['bsi_failure'] = 'You failed validation';
$string['bsi_error'] = 'Sorry an error occurred: "<strong><em>%s</em></strong>".  If you are unable to resolve this issue please contact your support.';
$string['bsi_generic_error'] = 'Requirements for use of BioSig-ID™ are not met, contact your institution for assistance.';

$string['configtimeoutdefaults'] = 'Enter timeout value required for re-authentication with BioSig-ID™.';
$string['timeoutdefaults'] = 'Timeout Settings';
$string['configtimeout'] = 'Enter timeout value required for re-authentication with BioSig-ID™.';
$string['timeout'] = 'Timeout';

$string['privacy:metadata:quizaccess_biosig:userid'] = 'BioSig-ID™ requires a unique idenfier to function in authenticating the end user.';
$string['privacy:metadata:quizaccess_biosig:biometricdata'] = 'This is the BioSig-ID™ profile which is used to authenticate the user.';
$string['privacy:metadata:quizaccess_biosig:email'] = 'In order to facilitate self resets, an email address is required.';
$string['privacy:metadata:quizaccess_biosig:firstname'] = 'Providing human readable reports for the institution.';
$string['privacy:metadata:quizaccess_biosig:lastname'] = 'Providing human readable reports for the institution.';
$string['privacy:metadata:quizaccess_biosig:externalpurpose'] = 'The BioSig-ID™ Moodle module creates a connection to the BioSig-ID™ interface server, which then upon server-to-server credential exchange, returns the BioSig-ID™ application server URL for the user to be presented with the BioSig-ID™ drawing pad inside a Moodle iframe construction. Returned results is a Boolean value true/false to indicate back to the BioSig-ID™ Moodle module to store 100% grade or not when used as a tool-instance and allow adaptive release conditions to open gated-items, or in quiz-instance usage to allow quiz to open or not';