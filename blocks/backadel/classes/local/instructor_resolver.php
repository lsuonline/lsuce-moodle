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
 * Resolves instructor tokens from archive filenames to Moodle user records.
 *
 * Token shapes handled:
 *  - Plain username  ("wjian15") — username lookup, then optional email-domain fallback.
 *  - Full email      ("wjian15@lsu.edu") — email lookup first, then local-part username fallback.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instructor_resolver {

    /**
     * In-process cache keyed by raw token string.
     *
     * @var array<string, array{user: stdClass|null, via: string}>
     */
    private static array $cache = [];

    /**
     * Resolve a raw instructor token from a filename to a Moodle user.
     *
     * Resolution chain:
     *  - Email token (@-containing): try mdl_user.email → try local-part as username.
     *  - Plain token: try mdl_user.username (exact + case-insensitive) → optional domain fallback.
     *
     * @param string $token Raw token extracted from archive filename.
     * @return array{user: stdClass|null, via: string}
     *         `via` is 'email' | 'username' | <domain-first-label> | 'none'.
     */
    public function resolve_token(string $token): array {
        $token = trim($token);
        if ($token === '') {
            return ['user' => null, 'via' => 'none'];
        }
        if (array_key_exists($token, self::$cache)) {
            return self::$cache[$token];
        }

        $result = strpos($token, '@') !== false
            ? $this->resolve_by_email_token($token)
            : $this->resolve_by_username_token($token);

        self::$cache[$token] = $result;
        return $result;
    }

    /**
     * Email-first resolution: exact email match, then local-part username fallback.
     *
     * @param string $email Full email address token (contains @).
     * @return array{user: stdClass|null, via: string}
     */
    private function resolve_by_email_token(string $email): array {
        global $DB;

        $users = $DB->get_records('user', ['email' => $email, 'deleted' => 0]);
        if ($users) {
            return ['user' => reset($users), 'via' => 'email'];
        }

        $local = explode('@', $email)[0];
        if ($local !== '') {
            $user = $DB->get_record('user', ['username' => $local, 'deleted' => 0]);
            if ($user !== false) {
                return ['user' => $user, 'via' => 'username'];
            }
        }

        return ['user' => null, 'via' => 'none'];
    }

    /**
     * Username-first resolution with optional configured email-domain fallback.
     *
     * Falls back to `username@block_backadel/instructor_email_domain` when no
     * exact or case-insensitive username match is found.
     *
     * @param string $username Plain username token (no @).
     * @return array{user: stdClass|null, via: string}
     */
    private function resolve_by_username_token(string $username): array {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
        if ($user !== false) {
            return ['user' => $user, 'via' => 'username'];
        }

        // Case-insensitive fallback for mixed-case legacy tokens.
        $safeun = $DB->sql_like_escape($username);
        $clause = $DB->sql_like('username', ':un', false) . ' AND deleted = :del';
        $user = $DB->get_record_select('user', $clause, ['un' => $safeun, 'del' => 0]);
        if ($user !== false) {
            return ['user' => $user, 'via' => 'username'];
        }

        // Configurable domain fallback (e.g. "lsu.edu").
        $domain = trim((string) get_config('block_backadel', 'instructor_email_domain'));
        if ($domain !== '') {
            $domain = ltrim($domain, '@');
            $email = $username . '@' . $domain;
            $users = $DB->get_records('user', ['email' => $email, 'deleted' => 0]);
            if ($users) {
                // Use the first label of the domain as the resolvedvia value:
                // "lsu" from "lsu.edu", "agcenter" from "agcenter.lsu.edu".
                $via = explode('.', $domain)[0];
                return ['user' => reset($users), 'via' => $via];
            }
        }

        return ['user' => null, 'via' => 'none'];
    }

    /**
     * Backward-compatible single-token resolver; returns user row or null.
     *
     * @param string $username Raw username token (plain or email).
     * @return stdClass|null Active user row, or null when unresolved.
     */
    public function resolve(string $username): ?stdClass {
        return $this->resolve_token($username)['user'];
    }

    /**
     * Batch-resolve multiple tokens reusing the in-process cache.
     *
     * @param string[] $usernames
     * @return array<string, stdClass|null>
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
