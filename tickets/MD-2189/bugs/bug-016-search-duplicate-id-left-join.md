# Bug-016: Course Search page throws "Duplicate value in column 'id'" on any search

## Status
Fixed — committed with fix below.

## Reported by
David (Telegram screenshot, 2026-05-12)

## Symptom
Navigating to **Course Search → Backup And...** tab, entering any text (e.g. "backadel") in the search box and clicking Search throws a Moodle exception:

```
Whoops \ Exception \ ErrorException (E_USER_NOTICE)
Did you remember to make the first column something unique in your call to get_records?
Duplicate value '2' found in column 'id'.
```

Stack trace ends at `blocks/backadel/search.php:84` → `$table->out(50, true)`.

## Root Cause

`results_table::setup_sql()` built:

```sql
SELECT co.id, co.shortname, co.fullname, cat.name AS category, ba.status
FROM {course} co
JOIN {course_categories} cat ON cat.id = co.category
LEFT JOIN {block_backadel_statuses} ba ON ba.coursesid = co.id
```

A course can have **multiple rows** in `block_backadel_statuses` (one per backup attempt: BACKUP → SUCCESS → DELETED → BACKUP again). The LEFT JOIN fan-out produces multiple rows with the same `co.id`, which causes Moodle's `get_records_sql()` to throw the duplicate-id error.

## Fix

**File**: `blocks/backadel/classes/local/table/results_table.php` — `setup_sql()`

Replace the LEFT JOIN with a correlated subquery that selects only the most-recent status row per course:

```php
$fields = 'co.id, co.shortname, co.fullname, cat.name AS category, '
    . '(SELECT ba.status FROM {block_backadel_statuses} ba '
    . ' WHERE ba.coursesid = co.id ORDER BY ba.id DESC LIMIT 1) AS status';
$from = '{course} co '
    . 'JOIN {course_categories} cat ON cat.id = co.category';
```

The subquery returns at most one row per course (the row with the highest `id`, i.e. the most recent status), eliminating the duplicate-id problem.

## Affected File
- `blocks/backadel/classes/local/table/results_table.php`

## Severity
Fatal — the Course Search page is completely unusable whenever any filter is active.
