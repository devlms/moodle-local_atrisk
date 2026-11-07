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
 * Tests for the scheduled tasks.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_atrisk\task;

/**
 * Tests for the scheduled tasks.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_atrisk\task\refresh_flags
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_atrisk\task\refresh_flags::class)]
final class tasks_test extends \advanced_testcase {
    public function test_tasks_are_registered_and_run(): void {
        $this->resetAfterTest();
        foreach (['\\local_atrisk\\task\\refresh_flags'] as $class) {
            $task = \core\task\manager::get_scheduled_task($class);
            $this->assertNotNull($task);
            $this->assertNotEmpty($task->get_name());
            ob_start();
            $task->execute();
            ob_end_clean();
        }
    }
}
