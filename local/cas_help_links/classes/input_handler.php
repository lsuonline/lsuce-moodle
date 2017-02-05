<?php
 
class local_cas_help_links_input_handler {

    /**
     * Accepts a given array of posted user link setting data and persists appropriately
     * 
     * @param  array $post_data
     * @param  int $user_id
     * @return boolean
     */
    public static function handle_user_settings_input($post_data, $user_id) {
        $link_objects = self::get_link_input_objects($post_data, $user_id);

        // iterate through all link objects
        foreach ($link_objects as $link) {
            // if input is given for an existing link record
            if ($link->id) {
                // update the cas_help_link record
                self::update_link_record($link, true);
            // otherwise, if input is given for a non-exisitent link
            } else {
                if (self::link_should_be_persisted($link, true))
                    self::insert_link_record($link);
            }
        }

        return true;
    }

    /**
     * Accepts a given array of posted category link setting data and persists appropriately
     * 
     * @param  array $post_data
     * @return boolean
     */
    public static function handle_category_settings_input($post_data)
    {
        $link_objects = self::get_link_input_objects($post_data);

        // iterate through all link objects
        foreach ($link_objects as $link) {
            // if input is given for an existing link record
            if ($link->id) {
                // update the cas_help_link record
                self::update_link_record($link, true);

            // otherwise, if input is given for a non-exisitent link
            } else {
                if (self::link_should_be_persisted($link, true))
                    self::insert_link_record($link);
            }
        }

        return true;
    }

    /**
     * Returns an array of formatted link objects from the given post data
     *
     * Optionally assigns ownership of the link to the given optional user id
     * 
     * @param  array  $post_data
     * @param  int $user_id
     * @return array
     */
    private static function get_link_input_objects($post_data, $user_id = 0)
    {
        // get all individual link-related inputs from posted data
        $link_input_arrays = self::get_link_input_arrays($post_data);

        // combine and convert link input arrays to an array of objects
        $link_input_objects = self::objectify_link_inputs($link_input_arrays);

        if ($user_id) {
            $link_input_objects = self::assign_user_to_link_objects($link_input_objects, $user_id);
        }

        return $link_input_objects;
    }

    /**
     * Reports whether or not the given link object should be persisted
     * 
     * @param  object $link
     * @param  boolean $check_for_duplicate_record
     * @return bool
     */
    private static function link_should_be_persisted($link, $check_for_duplicate_record = false)
    {        
        if ($check_for_duplicate_record && self::identical_link_exists($link)) {
            return false;
        }

        return ($link->display && ! $link->link) ? false : true;
    }

    /**
     * Reports whether or not identical link record(s) exist for this link object
     * 
     * @param  object $link link record to be persisted
     * @return boolean
     */
    private static function identical_link_exists($link)
    {
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

        $existing_records = $DB->get_records(self::get_link_table_name(), $params);

        return ! $existing_records ? false : true;
    }

    /**
     * Update the given link record
     *
     * Optionally delete this link record if it is unncessary (display on, no url)
     * 
     * @param  object $link_record
     * @return void
     */
    private static function update_link_record($link_record, $delete_unnecessary_links = true)
    {
        global $DB;
        
        if (self::link_should_be_persisted($link_record) || ! $delete_unnecessary_links) {
            $DB->update_record(self::get_link_table_name(), $link_record);
        } else {
            $DB->delete_records(self::get_link_table_name(), ['id' => $link_record->id]);
        }
    }

    /**
     * Insert the given link record
     * 
     * @param  object $link_record
     * @return void
     */
    private static function insert_link_record($link_record)
    {
        global $DB;

        $DB->insert_record(self::get_link_table_name(), $link_record);
    }

    /**
     * Returns the name of the 'help links' table
     * 
     * @return string
     */
    private static function get_link_table_name()
    {
        return 'local_cas_help_links';
    }

    /**
     * Returns an array of link objects now assigned to the given user id
     * 
     * @param  array $link_objects
     * @param  int $user_id
     * @return array
     */
    private static function assign_user_to_link_objects($link_objects, $user_id)
    {
        $output = [];

        foreach ($link_objects as $link) {
            $link->user_id = $user_id;

            $output[] = $link;
        }

        return $output;
    }

    /**
     * Returns an array of combined, formatted link objects from the given array of individual inputs
     * 
     * @param  array $link_inputs
     * @return array
     */
    private static function objectify_link_inputs($link_inputs)
    {
        $output = [];

        foreach ($link_inputs as $input) {
            $input = self::sanitize_link_input($input);

            // if this input has not been added to output yet
            if ( ! array_key_exists($input['id'], $output)) {
                $output[$input['id']] = self::transform_link_input_to_object($input);

            // otherwise, this link exists in output and needs missing field (display/link) to be updated
            } else {
                $output[$input['id']] = self::update_link_object($output[$input['id']], $input);
            }
        }

        return $output;
    }

    /**
     * Checks whether the given input array contains link information,
     * if so, asserts that the url contains an appropriate prefix
     * 
     * @param  array $input
     * @return array
     */
    private static function sanitize_link_input($input)
    {
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
    private static function format_url($url)
    {
        $invalid_url = get_string('invalid_url', 'local_cas_help_links');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://') {
            if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('/http[s]?:\/\/[^\.|^\,][-a-zA-Z0-9@:%._\+~#=]{0,256}\.[a-z]{2,6}\b[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*/i' ,$url)) {
                return $url;
            } else {
                throw new Exception($invalid_url . $url);
            }
        } else if (filter_var('http://' . $url, FILTER_VALIDATE_URL) && preg_match('/http[s]?:\/\/[^\.|^\,][-a-zA-Z0-9@:%._\+~#=]{0,256}\.[a-z]{2,6}\b[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*/i' ,'http://' . $url)) {
                return 'http://' . $url;
            } else {
                throw new Exception($invalid_url . $url);
        }
    }

    /**
     * Returns a link object with the given input property updated
     * 
     * @param  object $link_object
     * @param  array $input
     * @return object
     */
    private static function update_link_object($link_object, $input)
    {
        $link_object->$input['field'] = $input['input_value'];

        return $link_object;
    }

    /**
     * Returns a formatted link object from the given input array
     * 
     * @param  array $input
     * @return object
     */
    private static function transform_link_input_to_object($input)
    {
        $link_object = new stdClass();

        $link_object->id = $input['link_id'];
        $link_object->type = $input['link_type'];
        $link_object->category_id = $input['link_type'] == 'category' ? $input['entity_id'] : 0;
        $link_object->course_id = $input['link_type'] == 'course' ? $input['entity_id'] : 0;
        $link_object->display = $input['field'] == 'display' ? $input['input_value'] : '';
        $link_object->link = $input['field'] == 'link' ? $input['input_value'] : '';

        return $link_object;
    }

    /**
     * Returns an array of all formatted link input data
     * 
     * @param  array $post_data
     * @return array
     */
    private static function get_link_input_arrays($post_data)
    {
        $output = [];
        
        foreach ((array) $post_data as $name => $value) {
            $decodedInput = self::decode_input_name($name);

            if ( ! $decodedInput['is_link_input'])
                continue;

            $decodedInput['input_name'] = $name;
            
            if ($decodedInput['field'] == 'display') {
                $decodedInput['input_value'] = $value ? 0 : 1;
            } else {
                $decodedInput['input_value'] = $value;
            }

            $output[$name] = $decodedInput;
        }

        return $output;
    }

    /**
     * Returns an encoded input name string for the given attributes
     * 
     * @param  string $field  input field: display|link
     * @param  string $type  entity type: course|category|user
     * @param  int $link_id  cas_help_link record id (0 as default)
     * @param  int $entity_id  id of given entity type record
     * @return string
     */
    public static function encode_input_name($field, $type, $link_id, $entity_id)
    {
        return 'link_' . $link_id . '_' . $type . '_' . $entity_id . '_' . $field;
    }

    /**
     * Returns an array of data from the given encoded input name
     * 
     * @param  string $name
     * @return array
     */
    public static function decode_input_name($name)
    {
        $exploded = explode('_', $name);

        switch ($exploded[0]) {
            case 'link':
                return self::decode_link_input_name($name);
                
                break;
            
            default:
                return [
                    'is_link_input' => false
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
    public static function decode_link_input_name($name)
    {
        $exploded = explode('_', $name);

        $inputId = substr($name, 0, strrpos($name,'_'));

        return [
            'id' => $inputId,
            'is_link_input' => true,
            'is_record' => (int) $exploded[1] > 0 ? true : false,
            'link_id' => (int) $exploded[1],
            'link_type' => (string) $exploded[2],
            'entity_id' => (int) $exploded[3],
            'field' => (string) $exploded[4],
        ];
    }
}
