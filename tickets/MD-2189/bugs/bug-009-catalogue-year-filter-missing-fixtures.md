# BUG-009 — Catalogue page year filter: 2024 and 2023 fixture rows not visible

**Severity:** Medium  
**Found by:** e2e Wave 15 run (2026-05-12), assertions 7 and 7b  
**Status:** Fixed — resolved as a side-effect of Bug-017 (catalogue table col_actions() crash due to missing user name fields silently suppressed all row output; once fixed the year-filtered rows render correctly). Confirmed green in e2e run 2026-05-12 (138/138).

---

## Symptom

`catalogue.php?year=2024` renders a table (table element present, no exception) but does not contain the text `MATH` or `2024Spring`.

`catalogue.php?year=2023` likewise renders a table but does not contain `ENGL` or `2023fall`.

Both fixture files were seeded to backupdir before the test:

| Filename | Expected pattern | Expected year |
|---|---|---|
| `2024SpringMATH1201001_jsmith_1700000001.zip` | `semester_legacy` | 2024 |
| `2023fallENGL1001_mjones_1700000002.zip` | `semester_legacy_lc` | 2023 |

The `migrate_filesystem` scheduled task reported **"migrated 0 rows"** in this run (meaning files were already in catalogue from a prior run), and `import_ftp_catalogue.php` live import confirmed catalogue has **214 rows total** after the run.

---

## Likely root causes (in order of probability)

### 1. Pagination — fixture row is on page 2+ (most likely)

`catalogue_table` uses `$table->query_db(30, false)` — 30 rows per page. With 214 rows in catalogue and default sort `backup_ts DESC`, the 2024 and 2023 fixtures may not appear on page 1 if older (lower timestamp) fixtures dominate the sort.

**Verify:** Check `block_backadel_catalogue` for rows with `year='2024'` and `year='2023'`:
```sql
SELECT id, filename, year, semester, dept, course_num, backup_ts 
FROM mdl_block_backadel_catalogue 
WHERE year IN ('2024','2023') 
ORDER BY backup_ts DESC LIMIT 10;
```
If rows exist, the bug is the e2e assertion not accounting for pagination — fix is to use the year filter AND search for the specific shortname to force the row to the top, OR increase the page limit in the test URL.

### 2. Year column not populated during migration (second most likely)

If `filename_parser::parse()` returns `year=null` for either filename (e.g. `semester_legacy_lc` lowercase-year case), the `year` column in `block_backadel_catalogue` would be `NULL` and the year filter `WHERE year = '2023'` would skip it.

**Verify:** Check the row directly:
```sql
SELECT filename, year, semester, pattern FROM mdl_block_backadel_catalogue 
WHERE filename IN (
  '2024SpringMATH1201001_jsmith_1700000001.zip',
  '2023fallENGL1001_mjones_1700000002.zip'
);
```
If `year` is NULL, the bug is in `migrator.php` not setting `year` from `filename_parser` output, or in the parser itself.

### 3. Catalogue filter form year param type mismatch

`catalogue.php` reads `$year = optional_param('year', '', PARAM_INT)`. If `?year=2024` is cast to int `2024` but the DB column `year` is a VARCHAR/CHAR, the SQL comparison `year = 2024` may fail on MariaDB depending on implicit casting. However this is unlikely to cause a complete miss.

**Verify:** Check `db/install.xml` for the `year` column type.

---

## Files to investigate

- `blocks/backadel/classes/local/migrator.php` — sets `year` on the catalogue row from `filename_parser::parse()` output
- `blocks/backadel/classes/local/filename_parser.php` — `semester_legacy_lc` pattern year extraction
- `blocks/backadel/lib.php::backadel_catalogue_to_where()` — year param handling
- `blocks/backadel/catalogue.php` — `PARAM_INT` cast for year
- `tickets/MD-2189/e2e_backadel.py` lines 728–748 — the assertions; may need `?year=2024&q=MATH` to scope the filter

---

## Fix strategy (once root cause confirmed)

**If pagination:** Update e2e to add `&q=MATH` to the catalogue URL to force the specific fixture to the top of results.

**If year column NULL:** Fix `migrator.php` to correctly map `parsed['year']` → catalogue row `year` field. Ensure `semester_legacy_lc` parser output includes year.

**If PARAM_INT cast:** Change `optional_param('year', '', PARAM_ALPHANUM)` (or `PARAM_INT` with `(string)` cast in SQL) so the comparison is type-safe.
