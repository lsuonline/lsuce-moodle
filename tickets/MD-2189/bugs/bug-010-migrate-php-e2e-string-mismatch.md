# BUG-010 — migrate.php: e2e assertion checks wrong string for catalogue row count label

**Severity:** Low (e2e test bug; no production code defect)  
**Found by:** e2e Wave 15 run (2026-05-12), assertion 12  
**Status:** Open

---

## Symptom

The e2e assertion:

```python
check(
    "Catalogue entries:" in content_migrate or "catalogue entries" in content_migrate.lower(),
    "migrate.php: catalogue row count label visible",
)
```

…fails because the actual page renders:

```
Backup files catalogued: <strong data-region="migrate-count-catalogue">214</strong>
```

The lang string is `$string['migrate_catalogue_count'] = 'Backup files catalogued: ';`  
(`blocks/backadel/lang/en/block_backadel.php:238`)

The e2e checks for `"Catalogue entries:"` which does not exist in the page.

---

## Files

- `tickets/MD-2189/e2e_backadel.py` lines ~203–205 — wrong expected string
- `blocks/backadel/lang/en/block_backadel.php:238` — actual string
- `blocks/backadel/migrate.php:71` — rendering call

---

## Fix (one of two options)

**Option A — Fix the e2e assertion (preferred; no UI change):**

```python
# tickets/MD-2189/e2e_backadel.py
check(
    "Backup files catalogued" in content_migrate
    or 'data-region="migrate-count-catalogue"' in content_migrate,
    "migrate.php: catalogue row count label visible",
)
```

**Option B — Update the lang string to match the assertion wording:**

```php
// blocks/backadel/lang/en/block_backadel.php
$string['migrate_catalogue_count'] = 'Catalogue entries: ';
```

Option A is lower risk. Option B changes the visible UI label (may need stakeholder sign-off).

---

## Notes

The count IS being rendered correctly — `214` appears in the DOM under `data-region="migrate-count-catalogue"`. This is purely a label-text mismatch between the e2e script and the lang string.
