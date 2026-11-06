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
 * Contacts with flagged students, and the list a category page shows.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contacts {
    /**
     * Record that the current user contacted a student about a course.
     *
     * @param int $userid the student
     * @param int $courseid
     * @param string $note what was said or agreed
     * @return int the new record id
     */
    public static function record(int $userid, int $courseid, string $note): int {
        global $DB, $USER;
        $id = $DB->insert_record('local_atrisk_contact', [
            'userid' => $userid,
            'courseid' => $courseid,
            'contactedby' => $USER->id,
            'note' => trim($note),
            'timecreated' => time(),
        ]);
        \local_atrisk\event\student_contacted::create([
            'context' => \context_course::instance($courseid),
            'objectid' => $id,
            'relateduserid' => $userid,
        ])->trigger();
        return $id;
    }

    /**
     * Flagged students in courses of a category and its subcategories, with the latest contact.
     *
     * @param int $categoryid
     * @return array of rows: flag fields, user name fields, coursename, lastnote, lastcontactedby, lastcontacted
     */
    public static function for_category(int $categoryid): array {
        global $DB;
        $cat = \core_course_category::get($categoryid, MUST_EXIST, true);
        $path = $DB->get_field('course_categories', 'path', ['id' => $cat->id]);
        $names = \core_user\fields::for_name()->get_sql('u', false, '', '', false);
        $params = ['catid' => $cat->id, 'path' => $DB->sql_like_escape($path) . '/%'] + $names->params;
        $sql = "SELECT f.id, f.userid, f.courseid, f.inactive, f.missed, f.grade,
                       c.fullname AS coursename, c.category, {$names->selects},
                       lc.note AS lastnote, lc.contactedby AS lastcontactedby, lc.timecreated AS lastcontacted
                  FROM {local_atrisk_flag} f
                  JOIN {course} c ON c.id = f.courseid
                  JOIN {course_categories} cc ON cc.id = c.category
                  JOIN {user} u ON u.id = f.userid
             LEFT JOIN (SELECT userid, courseid, MAX(id) AS maxid
                          FROM {local_atrisk_contact}
                      GROUP BY userid, courseid) latest ON latest.userid = f.userid AND latest.courseid = f.courseid
             LEFT JOIN {local_atrisk_contact} lc ON lc.id = latest.maxid
                 WHERE (cc.id = :catid OR " . $DB->sql_like('cc.path', ':path') . ")
              ORDER BY lc.timecreated IS NOT NULL, f.missed DESC, f.inactive DESC, u.lastname, u.firstname, c.fullname";
        return $DB->get_records_sql($sql, $params);
    }
}
