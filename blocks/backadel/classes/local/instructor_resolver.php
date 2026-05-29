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
     * In-process cache keyed by the raw token string as passed by the caller.
     *
     * Known miss (bug-062): aliases for the same user are not deduplicated.
     * For example, "wjian15" and "wjian15@lsu.edu" produce two separate cache
     * entries and two DB round-trips. A partial mitigation lives in
     * {@see self::resolve_by_email_token()}, which back-fills the local-part
     * key after a successful username fallback so a later plain-username call
     * hits the cache. Full alias dedup is blocked on the bug-061 value object.
     *
     * @var array<string, array{user: stdClass|null, via: string}>
     */
    private static array $cache = [];

    /**
     * Configured instructor email domain fallback (e.g. "lsu.edu"), pre-loaded
     * once in the constructor to avoid a get_config() call per token resolution
     * (bug-066). Empty string when unset/disabled.
     *
     * @var string
     */
    private string $instructoremaildomain;

    /**
     * Constructor: pre-load configuration values used during resolution.
     */
    public function __construct() {
        $this->instructoremaildomain = trim((string) get_config('block_backadel', 'instructor_email_domain'));
    }

    /**
     * Reset the in-process token cache.
     *
     * Intended for long-running callers (CLI sweeps such as
     * `cli/migrate_filesystem.php`) and for tests that need a clean slate
     * without resorting to Reflection (bug-065).
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }

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

        $local = self::extract_local_part($email);
        if ($local !== '') {
            $user = $DB->get_record('user', ['username' => $local, 'deleted' => 0]);
            if ($user !== false) {
                $result = ['user' => $user, 'via' => 'username'];
                // Back-fill the cache under the local-part key so a subsequent
                // plain-username call for the same user hits the cache instead
                // of repeating the DB lookup (partial mitigation for bug-062).
                if (!array_key_exists($local, self::$cache)) {
                    self::$cache[$local] = $result;
                }
                return $result;
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

        // Configurable domain fallback (e.g. "lsu.edu"), pre-loaded in the
        // constructor to avoid a get_config() round-trip per token (bug-066).
        $domain = $this->instructoremaildomain;
        if ($domain !== '') {
            $domain = ltrim($domain, '@');
            $email = $username . '@' . $domain;
            $users = $DB->get_records('user', ['email' => $email, 'deleted' => 0]);
            if ($users) {
                // Use the first label of the domain as the resolvedvia value:
                // "lsu" from "lsu.edu", "agcenter" from "agcenter.lsu.edu".
                // Truncate to 32 chars to match the resolvedvia column (bug-068).
                $via = substr(explode('.', $domain)[0], 0, 32);
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

    /**
     * Return the local part of an email-shaped token, or the token itself when no `@` is present.
     *
     * Centralises the "strip @domain when present" pattern shared by the migrator's
     * teacher-row upsert and {@see self::resolve_by_email_token()}'s local-part fallback
     * (bug-061). Keeping the logic here means callers do not need to know how email
     * parsing works.
     *
     * @param string $token Raw token (plain username or full email).
     * @return string Local part before `@`, or the original token when `@` is absent.
     */
    public static function extract_local_part(string $token): string {
        $at = strpos($token, '@');
        return $at !== false ? substr($token, 0, $at) : $token;
    }
}
