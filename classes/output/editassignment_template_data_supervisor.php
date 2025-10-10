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
use renderer_base;
use context_system;
use taskflowadapter_tuines\form\editassignment_supervisor;

/**
 * Display this element
 * @package local_taskflow
 *
 */
class editassignment_template_data_supervisor implements editassignment_template_data_interface {
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
            'status' => [
                'label' => get_string('status'),
                'returnvalue' => fn($value) => assignment_status_facade::get_specific_names($value),
            ],
            'duedate' => [
                'label' => get_string('duedate', 'local_taskflow'),
                'returnvalue' => fn($value) => date('d.m.Y', $value),
            ],
            'packages' => [
                'label' => get_string('assignedpackages', 'taskflowadapter_tuines'),
                'returnvalue' => function ($value) use ($data) {
                    $assignment = new assignment($data['id']);
                    $targets = json_decode($assignment->targets);
                    $collecttargets = [];
                    foreach ($targets as $target) {
                        $completionstatus = get_string('notcompleted', 'local_taskflow');
                        if (
                            isset($target->completionstatus) &&
                            $target->completionstatus == 1
                        ) {
                            $completionstatus = get_string('completed', 'local_taskflow');
                        }
                        $collecttargets[] = $target->targetname . " ("
                         . $completionstatus . ")";
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
            ($supervisor->id ?? -1) == $USER->id
            || $hascapability
        ) {
            // We create the Form to edit the element. The Forms are stored in the Taskflowadapters.

            $form = new editassignment_supervisor(
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
            $classname = "\\\\taskflowadapter_tuines\\\\form\\\\editassignment_supervisor";
            $form->set_data_for_dynamic_submission();
            $this->data['adapter'] = $classname;
            $this->data['hashistory'] = false;
            $this->data['editassignmentform'] = $form->render();
        }
        $this->data['id'] = $assignment->id;
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
