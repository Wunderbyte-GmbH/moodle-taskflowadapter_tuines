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
 * Form to create rules.
 *
 * @package   taskflowadapter_tuines
 * @copyright 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace taskflowadapter_tuines\form;

use context_system;
use core_form\dynamic_form;
use html_writer;
use local_taskflow\local\assignment_status\assignment_status_facade;
use local_taskflow\local\assignments\assignment;
use local_taskflow\local\assignments\status\assignment_status;
use local_taskflow\local\history\history;

/**
 * Demo step 1 form.
 */
class editassignment_supervisor extends dynamic_form {
    /**
     * Definition.
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        // Add this hidden id to the form.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'overduecounter');
        $mform->setType('overduecounter', PARAM_INT);

        $mform->addElement('hidden', 'prolongedcounter');
        $mform->setType('prolongedcounter', PARAM_INT);

        $mform->addElement('hidden', 'actionbutton');
        $mform->setType('actionbutton', PARAM_ALPHANUMEXT);
        $mform->addElement('header', 'extensionheader', get_string('grantextension', 'taskflowadapter_tuines'));
        $mform->addElement('html', '<br>');
        $mform->addElement('html', html_writer::div(get_string('denytext', 'taskflowadapter_tuines')));
        $mform->addElement('html', '<br>');
        $mform->setExpanded('extensionheader', false);
        $mform->setType('status', PARAM_TEXT);
        $changereasonoptions = assignment_status::get_all_changereasons();
        $changereasonoptions = [ '' => get_string('choose', 'local_taskflow') ] + $changereasonoptions;
        // Reason for change.
        $mform->addElement(
            'select',
            'change_reason',
            get_string('changereason', 'local_taskflow'),
            $changereasonoptions,
        );
        $mform->setType('change_reason', PARAM_TEXT);
        // Comment.
        $mform->addElement(
            'textarea',
            'comment_approved',
            get_string(
                'comment',
                'local_taskflow'
            ),
            'wrap="virtual" rows="3" cols="50"'
        );
        $mform->setType('comment_approved', PARAM_TEXT);

        // Duedate.
        $mform->addElement('date_selector', 'duedate', get_string('extensionuntil', 'local_taskflow'));
        $mform->freeze('duedate');
        // Changes should be preserved on automatic update via import.
        $mform->addElement(
            'hidden',
            'keepchanges',
            '',
            get_string('keepchangesonimport', 'local_taskflow')
        );
        $mform->setType('keepchanges', PARAM_BOOL);
        $mform->setDefault('keepchanges', 1);
        // Submit Extension.
        $mform->addElement('button', 'extension', get_string('grantextension', 'taskflowadapter_tuines'));

         // Deny Extension.
        $mform->addElement('header', 'denyheader', get_string('denyextension', 'taskflowadapter_tuines'));
        $mform->setExpanded('denyheader', false);
        $mform->addElement('html', '<br>');
        $mform->addElement('html', html_writer::div(get_string('denytext', 'taskflowadapter_tuines')));
        $mform->addElement('html', '<br>');
        $mform->addElement(
            'textarea',
            'comment_denied',
            get_string('comment', 'local_taskflow'),
            'wrap="virtual" rows="3" cols="50"'
        );
        $mform->setType('comment_denied', PARAM_TEXT);
        $mform->addElement('button', 'declined', get_string('denyextension', 'taskflowadapter_tuines'));
    }

    /**
     * Process the form submission.
     * @return void
     */
    public function process_dynamic_submission(): void {
        global $USER;
        $data = $this->get_data();
        $mform = $this->_form;
        $assignment = new assignment($data->id);
        $data->useridmodified = $USER->id;

        if ($data->actionbutton == 'extension') {
            $data->comment = $data->comment_approved;
            $data->status = assignment_status_facade::get_status_identifier('prolonged');
            $data->prolongedcounter++;
        }

        if ($data->actionbutton == 'declined') {
            $data->duedate = $assignment->duedate;
            $data->comment = $data->comment_denied;
            $data->status = assignment_status_facade::get_status_identifier('prolonged');
            $data->prolongedcounter++;
        }
        // Unset all the Data properties not needed.
        unset($data->actionbutton, $data->comment_approved, $data->comment_denied);
        // Unset all the expanded properties.
        foreach ($data as $key => $value) {
            if (strpos($key, '_isexpanded_')) {
                unset($data->$key);
            }
        }
        $assignment->add_or_update_assignment((array)$data, history::TYPE_MANUAL_CHANGE, true);
    }

    /**
     * Set data for the form.
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $data = $this->_customdata ?? $this->_ajaxformdata ?? [];

        if (!empty($data['id'])) {
            // If no ID is provided, we create a new assignment.
            $assignment = new assignment($data['id']);
            $assignmentdata = $assignment->return_class_data();
            if ($assignmentdata) {
                $data = $assignmentdata;
                $ruledata = json_decode($assignment->rulejson);
                if (isset($ruledata->rulejson->rule->extensionperiod)) {
                    if ($assignment->duedate > strtotime('now')) {
                        $extensionperiod = (int) $assignment->duedate + (int) $ruledata->rulejson->rule->extensionperiod;
                    } else {
                        $extensionperiod = strtotime('today 23:59') + (int) $ruledata->rulejson->rule->extensionperiod;
                    }
                    $data->duedate = $extensionperiod;
                }
            } else {
                // If no assignment data is found, we initialize an empty array.
                $data = (object)[];
            }
        }
        $this->set_data($data);
    }


    /**
     * Validation for Correct behavior of necessary fields.
     *
     * @param array $data
     * @param mixed $files
     *
     * @return array
     *
     */
    public function validation($data, $files) {
        $errors = [];
        if ($data['actionbutton'] == 'extension') {
            if (empty($data['change_reason'])) {
                $errors['change_reason'] = get_string('change_reason_errorextension', 'taskflowadapter_tuines');
            }
            if (!empty($data['comment_denied'])) {
                $errors['comment_denied'] = get_string('comment_denied_errorextension', 'taskflowadapter_tuines');
            }
        }
        if ($data['actionbutton'] == 'declined') {
            if (!empty($data['change_reason'])) {
                $errors['change_reason'] = get_string('change_reason_errordeclined', 'taskflowadapter_tuines');
            }
            if (empty($data['comment_denied'])) {
                $errors['comment_denied'] = get_string('comment_denied_errordeclined', 'taskflowadapter_tuines');
            }
        }
        return $errors;
    }

    /**
     * Get the URL for the page.
     *
     * @return \moodle_url
     *
     */
    protected function get_page_url(): \moodle_url {
        return new \moodle_url('/local/taskflow/editassignment.php');
    }

    /**
     * Get the URL for the page.
     * @return \moodle_url
     */
    public function get_page_url_for_dynamic_submission(): \moodle_url {
        return $this->get_page_url();
    }

    /**
     * Get the context for the page.
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        return context_system::instance();
    }

    /**
     * Check access for the page.
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        require_login();
    }
}
