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
 * Observer for given events.
 *
 * @package   taskflowadapter_tuines
 * @author    Georg Maißer
 * @copyright 2023 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace taskflowadapter_tuines\task;

use core\task\scheduled_task;
use local_taskflow\local\external_adapter\external_api_repository;

/**
 * Observer class that handles user events.
 */
class fetch_dwh_data extends scheduled_task {
    /**
     * Triggered when a user profile field is deleted.
     * @return string;
     */
    public function get_name() {
        return get_string('task_fetch_remote_data', 'local_taskflow');
    }

    /**
     * Triggered when a user profile field is deleted.
     * @return void;
     */
    public function execute() {
        $url = get_config('local_taskflow', 'dwhurl');
        if (empty($url)) {
            $this->log_cli(get_string('fetchdwhurl', 'local_taskflow'));
            return;
        }

        $curl = new \curl();
        $response = $curl->get($url);
        if ($curl->get_errno()) {
            $this->log_cli(get_string('fetchdwhurlerror', 'local_taskflow', $curl->error));
            return;
        }

        $start = microtime(true);
        $apidatamanager = external_api_repository::create($response);
        $apidatamanager->process_incoming_data();
        $end = microtime(true);
        $elapsed = $end - $start;
        $this->log_cli(
            get_string('fetchdwhurlresponse', 'local_taskflow', $url) .
            get_string('executiontime', 'local_taskflow', sprintf('%.4f', $elapsed))
        );
    }

    /**
     * Triggered when a user profile field is deleted.
     * @param string $msg
     * @return void;
     */
    private function log_cli(string $msg): void {
        if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
            mtrace($msg);
        }
    }
}
