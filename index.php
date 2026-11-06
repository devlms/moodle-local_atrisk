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
 * Categories the user can see at-risk students in, with how many are listed.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/atrisk/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_atrisk'));
$PAGE->set_heading(get_string('pluginname', 'local_atrisk'));

$cats = core_course_category::make_categories_list('local/atrisk:view');
if (!$cats) {
    throw new required_capability_exception($context, 'local/atrisk:view', 'nopermissions', '');
}
$items = [];
foreach ($cats as $id => $name) {
    $items[] = [
        'name' => $name,
        'url' => (new moodle_url('/local/atrisk/category.php', ['id' => $id]))->out(false),
        'count' => count(\local_atrisk\local\contacts::for_category($id)),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_atrisk/index', ['categories' => $items]);
echo $OUTPUT->footer();
