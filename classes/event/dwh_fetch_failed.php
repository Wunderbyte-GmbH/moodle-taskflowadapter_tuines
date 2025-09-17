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

namespace taskflowadapter_tuines\event;

/**
 * Event dwh_fetch_failed
 *
 * @package    taskflowadapter_tuines
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dwh_fetch_failed extends \core\event\base {
    /**
     * Init parameters.
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Init parameters.
     * @return string
     */
    public static function get_name() {
        return get_string('eventdwhfetchfailed', 'taskflowadapter_tuines');
    }

    /**
     * Init parameters.
     * @return string
     */
    public function get_description() {
        return "DWH fetch failed for URL {$this->other['url']} with error: {$this->other['error']}";
    }
}
