# Course Backups (restore proxy)

**Course Backups** lists catalogue files for a selected **calendar year**, grouped and sorted for readability, and hands off to Moodle’s **native restore** wizard when you choose **Restore**.

## Year selector

The dropdown is built from **distinct years** present in the catalogue. Submitting the form changes `restore.php?year=####` and reloads the table. If there are no years (empty catalogue), you will see an empty-state message instead.

## Type badges

Each row shows a **Teaching**, **Blueprint**, or **Other** badge when course-type resolution succeeds:

- **Teaching** — Regular instructional offering (default assumption).
- **Blueprint** — Name matched **blueprint keywords** from settings (master shells, templates, etc.).
- **Other** — Everything else or unresolved.

Badges help operators prioritize restores during incidents.

## Restore hand-off

Clicking **Restore** validates the file, stages it if possible, and redirects into Moodle’s standard **restore** UI. You will choose destination category, merge options, and user data scope there—Backadel does not bypass those checks.

## Missing file messages

If the UI reports the file missing:

1. Confirm the row still shows **available** in **Catalogue**.
2. Check **path prefix rewrite** settings if data was migrated between servers.
3. Run **migration** after fixing storage mounts.
4. Look for typos in manual moves/renames outside Backadel.

If staging fails but the file exists, investigate **permissions** on `moodledata` and temp directories—the error notice points to staging problems.

## Permissions

You need **`block/backadel:managebackups`** (and standard restore capabilities in the target course context) to complete the workflow end-to-end.

## Sorting

Within a year, teaching courses typically appear **before** blueprint shells when both exist—useful when scrolling long lists during bulk operations.

## Related topics

- **Catalogue** — Deep filtering when you do not know the year yet.
- **Course Search** — Find the **live** course counterpart, if it still exists.

Always test a **restore into a scratch course** when validating a brittle archive before touching production sections.
