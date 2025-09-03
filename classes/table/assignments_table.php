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
 * Rules table.
 *
 * @package     taskflowadapter_tuines
 * @copyright   2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Georg Maißer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace taskflowadapter_tuines\table;
use context_system;
use html_writer;
use local_taskflow\local\assignments\assignment;
use local_taskflow\local\external_adapter\external_api_base;
use local_taskflow\local\supervisor\supervisor;
use local_taskflow\plugininfo\taskflowadapter;
use local_wunderbyte_table\output\table;
use moodle_url;

/**
 * Assignments table
 *
 * @package     taskflowadapter_tuines
 * @copyright   2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Georg Maißer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignments_table extends \local_taskflow\table\assignments_table {
    /**
     * Add column with actions.
     * @param mixed $values
     * @return string
     */
    public function col_actions($values) {
        global $OUTPUT, $USER, $PAGE;

        $url = new moodle_url('/local/taskflow/assignment.php', [
            'id' => $values->id,
        ]);

        $html = html_writer::div(html_writer::link(
            $url->out(),
            '<i class="icon fa fa-info-circle"></i>'
        ));
        $data = [];
        $supervisor = supervisor::get_supervisor_for_user($values->userid ?? 0);
        $hascapability = has_capability('local/taskflow:editassignment', context_system::instance());
        $assignment = new assignment($values->id);
        if (
            $hascapability ||
            (($supervisor->id ?? -1) === $USER->id && $this->is_allowed_to_edit($assignment))
        ) {
            $returnurl = $PAGE->url;
            $returnurlout = $returnurl->out(false);
            $url = new moodle_url('/local/taskflow/editassignment.php', [
                'id' => $values->id,
                'returnurl' => $returnurlout,
            ]);

            $html .= html_writer::div(html_writer::link(
                $url,
                "<i class='icon fa fa-edit'></i>"
            ));
            table::transform_actionbuttons_array($data);
        }
        return
            $html .
            $OUTPUT->render_from_template('local_wunderbyte_table/component_actionbutton', ['showactionbuttons' => $data]);
    }
    /**
     * Shows the latest comment.
     *
     * @param mixed $values
     *
     * @return string
     *
     */
    public function col_comment($values) {
        if (empty($values->annotation)) {
            $comment = "-";
        } else {
            $readabletime = userdate($values->timemodified, '%d.%m.%Y %H:%M');
            $comment = $readabletime . "; " . $values->annotation;
        }
        $shortcomment = shorten_text($comment, 200);
        return html_writer::div($shortcomment, '', ['title' => $comment]);
    }

    /**
     * Moodleid only for testing in tu_ines. DELETE Afterwords.
     *
     * @param mixed $values
     *
     * @return string
     *
     */
    public function col_testmoodleid($values) {
        return $values->userid;
    }

     /**
      * Fullname with URL.
      *
      * @param mixed $values
      *
      * @return string
      *
      */
    public function col_fullname($values) {
        $customfields = profile_user_record($values->userid);
        $externalidfield = external_api_base::return_shortname_for_functionname(taskflowadapter::TRANSLATOR_USER_EXTERNALID);
        $externalid = $customfields->$externalidfield;
        return html_writer::link("https://tiss.tuwien.ac.at/person/$externalid.html", $values->fullname);
    }

    /**
     * A supervisor is only allowed to edit when the overduecounter is <= 1 and the prolonged counter is <= 2.
     *
     * @param object $assignment
     *
     * @return boolean
     *
     */
    private function is_allowed_to_edit(object $assignment) {
        if ($assignment->overduecounter <= 1 && $assignment->prolongedcounter == 1) {
            return true;
        }
        return false;
    }
}
