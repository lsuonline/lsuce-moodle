<?php


interface Sentinel {
    
    /**
     * client classes must return a boolean
     * true, meaning the user has passed that classes requirements
     * false is the opposite ;)
     * @param stdClass $user
     */
    public static function allowUser(stdClass $user);
}

?>
