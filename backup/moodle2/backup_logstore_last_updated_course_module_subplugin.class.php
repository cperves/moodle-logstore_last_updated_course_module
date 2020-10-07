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
 * Backup implementation for the (tool_log) logstore_last_updated_course_module subplugin.
 *
 * @package    logstore_last_updated_course_module
 * @category   backup
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_logstore_last_updated_course_module_subplugin extends backup_tool_log_logstore_subplugin {

    /**
     * Returns the subplugin structure to attach to the 'logstore' XML element.
     *
     * @return backup_subplugin_element the subplugin structure to be attached.
     */
    protected function define_logstore_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        if ($this->step->get_name() == 'activity_logstores') {
            $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
            $subpluginlog = new backup_nested_element('logstore_lastupdated_log', array('id'), array(
                'cmid', 'lasttimeupdated'));
            $subplugin->add_child($subpluginwrapper);
            $subpluginwrapper->add_child($subpluginlog);
            $subpluginlog->set_source_table('logstore_lastupdated_log', array('cmid' => backup::VAR_MODID));
        }
        return $subplugin;
    }
}
