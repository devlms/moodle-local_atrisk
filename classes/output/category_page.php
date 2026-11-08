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

use renderable;
use renderer_base;
use templatable;

/**
 * The list of flagged students in a category.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_page implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param \core_course_category $category
     * @param \stdClass[] $rows from contacts::for_category()
     */
    public function __construct(
        /** @var \core_course_category the category */
        private \core_course_category $category,
        /** @var \stdClass[] flag rows */
        private array $rows
    ) {
    }

    /**
     * Why this student is listed, using the current thresholds.
     *
     * @param \stdClass $r
     * @return array of [kind, label]
     */
    private function reasons(\stdClass $r): array {
        $inactivedays = (int) get_config('local_atrisk', 'inactivedays');
        $missed = (int) get_config('local_atrisk', 'misseddeadlines');
        $gradebelow = (int) get_config('local_atrisk', 'gradebelow');
        $out = [];
        if ($inactivedays && $r->inactive >= $inactivedays) {
            $out[] = ['kind' => 'inactive', 'label' => get_string('reason_inactive', 'local_atrisk', (int) $r->inactive)];
        }
        if ($missed && $r->missed >= $missed) {
            $out[] = ['kind' => 'missed', 'label' => get_string('reason_missed', 'local_atrisk', (int) $r->missed)];
        }
        if ($gradebelow && $r->grade !== null && (float) $r->grade < $gradebelow) {
            $grade = format_float((float) $r->grade, 2, true, true);
            $out[] = ['kind' => 'grade', 'label' => get_string('reason_grade', 'local_atrisk', $grade)];
        }
        return $out;
    }

    /**
     * Template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;
        $staff = [];
        $ids = array_filter(array_unique(array_map(fn($r) => (int) $r->lastcontactedby, $this->rows)));
        if ($ids) {
            $fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
            $staff = $DB->get_records_list('user', 'id', $ids, '', $fields);
        }
        $rows = [];
        foreach ($this->rows as $r) {
            $contacted = false;
            if ($r->lastcontacted) {
                $by = $staff[$r->lastcontactedby] ?? null;
                $contacted = [
                    'by' => $by ? fullname($by) : get_string('someone', 'local_atrisk'),
                    'when' => userdate($r->lastcontacted, get_string('strftimedatetimeshort', 'langconfig')),
                    'note' => (string) $r->lastnote,
                ];
            }
            $rows[] = [
                'name' => fullname($r),
                'profileurl' => (new \moodle_url('/user/view.php', ['id' => $r->userid, 'course' => $r->courseid]))->out(false),
                'course' => format_string($r->coursename, true, [
                    'context' => \context_course::instance($r->courseid),
                    'escape' => false,
                ]),
                'courseurl' => (new \moodle_url('/course/view.php', ['id' => $r->courseid]))->out(false),
                'reasons' => $this->reasons($r),
                'contacted' => $contacted,
                'contacturl' => (new \moodle_url('/local/atrisk/contact.php', [
                    'userid' => $r->userid,
                    'courseid' => $r->courseid,
                    'cat' => $this->category->id,
                ]))->out(false),
            ];
        }
        return [
            // Unescaped here: the template escapes once.
            'category' => $this->category->get_formatted_name(['escape' => false]),
            'count' => count($rows),
            'rows' => $rows,
            'empty' => !$rows,
        ];
    }
}
