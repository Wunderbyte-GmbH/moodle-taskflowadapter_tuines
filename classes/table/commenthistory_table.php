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
use core_user;
use html_writer;
use local_taskflow\local\assignments\status\assignment_status;
use local_taskflow\local\history\types\typesfactory;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Assignments table
 *
 * @package     taskflowadapter_tuines
 * @copyright   2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Georg Maißer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class commenthistory_table extends wunderbyte_table {
    /**
     * Returns the fullname of the user who created the entry.
     *
     * @param mixed $values
     *
     * @return string
     *
     */
    public function col_createdby($values): string {
        return fullname(core_user::get_user($values->createdby));
    }

    /**
     * Returns the fullname of the user who created the entry.
     *
     * @param mixed $values
     *
     * @return string
     *
     */
    public function col_timecreated($values): string {
        return userdate($values->timecreated ?? 0, get_string('strftimedatetime', 'langconfig'));
    }
    /**
     * Returns the comment of the user.
     *
     * @param mixed $values
     *
     * @return string
     *
     */
    public function col_comment($values) {
        $jsonobject = json_decode($values->data);
        $changereasons = assignment_status::get_all_changereasons();
        $changename = $changereasons[$jsonobject->data->change_reason] ?? "";
        if (empty($values->annotation)) {
            return $changename;
        }
        if (!empty($changename)) {
            $changename = $changename . '; ';
        }
        return $changename . $values->annotation;
    }
}
