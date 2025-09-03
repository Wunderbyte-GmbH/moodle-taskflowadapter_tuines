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

use cache_helper;
use context_system;
use core_form\dynamic_form;
use local_taskflow\local\assignments\assignment;
use local_taskflow\local\history\history;
use taskflowadapter_tuines\output\comment_history;


/**
 * Demo step 1 form.
 */
class comment_form extends dynamic_form {
    /**
     * Definition.
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;


        $mform->addElement('static', 'commenthistory');
        $hiddenfields = [
        'id' => PARAM_INT,
        'userid' => PARAM_INT,
        'overduecounter' => PARAM_INT,
        'prolongedcounter' => PARAM_INT,
        'status' => PARAM_TEXT,
        'change_reason' => PARAM_TEXT,
        'duedate' => PARAM_INT,
        'keepchanges' => PARAM_INT,
        ];

        $mform->addElement('textarea', 'comment', get_string('comment', 'local_taskflow'), 'wrap="virtual" rows="3" cols="50"');
        $mform->setType('comment', PARAM_TEXT);
        foreach ($hiddenfields as $name => $type) {
            $mform->addElement('hidden', $name);
            $mform->setType($name, $type);
        }
        $mform->addElement('submit', 'submitcomment', get_string('submitcomment', 'taskflowadapter_tuines'));
    }

    /**
     * Process the form submission.
     * @return void
     */
    public function process_dynamic_submission(): void {
        global $USER;
        $data = $this->get_data();
        $data = (array) $data;
        $mform = $this->_form;
        $data['timemodified'] = time();
        $data['usermodified'] = $data['usermodified'] ?? $USER->id;
        history::log($data['id'], $data['userid'], 'manual_change', ['action' => 'updated', 'data' => $data], $USER->id, $data['comment'] ?? "");
        cache_helper::purge_by_event('changesinassignmentslist');
    }

    /**
     * Set data for the form.
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $PAGE;
        $data = $this->_customdata ?? $this->_ajaxformdata ?? [];

        if (!empty($data['id'])) {
            // If no ID is provided, we create a new assignment.
            $assignment = new assignment($data['id']);
            $assignmentdata = $assignment->return_class_data();
            if ($assignmentdata) {
                $data = $assignmentdata;
            } else {
                // If no assignment data is found, we initialize an empty array.
                $data = (object)[];
            }
        }
        $historydata = new comment_history($data->id);
        $renderer = $PAGE->get_renderer('local_taskflow');
        $data->commenthistory = $renderer->render_history($historydata);
        $this->set_data($data);
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
