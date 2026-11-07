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

namespace local_atrisk\output;

/**
 * Tests for the category page's template context.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_atrisk\output\category_page
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_atrisk\output\category_page::class)]
final class category_page_test extends \advanced_testcase {
    /** @var int|null course the rows point at, made on first use */
    private ?int $courseid = null;

    /**
     * A flag row as contacts::for_category returns it.
     *
     * @param array $over fields to override
     * @return \stdClass
     */
    private function row(array $over = []): \stdClass {
        $this->courseid = $this->courseid ?? (int) $this->getDataGenerator()->create_course()->id;
        $courseid = $this->courseid;
        return (object) ($over + [
            'id' => 1, 'userid' => 5, 'courseid' => $courseid, 'coursename' => 'Cells & Tissues', 'inactive' => 3, 'missed' => 0,
            'grade' => null, 'firstname' => 'Ana', 'lastname' => 'Silva', 'firstnamephonetic' => '', 'lastnamephonetic' => '',
            'middlename' => '', 'alternatename' => '', 'lastnote' => null, 'lastcontactedby' => null, 'lastcontacted' => null,
        ]);
    }

    public function test_only_reasons_over_the_thresholds_are_shown(): void {
        $this->resetAfterTest();
        set_config('inactivedays', 14, 'local_atrisk');
        set_config('misseddeadlines', 2, 'local_atrisk');
        set_config('gradebelow', 50, 'local_atrisk');
        $cat = $this->getDataGenerator()->create_category();
        $page = new category_page(\core_course_category::get($cat->id, MUST_EXIST, true), [
            $this->row(['inactive' => 20, 'missed' => 1, 'grade' => 72.5]),
            $this->row(['id' => 2, 'inactive' => 2, 'missed' => 3, 'grade' => 41.25]),
        ]);
        $ctx = $page->export_for_template(new \renderer_base(new \moodle_page(), ''));

        $this->assertSame(2, $ctx['count']);
        $this->assertSame(['inactive'], array_column($ctx['rows'][0]['reasons'], 'kind'));
        $this->assertSame('20 days without a visit', $ctx['rows'][0]['reasons'][0]['label']);
        $this->assertSame(['missed', 'grade'], array_column($ctx['rows'][1]['reasons'], 'kind'));
        $this->assertSame('3 missed', $ctx['rows'][1]['reasons'][0]['label']);
        $this->assertSame('Course total 41.25%', $ctx['rows'][1]['reasons'][1]['label']);
        $this->assertSame('Cells & Tissues', $ctx['rows'][0]['course']);
        $this->assertFalse($ctx['rows'][0]['contacted']);
    }

    public function test_the_latest_contact_shows_who_when_and_the_note(): void {
        $this->resetAfterTest();
        $cat = $this->getDataGenerator()->create_category();
        $staff = $this->getDataGenerator()->create_user(['firstname' => 'Rui', 'lastname' => 'Costa']);
        $page = new category_page(\core_course_category::get($cat->id, MUST_EXIST, true), [
            $this->row(['inactive' => 30, 'lastnote' => 'Phoned', 'lastcontactedby' => $staff->id, 'lastcontacted' => 1767225600]),
        ]);
        $ctx = $page->export_for_template(new \renderer_base(new \moodle_page(), ''));

        $this->assertSame('Rui Costa', $ctx['rows'][0]['contacted']['by']);
        $this->assertSame('Phoned', $ctx['rows'][0]['contacted']['note']);
        $this->assertNotEmpty($ctx['rows'][0]['contacted']['when']);
    }

    public function test_an_empty_category_says_so(): void {
        $this->resetAfterTest();
        $cat = $this->getDataGenerator()->create_category();
        $ctx = (new category_page(\core_course_category::get($cat->id, MUST_EXIST, true), []))
            ->export_for_template(new \renderer_base(new \moodle_page(), ''));
        $this->assertTrue($ctx['empty']);
        $this->assertSame([], $ctx['rows']);
    }
}
