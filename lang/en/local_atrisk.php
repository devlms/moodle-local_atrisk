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
 * English strings for local_atrisk.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['atrisk:view'] = 'See at-risk students in a category and mark them as contacted';
$string['event_student_contacted'] = 'At-risk student contacted';
$string['gradebelow'] = 'Course total below (%)';
$string['gradebelow_desc'] = 'A student whose course total is below this percentage is flagged. 0 turns this off.';
$string['inactivedays'] = 'Days without a visit';
$string['inactivedays_desc'] = 'A student who has not opened an enrolled course for this many days is flagged.';
$string['misseddeadlines'] = 'Missed deadlines';
$string['misseddeadlines_desc'] = 'A student with at least this many assignments past their due date and not submitted is flagged.';
$string['pluginname'] = 'At-risk students';
$string['privacy:metadata:contact'] = 'Who contacted a flagged student, when, and a note.';
$string['privacy:metadata:contact:contactedby'] = 'The staff member who contacted the student.';
$string['privacy:metadata:contact:courseid'] = 'The course.';
$string['privacy:metadata:contact:note'] = 'What was said or agreed.';
$string['privacy:metadata:contact:timecreated'] = 'When it was created.';
$string['privacy:metadata:contact:userid'] = 'The user the record belongs to.';
$string['privacy:metadata:flag'] = 'Students currently flagged in a course, rebuilt by a scheduled task.';
$string['privacy:metadata:flag:courseid'] = 'The course.';
$string['privacy:metadata:flag:grade'] = 'The course total as a percentage.';
$string['privacy:metadata:flag:inactive'] = 'Days since the student last opened the course.';
$string['privacy:metadata:flag:missed'] = 'Assignments past their due date and not submitted.';
$string['privacy:metadata:flag:timecreated'] = 'When it was created.';
$string['privacy:metadata:flag:userid'] = 'The user the record belongs to.';
$string['task_refresh_flags'] = 'Refresh at-risk students';
