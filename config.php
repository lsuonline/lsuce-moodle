<?php
/**
 * WARNING! Never ever edit this file for local development!
 * Instead, create a config.php in your remote /var/moodledata folder.
 * @author Mark Nielsen, Robert Russo
 */

$cemdlconfig = '/var/moodledata/config.php';

if (file_exists($cemdlconfig)) {
    include_once($cemdlconfig);
} else {
    die('There is no site configured for this url yet');
}
