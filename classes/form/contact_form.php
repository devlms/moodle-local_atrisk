<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_atrisk\form;

/**
 * Record that a flagged student was contacted.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contact_form extends \moodleform {
    /**
     * Fields.
     */
    protected function definition() {
        $f = $this->_form;
        $f->addElement('textarea', 'note', get_string('note', 'local_atrisk'), ['rows' => 4, 'cols' => 60]);
        $f->setType('note', PARAM_TEXT);
        $f->addRule('note', null, 'required', null, 'client');
        $f->addHelpButton('note', 'note', 'local_atrisk');
        $this->add_action_buttons(true, get_string('recordcontact', 'local_atrisk'));
    }

    /**
     * The note is required on the server too, not only in the browser.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim((string) ($data['note'] ?? '')) === '') {
            $errors['note'] = get_string('required');
        }
        return $errors;
    }
}
