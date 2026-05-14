# Bug-003 Fix Plan — Course Backups Restore button: route to native Moodle restore wizard

**Date:** 2026-05-08
**Bug:** [`bug-003.md`](./bug-003.md)
**Affected file:** `blocks/backadel/restore.php` (Restore button URL builder, lines 145–157)
**Complexity:** **S–M** (single file change for the happy path; small lib touch-up if we want a reusable helper)

---

## 1. Background — what's actually broken

When an admin opens the Course Backups admin page (`/blocks/backadel/restore.php`) **with no `id` URL parameter**:

```php
// blocks/backadel/restore.php:30
$restorecourseid = optional_param('id', SITEID, PARAM_INT);
```

`$restorecourseid` becomes `1` (SITEID). The Restore button (lines 145–157) embeds that `id=1` into a Simple Restore URL (`/blocks/simple_restore/list.php?id=1&name=catalogue&action=choosefile&fileid=…&sesskey=…`). Simple Restore's `list.php` short-circuits the file picker (because `action=choosefile` + `name=catalogue` + `fileid` are all set), calls `prep_restore()`, and lands the admin on a "**Overwrite current course**" confirm page targeting the **site course**.

Confirming would overwrite the front page. This is the worst possible default.

The implementation plan (§3.4) explicitly says the Restore button should route to **Moodle's native restore wizard**, where the admin chooses the destination during the wizard flow — there is no implicit "current course". This bug exists because the original wiring took the Simple Restore shortcut path designed for teachers restoring into their own course.

---

## 2. Intended UX flow

Click sequence after the fix:

1. Admin lands on `/blocks/backadel/restore.php` from the admin nav (no `id` param).
2. Admin browses by year, sees a list of catalogue rows with badges (teaching / blueprint / other).
3. Admin clicks **Restore** on a row.
4. Server-side handler:
   a. Resolves the catalogue row → absolute path on disk (via `backadel_resolve_path()`).
   b. Verifies `is_readable()` on the absolute path; if not, redirects back with a localised error notification (no draft staging, no redirect to /backup/restore.php).
   c. Stages the file as a `stored_file` in the **caller's user file area** so Moodle's native restore confirm stage can extract it.
   d. Redirects to Moodle's native restore confirm stage URL (`/backup/restore.php?contextid=<system_context_id>&pathnamehash=…&contenthash=…`).
5. Moodle's native wizard takes over: admin sees the standard "Confirm → Destination → Settings → Schema → Review → Process" flow, where **Destination** lets them pick "New course" / "Existing course" — never an implicit site-course target.

---

## 3. Important correction to the original §3.4 spec

The implementation plan §3.4 says:

> Copy file to user draft area → redirect to
> `/backup/restore.php?contextid=...&component=user&filearea=draft&itemid=...`

**That URL shape is wrong.** Moodle's native `/backup/restore.php` (read at `backup/restore.php:35-43`) only accepts:

- `contextid` (required)
- `stage` (optional, default = `STAGE_CONFIRM`)
- and at confirm stage, `restore_ui_stage_confirm::__construct()` (`backup/util/ui/restore_ui_stage.class.php:270-283`) requires **either**:
  - `filename=<file in $CFG->backuptempdir>` — file already extracted to disk in the backup temp dir, **or**
  - `pathnamehash=<hash> & contenthash=<hash>` — refers to a `stored_file` in `file_storage`.

It does **not** read `component`, `filearea`, or `itemid`. The `/backup/restorefile.php` page (the file-picker tree-view chooser) does, but that's a different page used to *select* a backup file, not start a restore from a known source.

The correct handoff is therefore one of these two shapes:

**Option A — `stored_file` + `pathnamehash`/`contenthash` (recommended):**

```
/backup/restore.php?contextid=<ctxid>&pathnamehash=<sha1>&contenthash=<sha1>
```

This is the path `/backup/restorefile.php` itself uses internally when a user clicks Restore on a backup row in the file tree (see `backup/restorefile.php:99-104`). The catalogue file gets stored as a real `stored_file` (e.g., in user backup area) and Moodle treats it identically to a "user backup" backup.

**Option B — copy to backup temp dir + `filename`:**

```
/backup/restore.php?contextid=<ctxid>&filename=<random-hash>
```

Where `<random-hash>` is a freshly-generated `restore_controller::get_tempdir_name()` token whose file already lives at `make_backup_temp_directory('') . '/' . <random-hash>`. This is the path `/backup/restorefile.php` uses for *uploaded* files (`backup/restorefile.php:130-136`).

**Recommendation: Option A.** Reasons:

- Cleaner: no orphan files in `$CFG->backuptempdir` if the admin abandons the wizard mid-flow (Moodle's existing `backup_helper::delete_old_backup_dirs()` does eventually clean these, but it's lazy).
- Reuses the same pathnamehash route the rest of the UI uses, which has been battle-tested.
- Avoids needing to copy the entire file before redirecting (still need to copy from disk into Moodle's file storage, but that's atomic).
- Faster on subsequent retries if the admin starts and abandons the wizard repeatedly: same content hash → file dedup'd in `filedir`.

Note: We are **not** using draft area (`component=user`, `filearea=draft`). Native restore reads from any `stored_file` reachable via `pathnamehash`. Using **`component=user`, `filearea=backup`** in the user's context (the same area Moodle's "private files / user backups" uses) is cleanest: cleanup is the user's problem, the file shows up in their existing "Manage backup files" UI, and it survives wizard abandonment for retry.

---

## 4. Code change — file-by-file

### 4.1 `blocks/backadel/restore.php` (primary change)

**Currently** (lines 145–157):

```php
$restoreurl = new moodle_url('/blocks/simple_restore/list.php', [
    'id'         => $restorecourseid,
    'name'       => 'catalogue',
    'action'     => 'choosefile',
    'restore_to' => $restoreto,
    'fileid'     => $row->id,
    'sesskey'    => sesskey(),
]);
$actions = html_writer::link(
    $restoreurl,
    get_string('coursebackups_restore', 'block_backadel'),
    ['class' => 'btn btn-sm btn-primary']
);
```

**New**: each Restore button POSTs (or GETs with sesskey) to the same `restore.php` page with an `action=stage&fileid=<catalogue_id>` parameter. The page handles the action server-side before the listing renders, then redirects.

**Plan:**

1. Drop `$restorecourseid` and `$restoreto` reads (lines 30–31). Stop accepting `id` and `restore_to` URL params for the listing view — they are vestigial Simple Restore concepts.

2. Add an action dispatcher near the top of the file, between the `require_capability(...)` call (line 35) and the catalogue table-existence check (line 38):

   ```php
   $action = optional_param('action', '', PARAM_ALPHA);
   $fileid = optional_param('fileid', 0, PARAM_INT);

   if ($action === 'stage' && $fileid > 0) {
       require_sesskey();
       \block_backadel\local\restore_handoff::stage_and_redirect($fileid, $context);
       // stage_and_redirect() never returns — it calls redirect().
   }
   ```

3. Rebuild the Restore button to point at `restore.php?action=stage&fileid=<id>&sesskey=...`:

   ```php
   $restoreurl = new moodle_url('/blocks/backadel/restore.php', [
       'action'  => 'stage',
       'fileid'  => $row->id,
       'year'    => $year,            // preserve year context for redirect-back
       'sesskey' => sesskey(),
   ]);
   $actions = html_writer::link(
       $restoreurl,
       get_string('coursebackups_restore', 'block_backadel'),
       ['class' => 'btn btn-sm btn-primary']
   );
   ```

4. Remove the hidden `id` field from the year-selector form (line 105) since `id` is no longer used.

### 4.2 New helper class — `blocks/backadel/classes/local/restore_handoff.php`

Encapsulates the staging logic. Single static method called from `restore.php` — easy to unit test.

```php
<?php
namespace block_backadel\local;

use context;
use context_user;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class restore_handoff {

    /**
     * Stage a catalogue backup as a stored_file in the current user's backup area
     * and redirect to Moodle's native restore wizard confirm stage.
     *
     * Never returns: always calls redirect() (success or error path).
     *
     * @param int     $catalogueid  block_backadel_catalogue.id
     * @param context $errorcontext Context to send the user back to on failure
     */
    public static function stage_and_redirect(int $catalogueid, context $errorcontext): void {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/blocks/simple_restore/lib.php');

        // 1. Resolve absolute path via existing helper.
        $abspath = backadel_resolve_path($catalogueid);

        $listingurl = new moodle_url('/blocks/backadel/restore.php');

        if ($abspath === '' || !is_readable($abspath)) {
            redirect(
                $listingurl,
                get_string('coursebackups_restore_filemissing', 'block_backadel'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        // 2. Capability: native restore wizard will re-check at the destination
        //    context, but gate here too so we don't stage a file the user
        //    can never use.
        $syscontext = \context_system::instance();
        require_capability('moodle/restore:restorecourse', $syscontext);

        // 3. Copy the catalogue file into the user's backup file area.
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $filename = basename($abspath);

        // Ensure unique filename in the user backup area to avoid collisions
        // on repeat clicks. file_storage::create_file_from_pathname() throws
        // if (contextid, component, filearea, itemid, filepath, filename)
        // collides; we use itemid=0 (Moodle's convention for user/backup) but
        // adjust filename if needed.
        $filename = self::unique_user_backup_filename($fs, $usercontext->id, $filename);

        $filerecord = (object) [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'backup',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
            'userid'    => $USER->id,
        ];

        try {
            $storedfile = $fs->create_file_from_pathname($filerecord, $abspath);
        } catch (\Throwable $e) {
            redirect(
                $listingurl,
                get_string('coursebackups_restore_stagefailed', 'block_backadel'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        // 4. Redirect to native Moodle restore wizard at the system context.
        //    The wizard's Destination stage will let the admin choose
        //    "new course" / "existing course".
        $restoreurl = new moodle_url('/backup/restore.php', [
            'contextid'    => $syscontext->id,
            'pathnamehash' => $storedfile->get_pathnamehash(),
            'contenthash'  => $storedfile->get_contenthash(),
        ]);
        redirect($restoreurl);
    }

    /**
     * Append " (n)" before extension if filename collides in user backup area.
     */
    private static function unique_user_backup_filename(
        \file_storage $fs, int $usercontextid, string $filename
    ): string {
        if (!$fs->file_exists($usercontextid, 'user', 'backup', 0, '/', $filename)) {
            return $filename;
        }
        $info = pathinfo($filename);
        $base = $info['filename'] ?? 'backup';
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        for ($i = 1; $i < 1000; $i++) {
            $candidate = "{$base} ({$i}){$ext}";
            if (!$fs->file_exists($usercontextid, 'user', 'backup', 0, '/', $candidate)) {
                return $candidate;
            }
        }
        // Fallback: timestamp suffix.
        return "{$base}-" . time() . $ext;
    }
}
```

### 4.3 Language strings — `blocks/backadel/lang/en/block_backadel.php`

Add two new strings:

```php
$string['coursebackups_restore_filemissing'] = 'The selected backup file could not be located on disk. The catalogue row may be stale or the file may have been moved or deleted.';
$string['coursebackups_restore_stagefailed'] = 'The backup file was found on disk but could not be staged for restore. Check filesystem permissions on the Moodle data directory.';
```

### 4.4 Tests — `blocks/backadel/tests/local/restore_handoff_test.php` (new)

PHPUnit coverage for `restore_handoff::stage_and_redirect()`:

| Case | Expectation |
|---|---|
| Happy path: catalogue row + readable file on disk | `redirect()` called with `/backup/restore.php?contextid=<sys>&pathnamehash=<sha1>&contenthash=<sha1>`; stored_file exists at `(usercontext, 'user', 'backup', 0, '/', filename)` |
| Catalogue id not found (`backadel_resolve_path()` returns `''`) | redirect to listing with `coursebackups_restore_filemissing` notification |
| Path returned but `is_readable()` false | redirect to listing with `coursebackups_restore_filemissing` notification |
| Two clicks in a row on same row | second call uses unique filename `name (1).mbz` (no exception) |
| User without `moodle/restore:restorecourse` system cap | `required_capability_exception` thrown |

**Note:** `redirect()` calls `die()` in normal Moodle, but in PHPUnit it throws `moodle_exception('redirecterrordetected')` when `$CFG->prevent_redirect_in_tests` is set, which our tests can catch and inspect. See `lib/moodlelib.php::redirect()`.

---

## 5. Existing infrastructure that gets reused

| Need | Existing helper | Location |
|---|---|---|
| Resolve catalogue id → absolute on-disk path | `backadel_resolve_path(int $catalogue_id): string` | `blocks/simple_restore/lib.php:37-65` |
| Apply `block_backadel/catalogue_path_prefix` rewrite | (built into `backadel_resolve_path()`) | same |
| Generate unused draft itemid | `file_get_unused_draft_itemid()` (only needed if we go with draft area; we do not) | `lib/filelib.php:372` |
| Add a file from a disk path into Moodle file storage | `file_storage::create_file_from_pathname($filerecord, $pathname): stored_file` | `lib/filestorage/file_storage.php:1280` |
| Compute `pathnamehash` / `contenthash` for native restore link | `stored_file::get_pathnamehash()`, `stored_file::get_contenthash()` | `lib/filestorage/stored_file.php` |
| Show the "Confirm restore" wizard from a stored_file | Native: `/backup/restore.php?contextid=…&pathnamehash=…&contenthash=…` (precedent: `backup/restorefile.php:99-104`) | core |

**No `catalogue_resolver.php` class exists yet** — the implementation plan §3.3 mentioned `simple_restore_utils::resolve_absolute_path()` but the actual landed code is the procedural `backadel_resolve_path()` in `blocks/simple_restore/lib.php`. We use that. (If we want to clean up class structure later, that's an MD-2189 polish task, not a bug fix.)

**`prep_restore()` in `blocks/simple_restore/lib.php:312-344` does NOT do draft-file staging.** It copies the file into `$CFG->backuptempdir/<random-hash>` and returns the random-hash filename. That's the Simple Restore path — it skips the native wizard's destination-picker step entirely because Simple Restore presupposes a target course is already chosen. **We do not call `prep_restore()`** in the fix; that's exactly the codepath that caused the bug.

---

## 6. Edge cases

| Edge case | Handling |
|---|---|
| Resolved abs path is empty (`backadel_resolve_path()` returned `''` because catalogue id missing) | Redirect to listing with `coursebackups_restore_filemissing` notification |
| Resolved abs path exists but not readable (perms) | Same — redirect with `coursebackups_restore_filemissing` |
| Path is on FTP / remote (not locally mounted) | `is_readable()` returns false → handled as above. Document in admin help text that `block_backadel/catalogue_path_prefix` must rewrite to a locally-mounted path. (Per §3.3 of impl plan: admin chooses mount strategy or prefix-rewrite.) Consider future-work issue: pull file via curl/FTP first into a temp file. **Out of scope for this bug.** |
| File too large to fit in `filedir` (rare but possible — pre-2018 backups can be 5+ GB) | `create_file_from_pathname()` will copy the whole file; no special handling. If it fails (disk full), the try/catch redirects with `coursebackups_restore_stagefailed`. **Long-term:** consider a streaming approach or symlink trick, but not required for MVP. |
| User clicks Restore twice on the same row before completing wizard | `unique_user_backup_filename()` appends `(1)`, `(2)` so both end up as separate stored_files. Old ones are eventually cleared by user via "Manage backup files" UI. |
| User lacks `moodle/restore:restorecourse` at system context | `require_capability()` throws → standard Moodle 403 page. Acceptable for MVP. (Could pre-check before showing the Restore button to hide it for these users, but `block/backadel:managebackups` and `moodle/restore:restorecourse` are both manager-default so this is a paranoia case.) |
| Catalogue row marked `status='missing'` (per §3.3 step 4 of impl plan) | Currently no-op: `backadel_resolve_path()` doesn't filter on status. **Recommended follow-up:** filter the SQL in `restore.php:46-84` to `WHERE cat.status != 'missing'`, or render those rows with a disabled-button + tooltip. **Out of scope for this bug — track separately.** |
| Catalogue row's `filepath`/`filepath_full` is a relative path that resolves under `$CFG->dataroot` but the file is not actually a backup mbz | The native restore wizard's confirm stage will throw `restore_ui_exception('invalidrestorefile')` (see `backup/util/ui/restore_ui_stage.class.php:294-296`). User sees the standard error page. Acceptable. |
| `$CFG->backuptempdir` filling up with abandoned wizard sessions | Not our problem with the stored_file approach (Option A). Native cleanup task `\backup_cron_automated_helper` handles this. |
| Direct URL `/blocks/backadel/restore.php?action=stage&fileid=X` with no sesskey | `require_sesskey()` rejects it with the standard "A required parameter (sesskey) was missing" exception. |
| Direct URL with valid sesskey but no `fileid` (or `fileid=0`) | The `if ($action === 'stage' && $fileid > 0)` guard skips action handling and falls through to the listing render. No error. |

---

## 7. Why not use draft area (`component=user, filearea=draft`)?

The original §3.4 plan said draft area; we deviate because:

1. **Native `/backup/restore.php` doesn't read draft URL params.** Drafts work for forms (filemanager / filepicker elements). The restore wizard doesn't use a filemanager element to receive its file — it gets a `pathnamehash` of an arbitrary `stored_file`.
2. **Drafts have no UI surface.** If the wizard fails or the admin abandons it, the file is invisible to them — they can't retry from "Manage backup files". Putting it in `user/backup` makes it visible there.
3. **Drafts are aggressively cleaned up.** Moodle's draft-cleanup task (`\repository::delete_old_draft_files()`) drops draft files older than ~4h. The native restore wizard can take longer than that for large backups (precheck, etc).
4. **`pathnamehash` works regardless of file area.** The user/backup area is not special — Moodle's restore wizard reads the file via `$fs->get_file_by_hash()` and doesn't care about its component/filearea.

---

## 8. Migration / deployment notes

- **No DB migration needed.** No schema changes.
- **No string-file regeneration needed** beyond the two new strings in §4.3.
- **Cache purge:** none needed (lang strings refresh on next request automatically; if not, `purge_caches`).
- **Backout:** revert `blocks/backadel/restore.php` and delete the new helper class. Single-commit revert is safe.
- **QA scenarios:**
  1. Open Course Backups page from admin nav (no `id` param). Click Restore on a teaching backup. Expect: Moodle's native restore wizard at `/backup/restore.php`, **NOT** "Overwrite current course".
  2. Manipulate a catalogue row to point at a non-existent path. Click Restore. Expect: redirected back to listing with red error notification.
  3. Click Restore twice in quick succession on same row (open in two tabs). Both should work, second creates `name (1).mbz` in user/backup.
  4. As a `coursecreator` user with `block/backadel:managebackups` but without `moodle/restore:restorecourse` at system: expect 403 on click. (May need to add system-level role override in QA env.)

---

## 9. Complexity & scope summary

- **Files modified:** 1 (`blocks/backadel/restore.php`)
- **Files added:** 1–2 (helper class; PHPUnit test file)
- **Lang strings added:** 2
- **Schema changes:** 0
- **Capability changes:** 0 (uses existing `block/backadel:managebackups` for page access; relies on core `moodle/restore:restorecourse` at system context for the restore step)
- **Risk:** Low. The fix is purely about where the Restore button points; the native Moodle restore wizard is the well-tested path.
- **Estimate:** **S** for happy path + error redirect; **M** if we land the PHPUnit suite in the same PR.

---

## 10. Out of scope (file as follow-ups)

1. **Filter `status='missing'` rows from listing SQL** (§3.3 step 4 of impl plan).
2. **Pre-check `moodle/restore:restorecourse` cap before rendering the Restore button** (currently we let core throw on click).
3. **Streaming / symlink for very large backups** instead of `create_file_from_pathname()` copy.
4. **Refactor `backadel_resolve_path()` into a `\block_backadel\local\catalogue_resolver` class** for cleaner namespacing (the impl plan §3.3 referred to a class; we kept the existing procedural function for minimal-touch fix).
5. **Remote/FTP path support** — currently relies on locally-mounted paths via `catalogue_path_prefix`. Long term: pluggable resolver that can pull from FTP into a temp file before staging.
