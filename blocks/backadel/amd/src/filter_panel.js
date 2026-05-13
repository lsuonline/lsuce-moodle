/**
 * Filter panel offcanvas initialisation for Backadel.
 *
 * Moodle 4.5 / Snap does not expose Bootstrap 5's Offcanvas class globally,
 * so data-bs-toggle="offcanvas" does nothing out of the box.  This module
 * wires up the open/close behaviour manually by toggling the `.show` class
 * and the `aria-modal` / body-scroll-lock attributes that Bootstrap 5 would
 * normally manage.
 *
 * @module     block_backadel/filter_panel
 * @copyright  2026 Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

    // Trap focus in offcanvas — focus first focusable child.
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

    // Close when clicking the backdrop (outside the offcanvas).
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
