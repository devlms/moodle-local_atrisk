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

require_login();
// Keyed by student and course, not by flag: flags are rebuilt every few hours and get new ids.
$userid = required_param('userid', PARAM_INT);
$course = get_course(required_param('courseid', PARAM_INT));
// The category the user came from: the course's own one or any category above it.
$catid = optional_param('cat', $course->category, PARAM_INT);
$path = explode('/', trim((string) $DB->get_field('course_categories', 'path', ['id' => $course->category]), '/'));
if (!in_array((string) $catid, $path, true)) {
    $catid = $course->category;
}
$context = context_coursecat::instance($catid);
require_capability('local/atrisk:view', $context);

$back = new moodle_url('/local/atrisk/category.php', ['id' => $catid]);
$student = core_user::get_user($userid, '*', MUST_EXIST);
if (!is_enrolled(context_course::instance($course->id), $student)) {
    throw new moodle_exception('notenrolled', 'local_atrisk');
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/atrisk/contact.php', ['userid' => $userid, 'courseid' => $course->id, 'cat' => $catid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('recordcontact', 'local_atrisk'));
$PAGE->set_heading(core_course_category::get($catid, MUST_EXIST, true)->get_formatted_name());
$PAGE->navbar->add(get_string('pluginname', 'local_atrisk'), $back);

$form = new \local_atrisk\form\contact_form($PAGE->url);

if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    \local_atrisk\local\contacts::record($userid, (int) $course->id, $data->note);
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
