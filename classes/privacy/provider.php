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
 * Privacy provider.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_atrisk\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider, driven by the TABLES map below.
 *
 * @package    local_atrisk
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Each table with personal data. The owner's rows are deleted with the owner; actor fields (who
     * changed or decided something) are set to 0 instead, so the owner's record stays whole.
     * 'instance' is the field holding the context instance id, or null for the system context.
     */
    private const TABLES = [
        'local_atrisk_flag' => [
            'key' => 'flag',
            'level' => CONTEXT_COURSE,
            'instance' => 'courseid',
            'owner' => 'userid',
            'actors' => [],
            'fields' => ['userid', 'courseid', 'inactive', 'missed', 'grade', 'timecreated'],
        ],
        'local_atrisk_contact' => [
            'key' => 'contact',
            'level' => CONTEXT_COURSE,
            'instance' => 'courseid',
            'owner' => 'userid',
            'actors' => ['contactedby'],
            'fields' => ['userid', 'courseid', 'contactedby', 'note', 'timecreated'],
        ],
    ];

    /**
     * Describe the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        foreach (self::TABLES as $table => $t) {
            $fields = [];
            foreach ($t['fields'] as $f) {
                $fields[$f] = "privacy:metadata:{$t['key']}:$f";
            }
            $collection->add_database_table($table, $fields, "privacy:metadata:{$t['key']}");
        }
        return $collection;
    }

    /**
     * SQL matching rows where any user field is one of the given users.
     *
     * @param array $t table definition
     * @param array $userids
     * @return array [sql, params]
     */
    private static function user_where(array $t, array $userids): array {
        global $DB;
        $where = [];
        $params = [];
        foreach (array_merge([$t['owner']], $t['actors']) as $f) {
            [$in, $p] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $where[] = "t.$f $in";
            $params += $p;
        }
        return ['(' . implode(' OR ', $where) . ')', $params];
    }

    /**
     * Contexts holding data about the user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $list = new contextlist();
        foreach (self::TABLES as $table => $t) {
            [$where, $params] = self::user_where($t, [$userid]);
            if ($t['instance'] === null) {
                if ($DB->record_exists_sql("SELECT 1 FROM {{$table}} t WHERE $where", $params)) {
                    $list->add_system_context();
                }
                continue;
            }
            $params['lvl'] = $t['level'];
            $list->add_from_sql(
                "SELECT ctx.id FROM {{$table}} t
                   JOIN {context} ctx ON ctx.instanceid = t.{$t['instance']} AND ctx.contextlevel = :lvl
                  WHERE $where",
                $params
            );
        }
        return $list;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $ctx = $userlist->get_context();
        foreach (self::TABLES as $table => $t) {
            if ($ctx->contextlevel != $t['level']) {
                continue;
            }
            foreach (array_merge([$t['owner']], $t['actors']) as $f) {
                $sql = "SELECT $f FROM {{$table}} WHERE $f > 0";
                $params = [];
                if ($t['instance'] !== null) {
                    $sql .= " AND {$t['instance']} = :i";
                    $params['i'] = $ctx->instanceid;
                }
                $userlist->add_from_sql($f, $sql, $params);
            }
        }
    }

    /**
     * Export the user's rows, one file per row.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            foreach (self::TABLES as $table => $t) {
                if ($ctx->contextlevel != $t['level']) {
                    continue;
                }
                [$where, $params] = self::user_where($t, [$userid]);
                if ($t['instance'] !== null) {
                    $where .= " AND t.{$t['instance']} = :i";
                    $params['i'] = $ctx->instanceid;
                }
                $rows = $DB->get_records_sql("SELECT t.* FROM {{$table}} t WHERE $where ORDER BY t.id", $params);
                foreach ($rows as $r) {
                    $data = [];
                    foreach ($t['fields'] as $f) {
                        $data[$f] = (strpos($f, 'time') === 0 && $r->$f) ? transform::datetime($r->$f) : $r->$f;
                    }
                    writer::with_context($ctx)->export_data(
                        [get_string('pluginname', 'local_atrisk'), $t['key'], $r->id],
                        (object) $data
                    );
                }
            }
        }
    }

    /**
     * Delete everything in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        foreach (self::TABLES as $table => $t) {
            if ($context->contextlevel != $t['level']) {
                continue;
            }
            if ($t['instance'] === null) {
                $DB->delete_records($table);
            } else {
                $DB->delete_records($table, [$t['instance'] => $context->instanceid]);
            }
        }
    }

    /**
     * Delete one user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            self::delete_users($ctx, [$userid]);
        }
    }

    /**
     * Delete several users' data in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        self::delete_users($userlist->get_context(), $userlist->get_userids());
    }

    /**
     * Owners lose their rows; actors lose only their id on other people's rows.
     *
     * @param \context $ctx
     * @param int[] $userids
     */
    private static function delete_users(\context $ctx, array $userids): void {
        global $DB;
        if (!$userids) {
            return;
        }
        foreach (self::TABLES as $table => $t) {
            if ($ctx->contextlevel != $t['level']) {
                continue;
            }
            $scope = '';
            $scopeparams = [];
            if ($t['instance'] !== null) {
                $scope = " AND {$t['instance']} = :i";
                $scopeparams['i'] = $ctx->instanceid;
            }
            [$in, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $DB->delete_records_select($table, "{$t['owner']} $in" . $scope, $params + $scopeparams);
            foreach ($t['actors'] as $f) {
                [$in, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                $DB->set_field_select($table, $f, 0, "$f $in" . $scope, $params + $scopeparams);
            }
        }
    }
}
