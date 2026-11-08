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
 * Navigation for local_atrisk.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add "At-risk students" to a category's settings menu for staff who can see it.
 *
 * @param navigation_node $navigation
 * @param context_coursecat $context
 */
function local_atrisk_extend_navigation_category_settings(navigation_node $navigation, context_coursecat $context) {
    if (has_capability('local/atrisk:view', $context)) {
        $navigation->add(
            get_string('pluginname', 'local_atrisk'),
            new moodle_url('/local/atrisk/category.php', ['id' => $context->instanceid]),
            navigation_node::TYPE_SETTING,
            null,
            'local_atrisk',
            new pix_icon('i/warning', '')
        );
    }
}

/**
 * Remove flags and contact notes of a course that is being deleted. After deletion its context is gone,
 * so the privacy API could no longer find them.
 *
 * @param stdClass $course
 */
function local_atrisk_pre_course_delete($course) {
    global $DB;
    $DB->delete_records('local_atrisk_flag', ['courseid' => $course->id]);
    $DB->delete_records('local_atrisk_contact', ['courseid' => $course->id]);
}
