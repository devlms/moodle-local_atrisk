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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_atrisk\local\detector::class)]
/**
 * Tests for the detector: who gets flagged and why.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_atrisk\local\detector
 */
final class detector_test extends \advanced_testcase {
    /** @var \stdClass course used by every test */
    private \stdClass $course;

    /** @var \grade_item|null the one graded item in the course, made on first use */
    private ?\grade_item $item = null;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('inactivedays', 14, 'local_atrisk');
        set_config('misseddeadlines', 2, 'local_atrisk');
        set_config('gradebelow', 50, 'local_atrisk');
        $this->course = $this->getDataGenerator()->create_course();
    }

    /**
     * Enrol a student who opened the course the given number of days ago.
     *
     * @param int|null $daysago null for never
     * @return \stdClass
     */
    private function student(?int $daysago = 1): \stdClass {
        global $DB;
        $u = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($u->id, $this->course->id, 'student', 'manual', time() - 60 * DAYSECS);
        if ($daysago !== null) {
            $DB->insert_record('user_lastaccess', [
                'userid' => $u->id, 'courseid' => $this->course->id, 'timeaccess' => time() - $daysago * DAYSECS,
            ]);
        }
        return $u;
    }

    /**
     * An assignment that was due the given number of days ago.
     *
     * @param int $daysago
     * @return \stdClass assign instance
     */
    private function assignment(int $daysago): \stdClass {
        return $this->getDataGenerator()->create_module('assign', [
            'course' => $this->course->id, 'duedate' => time() - $daysago * DAYSECS,
        ]);
    }

    /**
     * Mark a submission as submitted.
     *
     * @param \stdClass $assign
     * @param \stdClass $user
     */
    private function submit(\stdClass $assign, \stdClass $user): void {
        global $DB;
        $DB->insert_record('assign_submission', [
            'assignment' => $assign->id, 'userid' => $user->id, 'status' => 'submitted', 'latest' => 1,
            'attemptnumber' => 0, 'groupid' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
    }

    /**
     * Give a student a course total.
     *
     * @param \stdClass $user
     * @param float $grade out of 100
     */
    private function grade(\stdClass $user, float $grade): void {
        if (!$this->item) {
            $made = $this->getDataGenerator()->create_grade_item(['courseid' => $this->course->id, 'grademax' => 100]);
            $this->item = \grade_item::fetch(['id' => $made->id]);
        }
        $this->item->update_final_grade($user->id, $grade, 'test');
    }

    /**
     * Flags for the test course, by user id.
     *
     * @return array
     */
    private function flags(): array {
        global $DB;
        detector::refresh_course($this->course->id);
        $rows = $DB->get_records('local_atrisk_flag', ['courseid' => $this->course->id]);
        $out = [];
        foreach ($rows as $r) {
            $out[$r->userid] = $r;
        }
        return $out;
    }

    public function test_a_student_who_has_not_visited_for_a_while_is_flagged(): void {
        $away = $this->student(20);
        $here = $this->student(2);
        $flags = $this->flags();
        $this->assertArrayHasKey($away->id, $flags);
        $this->assertSame(20, (int) $flags[$away->id]->inactive);
        $this->assertArrayNotHasKey($here->id, $flags);
    }

    public function test_a_student_who_never_visited_counts_from_enrolment(): void {
        $never = $this->student(null);
        $flags = $this->flags();
        $this->assertSame(60, (int) $flags[$never->id]->inactive);
    }

    public function test_missed_deadlines_count_only_past_due_unsubmitted_work(): void {
        $u = $this->student(1);
        $ok = $this->student(1);
        $a1 = $this->assignment(3);
        $a2 = $this->assignment(5);
        $this->getDataGenerator()->create_module('assign', ['course' => $this->course->id, 'duedate' => time() + DAYSECS]);
        $this->submit($a1, $ok);
        $this->submit($a2, $ok);
        $flags = $this->flags();
        $this->assertSame(2, (int) $flags[$u->id]->missed);
        $this->assertArrayNotHasKey($ok->id, $flags);
    }

    public function test_an_extension_that_has_not_run_out_is_not_a_missed_deadline(): void {
        global $DB;
        $u = $this->student(1);
        $a1 = $this->assignment(3);
        $this->assignment(4);
        $DB->insert_record('assign_user_flags', [
            'assignment' => $a1->id, 'userid' => $u->id, 'extensionduedate' => time() + DAYSECS,
            'locked' => 0, 'mailed' => 0, 'workflowstate' => '', 'allocatedmarker' => 0,
        ]);
        $this->assertArrayNotHasKey($u->id, $this->flags());
    }

    public function test_a_low_course_total_is_flagged_with_the_percentage(): void {
        $low = $this->student(1);
        $fine = $this->student(1);
        $this->grade($low, 35);
        $this->grade($fine, 80);
        $flags = $this->flags();
        $this->assertEqualsWithDelta(35.0, (float) $flags[$low->id]->grade, 0.01);
        $this->assertArrayNotHasKey($fine->id, $flags);
    }

    public function test_ungraded_items_do_not_count_against_the_course_total(): void {
        $u = $this->student(1);
        $this->grade($u, 70);
        $this->getDataGenerator()->create_grade_item(['courseid' => $this->course->id, 'grademax' => 100]);
        $this->getDataGenerator()->create_grade_item(['courseid' => $this->course->id, 'grademax' => 100]);
        $this->assertArrayNotHasKey($u->id, $this->flags());
    }

    public function test_grade_threshold_zero_turns_the_grade_check_off(): void {
        set_config('gradebelow', 0, 'local_atrisk');
        $low = $this->student(1);
        $this->grade($low, 10);
        $this->assertArrayNotHasKey($low->id, $this->flags());
    }

    public function test_teachers_and_suspended_students_are_not_flagged(): void {
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $this->course->id, 'editingteacher');
        $gone = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($gone->id, $this->course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $flags = $this->flags();
        $this->assertArrayNotHasKey($teacher->id, $flags);
        $this->assertArrayNotHasKey($gone->id, $flags);
    }

    public function test_refreshing_clears_a_flag_once_the_reason_is_gone(): void {
        global $DB;
        $u = $this->student(20);
        $this->assertArrayHasKey($u->id, $this->flags());
        $DB->set_field('user_lastaccess', 'timeaccess', time(), ['userid' => $u->id, 'courseid' => $this->course->id]);
        $this->assertArrayNotHasKey($u->id, $this->flags());
    }

    public function test_refresh_all_skips_hidden_courses(): void {
        global $DB;
        $u = $this->student(20);
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        $this->getDataGenerator()->enrol_user($u->id, $hidden->id, 'student');
        detector::refresh_all();
        $this->assertTrue($DB->record_exists('local_atrisk_flag', ['courseid' => $this->course->id, 'userid' => $u->id]));
        $this->assertFalse($DB->record_exists('local_atrisk_flag', ['courseid' => $hidden->id]));
    }
}
