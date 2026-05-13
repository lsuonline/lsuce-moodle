# Run Migration (filesystem scan)

**Run Migration** queues an **adhoc** task that walks configured filesystem locations and updates the **`block_backadel_catalogue`** table. This is how new files on disk become searchable rows in **Catalogue** (and visible to downstream restore tooling).

## What the scan does

- Reads **admin settings**: primary storage path, **extra scan paths**, **excluded categories** (where applicable to discovery rules), **blueprint keywords** for course-type hints, and **path prefix rewrite** pairs.
- **Hashes filenames** and metadata so duplicate rows are not created on repeat runs.
- Classifies filenames into **patterns** (`backadel_modern`, `semester_legacy`, etc.) used by filters.

It does **not** by itself create Moodle courses; it indexes files.

## Counts on the page

After a run completes (check Tasks log), you may compare:

- **Catalogue entries** — Rows in the catalogue table.
- **Courses in catalogue** — Rows linked into the auxiliary course-resolution table when present.

Large jumps or flat zeros usually mean the scan path is wrong or empty.

## Settings that affect outcomes

| Setting | Role |
|---------|------|
| **Excluded category IDs** | Limits which Moodle categories participate in certain resolution paths (see site docs). |
| **Extra scan paths** | Additional absolute directories beyond the default backadel storage tree. |
| **Blueprint keywords** | Substrings that mark “blueprint/master” style backups for badges in restore. |
| **Catalogue path prefix rewrite** | `old=new` pairs so moved moodledata roots still resolve to real files. |

Misconfigured rewrites produce **missing** statuses even when files exist elsewhere.

## Runtime expectations

Duration scales with **file count** and **network/storage latency**. Huge mounted NFS shares may take many minutes—watch the adhoc task in **Task logs** rather than refreshing this page obsessively.

Queueing the task returns immediately; completion is asynchronous.

## Operational checklist

1. Confirm `backupdir` / path settings point at the live archive volume.
2. Fix permissions so the web/CLI user can **stat** every file.
3. Run migration after bulk copies of `.zip`/`.mbz` into the tree.
4. Re-run if you change **path prefix** rules.

## When things look stale

- New files absent? Migration not run, or path excludes them.
- Old ghosts still listed? File deleted but row not pruned—some sites keep rows as tombstones for audit; behavior depends on implementation version.

Use **Catalogue** filters (year/pattern/source) to verify the scan’s view matches reality.

## Related

- **Catalogue** — Human-friendly view of the same index.
- **Scheduled task** `migrate_filesystem` — Long-term automation equivalent to this manual trigger.

Treat migration as **reconciliation**: disk is the source of truth, catalogue is the index.
