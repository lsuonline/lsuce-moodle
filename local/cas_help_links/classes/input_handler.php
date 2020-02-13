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

defined('MOODLE_INTERNAL') || die;

class local_cas_help_links_input_handler {

    /**
     * Accepts a given array of posted user link setting data and persists appropriately
     *
     * @param  array $postdata, old name was $post_data
     * @param  int $userid, old name was $user_id
     * @return boolean
     */
    public static function handle_user_settings_input($postdata, $userid) {
        $linkobjects = self::get_link_input_objects($postdata, $userid); // Old name: $link_objects.

        // Iterate through all link objects.
        foreach ($linkobjects as $link) {
            // If input is given for an existing link record.
            if ($link->id) {
                // Update the cas_help_link record.
                self::update_link_record($link, true);
                // Otherwise, if input is given for a non-exisitent link.
            } else {
                if (self::link_should_be_persisted($link, true)) {
                    self::insert_link_record($link);
                }
            }
        }

        return true;
    }

    /**
     * Accepts a given array of posted category link setting AND course match setting data and persists appropriately
     *
     * @param  array $postdata, old name: $post_data
     * @return boolean
     */
    public static function handle_category_settings_input($postdata) {
        // First, handle category link inputs.
        self::handle_category_settings_link_input($postdata);

        // Second, handle "coursematch" inputs.
        self::handle_category_settings_coursematch_input($postdata);

        return true;
    }

    /**
     * Accepts a given array of posted category link setting data and persists appropriately
     *
     * @param  array $postdata, old name: $post_data
     * @return void
     */
    private static function handle_category_settings_link_input($postdata) {
        $linkobjects = self::get_link_input_objects($postdata);  // Old variable name: $link_objects.

        // Iterate through all link objects.
        foreach ($linkobjects as $link) {
            // If input is given for an existing link record.
            if ($link->id) {
                // Update the cas_help_link record.
                self::update_link_record($link, true);

                // Otherwise, if input is given for a non-exisitent link.
            } else {
                if (self::link_should_be_persisted($link, true)) {
                    self::insert_link_record($link);
                }
            }
        }
    }

    /**
     * Accepts a given array of posted category setting "coursematch" data and persists appropriately
     *
     * @param  array $postdata, old name: $post_data
     * @return void
     */
    private static function handle_category_settings_coursematch_input($postdata) {
        $coursematchobject = self::get_coursematch_input_object($postdata); // Old variable name: $coursematch_object.

        if (self::coursematch_should_be_persisted($coursematchobject, true)) {
            self::insert_coursematch_record($coursematchobject);
        }
    }

    /**
     * Returns an array of formatted link objects from the given post data
     *
     * Optionally assigns ownership of the link to the given optional user id
     *
     * @param  array  $postdata, old name: $post_data
     * @param  int $userid, old name: $user_id
     * @return array
     */
    private static function get_link_input_objects($postdata, $userid = 0) {
        // Get all individual link-related inputs from posted data.
        $linkinputarrays = self::get_link_input_arrays($postdata); // Old variable name: $link_input_arrays.

        // Combine and convert link input arrays to an array of objects.
        $linkinputobjects = self::objectify_link_inputs($linkinputarrays); // Old variable name: $link_input_objects.

        if ($userid) {
            $linkinputobjects = self::assign_user_to_link_objects($linkinputobjects, $userid);
        }

        return $linkinputobjects;
    }

    // Old variable name: $post_data.
    private static function get_coursematch_input_object($postdata) {
        $coursematchinputarrays = self::get_coursematch_input_arrays($postdata); // Old variable name: $coursematch_input_arrays.

        // Combine and convert coursematch input arrays to a single object.
        // Old variable name: $coursematch_input_object.
        $coursematchinputobject = self::objectify_coursematch_inputs($coursematchinputarrays);

        return $coursematchinputobject;
    }

    /**
     * Reports whether or not the given link object should be persisted
     *
     * @param  object $link
     * @param  boolean $checkforduplicaterecord, old variable name: $check_for_duplicate_record.
     * @return bool
     */
    private static function link_should_be_persisted($link, $checkforduplicaterecord = false) {
        if ($checkforduplicaterecord && self::identical_link_exists($link)) {
            return false;
        }

        return ($link->display && ! $link->link) ? false : true;
    }

    /**
     * Reports whether or not the given coursematch object should be persisted
     *
     * @param  object $coursematch
     * @return bool
     */
    private static function coursematch_should_be_persisted($coursematch) {
        if (self::identical_coursematch_exists($coursematch)) {
            return false;
        }

        return ( ! $coursematch->dept || ! $coursematch->number || ! $coursematch->link) ? false : true;
    }

    /**
     * Reports whether or not identical link record(s) exist for this link object
     *
     * @param  object $link link record to be persisted
     * @return boolean
     */
    private static function identical_link_exists($link) {
        global $DB;

        $params = [
            'type' => $link->type,
            'display' => $link->display,
            'link' => $link->link,
        ];

        if (property_exists($link, 'category_id')) {
            $params['category_id'] = $link->category_id;
        }

        if (property_exists($link, 'course_id')) {
            $params['course_id'] = $link->course_id;
        }

        if (property_exists($link, 'user_id')) {
            $params['user_id'] = $link->user_id;
        }

        // Old variable name: $existing_records.
        $existingrecords = $DB->get_records(self::get_link_table_name(), $params);

        return ! $existingrecords ? false : true;
    }

    /**
     * Reports whether or not an identical coursematch record exists for this coursematch object
     *
     * @param  object $coursematch  coursematch record to be persisted
     * @return boolean
     */
    private static function identical_coursematch_exists($coursematch) {
        global $DB;

        $params = [
            'type' => 'coursematch',
            'dept' => $coursematch->dept,
            'number' => $coursematch->number,
        ];

        // Old variable name: $existing_records.
        $existingrecords = $DB->get_records(self::get_link_table_name(), $params);

        return ! $existingrecords ? false : true;
    }

    /**
     * Update the given link record
     *
     * Optionally delete this link record if it is unncessary (display on, no url)
     * Old variable name: $delete_unnecessary_links.
     * @param  object $linkrecord, old variable name: $link_record.
     * @return void
     */
    private static function update_link_record($linkrecord, $deleteunnecessarylinks = true) {
        global $DB;

        if (self::link_should_be_persisted($linkrecord) || ! $deleteunnecessarylinks) {
            $DB->update_record(self::get_link_table_name(), $linkrecord);
        } else {
            $DB->delete_records(self::get_link_table_name(), ['id' => $linkrecord->id]);
        }
    }

    /**
     * Insert the given link record
     *
     * @param  object $linkrecord, old variable name: $link_record.
     * @return void
     */
    private static function insert_link_record($linkrecord) {
        global $DB;

        $DB->insert_record(self::get_link_table_name(), $linkrecord);
    }

    /**
     * Insert the given coursematch record
     *
     * @param  object $coursematchrecord, old variable name: $coursematch_record.
     * @return void
     */
    private static function insert_coursematch_record($coursematchrecord) {
        global $DB;

        $DB->insert_record(self::get_link_table_name(), $coursematchrecord);
    }

    /**
     * Returns the name of the 'help links' table
     *
     * @return string
     */
    private static function get_link_table_name() {
        return 'local_cas_help_links';
    }

    /**
     * Returns an array of link objects now assigned to the given user id
     *
     * @param  array $link_objects, old variable name: $link_objects.
     * @param  int $user_id
     * @return array
     */
    private static function assign_user_to_link_objects($linkobjects, $user_id) {
        $output = [];

        foreach ($linkobjects as $link) {
            $link->user_id = $user_id;

            $output[] = $link;
        }

        return $output;
    }

    /**
     * Returns an array of combined, formatted link objects from the given array of individual inputs
     *
     * @param  array $inputarrays, old variable name: $input_arrays.
     * @return array
     */
    private static function objectify_link_inputs($inputarrays) {
        $output = [];

        foreach ($inputarrays as $input) {
            $input = self::sanitize_link_input($input);

            // If this input has not been added to output yet.
            if ( ! array_key_exists($input['id'], $output)) {
                $output[$input['id']] = self::transform_link_input_to_object($input);

                // Otherwise, this link exists in output and needs missing field (display/link) to be updated.
            } else {
                $output[$input['id']] = self::update_object('link', $output[$input['id']], $input);
            }
        }

        return $output;
    }

    /**
     * Returns a formatted coursematch object from the given array of individual inputs
     *
     * @param  array $inputarrays, old variable name: $input_arrays.
     * @return array
     */
    private static function objectify_coursematch_inputs($inputarrays) {
        $output = [];

        foreach ($inputarrays as $input) {
            $input = self::sanitize_link_input($input);

            // If this input has not been added to output yet.
            if ( ! array_key_exists($input['id'], $output)) {
                $output[$input['id']] = self::transform_coursematch_input_to_object($input);

                // Otherwise, this link exists in output and needs missing field (dept/number/link) to be updated.
            } else {
                $output[$input['id']] = self::update_object('coursematch', $output[$input['id']], $input);
            }
        }

        return current($output);
    }

    /**
     * Checks whether the given input array contains link information,
     * if so, asserts that the url contains an appropriate prefix
     *
     * @param  array $input
     * @return array
     */
    private static function sanitize_link_input($input) {
        if (array_key_exists('field', $input)) {
            if ($input['field'] == 'link' && $input['input_value']) {
                $input['input_value'] = self::format_url(trim($input['input_value']));
            }
        }

        return $input;
    }

    /**
     * Returns the given URL with an apprpriate prefix (defaults to: http://)
     *
     * @param  string $url
     * @return string
     */
    private static function format_url($url) {
        $invalidurl = get_string('invalid_url', 'local_cas_help_links'); // Old variable name: invalid_url.
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
            if (filter_var($url, FILTER_VALIDATE_URL)
                && preg_match('/http[s]?:\/\/[^\.|^\,][-a-zA-Z0-9@:%._\+~#=]{0,256}\.[a-z]{2,6}\b[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*/i'
                , $url)) {
                return $url;
            } else {
                throw new Exception($invalidurl . $url);
            }
        } else if (filter_var('http://' . $url, FILTER_VALIDATE_URL)
                   && preg_match('/http[s]?:\/\/[^\.|^\,][-a-zA-Z0-9@:%._\+~#=]{0,256}\.[a-z]{2,6}\b[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*/i'
                   , 'http://' . $url)) {
                return 'http://' . $url;
        } else {
            throw new Exception($invalidurl . $url);
        }
    }

    /**
     * Returns an object with the given input property updated based on the type of object to be created
     *
     * @param  string $inputtype, old variable name: $inputType.
     * @param  object $object
     * @param  array $input
     * @return object
     */
    private static function update_object($inputtype, $object, $input) {
        if ($inputtype == 'coursematch' && $input['field'] == 'dept') {
            $object->{$input['field']} = strtoupper($input['input_value']);
        } else {
            $object->{$input['field']} = $input['input_value'];
        }

        return $object;
    }

    /**
     * Returns a formatted link object from the given input array
     *
     * @param  array $input
     * @return object
     */
    private static function transform_link_input_to_object($input) {
        // Old variable name: $link_object.
        $linkobject = new stdClass();
        $linkobject->id = $input['link_id'];
        $linkobject->type = $input['link_type'];
        $linkobject->category_id = $input['link_type'] == 'category' ? $input['entity_id'] : 0;
        $linkobject->course_id = $input['link_type'] == 'course' ? $input['entity_id'] : 0;
        $linkobject->display = $input['field'] == 'display' ? $input['input_value'] : '';
        $linkobject->link = $input['field'] == 'link' ? $input['input_value'] : '';

        return $linkobject;
    }

    /**
     * Returns a formatted coursematch object from the given input array
     *
     * @param  array $input
     * @return object
     */
    private static function transform_coursematch_input_to_object($input) {
        // Old variable name: $coursematch_object.
        $coursematchobject = new stdClass();
        $coursematchobject->type = 'coursematch';
        $coursematchobject->display = $input['field'] == 'display' ? '0' : '1';
        $coursematchobject->dept = $input['field'] == 'dept' ? strtoupper($input['input_value']) : '';
        $coursematchobject->number = $input['field'] == 'number' ? $input['input_value'] : '';
        $coursematchobject->link = $input['field'] == 'link' ? $input['input_value'] : '';

        return $coursematchobject;
    }

    /**
     * Returns an array of all formatted link input data
     *
     * @param  array $postdata, old name: $post_data
     * @return array
     */
    private static function get_link_input_arrays($postdata) {
        $output = [];

        foreach ((array) $postdata as $name => $value) {
            $decodedinput = self::decode_input_name($name); // Old name: $decodedInput.

            if ( ! array_key_exists('is_link_input', $decodedinput) || ! $decodedinput['is_link_input']) {
                continue;
            }

            $decodedinput['input_name'] = $name;

            if ($decodedinput['field'] == 'display') {
                $decodedinput['input_value'] = $value ? 0 : 1;
            } else {
                $decodedinput['input_value'] = $value;
            }

            $output[$name] = $decodedinput;
        }

        return $output;
    }

    /**
     * Returns an array of all formatted link input data
     *
     * @param  array $postdata, old name: $post_data
     * @return array
     */
    private static function get_coursematch_input_arrays($postdata) {
        $output = [];

        foreach ((array) $postdata as $name => $value) {
            $decodedinput = self::decode_input_name($name); // Old name: $decodedInput.

            if ( ! array_key_exists('is_coursematch_input', $decodedinput) || ! $decodedinput['is_coursematch_input']) {
                continue;
            }

            $decodedinput['input_name'] = $name;
            $decodedinput['input_value'] = $value;

            $output[$name] = $decodedinput;
        }

        return $output;
    }

    /**
     * Returns an encoded input name string for the given attributes
     *
     * @param  string $field  input field: display|link
     * @param  string $type  entity type: course|category|user
     * @param  int $linkid  cas_help_link record id (0 as default), old name: $link_id
     * @param  int $entityid  id of given entity type record, old name: $entity_id
     * @return string
     */
    public static function encode_input_name($field, $type, $linkid, $entityid) {
        return 'link_' . $linkid . '_' . $type . '_' . $entityid . '_' . $field;
    }

    /**
     * Returns an array of data from the given encoded input name
     *
     * @param  string $name
     * @return array
     */
    public static function decode_input_name($name) {
        $exploded = explode('_', $name);

        switch ($exploded[0]) {
            case 'link':
                return self::decode_link_input_name($name);
                break;
            case 'coursematch':
                return self::decode_coursematch_input_name($name);
                break;
            default:
                return [
                    'is_link_input' => false,
                    'is_coursematch_input' => false
                ];
                break;
        }
    }

    /**
     * Returns an array of data representing given link input name
     *
     * @param  string $name
     * @return array
     */
    public static function decode_link_input_name($name) {
        $exploded = explode('_', $name);

        $inputid = substr($name, 0, strrpos($name, '_')); // Old variable name: $inputId.

        return [
            'id' => $inputid,
            'is_link_input' => true,
            'is_record' => (int) $exploded[1] > 0 ? true : false,
            'link_id' => (int) $exploded[1],
            'link_type' => (string) $exploded[2],
            'entity_id' => (int) $exploded[3],
            'field' => (string) $exploded[4],
        ];
    }

    /**
     * Returns an array of data representing given link input name
     *
     * @param  string $name
     * @return array
     */
    public static function decode_coursematch_input_name($name) {
        $exploded = explode('_', $name);
        $inputid = substr($name, 0, strrpos($name, '_')); // Old variable name: $inputId.

        return [
            'id' => $inputid,
            'is_coursematch_input' => true,
            'field' => (string) $exploded[1],
        ];
    }

    /**
     * Accepts a given array of posted coursematch deletion data and handles appropriately
     *
     * @param  array $postdata, old name: $post_data
     * @return boolean
     */
    public static function handle_coursematch_deletion_input($postdata) {
        // TODO - authorization here?

        global $DB;

        $DB->delete_records(self::get_link_table_name(), [
            'type' => 'coursematch',
            'id' => $postdata->id
        ]);

        return true;
    }
}