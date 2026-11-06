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
 * Demo data for screenshots and preview sites. Never shipped (export-ignore).
 *
 * Run on a dev site: php local/atrisk/dev/seed.php
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

$gen = new testing_data_generator();
require_once($CFG->libdir . '/gradelib.php');
global $DB;

set_config('inactivedays', 14, 'local_atrisk');
set_config('misseddeadlines', 2, 'local_atrisk');
set_config('gradebelow', 50, 'local_atrisk');

$top = $DB->get_record('course_categories', ['idnumber' => 'ATR-SCI'])
    ?: $gen->create_category(['name' => 'Faculty of Science', 'idnumber' => 'ATR-SCI']);
$bio = $DB->get_record('course_categories', ['idnumber' => 'ATR-BIO'])
    ?: $gen->create_category(['name' => 'Biology', 'idnumber' => 'ATR-BIO', 'parent' => $top->id]);
$chem = $DB->get_record('course_categories', ['idnumber' => 'ATR-CHEM'])
    ?: $gen->create_category(['name' => 'Chemistry', 'idnumber' => 'ATR-CHEM', 'parent' => $top->id]);

$course = function (string $short, string $full, int $cat) use ($DB, $gen) {
    return $DB->get_record('course', ['shortname' => $short]) ?: $gen->create_course([
        'shortname' => $short, 'fullname' => $full, 'category' => $cat,
    ]);
};
$cell = $course('ATR-BIO101', 'Cell Biology', $bio->id);
$gen1 = $course('ATR-BIO210', 'Genetics', $bio->id);
$orgc = $course('ATR-CHEM201', 'Organic Chemistry', $chem->id);

// Name, days since last visit per course, missed deadlines per course, course totals per course.
$people = [
    ['Amara', 'Okafor', [$cell, 23, 0, 64.0]],
    ['Daniel', 'Moreau', [$cell, 2, 3, 58.5]],
    ['Yuki', 'Tanaka', [$gen1, 1, 0, 38.25]],
    ['Lucas', 'Ferreira', [$orgc, 31, 2, 41.0]],
    ['Sofia', 'Rossi', [$orgc, 3, 0, 82.0]],
    ['Omar', 'Haddad', [$gen1, 17, 0, 71.5]],
    ['Priya', 'Nair', [$cell, 1, 0, 88.0]],
];
$late = [];
foreach ([$cell, $gen1, $orgc] as $c) {
    if (!$DB->record_exists('assign', ['course' => $c->id])) {
        foreach ([9, 5, 2] as $i => $ago) {
            $gen->create_module('assign', [
                'course' => $c->id, 'name' => 'Problem set ' . ($i + 1), 'duedate' => time() - $ago * DAYSECS,
            ]);
        }
    }
    $late[$c->id] = array_values($DB->get_records('assign', ['course' => $c->id], 'duedate'));
}
foreach ($people as [$first, $last, [$c, $away, $missed, $total]]) {
    $username = strtolower('atr_' . $first);
    $u = $DB->get_record('user', ['username' => $username]) ?: $gen->create_user([
        'username' => $username, 'firstname' => $first, 'lastname' => $last,
    ]);
    $gen->enrol_user($u->id, $c->id, 'student', 'manual', time() - 70 * DAYSECS);
    $DB->delete_records('user_lastaccess', ['userid' => $u->id, 'courseid' => $c->id]);
    $DB->insert_record('user_lastaccess', ['userid' => $u->id, 'courseid' => $c->id, 'timeaccess' => time() - $away * DAYSECS]);
    // Everyone submitted except the deadlines they "missed".
    foreach ($late[$c->id] as $i => $a) {
        $DB->delete_records('assign_submission', ['assignment' => $a->id, 'userid' => $u->id]);
        if ($i >= $missed) {
            $DB->insert_record('assign_submission', [
                'assignment' => $a->id, 'userid' => $u->id, 'status' => 'submitted', 'latest' => 1,
                'attemptnumber' => 0, 'groupid' => 0, 'timecreated' => time(), 'timemodified' => time(),
            ]);
        }
    }
    $item = grade_item::fetch(['courseid' => $c->id, 'itemname' => 'Midterm exam'])
        ?: new grade_item(['courseid' => $c->id, 'itemname' => 'Midterm exam', 'grademax' => 100, 'itemtype' => 'manual']);
    if (!$item->id) {
        $item->insert();
    }
    $item->update_final_grade($u->id, $total, 'seed');
}

\local_atrisk\local\detector::refresh_all();

// One student already contacted, by an admin.
\core\session\manager::set_user(get_admin());
$amara = $DB->get_record('user', ['username' => 'atr_amara']);
if (!$DB->record_exists('local_atrisk_contact', ['userid' => $amara->id])) {
    $note = "Emailed her. She was ill for two weeks and will catch up with the recorded lectures.";
    \local_atrisk\local\contacts::record($amara->id, $cell->id, $note);
}
echo "Seeded local_atrisk: category {$top->id}\n";
