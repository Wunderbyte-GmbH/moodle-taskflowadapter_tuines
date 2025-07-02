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
 * Class taskflowadapter_tuines.
 *
 * @package     taskflowadapter_tuines
 * @copyright   2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      David Ala
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace taskflowadapter_tuines;

use admin_setting_configselect;
use admin_setting_configtext;
use admin_setting_heading;
use local_taskflow\plugininfo\taskflowadapter;


/**
 * Class for the TUINES taskflow adapter.
 */
class taskflowadapter_tuines extends taskflowadapter {
    /**
     * COMPONENTNAME
     *
     * @var string
     */
    private const COMPONENTNAME = 'taskflowadapter_tuines';
    /**
     * Loads API Settings to local_taskflow
     *
     * @param \part_of_admin_tree $adminroot
     * @param mixed $parentnodename
     * @param mixed $hassiteconfig
     *
     * @return void
     *
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        if (!$hassiteconfig) {
            return;
        }

        $allusercustomfields = profile_get_custom_fields();
        $usercustomfields = [];
        $settings = $adminroot->locate($parentnodename);
        $userlabelsettings = parent::return_user_label_settings();
        $cohortlabelsettings = parent::return_target_label_settings();
        if (!empty($allusercustomfields)) {
            foreach ($allusercustomfields as $userprofilefield) {
                $usercustomfields["{$userprofilefield->shortname}"] = $userprofilefield->name;
            }
        }
        $settings->add(
            new admin_setting_heading(
                self::COMPONENTNAME . '_api_settings',
                get_string('apisettings', self::COMPONENTNAME),
                get_string('apisettings_desc', self::COMPONENTNAME)
            )
        );
        parent::check_functions_usage($usercustomfields, self::COMPONENTNAME, $settings);
        parent::return_setting_special_treatment_fields($settings, self::COMPONENTNAME);
        foreach ($usercustomfields as $key => $label) {
            $settings->add(
                new admin_setting_configtext(
                    self::COMPONENTNAME . '/' . $key,
                    get_string('jsonkey', self::COMPONENTNAME) . $label,
                    get_string('enter_value', self::COMPONENTNAME),
                    '',
                    PARAM_TEXT
                )
            );
             $settings->add(
                 new admin_setting_configselect(
                     self::COMPONENTNAME . '/' . 'function_' . $key,
                     get_string('function', self::COMPONENTNAME) . $label,
                     get_string('set:function', self::COMPONENTNAME),
                     "",
                     $userlabelsettings,
                 )
             );
        }
        foreach ($cohortlabelsettings as $key => $label) {
            $settings->add(
                new admin_setting_configtext(
                    self::COMPONENTNAME . '/' . $key,
                    get_string('jsonkey', self::COMPONENTNAME) . $label,
                    get_string('enter_value', self::COMPONENTNAME),
                    '',
                    PARAM_TEXT
                )
            );
        }
    }
}
