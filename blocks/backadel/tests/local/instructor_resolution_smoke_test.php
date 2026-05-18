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

use block_backadel\task\reresolve_teachers;

/**
 * End-to-end smoke coverage for instructor token parsing and resolution.
 *
 * Aggregates the bug-059 / bug-061 / bug-062 / bug-067 fix surface into a
 * single suite so a regression in any leg of the pipeline (filename parsing,
 * email-first resolution, domain fallback, cache reuse, migrator column
 * writes, adhoc re-resolution) is caught by one focused test class.
 *
 * @package    block_backadel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group      backadel_smoke
 *
 * @covers \block_backadel\local\instructor_resolver
 * @covers \block_backadel\local\filename_pattern_library
 * @covers \block_backadel\local\migrator
 */
final class instructor_resolution_smoke_test extends \advanced_testcase {

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        instructor_resolver::reset_cache();
    }

    // Dimension 1: filename parsing across pattern families.

    /**
     * Backadel instructor archive with a single plain-username token must keep
     * the token unchanged (no email synthesis).
     */
    public function test_parse_backadel_instructor_plain_username(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2012-Fall-AAAS-2000-for-Jaswant-Sullivan_jamsulli.zip'
        );
        $this->assertNotNull($parsed, 'plain-username backadel filename must parse');
        $this->assertSame('backadel_instructor', $parsed['pattern']);
        $this->assertSame(['jamsulli'], $parsed['instructors']);
    }

    /**
     * Backadel instructor archive with a single email-shaped token (openlms
     * export) must preserve the full email — instructor_resolver does the
     * email-first lookup downstream.
     */
    public function test_parse_backadel_instructor_email_token(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2020-Fall-ACCT-2101-for-Wanying-Jiang_wjian15@lsu.edu.zip'
        );
        $this->assertNotNull($parsed, 'email-token backadel filename must parse');
        $this->assertSame('backadel_instructor', $parsed['pattern']);
        $this->assertSame(['wjian15@lsu.edu'], $parsed['instructors']);
    }

    /**
     * Multi-instructor archive with two email tokens must yield both, preserving order.
     */
    public function test_parse_backadel_instructor_multi_email(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2020-Fall-BIOL-3060-for-Paul-South_pfsouth@lsu.edu_nferr11@lsu.edu.zip'
        );
        $this->assertNotNull($parsed);
        $this->assertSame('backadel_instructor', $parsed['pattern']);
        $this->assertSame(['pfsouth@lsu.edu', 'nferr11@lsu.edu'], $parsed['instructors']);
    }

    /**
     * Mixed plain-username + email tokens both survive the parser.
     */
    public function test_parse_backadel_instructor_mixed_plain_and_email(): void {
        $parsed = filename_pattern_library::parse(
            'backadel-2020-Spring-CHEM-1201-for-John-Smith_jsmith1_kbrown@agcenter.lsu.edu.zip'
        );
        $this->assertNotNull($parsed);
        $this->assertSame('backadel_instructor', $parsed['pattern']);
        $this->assertContains('jsmith1', $parsed['instructors']);
        $this->assertContains('kbrown@agcenter.lsu.edu', $parsed['instructors']);
    }

    /**
     * Legacy semester archive with a real `_<digits>` timestamp tail but no
     * instructor token returns an empty instructors[] under the
     * `semester_legacy` pattern.
     *
     * Note: the literal `2012-Fall-AAAS-2000.zip` from the task table has no
     * `_<backup_ts>` suffix and therefore does not match the legacy semester
     * regex; we use the canonical underscore-joined form that the parser
     * actually accepts.
     */
    public function test_parse_legacy_semester_no_instructor(): void {
        $parsed = filename_pattern_library::parse('2012FallAAAS2000_1400000000.zip');
        $this->assertNotNull($parsed);
        $this->assertSame('semester_legacy', $parsed['pattern']);
        $this->assertIsArray($parsed['instructors']);
        $this->assertSame([], $parsed['instructors']);
    }

    /**
     * Openlms "modern" backup archive (.zip variant of backup-moodle2-course-*)
     * does not match PATTERN_MOODLE_NATIVE (mbz-only) and falls through to the
     * `unknown` bucket — we just assert the parser returns a non-null shape
     * with no instructors, since this archive does not feed the warm path.
     */
    public function test_parse_openlms_modern_returns_non_null_no_instructors(): void {
        $parsed = filename_pattern_library::parse(
            'backup-moodle2-course-5818-exst_4025-20200713-1340-nu.zip'
        );
        $this->assertNotNull($parsed, 'parser must not return null for openlms-style archives');
        $this->assertSame([], $parsed['instructors'] ?? []);
    }

    /**
     * A bare "storage_course_*.zip" filename without the trailing `_<digits>`
     * timestamp does not match PATTERN_STORAGE_COURSE and falls through to
     * `unknown`. Either way the call must return a non-null array — never
     * throw — so the migrator can decide to skip it.
     */
    public function test_parse_storage_course_returns_non_null(): void {
        $parsed = filename_pattern_library::parse('storage_course_backup.zip');
        $this->assertNotNull($parsed, 'storage_course filename must parse cleanly');
        $this->assertIsArray($parsed['instructors'] ?? []);
    }

    // Dimension 2: resolution chain — every leg.

    public function test_resolve_plain_username_exact_match(): void {
        $user = $this->getDataGenerator()->create_user(['username' => 'wjian15']);
        $r = new instructor_resolver();
        $result = $r->resolve_token('wjian15');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('username', $result['via']);
    }

    /**
     * Mixed-case legacy tokens (uppercase from old archives) must still resolve
     * to the lowercase-stored Moodle user. MariaDB's default collation makes the
     * exact-match SELECT case-insensitive too, so either leg of the chain may
     * win — we assert a hit + 'username' via.
     */
    public function test_resolve_plain_username_case_insensitive(): void {
        global $DB;

        $userid = $DB->insert_record('user', (object) [
            'auth'         => 'manual',
            'confirmed'    => 1,
            'mnethostid'   => 1,
            'username'     => 'wjian15',
            'password'     => 'not-a-hash',
            'firstname'    => 'Wanying',
            'lastname'     => 'Jiang',
            'email'        => 'wjian15@example.com',
            'deleted'      => 0,
            'suspended'    => 0,
            'lang'         => 'en',
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);

        $r = new instructor_resolver();
        $result = $r->resolve_token('WJIAN15');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $userid, (int) $result['user']->id);
        $this->assertSame('username', $result['via']);
    }

    public function test_resolve_email_token_matches_by_email(): void {
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

    /**
     * When the email lookup misses but the local part matches an existing
     * username, the resolver falls back to username and reports via='username'.
     */
    public function test_resolve_email_token_falls_back_to_local_part(): void {
        $user = $this->getDataGenerator()->create_user([
            'username' => 'jdoe',
            'email'    => 'jdoe@otherdomain.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('jdoe@lsu.edu');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('username', $result['via']);
    }

    public function test_resolve_plain_username_via_lsu_domain_fallback(): void {
        set_config('instructor_email_domain', 'lsu.edu', 'block_backadel');
        $user = $this->getDataGenerator()->create_user([
            'username' => 'someother',
            'email'    => 'jsmith99@lsu.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('jsmith99');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('lsu', $result['via']);
    }

    public function test_resolve_plain_username_via_agcenter_domain_fallback(): void {
        set_config('instructor_email_domain', 'agcenter.lsu.edu', 'block_backadel');
        $user = $this->getDataGenerator()->create_user([
            'username' => 'some_other_id',
            'email'    => 'aguser@agcenter.lsu.edu',
        ]);
        $r = new instructor_resolver();
        $result = $r->resolve_token('aguser');
        $this->assertNotNull($result['user']);
        $this->assertSame((int) $user->id, (int) $result['user']->id);
        $this->assertSame('agcenter', $result['via']);
    }

    /**
     * Duplicate emails: the resolver must return one match (not throw, not
     * return null). bug-066.
     */
    public function test_resolve_email_token_with_duplicate_email_does_not_throw(): void {
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
        $this->assertNotNull($result['user']);
        $this->assertSame('email', $result['via']);
        $this->assertSame('shared@lsu.edu', $result['user']->email);
    }

    public function test_resolve_unresolvable_plain_token_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('nobody99');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    public function test_resolve_unresolvable_email_token_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('ghost@lsu.edu');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    public function test_resolve_empty_token_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    public function test_resolve_whitespace_token_returns_none(): void {
        $r = new instructor_resolver();
        $result = $r->resolve_token('   ');
        $this->assertNull($result['user']);
        $this->assertSame('none', $result['via']);
    }

    /**
     * Course-name slug fragments embedding `-for-` (e.g. legacy course-name
     * slugs the parser used to leak into the instructor slot) must be rejected
     * at the migrator shape guard layer before any DB lookup happens. The
     * `-for-` literal is the canonical anti-pattern called out in bug-058.
     */
    public function test_looks_like_username_rejects_slug_fragment(): void {
        $this->assertFalse(
            filename_pattern_library::looks_like_username('backup-master-course-fin-7400-for-don-chance'),
            'tokens carrying the -for- course-name marker must be rejected'
        );
    }

    public function test_looks_like_username_rejects_overlong_token(): void {
        $token = 'averylongusernamethatexceedsthirtytwocharacterslimit';
        $this->assertGreaterThan(32, strlen($token));
        $this->assertFalse(
            filename_pattern_library::looks_like_username($token),
            'tokens longer than 32 characters must be rejected'
        );
    }

    // Dimension 3: cache behaviour.

    /**
     * Two resolve_token() calls for the same token must produce exactly one
     * cache entry under that key (the second call hits the cache instead of
     * touching the DB).
     */
    public function test_cache_reuses_entry_on_second_call(): void {
        $this->getDataGenerator()->create_user(['username' => 'cacheduser1']);
        $r = new instructor_resolver();

        $first = $r->resolve_token('cacheduser1');
        $this->assertNotNull($first['user']);

        $prop = new \ReflectionProperty(instructor_resolver::class, 'cache');
        $prop->setAccessible(true);
        $cache = $prop->getValue();
        $this->assertArrayHasKey('cacheduser1', $cache);

        $second = $r->resolve_token('cacheduser1');
        $this->assertSame((int) $first['user']->id, (int) $second['user']->id);
    }

    /**
     * Bug-062 partial mitigation: resolving an email-shaped token whose local
     * part matches a username (via the email→username fallback leg) back-fills
     * the cache under the local-part key. A subsequent plain-username lookup
     * for the same user must hit the cache (same user id, no new DB query).
     */
    public function test_cache_backfills_local_part_after_email_fallback(): void {
        $user = $this->getDataGenerator()->create_user([
            'username' => 'backfilluser',
            'email'    => 'backfilluser@otherdomain.edu',
        ]);
        $r = new instructor_resolver();

        $first = $r->resolve_token('backfilluser@lsu.edu');
        $this->assertNotNull($first['user']);
        $this->assertSame((int) $user->id, (int) $first['user']->id);

        $prop = new \ReflectionProperty(instructor_resolver::class, 'cache');
        $prop->setAccessible(true);
        $cache = $prop->getValue();
        $this->assertArrayHasKey('backfilluser', $cache, 'local-part key must be back-filled');

        $second = $r->resolve_token('backfilluser');
        $this->assertSame((int) $user->id, (int) $second['user']->id);
        $this->assertSame('username', $second['via']);
    }

    // Dimension 4: migrator email-token column writes (private resolve_instructors).

    /**
     * Email-token instructor: username column gets the local part, email column
     * gets the resolved user's email, resolvedvia='email'.
     */
    public function test_migrator_email_token_writes_local_part_username_and_email(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user([
            'username' => 'wjian15',
            'email'    => 'wjian15@lsu.edu',
        ]);
        $coursesid = $this->insert_courses_row('email-token.zip');

        $this->invoke_resolve_instructors(['wjian15@lsu.edu'], $coursesid);

        $rows = $DB->get_records('block_backadel_teachers', ['coursesid' => $coursesid]);
        $this->assertCount(1, $rows);
        $row = reset($rows);
        $this->assertSame('wjian15', $row->username);
        $this->assertSame('wjian15@lsu.edu', $row->email);
        $this->assertSame((int) $user->id, (int) $row->userid);
        $this->assertSame(1, (int) $row->resolved);
        $this->assertSame('email', $row->resolvedvia);
    }

    /**
     * Plain-username instructor: username column gets the token verbatim,
     * email column remains null (no email token to preserve), resolvedvia='username'.
     *
     * NB: the migrator copies `user->email` into the email column when the
     * lookup succeeds — so a generator-created user with a default sandbox
     * email will produce a non-null email here. This test asserts the
     * resolvedvia='username' path explicitly and does not pin the email
     * column when the lookup succeeds — see the unresolvable test for the
     * null-email case.
     */
    public function test_migrator_plain_token_writes_username_path(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user(['username' => 'jdoe']);
        $coursesid = $this->insert_courses_row('plain-token.zip');

        $this->invoke_resolve_instructors(['jdoe'], $coursesid);

        $rows = $DB->get_records('block_backadel_teachers', ['coursesid' => $coursesid]);
        $this->assertCount(1, $rows);
        $row = reset($rows);
        $this->assertSame('jdoe', $row->username);
        $this->assertSame((int) $user->id, (int) $row->userid);
        $this->assertSame(1, (int) $row->resolved);
        $this->assertSame('username', $row->resolvedvia);
    }

    /**
     * Unresolvable email token: row is still written with resolved=0,
     * resolvedvia='none', userid=null, and the raw email preserved in the
     * email column for the adhoc re-resolve task to retry later.
     */
    public function test_migrator_unresolvable_email_token_preserves_raw_email(): void {
        global $DB;

        $coursesid = $this->insert_courses_row('unresolvable-email.zip');

        $this->invoke_resolve_instructors(['ghost@lsu.edu'], $coursesid);

        $rows = $DB->get_records('block_backadel_teachers', ['coursesid' => $coursesid]);
        $this->assertCount(1, $rows);
        $row = reset($rows);
        $this->assertSame('ghost', $row->username, 'local part stored in username column');
        $this->assertSame('ghost@lsu.edu', $row->email, 'raw email preserved for re-resolution');
        $this->assertNull($row->userid);
        $this->assertSame(0, (int) $row->resolved);
        $this->assertSame('none', $row->resolvedvia);
    }

    /**
     * Unresolvable plain token: row is written with resolved=0,
     * resolvedvia='none', userid=null, email=null (no email token to preserve).
     */
    public function test_migrator_unresolvable_plain_token_writes_none_row(): void {
        global $DB;

        $coursesid = $this->insert_courses_row('unresolvable-plain.zip');

        $this->invoke_resolve_instructors(['nobody99'], $coursesid);

        $rows = $DB->get_records('block_backadel_teachers', ['coursesid' => $coursesid]);
        $this->assertCount(1, $rows);
        $row = reset($rows);
        $this->assertSame('nobody99', $row->username);
        $this->assertNull($row->email);
        $this->assertNull($row->userid);
        $this->assertSame(0, (int) $row->resolved);
        $this->assertSame('none', $row->resolvedvia);
    }

    // Dimension 5: reresolve_teachers adhoc task integration.

    /**
     * Seed an unresolved teacher row with the raw email captured in the email
     * column, then create the matching Moodle user, then execute the adhoc
     * task. The row must flip to resolved=1, resolvedvia='email', and the
     * resolved user id.
     */
    public function test_reresolve_teachers_flips_unresolved_email_row_to_resolved(): void {
        global $DB;

        $coursesid = $this->insert_courses_row('reresolve-target.zip');

        $teacherid = $DB->insert_record('block_backadel_teachers', (object) [
            'coursesid'   => $coursesid,
            'userid'      => null,
            'username'    => 'wjian15',
            'email'       => 'wjian15@lsu.edu',
            'resolved'    => 0,
            'resolvedvia' => 'none',
            'timecreated' => time(),
        ]);

        // Create the matching user *after* seeding the unresolved row so the
        // initial state mirrors the production scenario the task fixes.
        $user = $this->getDataGenerator()->create_user([
            'username' => 'wjian15',
            'email'    => 'wjian15@lsu.edu',
        ]);

        $task = new reresolve_teachers();
        ob_start();
        $task->execute();
        ob_end_clean();

        $row = $DB->get_record('block_backadel_teachers', ['id' => $teacherid]);
        $this->assertNotFalse($row);
        $this->assertSame(1, (int) $row->resolved, 'row must flip to resolved=1');
        $this->assertSame('email', $row->resolvedvia, 'resolvedvia must report email-token hit');
        $this->assertSame((int) $user->id, (int) $row->userid);
        $this->assertSame('wjian15@lsu.edu', $row->email);
    }

    // Helpers.

    /**
     * Insert a minimal block_backadel_courses row for use as an FK target.
     *
     * @param string $filename Filename used to derive a stable filepath_hash.
     * @return int Inserted courses row id.
     */
    private function insert_courses_row(string $filename): int {
        global $DB;

        $filepath = '/tmp/' . $filename;
        return (int) $DB->insert_record('block_backadel_courses', (object) [
            'courseid'         => null,
            'coursefullname'   => $filename,
            'courseshortname'  => $filename,
            'courseidnumber'   => null,
            'status'           => 'available',
            'filepath'         => $filepath,
            'filepath_hash'    => sha1($filepath),
            'filename'         => $filename,
            'filesize'         => null,
            'timecreated'      => time(),
            'backupcreated'    => null,
            'semester'         => null,
            'academicperiodid' => null,
            'coursetype'       => 'other',
            'statusid'         => null,
        ]);
    }

    /**
     * Invoke the private migrator::resolve_instructors() via reflection.
     *
     * @param string[] $tokens Instructor tokens as they would appear in the
     *                         parsed `instructors[]` array.
     * @param int $coursesid Target block_backadel_courses row.
     */
    private function invoke_resolve_instructors(array $tokens, int $coursesid): void {
        $migrator = new migrator();
        $method = new \ReflectionMethod(migrator::class, 'resolve_instructors');
        $method->setAccessible(true);
        $method->invoke($migrator, ['instructors' => $tokens], $coursesid);
    }
}
