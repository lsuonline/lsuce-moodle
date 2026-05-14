#!/usr/bin/env python3
"""
MD-2189 Wave 4 — Playwright e2e: Mustache modal templates + offcanvas CSS (styles.css).

Validates:
  Group 1 — Catalogue filter offcanvas (styles.css / filter_panel.js)
  Group 2 — Instructors modal (Mustache instructors_modal_body)
  Group 3 — Course type override modal (Mustache coursetype_override_modal)
  Group 4 — Regression: search.php + catalogue.php filter panels open
"""

import os
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

BASE_URL = os.environ.get("PLAYWRIGHT_BASE_URL", "http://localhost:8099")
ADMIN_USER = "admin"
ADMIN_PASS = "Admin1234!"

SHOTS_DIR = Path(__file__).resolve().parent / "screenshots" / "wave4"
SHOTS_DIR.mkdir(parents=True, exist_ok=True)

passed: list[str] = []
failed: list[str] = []
skipped: list[str] = []
_test_name: str = ""


def begin(name: str) -> None:
    global _test_name
    _test_name = name
    print(f"\n  [ ] {name}")


def ok(detail: str = "") -> None:
    msg = f"PASS: {_test_name}" + (f" — {detail}" if detail else "")
    passed.append(msg)
    print(f"  [PASS] {detail or ''}")


def fail(detail: str = "") -> None:
    msg = f"FAIL: {_test_name}" + (f" — {detail}" if detail else "")
    failed.append(msg)
    print(f"  [FAIL] {detail or ''}")


def skip(reason: str) -> None:
    skipped.append(f"SKIP: {_test_name} — {reason}")
    print(f"  [SKIP] {reason}")


def shot(page, name: str) -> None:
    page.screenshot(path=str(SHOTS_DIR / f"{name}.png"), full_page=False)


def login(page) -> None:
    page.goto(f"{BASE_URL}/login/index.php")
    page.wait_for_load_state("networkidle")
    page.fill("#username", ADMIN_USER)
    page.fill("#password", ADMIN_PASS)
    page.click("#loginbtn")
    page.wait_for_load_state("networkidle")
    if "/login/index.php" in (page.url or ""):
        raise RuntimeError("Login did not leave login page — check credentials or site URL.")


def force_close_any_modal(page) -> None:
    page.evaluate(
        """
        () => {
            document.querySelectorAll('.modal.show [data-action="hide"]').forEach((b) => b.click());
        }
    """
    )
    page.wait_for_timeout(250)


def panel_is_visible_open(page, panel_id: str) -> bool:
    return page.evaluate(
        f"""
        (() => {{
            const el = document.getElementById('{panel_id}');
            if (!el) return false;
            return el.classList.contains('show');
        }})()
    """
    )


def page_body_visible(page) -> bool:
    return page.evaluate(
        """
        (() => {
            const el = document.getElementById('page');
            if (!el) return true;
            const style = window.getComputedStyle(el);
            return style.visibility !== 'hidden' && style.display !== 'none';
        })()
    """
    )


def close_all_offcanvas(page) -> None:
    page.evaluate(
        """
        () => {
            document.querySelectorAll('.offcanvas.show').forEach(el => el.classList.remove('show'));
            document.body.classList.remove('offcanvas-open');
        }
    """
    )
    page.wait_for_timeout(200)


def close_top_modal(page) -> None:
    page.evaluate(
        """
        () => {
            const btn =
                document.querySelector('.modal.show [data-action="hide"]')
                || document.querySelector('[data-region="modal-container"] [data-action="hide"]');
            if (btn) {
                btn.click();
            }
        }
    """
    )
    page.wait_for_timeout(300)


def run(playwright) -> int:
    browser = playwright.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={"width": 1280, "height": 900})
    page = ctx.new_page()

    login(page)

    # ------------------------------------------------------------------ #
    # GROUP 1 — Catalogue offcanvas (styles.css)
    # ------------------------------------------------------------------ #

    page.goto(f"{BASE_URL}/blocks/backadel/catalogue.php")
    page.wait_for_load_state("networkidle")
    shot(page, "catalogue_initial")

    begin("catalogue.php: filter panel is offcanvas (fixed positioning from styles.css)")
    pos_ok = page.evaluate(
        """
        (() => {
            const el = document.getElementById('backadel-catalogue-filters');
            if (!el || !el.classList.contains('backadel-offcanvas-panel')) return false;
            return window.getComputedStyle(el).position === 'fixed';
        })()
    """
    )
    if pos_ok:
        ok("#backadel-catalogue-filters uses position:fixed (scoped offcanvas CSS)")
    else:
        fail("panel missing marker class or not position:fixed")

    begin("catalogue.php: filter form lives inside offcanvas, not inline in main flow")
    form_in_panel = page.evaluate(
        """
        (() => {
            const panel = document.getElementById('backadel-catalogue-filters');
            if (!panel) return false;
            return !!panel.querySelector('form');
        })()
    """
    )
    if form_in_panel:
        ok("form is nested inside #backadel-catalogue-filters")
    else:
        fail("expected moodleform inside offcanvas drawer")

    begin("catalogue.php: opening Filters adds .show to offcanvas panel")
    filters_btn = page.query_selector("button[data-bs-target='#backadel-catalogue-filters']")
    if not filters_btn:
        fail("Filters trigger not found")
    else:
        filters_btn.click()
        page.wait_for_timeout(450)
        shot(page, "catalogue_panel_open")
        if panel_is_visible_open(page, "backadel-catalogue-filters"):
            ok("panel has .show when open")
        else:
            fail("panel did not receive .show — filter_panel.js / CSS regression")

    begin("catalogue.php: #page stays visible while offcanvas open (Snap regression guard)")
    if page_body_visible(page):
        ok("#page remains visible")
    else:
        fail("#page hidden while filter open")

    close_all_offcanvas(page)

    # Delegated AMD handlers register after block JS loads; avoids flaky first-click misses.
    page.wait_for_timeout(2500)

    # ------------------------------------------------------------------ #
    # GROUP 2 — Instructors modal (Mustache)
    # ------------------------------------------------------------------ #

    begin("catalogue.php: instructors modal — open if fixture data exists")

    instructors_btn = page.query_selector('[data-action="show-instructors"]')
    if not instructors_btn:
        skip("no fixture data — no [data-action=show-instructors] buttons on catalogue")
    else:
        try:
            instructors_btn.click()
            page.wait_for_selector(
                '.modal.show [data-region="body"] table.table',
                timeout=12000,
            )
            shot(page, "instructors_modal")
            body = page.locator(".modal.show").locator('[data-region="modal"]').locator('[data-region="body"]')
            tbl = body.locator("table")
            if tbl.count() < 1:
                fail("expected <table> in modal body")

            hdr = body.inner_text()
            if "Username" not in hdr or "Full name" not in hdr:
                fail(f"missing column headers in modal body: {hdr[:200]}")
            close_top_modal(page)
            page.wait_for_timeout(200)
            ok("table + Username / Full name headers present")
        except Exception as exc:
            fail(f"instructors modal flow failed: {exc}")
            force_close_any_modal(page)

    # ------------------------------------------------------------------ #
    # GROUP 3 — Course type override modal (Mustache)
    # ------------------------------------------------------------------ #

    force_close_any_modal(page)

    begin("catalogue.php: course type override modal — open first coursetype control")

    override_btn = page.query_selector('[data-action="coursetype-override"]')
    if not override_btn:
        skip("no catalogue rows — no [data-action=coursetype-override] button")
    else:
        try:
            override_btn.click()
            page.wait_for_selector(
                '.modal.show [data-region="body"] input[type="radio"]',
                timeout=12000,
            )
            shot(page, "coursetype_modal")
            body = page.locator(".modal.show").locator('[data-region="modal"]').locator('[data-region="body"]')
            radios = body.locator('input[type="radio"]')
            n = radios.count()
            if n != 3:
                fail(f"expected 3 course type radios, got {n}")

            note = body.locator('textarea[data-field="override-note"]')
            if note.count() != 1:
                fail("expected single note textarea with data-field=override-note")

            close_top_modal(page)
            page.wait_for_timeout(200)
            ok("3 radios + note textarea; closed without save")
        except Exception as exc:
            fail(f"coursetype modal flow failed: {exc}")
            force_close_any_modal(page)

    # ------------------------------------------------------------------ #
    # GROUP 4 — Regression (wave 1–3 smoke)
    # ------------------------------------------------------------------ #

    page.goto(f"{BASE_URL}/blocks/backadel/search.php")
    page.wait_for_load_state("networkidle")
    shot(page, "search_initial")

    begin("search.php: filter panel opens (regression)")
    sbtn = page.query_selector("button[data-bs-target='#backadel-search-filters']")
    if not sbtn:
        fail("search.php Filters button missing")
    else:
        sbtn.click()
        page.wait_for_timeout(450)
        if panel_is_visible_open(page, "backadel-search-filters"):
            ok("search offcanvas opened")
        else:
            fail("search filter panel did not open")
    close_all_offcanvas(page)

    page.goto(f"{BASE_URL}/blocks/backadel/catalogue.php")
    page.wait_for_load_state("networkidle")

    begin("catalogue.php: filter panel opens (regression)")
    cbtn = page.query_selector("button[data-bs-target='#backadel-catalogue-filters']")
    if not cbtn:
        fail("catalogue Filters button missing")
    else:
        cbtn.click()
        page.wait_for_timeout(450)
        if panel_is_visible_open(page, "backadel-catalogue-filters"):
            ok("catalogue offcanvas opened")
        else:
            fail("catalogue filter panel did not open")

    browser.close()

    print(f"\n{'='*60}")
    print(
        f"  Wave 4 results: {len(passed)} passed, {len(skipped)} skipped, {len(failed)} failed"
    )
    print(f"{'='*60}")
    for m in passed:
        print(f"  ✓ {m}")
    for m in skipped:
        print(f"  ○ {m}")
    for m in failed:
        print(f"  ✗ {m}")

    return len(failed)


if __name__ == "__main__":
    with sync_playwright() as pw:
        code = run(pw)
    sys.exit(code)
