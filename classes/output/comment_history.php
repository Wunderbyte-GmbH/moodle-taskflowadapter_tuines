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

use local_taskflow\local\history\history;
use renderable;
use renderer_base;
use taskflowadapter_tuines\table\commenthistory_table;
use templatable;

/**
 * Display this element
 * @package taskflowadapter_tuines
 *
 */
class comment_history implements renderable, templatable {
    /**
     * data is the array used for output.
     *
     * @var array
     */
    private $data = [];

    /**
     * Constructor.
     *
     * @param int $assignmentid
     * @param int $userid
     * @param int $limit
     *
     */
    public function __construct(int $assignmentid = 0, int $userid = 0, $limit = 0) {
        // Create the table.
        $table = new commenthistory_table('taskflowadapter_tuines_commenthistory' . $userid . '_' . $assignmentid);

        $columns = [
            'comment' => get_string('comment', 'local_taskflow'),
            'timemodified' => get_string('date', 'local_taskflow'),
            'createdby' => get_string('usermodified', 'local_taskflow'),
        ];

        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->tabletemplate = 'local_taskflow/history_list';

        // Which table do we need.
        [$select, $from, $where, $params] =
            \local_taskflow\local\history\history::return_sql($assignmentid, $userid, history::TYPE_MANUAL_CHANGE, $limit, true);

        $table->set_sql($select, $from, $where, $params);

        $table->sort_default_column = 'timemodified';
        $table->sort_default_order = SORT_DESC;

        $table->pageable(true);
        $table->showrowcountselect = true;

        $html = $table->outhtml(5, true);
        $data['table'] = $html;

        $this->data = $data;
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
