<?php
/**
 * WARNING! Never ever edit this file for local development!
 *
 * Instead, create a config.php in your remote /var/moodledata folder.
 *
 * @author Mark Nielsen, Robert Russo
 */

$_mdl_config_file = '/var/moodledata/config.php';

if (file_exists($_mdl_config_file)) {
    include $_mdl_config_file;
} else {
    die('There is no site configured for this url');
}
