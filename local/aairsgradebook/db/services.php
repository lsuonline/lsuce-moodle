<?php
// We defined the web service functions to install.
$functions = array(
        'local_aairsgradebook_gradebookservice' => array(
                'classname'   => 'local_aairsgradebook_external',
                'methodname'  => 'gradebookservice',
                'classpath'   => 'local/aairsgradebook/externallib.php',
                'description' => 'Runs the grade_update() functuon',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'AAIRS Gradebook Service' => array(
                'functions' => array ('local_aairsgradebook_gradebookservice'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
