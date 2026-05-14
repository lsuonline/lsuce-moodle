# BUG-022 — search.php status filter: e2e doesn't verify it works

**Severity:** Low  
**Found by:** PDF review page 31 (search-filter-status.png)

## Symptom
`search-filter-status.png` shows the search page with `?status=SUCCESS` applied but the screenshot looks identical to unfiltered — no visible proof the filter works. David noted: "Do the e2e tests actually verify anything there?"

## Root cause
`test_search_filter_status()` only checks "no exception" — it doesn't assert that filtering by status changes the result set. All e2e fixture data has `status=SUCCESS` so filtering by SUCCESS returns everything (visually identical to no filter).

## Fix
In `tickets/MD-2189/e2e_backadel.py::test_search_filter_status()`: also navigate to `status=FAIL` (no fixture has FAIL status) and assert zero data rows, proving the filter actually narrows results. Update the screenshot to capture both states.
