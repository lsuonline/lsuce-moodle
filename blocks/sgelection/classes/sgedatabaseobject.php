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
 * Base class for ballot elements classes
 *
 * @package    block_sgelection
 * @copyright  2014 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once 'sgeobject.php';

abstract class sge_database_object extends sge_object{

    static $tablename;

    public function save(){
        global $DB;
        // @TODO make this less hacky;
        if(!isset($this->id) || $this->id <= 0){
            $id = $DB->insert_record(static::$tablename, $this);
            if (!$id) {
                print_error('inserterror', 'block_sgelection');
            }else{
                $this->id = $id;
            }
        }else{
            $DB->update_record(static::$tablename, $this);
        }
        return $this;
    }

    public static function get_by_id($id){
        global $DB;
        $fields = array_keys($DB->get_columns(static::$tablename));
        $sql = sprintf("SELECT %s FROM {%s} WHERE id = %s", implode(',', $fields), static::$tablename, $id);
        $row = $DB->get_record_sql($sql);

        if(false === $row){
            return false;
        }
        $params = array_combine($fields, (array)$row);
        return new static($params);
    }

    public static function get_all($params = array()){
        global $DB;
        $rows = $DB->get_records(static::$tablename, $params);
        return static::classify_rows($rows);
    }

    public static function classify_rows($rows){
        $instances = array();
        foreach($rows as $row){
            $instances[$row->id] = new static($row);
        }
        return $instances;
    }

    public function delete(){
        global $DB;
        $DB->delete_records(static::$tablename, array('id'=>$this->id));
        unset($this);
    }

    public function logaction($action, $context = null, $additionalparams = array()){
        if(empty($this->id)){
            throw new coding_exception("Cannot log database object actions until it has an id.");
        }
        $context = $context == null ? context_system::instance() : $context;
        $eventparams = array_merge(array(
                'objectid' => $this->id,
                'context'  => $context
            ), $additionalparams);
        $class = get_class($this);
        $classaction = $class.'_'.$action;
        $eventname = '\block_sgelection\event\\'.$classaction;
        $event = $eventname::create($eventparams);
        $event->trigger();
    }
}
