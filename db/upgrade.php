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
 * Standard log reader/writer cron task.
 *
 * @package    logstore_last_updated_course_module
 * @copyright  2021 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_logstore_last_updated_course_module_upgrade($oldversion) {
    global $CFG;

    if ($oldversion < 2021021800) {
        // For existing installations, set the new jsonformat option to off (no behaviour change).
        // New installations default to on.
        set_config('jsonformat', 0, 'logstore_last_updated_course_module');
        upgrade_plugin_savepoint(true, 2021022300, 'logstore', 'last_updated_course_module');
    }
    return true;
}
