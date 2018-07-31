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
 * Define all the backup steps that will be used by the backup_peoplealchemy_activity_task
 *
 * @package    mod_peoplealchemy
 * @copyright  2017 Josh Willcock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

 /**
  * Define the complete Clear Lesson structure for backup, with file and id annotations.
  */
class backup_peoplealchemy_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // Define each element separated.
        $peoplealchemy = new backup_nested_element('peoplealchemy', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'timemodified'));

        // Define sources.
        $peoplealchemy->set_source_table('peoplealchemy', array('id' => backup::VAR_ACTIVITYID));
        // Define id annotations.
        // Module has no id annotations.

        // Define file annotations
        $peoplealchemy->annotate_files('peoplealchemy', 'intro', null); // This file area hasn't itemid.
        // Return the root element (peoplealchemy), wrapped into standard activity structure.
        return $this->prepare_activity_structure($peoplealchemy);

    }
}
