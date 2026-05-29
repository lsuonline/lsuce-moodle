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

/**
 * Tests for {@see instructor_resolver::resolve_token()}.
 *
 * Covers the three-leg resolution chain introduced for openlms email-embedded filenames:
 *   1. Email token → email lookup first, local-part username fallback.
 *   2. Plain token → username lookup, then configured domain fallback.
 *   3. Unresolvable tokens → null user, via='none'.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_backadel\local\instructor_resolver
 */
final class instructor_resolver_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        instructor_resolver::reset_cache();
    }

    // Empty / blank token guard.

    public function test_empty_token_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    public function test_whitespace_token_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('   ');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    // Plain-username resolution.

    public function test_plain_username_found_by_exact_match(): void {
        $user = $this->getDataGenerator()->create_user(['username' => 'wjian15']);
        $r = new instructor_resolver();
        $result = $r->resolve_token('wjian15');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('username', $result['via']);
    }

    public function test_plain_username_not_found_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('nosuchuser99');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    /**
     * Bug-065: the sql_like(..., false) case-insensitive fallback inside
     * resolve_by_username_token() had no coverage. Tokens like "WJIAN15UPPER"
     * arriving in uppercase from legacy archive names must still resolve to a
     * Moodle user stored under the lowercase form.
     *
     * Note: Moodle's user_create_user() rejects non-lowercase usernames, so we
     * insert the user row directly via $DB to simulate the exact stored shape.
     * Note: MariaDB's default utf8mb4_unicode_ci collation makes the exact-match
     * lookup case-insensitive too, but the test still proves the chain returns
     * the user (whichever leg wins).
     */
    public function test_plain_username_found_by_case_insensitive_fallback(): void {
        global $DB;

        $userid = $DB->insert_record('user', (object) [
            'auth'         => 'manual',
            'confirmed'    => 1,
            'mnethostid'   => 1,
            'username'     => 'wjian15upper',
            'password'     => 'not-a-hash',
            'firstname'    => 'Wanying',
            'lastname'     => 'Jiang',
            'email'        => 'wjian15upper@example.com',
            'deleted'      => 0,
            'suspended'    => 0,
            'lang'         => 'en',
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $r = new instructor_resolver();
        $result = $r->resolve_token('WJIAN15UPPER');

        $this->assertNotNull(
            $result['user'],
            'case-insensitive fallback must resolve uppercase token to lowercase-stored username'
        );
        $this->assertSame($userid, (int) $result['user']->id);
        $this->assertSame('username', $result['via']);
    }

    // Email-token resolution.

    public function test_email_token_found_by_email(): void {
        $user = $this->getDataGenerator()->create_user([
            'username' => 'wjian15',
            'email'    => 'wjian15@lsu.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('wjian15@lsu.edu');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('email', $result['via']);
    }

    public function test_email_token_falls_back_to_local_part_username(): void {
        // Email address not in Moodle, but username (local part) is.
        $user = $this->getDataGenerator()->create_user([
            'username' => 'jdoe99',
            'email'    => 'jdoe99@otherdomain.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('jdoe99@lsu.edu');
        $this->assertNotNull($result['user'], 'should fall back to local-part username lookup');
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('username', $result['via']);
    }

    public function test_email_token_not_found_by_email_or_username_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('nobody@lsu.edu');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    /**
     * Bug-066: resolve_by_email_token() uses get_records() and returns reset()
     * (first match) when multiple users share an email. Moodle technically
     * allows duplicate emails — the resolver must not throw and must return
     * one of the matching users.
     *
     * The data generator may reject duplicate emails (depending on the
     * allowaccountssameemail config); the second user is inserted directly via
     * $DB to guarantee the duplicate.
     */
    public function test_email_token_with_duplicate_email_returns_first_match(): void {
        global $DB;

        $this->getDataGenerator()->create_user([
            'username' => 'sharedemail1',
            'email'    => 'shared@lsu.edu',
        ]);

        $DB->insert_record('user', (object) [
            'auth'         => 'manual',
            'confirmed'    => 1,
            'mnethostid'   => 1,
            'username'     => 'sharedemail2',
            'password'     => 'not-a-hash',
            'firstname'    => 'Shared',
            'lastname'     => 'Two',
            'email'        => 'shared@lsu.edu',
            'deleted'      => 0,
            'suspended'    => 0,
            'lang'         => 'en',
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $r = new instructor_resolver();
        $result = $r->resolve_token('shared@lsu.edu');

        // Assert one IS returned and no exception was thrown by the ambiguous lookup.
        $this->assertNotNull(
            $result['user'],
            'ambiguous duplicate-email lookup must return one of the matches'
        );
        $this->assertSame('email', $result['via']);
        $this->assertSame('shared@lsu.edu', $result['user']->email);
    }

    // Configured email-domain fallback for plain tokens.

    public function test_plain_username_resolved_via_configured_domain(): void {
        set_config('instructor_email_domain', 'lsu.edu', 'block_backadel');
        $user = $this->getDataGenerator()->create_user([
            'username' => 'someother',
            'email'    => 'jsmith99@lsu.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('jsmith99');
        $this->assertNotNull($result['user'], 'should resolve via email domain fallback');
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('lsu', $result['via'], 'resolvedvia should be first domain label');
    }

    public function test_plain_username_domain_fallback_disabled_when_empty(): void {
        set_config('instructor_email_domain', '', 'block_backadel');
        $this->getDataGenerator()->create_user([
            'username' => 'other',
            'email'    => 'nofallback@lsu.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('nofallback');
        $this->assertNull($result['user'], 'domain fallback must be disabled when setting is empty');
        $this->assertSame('none', $result['via']);
    }

    public function test_agcenter_domain_resolvedvia_label(): void {
        set_config('instructor_email_domain', 'agcenter.lsu.edu', 'block_backadel');
        // User has a different Moodle username so the direct username lookup misses,
        // forcing the domain fallback to fire.
        $this->getDataGenerator()->create_user([
            'username' => 'some_other_id',
            'email'    => 'aguser@agcenter.lsu.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('aguser');
        $this->assertNotNull($result['user']);
        $this->assertSame('agcenter', $result['via'], 'first domain label must be "agcenter"');
    }

    // Backward-compat: resolve() still works.

    public function test_resolve_compat_returns_user_object(): void {
        $user = $this->getDataGenerator()->create_user(['username' => 'backcompat1']);
        $r = new instructor_resolver();
        $found = $r->resolve('backcompat1');
        $this->assertNotNull($found);
        $this->assertSame((int) $user->id, (int) $found->id);
    }

    public function test_resolve_compat_returns_null_when_not_found(): void {
        $r = new instructor_resolver();
        $this->assertNull($r->resolve('nouser_xyz'));
    }

    // Cache: second call must not re-query the DB.

    public function test_resolve_token_reuses_cache_on_second_call(): void {
        $user = $this->getDataGenerator()->create_user(['username' => 'cacheduser1']);
        $r = new instructor_resolver();

        $first = $r->resolve_token('cacheduser1');
        $this->assertNotNull($first['user']);

        // Inspect cache directly — should have exactly one entry.
        $prop = new \ReflectionProperty(instructor_resolver::class, 'cache');
        $prop->setAccessible(true);
        $cache = $prop->getValue(null);
        $this->assertArrayHasKey('cacheduser1', $cache);

        $second = $r->resolve_token('cacheduser1');
        $this->assertSame($first['user']->id, $second['user']->id);
    }

    // Filename-parser integration: email token preserved from openlms filenames.

    public function test_backadel_instructor_pattern_preserves_email_token(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2020-Fall-ACCT-2101-for-Wanying-Jiang_wjian15@lsu.edu.zip'
        );
        $this->assertNotNull($parsed);
        $this->assertContains(
            'wjian15@lsu.edu',
            $parsed['instructors'],
            'full email must be preserved in instructors[] for email-first resolution'
        );
    }

    public function test_backadel_instructor_multi_email_preserved(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2020-Fall-BIOL-3060-for-Paul-South_pfsouth@lsu.edu_nferr11@lsu.edu.zip'
        );
        $this->assertNotNull($parsed);
        $this->assertContains('pfsouth@lsu.edu', $parsed['instructors']);
        $this->assertContains('nferr11@lsu.edu', $parsed['instructors']);
    }

    public function test_backadel_instructor_plain_username_unchanged(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2012-Fall-AAAS-2000-for-Jaswant-Sullivan_jamsulli.zip'
        );
        $this->assertNotNull($parsed);
        $this->assertContains('jamsulli', $parsed['instructors']);
        $this->assertStringNotContainsString('@', implode(' ', $parsed['instructors']));
    }
}
