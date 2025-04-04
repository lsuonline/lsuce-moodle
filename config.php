<?php
/**
 * WARNING! Never ever edit this file for local development!
 * Instead, create a config_local.php with your config for Moodle.
 *
 * @author Mark Nielsen
 */

if (!empty($_SERVER['MR_CONFIG_FILENAME'])) {
    $_mdl_config_file = $_SERVER['MR_CONFIG_FILENAME'];
} else {
    $_mdl_config_file = dirname(__FILE__).'/config_local.php';
}
if (file_exists($_mdl_config_file)) {
    include $_mdl_config_file;
} else {
    die('There is no site configured for this url');
}
