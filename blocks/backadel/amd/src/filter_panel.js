/**
 * Filter panel offcanvas initialisation for Backadel.
 *
 * Moodle 4.5 / Snap does not expose Bootstrap 5's Offcanvas class globally,
 * so data-bs-toggle="offcanvas" does nothing out of the box.  This module
 * wires up the open/close behaviour manually AND injects the positioning CSS
 * that Bootstrap 5 would normally provide via its stylesheet (which Snap
 * does not include on standalone admin pages).
 *
 * @module     block_backadel/filter_panel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Inject Bootstrap-5-compatible offcanvas CSS that Snap omits on admin pages. */
const injectStyles = () => {
    if (document.getElementById('block-backadel-offcanvas-css')) {
        return; // Already injected.
    }
    const style = document.createElement('style');
    style.id = 'block-backadel-offcanvas-css';
    style.textContent = `
/* block_backadel: offcanvas positioning — Snap does not ship Bootstrap 5 offcanvas CSS */
.offcanvas {
    position: fixed !important;
    top: 0;
    right: 0;
    bottom: 0;
    width: min(400px, 90vw);
    z-index: 1055;
    display: flex;
    flex-direction: column;
    background-color: #fff;
    border-left: 1px solid rgba(0,0,0,.175);
    transform: translateX(100%);
    transition: transform .3s ease-in-out;
    visibility: hidden;
    overflow-y: auto;
    pointer-events: none;
}
.offcanvas.show {
    transform: none !important;
    visibility: visible !important;
    pointer-events: auto;
}
.offcanvas-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    flex-shrink: 0;
}
.offcanvas-title {
    margin-bottom: 0;
    line-height: 1.5;
    font-size: 1.1rem;
    font-weight: 600;
}
.offcanvas-body {
    flex-grow: 1;
    padding: 1rem;
    overflow-y: auto;
}
/* Dim backdrop when an offcanvas is open.
   pointer-events: none lets clicks pass through to elements below (e.g. Snap sidebar trigger
   at z-index 1050/1051) so Snap's drawer remains usable while the filter panel is open.
   The document-level dismiss listener (below) still closes the panel on any outside click. */
body.offcanvas-open::before {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1054;
    pointer-events: none;
}
`;
    document.head.appendChild(style);
};

/**
 * Open an offcanvas panel.
 *
 * @param {HTMLElement} offcanvasEl The offcanvas element.
 */
const openOffcanvas = (offcanvasEl) => {
    offcanvasEl.classList.add('show');
    offcanvasEl.setAttribute('aria-modal', 'true');
    offcanvasEl.setAttribute('role', 'dialog');
    offcanvasEl.removeAttribute('aria-hidden');
    document.body.classList.add('offcanvas-open');

    // Focus first focusable child.
    const focusable = offcanvasEl.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    if (focusable) {
        focusable.focus();
    }
};

/**
 * Close an offcanvas panel.
 *
 * @param {HTMLElement} offcanvasEl The offcanvas element.
 */
const closeOffcanvas = (offcanvasEl) => {
    offcanvasEl.classList.remove('show');
    offcanvasEl.removeAttribute('aria-modal');
    offcanvasEl.removeAttribute('role');
    offcanvasEl.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('offcanvas-open');
};

/**
 * Initialise all offcanvas trigger buttons on the page.
 */
export const init = () => {
    injectStyles();

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
            openOffcanvas(offcanvasEl);
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

    // Close on Escape key.
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
