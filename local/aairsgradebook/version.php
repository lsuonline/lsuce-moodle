<?php
$plugin->version  = 2012061700;   // The (date) version of this module + 2 extra digital for daily versions
                                  // This version number is displayed into /admin/forms.php
                                  // TODO: if ever this plugin get branched, the old branch number
                                  // will not be updated to the current date but just incremented. We will
                                  // need then a $plugin->release human friendly date. For the moment, we use
                                  // display this version number with userdate (dev friendly)
$plugin->requires = 2010112400;  // Requires this Moodle version - at least 2.0
$plugin->cron     = 0;
$plugin->release = '1.0 (Build: 2011101202)';
$plugin->maturity = MATURITY_STABLE;