// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Phonetic Modal for Tiny.
 *
 * @module      tiny_phonetic/modal
 * @copyright   2026 LSU Online & Continuing Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalRegistry from 'core/modal_registry';

const PhoneticModal = class extends Modal {
    static TYPE = 'tiny_phonetic/modal';
    static TEMPLATE = 'tiny_phonetic/modal';

    configure(cfg) {
        super.configure(Object.assign({}, cfg, {
            removeOnClose: true,
            large: true,
        }));
    }
};

ModalRegistry.register(PhoneticModal.TYPE, PhoneticModal, PhoneticModal.TEMPLATE);

export default PhoneticModal;
