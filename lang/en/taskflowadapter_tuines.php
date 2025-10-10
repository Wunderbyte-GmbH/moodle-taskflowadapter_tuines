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
 * This file contains language strings for the taskflow adapter.
 *
 * @package     taskflowadapter_tuines
 * @copyright   2025 Wunderbyte GmbH
 * @author      David Ala
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['apisettings'] = "INES API Settings";
$string['apisettings_desc'] = "Configure taskflow key-value pairs.";
$string['assignedpackages'] = "Assigned Packages";
$string['change_reason_errordeclined'] = "If the deadline extension is denied, no reason may be selected.";
$string['change_reason_errorextension'] = "If the deadline is extended, a reason must be selected.";
$string['choose'] = "Choose...";
$string['comment_denied_errordeclined'] = "When denying the extension request, a comment must be provided";
$string['comment_denied_errorextension'] = "When granting an extension, the comment for rejection must be empty";
$string['denyextension'] = "Deny Extension";
$string['denytext'] = "If there are no valid reasons for extending the deadline, the request may be denied. Providing a justification for the denial is mandatory. Attention: Employees who fail to complete the standard training within the prescribed timeframe may receive a written warning and face corresponding employment-related consequences.";
$string['dwhurl'] = "Data Warehouse Url";
$string['dwhurl_desc'] = "Insert Data Warehouse Url for import";
$string['edit'] = "Edit";
$string['enter_value'] = 'Enter a suitable JSON key for this setting';
$string['eventdwhfetchfailed'] = 'DWH import error';
$string['excludestatus'] = 'Do not use status';
$string['excludestatus_desc'] = 'Status changes to the following statuses will not be executed';
$string['extensiontext'] = 'If there are valid and compelling reasons preventing employees from completing the standard training within the prescribed timeframe, supervisors may extend the deadline once. Providing a reason is mandatory for this extension.';
$string['function'] = 'Assign function to userprofilefield: ';
$string['grantextension'] = "Grant Extension";
$string['internalid'] = 'Internal ID';
$string['jsonkey'] = 'JSON key for userprofilefield: ';
$string['lessfunctions'] = '<div class="alert alert-danger" role="alert">Not all functions were selected during the last save. This may cause errors.</div>';
$string['manyfunctions'] = '<div class="alert alert-danger" role="alert">Functions were selected multiple times during the last save. This may cause errors.</div>';
$string['mappingdescription'] = 'Taskflow key-value pair explanation';
$string['mappingdescription_desc'] = 'This creates the mapping. The upper field indicates which JSON field is linked to the user profile field. The lower field indicates which function this field represents. Not every user profile field must have a function.';
$string['necessaryuserprofilefields'] = "User profile fields required to be filled in for Taskflow";
$string['necessaryuserprofilefieldsdesc'] = "User profile fields that are not allowed to be empty for the user to be considered in a Taskflow update. If the selected fields are empty, user updates will not be processed in Wunderbyte Taskflow. Leave this setting empty if no fields are required.";
$string['pluginname'] = "INES";
$string['set:function'] = 'Select a function';
$string['submitcomment'] = 'Kommentar speichern';
$string['subplugintype_taskflowadapter_plural'] = 'Taskflow adapter extensions';
$string['targetgroupfields'] = '<i class="fa-solid fa-people-group" aria-hidden="true"></i><strong> Fields for Units </strong>';
$string['training'] = "Go to training";
$string['tuines'] = "Ines API";
$string['usermappingfields'] = '<i class="fa-solid fa-user" aria-hidden="true"></i> <strong> Fields for User </strong>';
$string['usingprolongedstate'] = 'Use prolonged state';
$string['usingprolongedstate_desc'] = 'Use prolonged state to mark first automated expansion of due date';
