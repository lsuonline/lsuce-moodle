<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_mhaairs_upgrade($oldversion = 0) {
    global $DB;

    $result = true;

    if ($result && $oldversion < 2011091314) {
        // Check for multiple instances of mhaairs blocks in all courses.
        $blockname = 'mhaairs';
        $tbl = 'block_instances';
        $sql = 'SELECT distinct parentcontextid FROM {block_instances} WHERE blockname = :blockname';
        $instances = $DB->get_records_sql($sql, array('blockname' => $blockname));
        if (!empty($instances)) {
            $delete_arr = array();
            foreach ($instances as $instance) {
                $params = array('parentcontextid' => $instance->parentcontextid, 'blockname' => $blockname);
                $recs = $DB->get_records($tbl, $params, '', 'id');

                $inst = 1;  // Helps mark first instance, which we will always keep.

                foreach ($recs as $record) {
                    $id = $record->id;
                    $newvalue = "";  // Set configdata to empty string.

                    if ($inst == 1) {
                        $DB->set_field($tbl, 'configdata', $newvalue, array('blockname' => $blockname));
                        $inst++;
                    } else {
                        // Delete list.
                        $delete_arr[] = $id;
                    }
                }
                try {
                    try {
                        $transaction = $DB->start_delegated_transaction();
                        $DB->delete_records_list($tbl, 'id', $delete_arr);
                        $transaction->allow_commit();
                    } catch (Exception $e) {
                        if (!empty($transaction) && !$transaction->is_disposed()) {
                            $transaction->rollback($e);
                        }
                    }
                } catch (Exception $e) {
                    $result = false;
                }
            }
        }
    }

    if ($result && $oldversion < 2013120203) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/externallib.php');
        external_delete_descriptions('local_aairsgradebook');
        upgrade_block_savepoint($result, 2013120203, 'mhaairs');
    }

    if ($result && ($oldversion < 2013120204)) {
        // Is Redis extension present?
        if (class_exists('Redis')) {
            $redisrv = get_config('core', 'local_mr_redis_server');
            if (!empty($redisrv)) {
                set_config('block_mhaairs_locktype', 'redislock');
            }
        }

        upgrade_block_savepoint($result, 2013120204, 'mhaairs');
    }

    return $result;
}
