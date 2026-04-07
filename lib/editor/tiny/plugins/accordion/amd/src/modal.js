// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Accordion attributes modal.
 *
 * @module      tiny_accordion/modal
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';

export default class AccordionAttributesModal extends Modal {
    static TYPE = 'tiny_accordion/attributes_modal';
    static TEMPLATE = 'tiny_accordion/attributes_modal';

    registerEventListeners() {
        super.registerEventListeners();
        this.registerCloseOnSave();
        this.registerCloseOnCancel();
    }

    configure(modalConfig) {
        modalConfig.show = true;
        modalConfig.removeOnClose = true;
        super.configure(modalConfig);
    }
}

AccordionAttributesModal.registerModalType();
