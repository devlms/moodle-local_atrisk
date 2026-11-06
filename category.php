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
 * Flagged students in one category and its subcategories.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$category = core_course_category::get($id, MUST_EXIST, true);
$context = context_coursecat::instance($id);
require_login();
require_capability('local/atrisk:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/atrisk/category.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$title = get_string('pluginname', 'local_atrisk') . ': ' . $category->get_formatted_name();
$PAGE->set_title($title);
$PAGE->set_heading($category->get_formatted_name());

$page = new \local_atrisk\output\category_page($category, \local_atrisk\local\contacts::for_category($id));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_atrisk'));
echo $OUTPUT->render_from_template('local_atrisk/category', $page->export_for_template($OUTPUT));
echo $OUTPUT->footer();
