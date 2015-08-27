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
 * Redis-backed lock factory.
 *
 * @package   local_redislock
 * @author    Sam Chaffee
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_redislock\lock;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_factory;
use core\lock\lock;

/**
 * Redis-backed lock factory class.
 *
 * @package   local_redislock
 * @author    Sam Chaffee
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class redis_lock_factory implements lock_factory {
    /**
     * @var \Redis An instance of the PHPRedis extension class.
     */
    protected $redis;

    /**
     * @var string The type this lock is used for (e.g. cron, cache).
     */
    protected $type;

    /**
     * @var array Keeps track of open locks to be closed on shutdown if not properly closed.
     */
    protected $openlocks = [];

    /**
     * @param string $type The type this lock is used for (e.g. cron, cache).
     * @param \Redis $redis An instance of the PHPRedis extension class.
     * @throws \coding_exception
     */
    public function __construct($type, \Redis $redis = null) {
        $this->type = $type;

        if (is_null($redis)) {
            $redis = $this->bootstrap_redis();
        }
        $this->redis = $redis;

        \core_shutdown_manager::register_function(array($this, 'auto_release'));
    }

    /**
     * Is this lock factory available.
     *
     * @return boolean True if this lock type is available in this environment.
     */
    public function is_available() {
        return $this->redis instanceof \Redis;
    }

    /**
     * Return information about the blocking behaviour of the locks on this platform.
     *
     * @return boolean False if attempting to get a lock will block indefinitely.
     */
    public function supports_timeout() {
        return true;
    }

    /**
     * Will this lock be automatically released when the process ends.
     * This should never be relied upon in code - but is useful in the case of
     * fatal errors. If a lock type does not support this auto release,
     * the max lock time parameter must be obeyed to eventually clean up a lock.
     *
     * @return boolean True if this lock type will be automatically released when the current process ends.
     */
    public function supports_auto_release() {
        return true;
    }

    /**
     * Supports recursion.
     *
     * @return boolean True if attempting to get 2 locks on the same resource will "stack"
     */
    public function supports_recursion() {
        return false;
    }

    /**
     * Get a lock within the specified timeout or return false.
     *
     * @param string $resource The identifier for the lock. Should use frankenstyle prefix.
     * @param int    $timeout The number of seconds to wait for a lock before giving up.
     * @param int    $maxlifetime The number of seconds to wait before reclaiming a stale lock.
     * @return lock|boolean An instance of \core\lock\lock if the lock was obtained, or false.
     * @throws \coding_exception
     */
    public function get_lock($resource, $timeout, $maxlifetime = 86400) {
        global $CFG;
        $giveuptime = time() + $timeout;

        $resource = $this->type . '_' . $resource;
        $resource = clean_param($resource, PARAM_ALPHAEXT);
        $resource = clean_param($resource, PARAM_FILE);

        if (empty($resource)) {
            throw new \coding_exception('Passed unique key is empty (after cleaning)');
        }

        if (!empty($CFG->MR_SHORT_NAME)) {
            $resource = $CFG->MR_SHORT_NAME . '_' . $resource;
        } else {
            $resource = $CFG->dbname . '_' . $resource;
        }

        do {
            $now = time();
            if ($locked = $this->redis->setnx($resource, $this->get_lock_value())) {
                $this->redis->expire($resource, $maxlifetime);
            } else {
                usleep(rand(10000, 250000)); // Sleep between 10 and 250 milliseconds.
            }
        } while (!$locked && $now < $giveuptime);

        if ($locked) {
            $lock = new lock($resource, $this);
            $this->openlocks[$resource] = $lock;
            return $lock;
        }

        return false;
    }

    /**
     * Release a lock that was previously obtained with @get_lock.
     *
     * @param lock $lock The lock to release.
     * @return boolean True if the lock is no longer held (including if it was never held).
     */
    public function release_lock(lock $lock) {
        $resource = $lock->get_key();

        if ($value = $this->redis->get($resource)) {
            if ($value == $this->get_lock_value()) {
                // This is the process' lock, release it.
                $unlocked = $this->redis->del($resource);
            } else {
                // Don't release another process' lock.
                $unlocked = false;
            }
        } else {
            // Never held that lock or it's already released.
            $unlocked = true;
        }

        if ($unlocked) {
            unset($this->openlocks[$resource]);
        }

        return (bool) $unlocked;
    }

    /**
     * Extend the timeout on a held lock.
     *
     * @param lock $lock lock obtained from this factory.
     * @param int  $maxlifetime new max time to hold the lock.
     * @return boolean True if the lock was extended.
     */
    public function extend_lock(lock $lock, $maxlifetime = 86400) {
        $resource = $lock->get_key();
        $extended = false;
        if ($value = $this->redis->get($resource)) {
            if ($value == $this->get_lock_value()) {
                $extended = $this->redis->expire($resource, $maxlifetime);
            }
        }

        return $extended;
    }

    /**
     * Auto release any open locks on shutdown.
     */
    public function auto_release() {
        // Called from the shutdown handler. Must release all open locks.
        /** @var lock $lock */
        foreach ($this->openlocks as $lock) {
            $lock->release();
        }

        $this->redis->close();
    }

    /**
     * Returns the TTL for a lock.
     *
     * @param lock $lock
     * @return int
     */
    public function get_ttl(lock $lock) {
        $resource = $lock->get_key();
        return $this->redis->ttl($resource);
    }

    /**
     * Bootstraps a \Redis instance.
     *
     * @return \Redis
     * @throws \coding_exception
     */
    protected function bootstrap_redis() {
        global $CFG;

        if (!class_exists('Redis')) {
            throw new \coding_exception('Redis class not found, Redis PHP Extension is probably not installed on host: ' . $this->get_hostname());
        }
        if (empty($CFG->local_redislock_redis_server)) {
            throw new \coding_exception('Redis connection string is not configured in $CFG->local_redislock_redis_server');
        }

        try {
            $redis = new \Redis();
            $redis->connect($CFG->local_redislock_redis_server);
        } catch (\RedisException $e) {
            throw new \coding_exception("RedisException caught on host {$this->get_hostname()} with message: {$e->getMessage()}");
        }

        return $redis;
    }

    /**
     * Returns the hostname or 'UNKNOWN' for use in the lock value.
     *
     * @return string
     */
    protected function get_hostname() {
        if (($hostname = gethostname()) === false) {
            $hostname = 'UNKOWN';
        }
        return $hostname;
    }

    /**
     * Get the value that should be used for the lock.
     *
     * @return string
     */
    protected function get_lock_value() {
        return http_build_query(array(
            'hostname' => $this->get_hostname(),
            'processid' => getmypid(),
        ), null, '&');
    }
}