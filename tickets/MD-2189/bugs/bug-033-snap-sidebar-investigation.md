# Bug 033 — Snap Sidebar Still Blocked After Bug-031 Fix

**Date:** 2026-05-13  
**Pages affected:** `blocks/backadel/catalogue.php`, `blocks/simple_restore/list.php` (any page loading `block_backadel/filter_panel`)  
**Symptom:** Snap's right-side icon strip (admin drawer, feeds drawer, messaging) cannot be clicked while (or after opening and closing) the filter panel offcanvas.  
**Previous fix:** bug-031 — added `body.offcanvas-open::before { pointer-events: none }` to `filter_panel.js`  
**Status of previous fix:** Does NOT resolve the problem.

---

## 1. Environment confirmed

Investigated via Playwright on http://localhost:8099 (admin user).  
Pages tested: `/blocks/backadel/catalogue.php`

Note: `blocks/simple_restore/list.php` loads `block_backadel/filter_panel` (line 241 of list.php) — the identical JS module, so the same bug applies there.

---

## 2. Root Cause

### Two elements both anchor to `right: 0` with the offcanvas on top

The Snap sidebar and the Backadel offcanvas filter panel are both `position: fixed; right: 0` elements. When the filter panel opens, it completely overlaps the sidebar strip:

| Element | Position | Width | z-index | right | Covers (at 1050px viewport) |
|---|---|---|---|---|---|
| `#snap-sidebar-menu` | `fixed` | `50px` | **1050** | `0px` | x = 1000–1050 |
| `#backadel-catalogue-filters` (.offcanvas.show) | `fixed` | `min(400px, 90vw)` = 400px | **1055** | `0px` | x = 650–1050 |

The offcanvas panel's rightmost 50px (x = 1000–1050) lies **directly on top of the entire Snap sidebar strip** (also x = 1000–1050). Since `z-index: 1055 > 1050`, the offcanvas wins and intercepts all pointer events.

### Computed style evidence (Playwright session)

With filter panel open (`body.offcanvas-open`), `document.elementsFromPoint(1010, 197)` (the center of the `#admin-menu-trigger`) returns:

```
1st: DIV.offcanvas-body         z-index: auto   pointer-events: auto   (inside #backadel-catalogue-filters)
2nd: DIV#backadel-catalogue-filters  z-index: 1055  pointer-events: auto
3rd: svg#snap-admin-icon        z-index: auto   (blocked, can't receive events)
4th: A#admin-menu-trigger       z-index: auto   (blocked)
5th: DIV#snap-sidebar-menu      z-index: 1050   (blocked)
```

`document.elementFromPoint(1010, 197)` returns `.offcanvas-body` — confirming clicks land on the offcanvas, not the sidebar trigger.

The offcanvas rect when open: `{ x: 635, y: 0, width: 400, height: 778 }` — its right edge is at x=1035, covering the full viewport width at 1050px.

### Viewport math

```
viewport width: 1050px
offcanvas width: min(400px, 90vw) = min(400, 945) = 400px
offcanvas left edge: 1050 - 400 = 650px
sidebar left edge:   1050 - 50  = 1000px
overlap: 400 - (1000 - 650) = 50px  ← the sidebar is entirely inside the offcanvas footprint
```

This affects **every** common desktop viewport width: a 400px offcanvas always extends further left than a 50px sidebar by 350px. The sidebar is always 100% covered.

---

## 3. Why Bug-031 Did Not Fix This

Bug-031 targeted the wrong element. The injected CSS:

```css
body.offcanvas-open::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1054;
    pointer-events: none;   /* <-- the bug-031 fix */
}
```

Computed style confirms `body::before` has `pointer-events: none` and `z-index: 1054` — the fix IS being applied exactly as written. But the dim backdrop pseudo-element is irrelevant:

- The intercepting element is `#backadel-catalogue-filters` (the offcanvas `<div>` itself) at **z-index: 1055**
- The backdrop pseudo-element sits at z-index 1054 — *below* the offcanvas
- The sidebar sits at z-index 1050 — *below* both

No amount of making the backdrop non-interactive helps when the opaque offcanvas DOM element is the topmost layer covering the sidebar.

Additionally, even if pointer events somehow reached the sidebar, the document-level JS dismiss listener (filter_panel.js lines 163–171) would fire first: it closes all open offcanvas panels on any click whose target is neither inside `.offcanvas` nor a `[data-bs-toggle="offcanvas"]` element. `#admin-menu-trigger` is neither, so the filter panel would close on the down-stroke, preventing the sidebar from toggling.

---

## 4. Secondary Scope Finding

`blocks/simple_restore/list.php` loads the same AMD module:

```php
// list.php line 241:
$PAGE->requires->js_call_amd('block_backadel/filter_panel', 'init');
```

It also renders `block_backadel/local/filter_panel` (line 322). The bug is identical on the SR list page.

---

## 5. Recommended Fix

### Option A — Shrink the offcanvas width so it stops before the sidebar (RECOMMENDED)

In `filter_panel.js` `injectStyles()`, change the `.offcanvas` width/right to leave room for Snap's 50px sidebar:

```css
/* Before: */
.offcanvas {
    right: 0;
    width: min(400px, 90vw);
    …
}

/* After: */
.offcanvas {
    right: 50px;          /* stop before Snap's 50px sidebar strip */
    width: min(400px, calc(90vw - 50px));
    …
}
```

This is the safest fix: the offcanvas slides in from the right but stops flush with the sidebar's left edge, never overlapping it. The sidebar remains fully clickable at all times.

If the Snap sidebar is not always 50px (it may vary), use a CSS custom property:

```css
.offcanvas {
    right: var(--snap-sidebar-width, 50px);
    width: min(400px, calc(90vw - var(--snap-sidebar-width, 50px)));
}
```

### Option B — Raise Snap sidebar z-index above the offcanvas

Add to `filter_panel.js` `injectStyles()`:

```css
#snap-sidebar-menu {
    z-index: 1060 !important;   /* above offcanvas (1055) */
}
```

This keeps the sidebar clickable through the offcanvas, but the sidebar icons would render visually on top of (partially obscuring) the offcanvas panel content — less clean visually.

### Option C — Clip the offcanvas to avoid covering the sidebar at the DOM level

Set `overflow: hidden` on the offcanvas and reduce `right` by 50px as in Option A. Functionally identical to A.

### Which option to implement

**Option A** is the correct fix. It addresses the spatial overlap at its source and has no visual downsides. The sidebar icons remain accessible and the filter panel content is simply 50px narrower on the right — imperceptible to users since the panel's visual border is well within the remaining 400px.

The JS dismiss listener (secondary issue) does not need a separate fix once Option A is implemented: since the sidebar is no longer physically covered by the offcanvas, click events reach `#admin-menu-trigger` naturally without triggering the document dismiss handler.

---

## 6. Evidence Files

- `tickets/MD-2189/bugs/snap-sidebar-initial.png` — catalogue page before filter opens
- `tickets/MD-2189/bugs/snap-sidebar-filter-open.png` — filter open, showing complete overlap of sidebar by offcanvas panel

---

## 7. Files to Modify

- `/home/homelab/work/lsu/lsuce-moodle/blocks/backadel/amd/src/filter_panel.js` — change `.offcanvas { right: 0 }` to `right: 50px` and adjust width formula  
- `/home/homelab/work/lsu/lsuce-moodle/blocks/backadel/amd/build/filter_panel.min.js` — rebuild after src change (`grunt amd` or equivalent)
