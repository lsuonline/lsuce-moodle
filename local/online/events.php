<?php

abstract class online_enrollment_events {
    public static function ues_list_provider($data) {
        $data->plugins += array('online' => get_string('pluginname', 'local_online'));
        return $data;
    }

    public static function ues_load_online_provider($data) {
        require_once dirname(__FILE__) . '/provider.php';
        return true;
    }
}
