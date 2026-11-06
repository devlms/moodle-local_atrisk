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
 * Works out which students in a course may be falling behind, and stores them as flags.
 *
 * A student is flagged for any of three reasons, each set in the plugin settings:
 * no visit to the course for a number of days, assignments past their due date and not submitted,
 * or a course total under a percentage. Flags are rebuilt from scratch on each refresh, so a flag
 * disappears as soon as its reason does.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class detector {
    /**
     * Rebuild flags for every visible course except the front page.
     */
    public static function refresh_all(): void {
        global $DB;
        $ids = $DB->get_fieldset_select('course', 'id', 'id <> :site AND visible = 1', ['site' => SITEID]);
        foreach ($ids as $id) {
            self::refresh_course((int) $id);
        }
        // Hidden or deleted courses keep no flags.
        $DB->delete_records_select(
            'local_atrisk_flag',
            'courseid NOT IN (SELECT id FROM {course} WHERE visible = 1)'
        );
    }

    /**
     * Rebuild the flags for one course.
     *
     * @param int $courseid
     */
    public static function refresh_course(int $courseid): void {
        global $DB;
        $now = time();
        $inactivedays = max(0, (int) get_config('local_atrisk', 'inactivedays'));
        $missedlimit = max(0, (int) get_config('local_atrisk', 'misseddeadlines'));
        $gradebelow = max(0, (int) get_config('local_atrisk', 'gradebelow'));

        $students = self::students($courseid);
        $flags = [];
        if ($students) {
            $missed = $missedlimit ? self::missed($courseid, array_keys($students), $now) : [];
            $grades = $gradebelow ? self::grades($courseid, array_keys($students)) : [];
            foreach ($students as $userid => $s) {
                $since = $s->lastaccess ?: $s->enrolled;
                $inactive = (int) floor(($now - $since) / DAYSECS);
                $m = $missed[$userid] ?? 0;
                $g = $grades[$userid] ?? null;
                $why = ($inactivedays && $inactive >= $inactivedays)
                    || ($missedlimit && $m >= $missedlimit)
                    || ($gradebelow && $g !== null && $g < $gradebelow);
                if ($why) {
                    $flags[] = (object) [
                        'userid' => $userid,
                        'courseid' => $courseid,
                        'inactive' => $inactive,
                        'missed' => $m,
                        'grade' => $g,
                        'timecreated' => $now,
                    ];
                }
            }
        }
        $tx = $DB->start_delegated_transaction();
        $DB->delete_records('local_atrisk_flag', ['courseid' => $courseid]);
        if ($flags) {
            $DB->insert_records('local_atrisk_flag', $flags);
        }
        $tx->allow_commit();
    }

    /**
     * Active students in the course, with their last visit and when they were enrolled.
     *
     * Students are users with an active enrolment and a role from the student archetype.
     *
     * @param int $courseid
     * @return array userid => {lastaccess, enrolled}
     */
    private static function students(int $courseid): array {
        global $DB;
        $roles = array_keys(get_archetype_roles('student'));
        if (!$roles) {
            return [];
        }
        $context = \context_course::instance($courseid);
        [$rolesql, $params] = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'r');
        $now = time();
        $params += [
            'courseid' => $courseid, 'courseid2' => $courseid, 'ctx' => $context->id,
            'active' => ENROL_USER_ACTIVE, 'enabled' => ENROL_INSTANCE_ENABLED,
            'now1' => $now, 'now2' => $now,
        ];
        $sql = "SELECT u.id, COALESCE(la.timeaccess, 0) AS lastaccess,
                       MIN(CASE WHEN ue.timestart > 0 THEN ue.timestart ELSE ue.timecreated END) AS enrolled
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = :active
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid AND e.status = :enabled
                  JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :ctx AND ra.roleid $rolesql
             LEFT JOIN {user_lastaccess} la ON la.userid = u.id AND la.courseid = :courseid2
                 WHERE u.deleted = 0 AND u.suspended = 0
                   AND (ue.timestart = 0 OR ue.timestart <= :now1)
                   AND (ue.timeend = 0 OR ue.timeend > :now2)
              GROUP BY u.id, la.timeaccess";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Assignments past their due date (or extension) that each student has not submitted.
     *
     * @param int $courseid
     * @param int[] $userids
     * @param int $now
     * @return array userid => count
     */
    private static function missed(int $courseid, array $userids, int $now): array {
        global $DB;
        [$usql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $params += ['courseid' => $courseid, 'now1' => $now, 'now2' => $now, 'submitted' => 'submitted', 'modname' => 'assign'];
        $sql = "SELECT u.id, COUNT(a.id) AS missed
                  FROM {user} u
                  JOIN {assign} a ON a.course = :courseid AND a.duedate > 0 AND a.duedate < :now1
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
                                          AND cm.visible = 1 AND cm.deletioninprogress = 0
             LEFT JOIN {assign_user_flags} f ON f.assignment = a.id AND f.userid = u.id
             LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = u.id
                                            AND s.latest = 1 AND s.status = :submitted
                 WHERE u.id $usql
                   AND s.id IS NULL
                   AND (f.extensionduedate IS NULL OR f.extensionduedate = 0 OR f.extensionduedate < :now2)
              GROUP BY u.id";
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Course totals as percentages, for students who have one.
     *
     * @param int $courseid
     * @param int[] $userids
     * @return array userid => percentage
     */
    private static function grades(int $courseid, array $userids): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        grade_regrade_final_grades_if_required(get_course($courseid));
        $item = \grade_item::fetch_course_item($courseid);
        if (!$item || $item->grademax <= $item->grademin) {
            return [];
        }
        [$usql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $params['itemid'] = $item->id;
        // Natural aggregation leaves ungraded items out of each student's own maximum, which it stores
        // per student in rawgrademax. Use that when it is set, the item's range otherwise.
        $rows = $DB->get_records_select(
            'grade_grades',
            "itemid = :itemid AND finalgrade IS NOT NULL AND userid $usql",
            $params,
            '',
            'userid, finalgrade, rawgrademin, rawgrademax'
        );
        $out = [];
        foreach ($rows as $r) {
            $min = (float) $item->grademin;
            $max = (float) $item->grademax;
            if ($r->rawgrademax !== null && (float) $r->rawgrademax > (float) $r->rawgrademin) {
                $min = (float) $r->rawgrademin;
                $max = (float) $r->rawgrademax;
            }
            $out[$r->userid] = round(($r->finalgrade - $min) / ($max - $min) * 100, 2);
        }
        return $out;
    }
}
