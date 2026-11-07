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

namespace local_atrisk\local;

/**
 * Tests for recording who contacted a flagged student, and for listing a category.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_atrisk\local\contacts
 * @covers     \local_atrisk\event\student_contacted
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_atrisk\local\contacts::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_atrisk\event\student_contacted::class)]
final class contacts_test extends \advanced_testcase {
    public function test_recording_a_contact_stores_who_when_and_the_note_and_logs_it(): void {
        global $DB;
        $this->resetAfterTest();
        $staff = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($staff);
        $sink = $this->redirectEvents();

        $id = contacts::record($student->id, $course->id, 'Phoned, will catch up by Friday');

        $row = $DB->get_record('local_atrisk_contact', ['id' => $id]);
        $this->assertEquals($student->id, $row->userid);
        $this->assertEquals($staff->id, $row->contactedby);
        $this->assertSame('Phoned, will catch up by Friday', $row->note);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\local_atrisk\event\student_contacted::class, $events[0]);
        $this->assertEquals($student->id, $events[0]->relateduserid);
        $this->assertEquals(\context_course::instance($course->id)->id, $events[0]->contextid);
    }

    public function test_category_list_has_flags_in_subcategories_with_the_latest_contact(): void {
        global $DB;
        $this->resetAfterTest();
        $top = $this->getDataGenerator()->create_category(['name' => 'Science']);
        $sub = $this->getDataGenerator()->create_category(['name' => 'Biology', 'parent' => $top->id]);
        $other = $this->getDataGenerator()->create_category(['name' => 'Arts']);
        $c1 = $this->getDataGenerator()->create_course(['category' => $sub->id, 'fullname' => 'Cells']);
        $c2 = $this->getDataGenerator()->create_course(['category' => $other->id]);
        $s1 = $this->getDataGenerator()->create_user(['firstname' => 'Ana', 'lastname' => 'Silva']);
        $s2 = $this->getDataGenerator()->create_user();
        foreach ([[$s1, $c1], [$s2, $c2]] as [$u, $c]) {
            $DB->insert_record('local_atrisk_flag', [
                'userid' => $u->id, 'courseid' => $c->id, 'inactive' => 20, 'missed' => 0, 'grade' => null,
                'timecreated' => time(),
            ]);
        }
        $this->setAdminUser();
        contacts::record($s1->id, $c1->id, 'First call');
        $this->waitForSecond();
        contacts::record($s1->id, $c1->id, 'Second call');

        $rows = contacts::for_category($top->id);

        $this->assertCount(1, $rows);
        $r = reset($rows);
        $this->assertEquals($s1->id, $r->userid);
        $this->assertSame('Cells', $r->coursename);
        $this->assertSame('Second call', $r->lastnote);
        $this->assertEquals(get_admin()->id, $r->lastcontactedby);
    }
}
