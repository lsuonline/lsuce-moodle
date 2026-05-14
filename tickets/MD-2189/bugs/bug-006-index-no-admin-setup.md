# Bug-006 — index.php missing admin_externalpage_setup and not in tab strip

**Date:** 2026-05-12
**File:** `blocks/backadel/index.php`
**Severity:** Medium — page loads but shows no admin breadcrumbs, no left sidebar, no tab strip

## Symptom

`/blocks/backadel/index.php` renders with only a "Dashboard" breadcrumb and the Snap theme
header instead of the admin layout. No tab strip appears.

## Root cause

1. `index.php` calls `require_login()` + manual `$PAGE` setup (lines 26–47) instead of
   `admin_externalpage_setup()`.
2. `index.php` is **not registered** as an `admin_externalpage` in `blocks/backadel/settings.php`.
   The seven registered pages are: migrate, coursebackups, catalogue, results, failed, delete —
   index is absent.
3. The tab strip `$validplaces` array in settings.php does not include a selector for index.php,
   so even if the page happened to match, the AMD module would not inject the tabs.

## Fix

1. In `blocks/backadel/settings.php`:
   - Add `admin_externalpage` registration for `block_backadel_index` pointing to `/blocks/backadel/index.php`.
   - Add its link/string/selector to the `$links`/`$strings`/`$validplaces` arrays in the tab strip block.
   - Selector: `#page-admin-blocks-backadel-index`

2. In `blocks/backadel/index.php`:
   - Add `require_once($CFG->libdir . '/adminlib.php')`.
   - Replace `require_login()` + `is_siteadmin()` guard + manual `$PAGE` setup with
     `admin_externalpage_setup('block_backadel_index')` + `require_capability('block/backadel:managebackups', context_system::instance())`.
   - Remove the legacy `$PAGE->requires->js(...)` calls for the old jquery/index.js
     (keep them only if they are still needed by the page).

3. In `blocks/backadel/lang/en/block_backadel.php`:
   - Add `$string['nav_index'] = 'Search';` and `$string['tab_index'] = 'Search';`.
