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
 * logstore_last_updated_course_module backup restore tests.
 *
 * @package    logstore_last_updated_course_module
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_last_updated_course_module;
use advanced_testcase;
use backup;
use backup_controller;
use restore_controller;
use restore_dbops;
use stdClass;
use restore_controller_exception;

global $CFG;
require_once($CFG->dirroot . '/backup/controller/tests/controller_test.php');

class backup_restore_test extends advanced_testcase {
    private $course;
    private $module;

    public function test_backup_restore() {
        global $DB, $CFG;
        $CFG->logstore_user_patch = 1;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->setup_datas();
        // Add log datas.
        $record = new  stdClass();
        $record->cmid = $this->module->cmid;
        $record->lasttimeupdated = time();
        $DB->insert_record('logstore_lastupdated_log', $record);
        $newcourseid = $this->backup_restore_course();
        // Retrieve module in new course.
        $rawmodules = get_course_mods($newcourseid);
        $this->assertCount(1, $rawmodules);
        $newmodule = array_pop($rawmodules);
        // Retrieve cmid.
        $dbrecords = $DB->get_records('logstore_lastupdated_log', array('cmid' => $newmodule->id));;
        $this->assertTrue(is_array($dbrecords));
        $this->assertCount(1, $dbrecords);
    }

    private function setup_datas() {
        global $DB;
        set_config('enabled_stores', 'logstore_last_updated_course_module', 'tool_log');
        get_log_manager(true);
        $this->course = $this->getDataGenerator()->create_course();
        $this->module = $this->getDataGenerator()->create_module('workshop', array('course' => $this->course->id));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user(2, $this->course->id, $studentrole->id);
        get_log_manager(true);
    }

    /**
     * @param $CFG
     * @param object $USER
     * @return int
     * @throws restore_controller_exception
     */
    private function backup_restore_course() {
        global $CFG, $USER;
        $CFG->keeptempdirectoriesonbackup = 1;
        set_config('backup_general_logs', 1, 'backup');
        set_config('backup_general_users', 1, 'backup');

        make_backup_temp_directory('');
        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $result = $bc->get_results();
        $bc->destroy();

        // Set up restore.
        $newcourseid = restore_dbops::create_new_course('Test fullname', 'Test shortname',
            $this->course->category);
        $rc = new restore_controller($backupid, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id,
            backup::TARGET_NEW_COURSE);
        $rc->execute_precheck();

        // Execute restore.
        $rc->execute_plan();
        $rc->destroy();
        return $newcourseid;
    }

}