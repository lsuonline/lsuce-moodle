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
* @package    backup-convert
* @subpackage cc-library
* @copyright  2011 Darko Miletic <dmiletic@moodlerooms.com>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once 'gral_lib/cssparser.php';
require_once 'xmlbase.php';

class general_cc_file extends XMLGenericDocument {
    /**
     *
     * Root element
     * @var DOMElement
     */
    protected $root = null;
    protected $rootns = null;
    protected $rootname = null;
    protected $ccnamespaces = array();
    protected $ccnsnames = array();

    public function __construct() {
        parent::__construct();

        foreach ($this->ccnamespaces as $key => $value){
            $this->registerNS($key,$value);
        }
    }


    protected function on_create() {
        $rootel = $this->append_new_element_ns($this->doc,
                                               $this->ccnamespaces[$this->rootns],
                                               $this->rootname);
        //add all namespaces
        // BEGIN LSU IMS CC namespace fix - skip reserved 'xmlns' prefix to avoid DOMException.
        $raw = get_config('backup', 'imscc_namespace_fix');
        $usenamespacefix = ($raw === false) ? true : (bool) $raw; // Default to fix when not set.
        foreach ($this->ccnamespaces as $key => $value) {
            if ($usenamespacefix && $key === 'xmlns') {
                continue; // 'xmlns' is reserved in XML - cannot use with createAttributeNS().
            }
            $dummy_attr = "{$key}:dummy";
            $this->doc->createAttributeNS($value,$dummy_attr);
        }
        // END LSU IMS CC namespace fix.

        // add location of schemas
        $schemaLocation='';
        foreach ($this->ccnsnames as $key => $value) {
            $vt = empty($schemaLocation) ? '' : ' ';
            $schemaLocation .= $vt.$this->ccnamespaces[$key].' '.$value;
        }

        if (!empty($schemaLocation) && isset($this->ccnamespaces['xsi'])) {
            $this->append_new_attribute_ns($rootel,
                                           $this->ccnamespaces['xsi'],
                                           'xsi:schemaLocation',
                                            $schemaLocation);
        }

        $this->root = $rootel;
    }

}
