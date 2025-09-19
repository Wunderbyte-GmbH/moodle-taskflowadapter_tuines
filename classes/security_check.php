<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit class to manage users.
 *
 * @package taskflowadapter_tuines
 * @author Georg Maißer
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace taskflowadapter_tuines;

use local_taskflow\local\assignment_status\assignment_status_facade;
use local_taskflow\local\assignments\assignments_facade;
use local_taskflow\local\external_adapter\external_api_base;
use local_taskflow\task\update_assignment;
use core\task\manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Class unit
 *
 * @author Georg Maißer
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class security_check {
    /** @var array Stores the external user data. */
    protected array $users = [];

    /**
     * Private constructor to prevent direct instantiation.
     * @param array $users
     */
    public function __construct($users) {
        $this->users = $users;
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param string $adapterfield
     * @param string $contractendfield
     * @return void
     */
    public function user_check($adapterfield, $contractendfield) {
        $missingpersons = $this->get_missing_persons($adapterfield);
        $this->inactivate_moodle_users($missingpersons);
        $this->activate_moodle_users($missingpersons);
        $this->set_all_assignments_of_missing_persons_dropped_out($missingpersons, $contractendfield);
        $this->open_all_dropped_out_assignments($missingpersons);
        return;
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param array $missingpersons
     * @return void
     */
    private function activate_moodle_users($missingpersons) {
        global $DB;

        if (!empty($missingpersons)) {
            $missingpersonsids = array_keys($missingpersons);
            [$notinsql, $notinparams] = $DB->get_in_or_equal($missingpersonsids, SQL_PARAMS_NAMED, 'param', false);
            $where = "suspended = 1 AND id $notinsql";
            $params = $notinparams;
        } else {
            $where = "suspended = 1";
            $params = [];
        }

        $suspendedusers = $DB->get_records_select('user', $where, $params, '', 'id, suspended, timemodified');

        foreach ($suspendedusers as $user) {
            $user->suspended = 0;
            $user->timemodified = time();
            external_api_base::$importing = false;
            user_update_user($user);
        }
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param array $missingpersons
     * @return void
     */
    private function inactivate_moodle_users($missingpersons) {
        global $DB;
        foreach ($missingpersons as $missingperson) {
            if (
                $missingperson->suspended == '1' ||
                is_siteadmin($missingperson->id)
            ) {
                mtrace("Skipped admin user with id {$missingperson->id}");
                continue;
            }

            $missingperson->suspended = 1;
            $missingperson->timemodified = time();

            user_update_user($missingperson);
            \core\session\manager::kill_user_sessions($missingperson->id);
        }
        return;
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param string $adapterfield
     * @return array
     */
    private function get_missing_persons($adapterfield) {
        global $DB;

        $userids = [];
        foreach ($this->users as $u) {
            $userids[] = (int)$u->id;
        }
        if (empty($adapterfield)) {
            return [];
        }

        $notempty = $DB->sql_isnotempty('d', 'data', false, true);

        $sql = "SELECT u.id, u.suspended, u.timemodified
                FROM {user} u
                JOIN {user_info_field} f ON f.shortname = :shortname
                JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = f.id
                WHERE $notempty
                AND u.deleted = 0";

        $params = ['shortname' => $adapterfield];

        if (!empty($userids)) {
            [$notin, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid', false);
            $sql .= " AND u.id $notin";
            $params += $inparams;
        }
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param array $missingpersons
     * @param string $contractendfield
     * @return void
     */
    private function set_all_assignments_of_missing_persons_dropped_out($missingpersons, $contractendfield) {
        foreach ($missingpersons as $missingperson) {
            assignments_facade::set_all_assignments_of_user_to_status(
                $missingperson->id,
                assignment_status_facade::get_status_identifier('droppedout')
            );
        }
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param array $missingpersons
     * @return void
     */
    private function open_all_dropped_out_assignments($missingpersons) {
        global $DB;

        $status = assignment_status_facade::get_status_identifier('droppedout');
        $sql = "SELECT a.*
            FROM {local_taskflow_assignment} a
            WHERE a.status = :status";
        $params = ['status' => $status];

        if (!empty($missingpersons) && is_array($missingpersons)) {
            $missingpersonsids = array_keys($missingpersons);
            [$notin, $notinparams] = $DB->get_in_or_equal(
                $missingpersonsids,
                SQL_PARAMS_NAMED,
                'mp',
                false
            );
            $sql .= " AND a.userid $notin";
            $params += $notinparams;
        }
        $assignments = $DB->get_records_sql($sql, $params);
        foreach ($assignments as $assignment) {
            if ($this->is_rule_still_valid($assignment)) {
                assignments_facade::reopen_missing_person_assignment($assignment->id);
                $task = new update_assignment();
                $task->set_custom_data([
                    'assignmentid' => $assignment->id,
                    'userid'   => $assignment->userid,
                    'id'   => $assignment->ruleid,
                ]);
                manager::queue_adhoc_task($task);
            }
        }
        return;
    }

    /**
     * Creates Supervisor with internalid in customfield.
     * @param stdClass $assignment
     * @return bool
     */
    private function is_rule_still_valid($assignment): bool {
        if (empty($assignment->unitid)) {
            return true;
        }
        return cohort_is_member($assignment->unitid, $assignment->userid);
    }
}
