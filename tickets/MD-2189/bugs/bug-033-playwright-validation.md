# Bug 033 — Playwright Validation: Snap Sidebar Buttons on Backadel Catalogue

**Date captured:** 2026-05-14  
**URL tested:** `http://localhost:8099/blocks/backadel/catalogue.php`  
**Moodle version:** 4.5 (dev Docker instance)  
**Theme:** Snap  

---

## Summary

Clicking the gear (admin) or person-with-book (Snap feeds) sidebar icons on the Backadel Catalogue page causes `#region-main` to become invisible — the entire main page body disappears, leaving only the header and footer. The chat bubble (messaging drawer) works correctly as an overlay without hiding the main content.

---

## Sidebar Button Selectors Found

| Icon | Selector | ID | Href/Action |
|------|----------|----|-------------|
| Gear (admin drawer) | `#admin-menu-trigger` | `admin-menu-trigger` | `#inst9` |
| Person-with-book (Snap feeds) | `#snap_feeds_side_menu_trigger` | `snap_feeds_side_menu_trigger` | `#snap_feeds_side_menu` |
| Chat bubble (messaging) | `button[data-toggle="drawer"]` | — | `data-action="toggle-drawer"` |

---

## Test Results Per Icon

### 1. Gear icon (`#admin-menu-trigger`) — BROKEN

**Screenshot:** `screenshots/sidebar-gear-open.png`

- Clicked: gear icon toggled `aria-expanded="true"` on `#admin-menu-trigger`
- Result: **Main catalogue content disappeared entirely**
- Viewport showed only the page debug footer (data retention tables, cache stats)
- `#region-main` computed `visibility: hidden`
- `#inst9` (admin settings block) computed `display: flex` — it opened but as a page takeover, not an overlay
- The catalogue table, heading, pagination, tabs: all invisible

### 2. Person-with-book icon (`#snap_feeds_side_menu_trigger`) — BROKEN

**Screenshot:** `screenshots/sidebar-person-open.png`

- Clicked: `#snap_feeds_side_menu` gained class `state-visible`, `aria-expanded="true"`
- Result: **Same as gear — main catalogue content disappeared entirely**
- `#region-main` computed `visibility: hidden`
- Only footer content visible in viewport

### 3. Chat bubble (messaging drawer) — WORKS CORRECTLY

**Screenshot:** `screenshots/sidebar-chat-open.png`

- Clicked: messaging drawer opened as a right-side overlay panel
- Result: **Main catalogue content remained fully visible** behind the overlay
- Breadcrumbs, heading "Backadel Catalogue", tabs, result count, table header all visible
- `#region-main` computed `visibility: visible`
- This is the correct behaviour — drawer as non-destructive overlay

---

## Filter Panel Test

### Filter panel alone — WORKS CORRECTLY

**Screenshot:** `screenshots/sidebar-filters-open.png`

- Clicked "Filters" button: filter panel slid in from right as overlay
- Main catalogue content (heading, tabs, result count, pagination, table) remained visible behind panel
- `#region-main` computed `visibility: visible`

### Gear icon with filter panel open — BROKEN

**Screenshot:** `screenshots/sidebar-gear-with-filters.png`

- Filter panel was open; then clicked gear icon
- Result: **Both the filter panel and the main content disappeared** — only footer visible
- Same `visibility: hidden` on `#region-main`

---

## Root Cause (Identified)

The bug is caused by Bootstrap 5's `.offcanvas` CSS class conflicting with Snap's drawer mechanism:

```
CSS rules found:
  .offcanvas        { visibility: hidden }   ← hides element
  .offcanvas.show   { visibility: visible }  ← shows element only when .show present
```

When the admin drawer or Snap feeds drawer is triggered, Snap's JavaScript adds the class `offcanvas` to `#page` (the main page wrapper div), but does NOT add the `offcanvas show` pairing. Because `#page` gets `visibility: hidden` from Bootstrap's `.offcanvas` rule without the `show` modifier, every child element — including `#region-main`, `#region-main-box`, `#moodle-page`, and `#page-content` — inherits `visibility: hidden`.

**Inheritance chain when broken:**
```
#page.offcanvas              → visibility: hidden  (Bootstrap .offcanvas rule)
  └─ #page-content           → visibility: hidden  (inherited)
       └─ #moodle-page       → visibility: hidden  (inherited)
            └─ #region-main-box → visibility: hidden (inherited)
                 └─ #region-main    → visibility: hidden (inherited)
```

The chat/messaging drawer uses a completely different mechanism (`data-action="toggle-drawer"` rendering into `[data-region="right-hand-drawer"]`) and does not touch `#page`'s class list, so it works correctly.

---

## Recommended Fix Direction

The fix should ensure that either:

1. Snap's admin/feeds drawer JavaScript does NOT add `offcanvas` to `#page`, OR
2. Snap's JS correctly pairs `offcanvas` with `show` (i.e. adds both `offcanvas show` when opening), OR
3. The Backadel catalogue page overrides the Snap drawer CSS so that `#page.offcanvas` does not propagate `visibility: hidden` down the tree (e.g. `#page-content, #region-main { visibility: visible !important }` when in the admin drawer open state)

Option 2 is likely the cleanest: inspect the Snap theme JS that handles `#admin-menu-trigger` clicks and ensure it adds `show` alongside `offcanvas`. File to investigate: `theme/snap/amd/src/` — look for the admin drawer toggle handler.

---

## Screenshots

| File | Description |
|------|-------------|
| `sidebar-baseline.png` | Catalogue page before any sidebar interaction (full page) |
| `sidebar-gear-open.png` | After clicking gear icon — main content gone, only footer visible |
| `sidebar-person-open.png` | After clicking person-with-book icon — same breakage |
| `sidebar-chat-open.png` | After clicking chat bubble — correct overlay, content visible |
| `sidebar-filters-open.png` | Filter panel open — correct overlay, content visible |
| `sidebar-gear-with-filters.png` | Gear clicked while filter panel open — body disappears |
