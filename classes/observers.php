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
 *
 *
 * @package    logstore_last_updated_course_module
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_last_updated_course_module;

defined('MOODLE_INTERNAL') || die();

class observers {
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB, $CFG;
        // Remove orphans.
        $DB->execute(
            'delete from {logstore_lastupdated_log} lll where not exists (select from {course_modules} where id=lll.cmid) ');
    }

    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        global $DB;
        $DB->delete_records('logstore_lastupdated_log', array('cmid' => $event->contextinstanceid));
    }

}