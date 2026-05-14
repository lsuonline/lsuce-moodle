# Bug-008 — search.php: `search_instructions` lang string cache miss

**Page:** `blocks/backadel/search.php`
**Error:** `Invalid get_string() identifier: 'search_instructions' or component 'block_backadel'`
**Severity:** Blocker (page throws Whoops exception on every load)

## Root cause

`search.php:68` calls `get_string('search_instructions', 'block_backadel')`. The string was added to `lang/en/block_backadel.php` by the Opus review agent **after** the pre-review cache purge. The lang string cache was never purged again, so Moodle served a stale string cache that did not include the new key.

## Fix

Run `purge_all_caches()` (or `./run-docker-exec.sh php admin/cli/purge_caches.php`) after any change to a lang file. Applied immediately via PHP CLI purge.

**No code change required** — the string `$string['search_instructions']` is correctly present in `lang/en/block_backadel.php:78`.

## Prevention

Always purge caches after every agent wave that touches lang files. Add to deployment checklist.
