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

use local_taskflow\local\assignments\assignments_facade;
use local_taskflow\local\assignments\status\assignment_status;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Class unit
 *
 * @author Georg Maißer
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Security_Check {
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
     * @return void
     */
    public function user_check($adapterfield, $contractendfield) {
        $missingpersons = $this->get_missing_persons($adapterfield);
        foreach ($missingpersons as $missingperson) {
            profile_load_custom_fields($missingperson);
            if (
                isset($missingperson->profile[$contractendfield]) &&
                $missingperson->profile[$contractendfield] < time()
            ) {
                assignments_facade::set_all_assignments_of_user_to_status(
                    $missingperson->id,
                    assignment_status::STATUS_DROPPED_OUT
                );
            }
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

        $sql = "SELECT u.*
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
}
