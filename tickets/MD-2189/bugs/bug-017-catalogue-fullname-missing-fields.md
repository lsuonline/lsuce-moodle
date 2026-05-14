# Bug-017: Catalogue table renders blank — instructor fullname() crashes on partial user object

## Status
Fixed — committed `c488ea3d2a0`.

## Symptom
Navigating to **Backadel → Catalogue** as admin renders the filter form and the
"Backadel Catalogue — N result(s)" heading, but **no table at all** — no column headers,
no rows, no Actions column.

The check `"Actions" in page.content()` fails because the generaltable is never output.

## Root Cause

`catalogue_table::resolve_instructor_pairs_for_row()` fetched instructor user records with:

```sql
SELECT id, username, firstname, lastname FROM {user} WHERE deleted = 0 AND username IN (...)
```

Then passed the result objects to `fullname($obj)`. In Moodle 4.5, `fullname()` calls
`core\user::get_fullname()` which calls `debugging()` if the object is missing the
extended name fields (`firstnamephonetic`, `lastnamephonetic`, `middlename`, `alternatename`).

In developer mode, `debugging()` throws an `ErrorException`. This aborted `col_actions()`
mid-execution, which caused `flexible_table::format_row()` to throw and silently stop
rendering. The table (including all headers) was never written to output.

The error appeared only as an HTML comment in the Moodle debug footer, not as a Whoops page,
making it invisible without inspecting the raw DOM.

## Fix

**File**: `blocks/backadel/classes/local/table/catalogue_table.php` — `resolve_instructor_pairs_for_row()`

Replace the hardcoded field list with `core_user\fields::get_name_fields()`:

```php
$namefields = implode(', ', \core_user\fields::get_name_fields());
$sql = "SELECT id, username, $namefields FROM {user} WHERE deleted = 0 AND username $insql";
```

## Affected File
- `blocks/backadel/classes/local/table/catalogue_table.php`

## Severity
Fatal — entire catalogue table invisible for any site with instructor data in catalogue rows.
