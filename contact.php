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

/**
 * Record that a flagged student was contacted.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$id = required_param('id', PARAM_INT);
$flag = $DB->get_record('local_atrisk_flag', ['id' => $id], '*', MUST_EXIST);
$course = get_course($flag->courseid);
$context = context_coursecat::instance($course->category);
require_login();
require_capability('local/atrisk:view', $context);

$back = new moodle_url('/local/atrisk/category.php', ['id' => $course->category]);
$student = core_user::get_user($flag->userid, '*', MUST_EXIST);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/atrisk/contact.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('recordcontact', 'local_atrisk'));
$PAGE->set_heading(core_course_category::get($course->category, MUST_EXIST, true)->get_formatted_name());

$form = new \local_atrisk\form\contact_form($PAGE->url);
$form->set_data(['id' => $id]);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    \local_atrisk\local\contacts::record($flag->userid, $flag->courseid, $data->note);
    $done = get_string('contactrecorded', 'local_atrisk', s(fullname($student)));
    redirect($back, $done, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('contactheading', 'local_atrisk', (object) [
    'name' => s(fullname($student)),
    'course' => format_string($course->fullname, true, ['context' => context_course::instance($course->id)]),
]));
$form->display();
echo $OUTPUT->footer();
