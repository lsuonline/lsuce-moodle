# Delete Archives (completed backups)

This page lists courses in **`SUCCESS`** status: Backadel believes a backup completed and the course is eligible for the **delete archive** lifecycle step (policy-dependent).

## What “delete archive” actually removes

The **Delete archive** action from **Course Search** (and workflows tied to this status) removes the **Moodle course** from the site database and clears the associated **Backadel status** entry. It is **not** the same as deleting a row from the **Catalogue** table or erasing the physical `.mbz`/`.zip` from disk.

### Practical implications

- Users **lose access** to the course shell immediately after the course is deleted.
- **Backup files** may still exist under your storage path until operators or other jobs remove them.
- **Restore** from a file is still possible **if** the archive exists and you use **Course Backups** or the restore wizard appropriately.

Always align with records retention policy before bulk deletes.

## Irreversibility

Assume **course deletion cannot be undone** from inside Moodle without a restore from backup. There is no “soft undo” button for the entire course.

## When to use this page vs other tools

| Goal | Tool |
|------|------|
| Remove stale **course shells** after archiving | **Delete Archives** / delete action |
| Inspect whether a **file** still exists | **Catalogue** |
| **Restore** into a new or existing course | **Course Backups** → native restore |

## Workflow tips

1. Verify the backup file in **Catalogue** (status **available**) or on disk.
2. Communicate with instructors if the course might still be needed read-only.
3. Perform deletes in **small batches** during low-traffic windows.
4. After deleting, spot-check **Course Search** to ensure status matches expectations.

## Permissions

Only roles with **`block/backadel:deletearchive`** (and related admin context) should access this report. Delegate carefully.

## Related help topics

- **Course Search** — Where the per-row delete action usually lives.
- **Catalogue** — Confirm filenames and disk presence before deleting Moodle courses.

If you are unsure whether the `.mbz` is safe in long-term storage, **stop** and resolve that before deleting the live course.
