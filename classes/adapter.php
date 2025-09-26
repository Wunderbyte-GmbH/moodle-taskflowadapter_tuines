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

use DateTime;
use local_taskflow\event\unit_updated;
use local_taskflow\local\assignment_process\longleave_facade;
use local_taskflow\local\assignments\assignments_facade;
use local_taskflow\local\supervisor\supervisor;
use local_taskflow\plugininfo\taskflowadapter;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/cohort/lib.php');

use local_taskflow\local\external_adapter\external_api_interface;
use local_taskflow\local\external_adapter\external_api_base;
use stdClass;

/**
 * Class unit
 *
 * @author Georg Maißer
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adapter extends external_api_base implements external_api_interface {
    /** @var array Stores the external user data. */
    protected array $issidmatching = [];

    /**
     * Private constructor to prevent direct instantiation.
     */
    public function process_incoming_data() {
        $this->create_or_update_units();
        $this->create_or_update_users();
        $this->create_or_update_supervisor();
        $this->save_all_user_infos($this->users);
        $securitymanager = new security_check(
            $this->users
        );
        $securitymanager->user_check(
            $this->return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_EXTERNALID),
            $this->return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_CONTRACTEND)
        );

        // Trigger unit update.
        foreach ($this->unitmapping as $unitid) {
            $event = unit_updated::create([
                'objectid' => $unitid,
                'context'  => \context_system::instance(),
                'userid'   => $unitid,
                'other'    => [
                    'unitid' => $unitid,
                ],
            ]);
            $event->trigger();
        }
    }

    /**
     * Creates Supervisor with internalid in customfield.
     */
    private function create_or_update_supervisor() {
        foreach ($this->users as $user) {
            $shortname = $this->return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_SUPERVISOR);
            $supervisorid = (int)($user->profile[$shortname] ?? 0);

            // Run the error validation on the user data.
            $this->usererror = true;
            $this->supervisor_validation($supervisorid);
            if (!$this->usererror) {
                continue;
            }
            if ($supervisorid) {
                $supervisorinstance = new supervisor($supervisorid, $user->id);
                $supervisorinstance->set_supervisor_for_user($supervisorid, $shortname, $user, $this->users);
            }
        }
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function create_or_update_units() {
        foreach ($this->externaldata->targetGroups as $targetgroup) {
            $translatedtargetgroup = $this->translate_incoming_target_groups($targetgroup);
            $unit = $this->unitrepo->create_unit((object)$translatedtargetgroup);
            $this->unitmapping[$translatedtargetgroup['unitid']] = $unit->get_id();
        }
    }

    /**
     *
     * Private constructor to prevent direct instantiation.
     */
    private function create_or_update_users() {
        external_api_base::$importing = true;
        foreach ($this->externaldata->persons as $newuser) {
            $jsonkey = $this->return_jsonkey_for_functionname(taskflowadapter::TRANSLATOR_USER_TARGETGROUP);
            $units = $newuser->$jsonkey;

            $this->usererror = true;
            $this->units_validation($units);
            $translateduser = $this->translate_incoming_data($newuser);
            if (!$this->usererror) {
                continue;
            }

            // We need to get the old user record.
            // We need to compare it to the translated user data.
            // we need to update if necessary.
            $externalid = $this->return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_EXTERNALID);
            if (empty(self::$usersbyemail[$translateduser[$externalid]])) {
                // If the user does not exist, we create a new one.
                $olduser = $this->userrepo->get_user_by_mail(
                    $translateduser[$externalid]
                );
            } else {
                // If the user exists, we get the old user.
                $olduser = self::$usersbyemail[$translateduser[$externalid]];
            }

            if ($olduser) {
                 // We store the user for the whole process.
                self::$usersbyid[$olduser->id] = $olduser;
                self::$usersbyemail[$olduser->email] = $olduser;

                $oldtargetgroup = $this->return_value_for_functionname(
                    taskflowadapter::TRANSLATOR_USER_TARGETGROUP,
                    $olduser
                );
                if (!is_array($oldtargetgroup)) {
                    $oldtargetgroup = json_decode($oldtargetgroup, true);
                }
            } else {
                $oldtargetgroup = [];
            }
            $oldonlongleave = empty($olduser) ? null :
                $this->return_value_for_functionname(taskflowadapter::TRANSLATOR_USER_LONG_LEAVE, $olduser);
            $oldtargetgroup = !empty($oldtargetgroup) ? $oldtargetgroup : [];

            $newuser = $this->userrepo->update_or_create($translateduser);
            $externalidfieldname = $this->return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_EXTERNALID);
            $this->create_user_with_customfields($newuser, $translateduser, $externalidfieldname);
            $externalid = $this->return_value_for_functionname(taskflowadapter::TRANSLATOR_USER_EXTERNALID, $newuser);

            $this->issidmatching[$externalid] = $newuser->id;

            $newtargetgroup = $this->return_value_for_functionname(taskflowadapter::TRANSLATOR_USER_TARGETGROUP, $newuser);
            if (
                is_array($oldtargetgroup)
                && is_array($newtargetgroup)
            ) {
                $this->invalidate_units_on_change(
                    $oldtargetgroup,
                    $newtargetgroup,
                    $newuser->id
                );
            }
            $onlongleave = $this->return_value_for_functionname(taskflowadapter::TRANSLATOR_USER_LONG_LEAVE, $newuser) ?? 0;
            if (
                $this->contract_ended($newuser) ||
                $onlongleave
            ) {
                longleave_facade::longleave_activation($newuser->id);
            } else if ($this->on_longleave_change($oldonlongleave, $onlongleave)) {
                longleave_facade::longleave_deactivation($newuser->id);
            } else {
                self::create_or_update_unit_members($translateduser, $newuser);
            }
        }
    }

    /**
     * Private constructor to prevent direct instantiation.
     * @param int $oldonlongleave
     * @param int $onlongleave
     * @return bool
     */
    private function on_longleave_change(
        $oldonlongleave,
        $onlongleave
    ) {
        if (
            is_number($oldonlongleave) &&
            $oldonlongleave != $onlongleave
        ) {
            return true;
        }
        return false;
    }

    /**
     * Private constructor to prevent direct instantiation.
     * @param array $olduserunits
     * @param array $newuserunits
     * @param int $userid
     * @return void
     */
    private function invalidate_units_on_change(
        $olduserunits,
        $newuserunits,
        $userid
    ) {
        $invalidunits = array_diff($olduserunits, $newuserunits);
        if (count($invalidunits) >= 1) {
            $invalidmoodleunitids = [];
            foreach ($invalidunits as $invalidunit) {
                $invalidmoodleunitids[] = $this->unitmapping[$invalidunit];
                if (cohort_is_member($this->unitmapping[$invalidunit], $userid)) {
                    cohort_remove_member(
                        $this->unitmapping[$invalidunit],
                        $userid
                    );
                }
            }
            assignments_facade::set_user_units_assignments_inactive(
                $userid,
                $invalidmoodleunitids
            );
        }
    }

    /**
     * Private constructor to prevent direct instantiation.
     * @param stdClass $user
     * @return bool
     */
    private function contract_ended($user) {
        $storedenddate = $this->return_value_for_functionname(
            taskflowadapter::TRANSLATOR_USER_CONTRACTEND,
            $user
        ) ?? '';
        $enddate = DateTime::createFromFormat(
            'Y-m-d',
            $storedenddate
        );

        $this->dates_validation($enddate, $storedenddate);

        $now = new DateTime();
        if (
            $enddate &&
            $enddate < $now
        ) {
            return true;
        }
        return false;
    }

    /**
     * Private constructor to prevent direct instantiation.
     * @param array $translateduser
     * @param stdClass $user
     */
    private function create_or_update_unit_members($translateduser, $user) {
        $unitid = $this->return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_TARGETGROUP);
        $unitidarray = $user->profile[$unitid] ?? '';

        if (!is_array($unitidarray)) {
            return; // Ensure we are dealing with an array.
        }
        foreach ($unitidarray as $unitid) {
            if (!empty($this->unitmapping[$unitid])) {
                $unitmemberinstance =
                    $this->unitmemberrepo->update_or_create($user, $this->unitmapping[$unitid]);
                if (get_config('local_taskflow', 'organisational_unit_option') == 'cohort') {
                    cohort_add_member($this->unitmapping[$unitid], $user->id);
                }
            }
        }
    }
    /**
     * Checks if necessary Customfields are set for user created or updated.
     *
     * @param stdClass $user
     *
     * @return boolean
     *
     */
    public function necessary_customfields_exist(stdClass $user) {
        $customfields = get_config('taskflowadapter_tuines', "necessaryuserprofilefields");
        // Need to check first if it is one customfield that was checked or multiple.
        if (empty($customfields)) {
            return true;
        }
        if (is_string($customfields)) {
            if (empty($user->profile[$customfields])) {
                return false;
            }
        }
        if (is_array($customfields)) {
            foreach ($customfields as $customfield) {
                if (empty($user->profile[$customfield])) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Gives the Adapter the information to react on user created/updated.
     *
     * @return boolean
     *
     */
    public static function is_allowed_to_react_on_user_events() {
        return false;
    }
}
