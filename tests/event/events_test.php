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
 * Tests for the events.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_atrisk\event;

/**
 * Tests for the events.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_atrisk\event\student_contacted
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_atrisk\event\student_contacted::class)]
final class events_test extends \advanced_testcase {
    public function test_events_trigger_with_a_name_and_description(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $sink = $this->redirectEvents();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        \local_atrisk\local\contacts::record($student->id, $course->id, 'Called');
        $events = array_values(array_filter($sink->get_events(), fn($e) => $e->component === 'local_atrisk'));
        $this->assertSame(['\\local_atrisk\\event\\student_contacted'], array_map(fn($e) => '\\' . get_class($e), $events));
        foreach ($events as $e) {
            $this->assertNotEmpty($e::get_name());
            $this->assertNotEmpty($e->get_description());
        }
    }
}
