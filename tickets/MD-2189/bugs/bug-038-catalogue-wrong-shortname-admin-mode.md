# bug-038: catalogue lookup uses wrong shortname in admin mode

**Status:** Fixed — commit MD-2209 message on MD-2189  
**Reported:** 2026-05-14 by Robert (via David)  
**File:** `blocks/simple_restore/lib.php` — `backup_list()`

## Symptom

In admin mode (`id=SITEID`), the semester backup table shows `—` (em-dash) for
Year, Semester, Department, Course type, and Status columns even though the data
exists in `block_backadel_catalogue`.

## Root Cause

`backup_list()` builds `$catalogueshort` from `$course->shortname`. In admin mode
`$course` is the **site course** (id=SITEID), whose shortname is irrelevant. The
catalogue SQL query therefore finds nothing and falls back to the filesystem scan
(`backadel_backups()`), which returns rows with only filename/size/modified — no
year/semester/dept metadata.

The filesystem fallback correctly uses `$data->shortname` (Robert's searched
course code, e.g. `LA-1203`), so files are found — but without metadata.

## Fix

```php
// Before
$catalogueshort = (string) ($course->shortname ?? '');

// After
$catalogueshort = (isset($data->shortname) && (string) $data->shortname !== '')
    ? (string) $data->shortname
    : (string) ($course->shortname ?? '');
```
