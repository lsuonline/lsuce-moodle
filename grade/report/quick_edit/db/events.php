<?php
$observers = array(array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_anonymous_edited'
                       , 'callback'   => 'post_grades_handler::quick_edit_anonymous_edited'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_grade_edited'
                       , 'callback'   => 'post_grades_handler::quick_edit_grade_edited'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_anonymous_instantiated'
                       , 'callback'   => 'post_grades_handler::quick_edit_anonymous_instantiated'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_grade_instantiated'
                       , 'callback'   => 'post_grades_handler::quick_edit_grade_instantiated'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_anonymous_table_built'
                       , 'callback'   => 'post_grades_handler::quick_edit_anonymous_table_built'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_grade_table_built'
                       , 'callback'   => 'post_grades_handler::quick_edit_grade_table_built'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_other_instantiated'
                       , 'callback'   => 'post_grades_handler::quick_edit_other_instantiated'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_other_edited'
                       , 'callback'   => 'post_grades_handler::quick_edit_other_edited'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                        )
                 , array('eventname'  => 'namespace grade_report\quick_edit\event\quick_edit_other_table_built'
                       , 'callback'   => 'post_grades_handler::quick_edit_other_table_built'
                       , 'priority'   => '0'
                       , 'internal'   => '1'
                       , 'includefile' => '/blocks/post_grades/events.php'
                       )
                  );