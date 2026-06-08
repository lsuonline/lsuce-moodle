/**
 * Filter panel offcanvas initialisation for Backadel.
 *
 * Moodle 4.5 / Snap does not expose Bootstrap 5's Offcanvas class globally,
 * so data-bs-toggle="offcanvas" does nothing out of the box.  This module
 * wires up the open/close behaviour manually.  Offcanvas positioning CSS lives
 * in block_backadel/styles.css (plugin stylesheet).
 *
 * Accessibility: implements a WCAG 2.4.3 / 2.4.7-compliant focus trap while
 * the panel is open and returns focus to the triggering element on close.
 *
 * @module     block_backadel/filter_panel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

/**
 * Return all currently-visible focusable descendants of an element.
 *
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
const getFocusable = (container) =>
    Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR)).filter(
        (el) => !el.closest('[hidden]') && el.offsetParent !== null
    );

/** @type {Map<HTMLElement, {handler: Function, trigger: HTMLElement|null}>} */
const activePanels = new Map();

/**
 * Attach a Tab-key focus trap to an open offcanvas element.
 *
 * @param {HTMLElement} offcanvasEl
 */
const attachFocusTrap = (offcanvasEl) => {
    const trapHandler = (e) => {
        if (e.key !== 'Tab') {
            return;
        }
        const focusable = getFocusable(offcanvasEl);
        if (!focusable.length) {
            e.preventDefault();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey) {
            if (document.activeElement === first) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    };
    offcanvasEl.addEventListener('keydown', trapHandler);
    return trapHandler;
};

/**
 * Open an offcanvas panel and activate the focus trap.
 *
 * @param {HTMLElement} offcanvasEl The offcanvas element.
 * @param {HTMLElement|null} triggerEl The element that opened the panel.
 */
const openOffcanvas = (offcanvasEl, triggerEl) => {
    offcanvasEl.classList.add('show');
    offcanvasEl.setAttribute('aria-modal', 'true');
    offcanvasEl.setAttribute('role', 'dialog');
    offcanvasEl.removeAttribute('aria-hidden');
    document.body.classList.add('offcanvas-open');

    const trapHandler = attachFocusTrap(offcanvasEl);
    activePanels.set(offcanvasEl, {handler: trapHandler, trigger: triggerEl ?? null});

    // Focus first focusable child.
    const focusable = getFocusable(offcanvasEl);
    if (focusable.length) {
        focusable[0].focus();
    }
};

/**
 * Close an offcanvas panel, remove the focus trap, and return focus.
 *
 * @param {HTMLElement} offcanvasEl The offcanvas element.
 */
const closeOffcanvas = (offcanvasEl) => {
    offcanvasEl.classList.remove('show');
    offcanvasEl.removeAttribute('aria-modal');
    offcanvasEl.removeAttribute('role');
    offcanvasEl.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('offcanvas-open');

    const panel = activePanels.get(offcanvasEl);
    if (panel) {
        offcanvasEl.removeEventListener('keydown', panel.handler);
        if (panel.trigger && typeof panel.trigger.focus === 'function') {
            panel.trigger.focus();
        }
        activePanels.delete(offcanvasEl);
    }
};

/**
 * Initialise all offcanvas trigger buttons on the page.
 */
export const init = () => {
    // Handle trigger buttons (data-bs-toggle="offcanvas").
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-bs-toggle="offcanvas"]');
        if (!trigger) {
            return;
        }
        const targetId = trigger.getAttribute('data-bs-target');
        if (!targetId) {
            return;
        }
        const offcanvasEl = document.querySelector(targetId);
        if (!offcanvasEl) {
            return;
        }
        e.preventDefault();
        if (offcanvasEl.classList.contains('show')) {
            closeOffcanvas(offcanvasEl);
        } else {
            openOffcanvas(offcanvasEl, trigger);
        }
    });

    // Handle dismiss buttons (data-bs-dismiss="offcanvas").
    document.addEventListener('click', (e) => {
        const dismiss = e.target.closest('[data-bs-dismiss="offcanvas"]');
        if (!dismiss) {
            return;
        }
        const offcanvasEl = dismiss.closest('.offcanvas');
        if (offcanvasEl) {
            closeOffcanvas(offcanvasEl);
        }
    });

    // Close on Escape key (Bootstrap does this when it owns the element; we handle our manual wiring).
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') {
            return;
        }
        document.querySelectorAll('.offcanvas.show').forEach(closeOffcanvas);
    });

    // Close when clicking the backdrop (body::before pseudo-element area).
    document.addEventListener('click', (e) => {
        if (e.target.closest('.offcanvas')) {
            return;
        }
        if (e.target.closest('[data-bs-toggle="offcanvas"]')) {
            return;
        }
        document.querySelectorAll('.offcanvas.show').forEach(closeOffcanvas);
    });
};
