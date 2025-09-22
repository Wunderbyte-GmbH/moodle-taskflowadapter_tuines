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
 * Contains class mod_questionnaire\output\indexpage
 *
 * @package    taskflowadapter_tuines
 * @copyright  2025 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace taskflowadapter_tuines\output;

use local_taskflow\local\assignment_status\assignment_status_facade;
use local_taskflow\local\assignments\assignment;
use local_taskflow\output\editassignment_template_data_interface;
use local_taskflow\local\supervisor\supervisor;
use local_taskflow\output\history;
use taskflowadapter_tuines\form\comment_form;
use renderer_base;
use context_system;
use taskflowadapter_tuines\form\editassignment_admin;

/**
 * Display this element
 * @package local_taskflow
 *
 */
class editassignment_template_data_admin implements editassignment_template_data_interface {
    /**
     * data is the array used for output.
     *
     * @var array
     */
    private $data = [];

    /**
     * Constructor.
     * @param array $data
     */
    public function __construct(array $data) {

        global $DB, $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        if (empty($data['id'])) {
            throw new \moodle_exception('invalidassignmentid', 'local_taskflow');
        }
        $returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

        if (!empty($returnurl)) {
            $this->data['returnurl'] = $returnurl;
        }

        $labels = [
            'fullname' => [
                'label' => get_string('fullname'),
                'returnvalue' => fn($value) => format_string($value),
            ],
            'unitid' => [
                'label' => get_string('targets', 'local_taskflow'),
                'returnvalue' => function ($value) use ($DB) {
                    if (empty($value)) {
                        return "";
                    }
                    $cohort = $DB->get_record('cohort', ['id' => $value], 'name');
                    return $cohort->name ?? "";
                },
            ],
            'name' => [
                'label' => get_string('name'),
                'returnvalue' => fn($value) => format_string($value),
            ],
            'ruledescription' => [
                'label' => get_string('description'),
                'returnvalue' => fn($value) => format_string($value),
            ],
            'assigneddate' => [
                'label' => get_string('assigneddate', 'local_taskflow'),
                'returnvalue' => fn($value) => userdate($value),
            ],
            'status' => [
                'label' => get_string('status'),
                'returnvalue' => fn($value) => assignment_status_facade::get_specific_names($value),
            ],
            'usermodified' => [
                'label' => get_string('usermodified', 'local_taskflow'),
                'returnvalue' => function ($value) {
                    $user = \core_user::get_user($value);
                    return fullname($user);
                },
            ],
            'packages' => [
                'label' => get_string('assignedpackages', 'taskflowadapter_tuines'),
                'returnvalue' => function ($value) use ($data) {
                    $assignment = new assignment($data['id']);
                    $targets = json_decode($assignment->targets);
                    $collecttargets = [];
                    foreach ($targets as $target) {
                         $collecttargets[] = $target->targetname . " ("
                         . assignment_status_facade::get_specific_names($target->completionstatus) . ")";
                    }
                    return implode("<br>", $collecttargets);
                },
            ],
        ];

        $assignment = new assignment($data['id']);
        $supervisor = supervisor::get_supervisor_for_user($assignment->userid);
        $this->data['assignmentdata'] = [];

        $assignmentdata = $assignment->return_class_data();
        foreach ($labels as $key => $value) {
            $this->data['assignmentdata'][] = [
                'label' => $value['label'],
                'value' => $value['returnvalue']($assignmentdata->{$key} ?? ''),
            ];
        }
        $hascapability = has_capability('local/taskflow:viewassignment', context_system::instance());

        if (
            $hascapability
        ) {
            // We create the Form to edit the element. The Forms are stored in the Taskflowadapters.

            $form = new editassignment_admin(
                null,
                null,
                'post',
                '',
                [],
                true,
                [
                    'id' => $assignment->id,
                ]
            );
            $classname = "\\\\taskflowadapter_tuines\\\\form\\\\editassignment_admin";
            $form->set_data_for_dynamic_submission();
            $this->data['adapter'] = $classname;
            $this->data['editassignmentform'] = $form->render();

             $commentform = new comment_form(
                 null,
                 null,
                 'post',
                 '',
                 [],
                 true,
                 ['id' => $assignment->id, 'userid' => $USER->id]
             );
            $commentform->set_data_for_dynamic_submission();
            $this->data['hascommentform'] = true;
            $this->data['commentform'] = $commentform->render();
        }
        $this->data['id'] = $assignment->id;
        $historydata = new history($assignment->id);
        /** @var \local_taskflow\output\renderer $renderer */
        $renderer = $PAGE->get_renderer('local_taskflow');
        $this->data['historylist'] = $renderer->render_history($historydata);
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        return $this->data;
    }
}
