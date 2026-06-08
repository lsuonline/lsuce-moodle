# Course Backups (restore proxy)

**Course Backups** lists catalogue files for a selected **calendar year**, grouped and sorted for readability, and hands off to the **Simple Restore** confirm flow when you choose **Restore**.

## Year selector

The dropdown is built from **distinct years** present in the catalogue. Submitting the form changes `restore.php?year=####` and reloads the table. If there are no years (empty catalogue), you will see an empty-state message instead.

## Type badges

Each row shows a **Teaching**, **Blueprint**, or **Other** badge when course-type resolution succeeds:

- **Teaching** — Regular instructional offering (default assumption).
- **Blueprint** — Name matched **blueprint keywords** from settings (master shells, templates, etc.).
- **Other** — Everything else or unresolved.

Badges help operators prioritize restores during incidents.

## Restore confirm flow

Clicking **Restore** on a row:

1. Stages the archive file into Moodle's backup temp directory.
2. Redirects to `restore.php` which shows a **confirmation page** — the course name and restore type are displayed and you must click **Continue** to proceed.
3. On confirmation, the restore executes. If **async restore** is enabled by the administrator, the job is queued and you will not wait for it to complete on-screen; a progress indicator appears instead.
4. On success (sync mode), a success notification is shown and a **Continue** button returns you to the course.

If the staged file is empty (0 bytes), the restore is blocked with an error before it reaches the confirm step.

## Missing file messages

If the UI reports the file missing:

1. Confirm the row still shows **available** in **Catalogue**.
2. Check **path prefix rewrite** settings if data was migrated between servers.
3. Run **migration** after fixing storage mounts.
4. Look for typos in manual moves/renames outside Backadel.

If staging fails but the file exists, investigate **permissions** on `moodledata` and temp directories — the error notice points to staging problems.

## Permissions

You need **`block/simple_restore:canrestore`** in the course context (or **`block/simple_restore:canrestorearchive`** at system context for archive-server mode) to complete the workflow end-to-end.

## Sorting

Within a year, teaching courses typically appear **before** blueprint shells when both exist — useful when scrolling long lists during bulk operations.

## Related topics

- **Catalogue** — Deep filtering when you do not know the year yet.
- **Course Search** — Find the **live** course counterpart, if it still exists.

Always test a **restore into a scratch course** when validating a brittle archive before touching production sections.
