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

declare(strict_types=1);

namespace block_backadel\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;

/**
 * Resolves instructor username tokens from archive names to Moodle user records.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructor_resolver {

    /** @var array<string, stdClass|null> Cache of username → user record (or cached miss). */
    private static array $cache = [];

    /**
     * Find a Moodle user for a parsed username token.
     *
     * @param string $username Raw username extracted from filenames or manifests.
     * @return stdClass|null Active user row from {user}, or null when unresolved.
     */
    public function resolve(string $username): ?stdClass {
        global $DB;

        if (trim($username) === '') {
            return null;
        }

        if (array_key_exists($username, self::$cache)) {
            $cached = self::$cache[$username];
            return $cached;
        }

        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
        if ($user === false) {
            $safeun = $DB->sql_like_escape($username);
            $clause = $DB->sql_like('username', ':un', false) . ' AND deleted = :del';
            $user = $DB->get_record_select('user', $clause, ['un' => $safeun, 'del' => 0]);
            if ($user === false) {
                self::$cache[$username] = null;
                return null;
            }
        }

        self::$cache[$username] = $user;
        return $user;
    }

    /**
     * Batch-resolve multiple username tokens reusing cache.
     *
     * @param string[] $usernames
     * @return array<string, stdClass|null> Map of original tokens to user rows or null.
     */
    public function resolve_many(array $usernames): array {
        $out = [];
        foreach ($usernames as $username) {
            if (!is_string($username)) {
                continue;
            }
            $out[$username] = $this->resolve($username);
        }
        return $out;
    }
}
