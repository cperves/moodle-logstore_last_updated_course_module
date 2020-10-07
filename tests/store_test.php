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
 * last_viewed_course_module log store tests.
 *
 * @package    logstore_last_updated_course_module
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class logstore_last_updated_course_moodle_store_testcase extends advanced_testcase {
    /**
     * @var bool Determine if we disabled the GC, so it can be re-enabled in tearDown.
     */

    private $user1;
    private $user2;
    private $course1;
    private $course2;
    private $resource1;
    private $resourcecontext1;
    private $cmresource1;
    private $resource2;
    private $resourcecontext2;
    private $cmresource2;


    public function test_logstore_enabling() {
        $this->setup_datas();
        // Test all plugins are disabled by this command.
        set_config('enabled_stores', '', 'tool_log');
        $manager = get_log_manager(true);
        $stores = $manager->get_readers();
        $this->assertCount(0, $stores);

        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_last_updated_course_module', 'tool_log');
        $manager = get_log_manager(true);

        $stores = $manager->get_readers();
        $this->assertCount(1, $stores);
        $this->assertEquals(array('logstore_last_updated_course_module'), array_keys($stores));
        /** @var \logstore_last_updated_course_module\log\store $store */
        $store = $stores['logstore_last_updated_course_module'];
        $this->assertInstanceOf('logstore_last_updated_course_module\log\store', $store);
        $this->assertInstanceOf('tool_log\log\writer', $store);
        $this->assertTrue($store->is_logging());
    }

    public function test_course_viewed() {
        global $DB;
        $this->setup_datas();
        $this->set_log_store();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(0, $logs);
        $this->setCurrentTimeStart();
        $this->setUser(0);
        $event1 = \core\event\course_viewed::create(
                array('context' => context_course::instance($this->course1->id)));
        $event1->trigger();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(0, $logs);
    }

    public function test_module_created() {
        global $DB;
        $this->set_log_store();
        $this->setup_datas();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(0, $logs);
        $this->setCurrentTimeStart();
        $this->setUser($this->user1);
        $this->assertEquals(0, $DB->count_records('logstore_lastupdated_log'));
        $this->set_resources();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(2, $logs);
        // Check datas.
        $log = array_shift($logs);
        $this->assertEquals($this->cmresource1->id, $log->cmid);
        $log = array_shift($logs);
        $this->assertEquals($this->cmresource2->id, $log->cmid);
    }

    public function test_module_updated() {
        global $DB;
        $this->setup_datas();
        $this->set_log_store();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(0, $logs);
        $this->setCurrentTimeStart();
        $this->setUser($this->user1);
        $this->assertEquals(0, $DB->count_records('logstore_lastupdated_log'));
        $this->set_resources();
        $event = \core\event\course_module_updated::create_from_cm($this->cmresource2);
        $event->trigger();
        get_log_manager(true);
        $logs = $DB->get_records('logstore_lastupdated_log', array('cmid' => $this->cmresource2->id), 'id ASC');
        $this->assertCount(1, $logs);
        // Check datas.
        $log = array_shift($logs);
        $this->assertEquals($this->cmresource2->id, $log->cmid);
    }

    public function test_course_deleted() {
        global $DB;
        $this->setup_datas();
        $this->set_log_store();
        $this->setUser($this->user1);
        $this->set_resources();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(2, $logs);
        ob_start();
        delete_course($this->course1->id);
        get_log_manager(true);
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(1, $logs); // Other entry is for course 2.
        delete_course($this->course2->id);
        get_log_manager(true);
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(0, $logs);
        $notice = ob_get_contents();
        ob_end_clean();
    }

    public function test_course_module_deleted() {
        global $DB;
        $this->setup_datas();
        $this->set_log_store();
        $this->setUser($this->user1);
        $this->set_resources();
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(2, $logs);
        course_delete_module($this->cmresource1->id);
        get_log_manager(true);
        $logs = $DB->get_records('logstore_lastupdated_log', array(), 'id ASC');
        $this->assertCount(1, $logs);
    }

    /**
     * Test that the standard log cleanup works correctly.
     */
    public function test_cleanup_task() {
        global $DB;
        $this->set_log_store();
        $this->setup_datas();
        $this->set_resources();
        $this->assertEquals(2, $DB->count_records('logstore_lastupdated_log'));
        // Artifically modify last date.
        $record = $DB->get_record('logstore_lastupdated_log', array('cmid' => $this->cmresource1->id));
        $record->lasttimeupdated -= 3600 * 24 * 30;
        $DB->update_record('logstore_lastupdated_log', $record);
        // Remove all logs before "today".
        set_config('loglifetime', 1, 'logstore_last_updated_course_module');
        $this->expectOutputString(" Deleted old log records from last_viewed_course_module log store.\n");
        $clean = new \logstore_last_updated_course_module\task\cleanup_task();
        $clean->execute();
        $this->assertEquals(1, $DB->count_records('logstore_lastupdated_log'));
    }


    /**
     * @param $course1
     * @param $resource1
     * @param $course2
     * @param $resource2
     * @throws coding_exception
     */
    private function setup_datas() {
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->setAdminUser();
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();
    }

    private function set_log_store() {
        set_config('enabled_stores', '', 'tool_log');
        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_last_updated_course_module', 'tool_log');
        // Force reload.
        get_log_manager(true);
    }

    private function set_resources() {
        $this->resource1 = $this->getDataGenerator()->create_module('resource', array('course' => $this->course1));
        $this->resourcecontext1 = context_module::instance($this->resource1->cmid);
        $this->cmresource1 = get_coursemodule_from_instance('resource', $this->resource1->id);
        $this->resource2 = $this->getDataGenerator()->create_module('resource', array('course' => $this->course2));
        $this->resourcecontext2 = context_module::instance($this->resource2->cmid);
        $this->cmresource2 = get_coursemodule_from_instance('resource', $this->resource2->id);
        get_log_manager(true);
    }
}
