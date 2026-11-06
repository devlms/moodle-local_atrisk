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
 * Tests for the privacy provider.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_atrisk\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/**
 * Tests for the privacy provider.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_atrisk\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    public function test_metadata_names_every_table(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('local_atrisk'));
        $names = array_map(fn($i) => $i->get_name(), $collection->get_collection());
        foreach (['local_atrisk_flag', 'local_atrisk_contact'] as $table) {
            $this->assertContains($table, $names);
        }
    }

    public function test_find_export_and_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $alice = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $DB->insert_record('local_atrisk_flag', [
            'userid' => $alice->id,
            'courseid' => $course->id,
            'inactive' => 1,
            'missed' => 1,
            'grade' => 1.5,
            'timecreated' => time(),
        ]);
        $DB->insert_record('local_atrisk_flag', [
            'userid' => $bob->id,
            'courseid' => $course->id,
            'inactive' => 1,
            'missed' => 1,
            'grade' => 1.5,
            'timecreated' => time(),
        ]);
        $DB->insert_record('local_atrisk_contact', [
            'userid' => $alice->id,
            'courseid' => $course->id,
            'contactedby' => $bob->id,
            'note' => 'Sample',
            'timecreated' => time(),
        ]);
        $DB->insert_record('local_atrisk_contact', [
            'userid' => $bob->id,
            'courseid' => $course->id,
            'contactedby' => $alice->id,
            'note' => 'Sample',
            'timecreated' => time(),
        ]);

        // Table local_atrisk_flag.
        $ctx = \context_course::instance($course->id);
        $this->assertContains((int) $ctx->id, array_map('intval', provider::get_contexts_for_userid($alice->id)->get_contextids()));
        $userlist = new \core_privacy\local\request\userlist($ctx, 'local_atrisk');
        provider::get_users_in_context($userlist);
        $this->assertContains((int) $alice->id, array_map('intval', $userlist->get_userids()));
        $this->export_context_data_for_user($alice->id, $ctx, 'local_atrisk');
        $this->assertTrue(writer::with_context($ctx)->has_any_data());
        provider::delete_data_for_user(new approved_contextlist($alice, 'local_atrisk', [$ctx->id]));
        $this->assertSame(0, $DB->count_records('local_atrisk_flag', ['userid' => $alice->id]));
        $this->assertGreaterThan(0, $DB->count_records('local_atrisk_flag', ['userid' => $bob->id]));
        provider::delete_data_for_all_users_in_context($ctx);
        $this->assertSame(0, $DB->count_records('local_atrisk_flag', ['courseid' => $course->id]));
        // Table local_atrisk_contact.
        $ctx = \context_course::instance($course->id);
        $this->assertContains((int) $ctx->id, array_map('intval', provider::get_contexts_for_userid($alice->id)->get_contextids()));
        $userlist = new \core_privacy\local\request\userlist($ctx, 'local_atrisk');
        provider::get_users_in_context($userlist);
        $this->assertContains((int) $alice->id, array_map('intval', $userlist->get_userids()));
        $this->export_context_data_for_user($alice->id, $ctx, 'local_atrisk');
        $this->assertTrue(writer::with_context($ctx)->has_any_data());
        provider::delete_data_for_user(new approved_contextlist($alice, 'local_atrisk', [$ctx->id]));
        $this->assertSame(0, $DB->count_records('local_atrisk_contact', ['userid' => $alice->id]));
        $this->assertSame(0, $DB->count_records('local_atrisk_contact', ['contactedby' => $alice->id]));
        $this->assertGreaterThan(0, $DB->count_records('local_atrisk_contact', ['userid' => $bob->id]));
        provider::delete_data_for_all_users_in_context($ctx);
        $this->assertSame(0, $DB->count_records('local_atrisk_contact', ['courseid' => $course->id]));
    }
}
