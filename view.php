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
 * People Alchemy module main user interface
 *
 * @package    mod_peoplealchemy
 * @copyright  2018 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once("$CFG->dirroot/mod/clearlesson/lib.php");
require_once("$CFG->dirroot/mod/clearlesson/locallib.php");
require_once($CFG->libdir . '/completionlib.php');
$id = optional_param('id', 0, PARAM_INT);        // Course module ID.
if ($id == 0) {  // Two ways to specify the module.
    $context = \context_system::instance();
} else {
    $cm = get_coursemodule_from_id('peoplealchemy', $id, 0, false, MUST_EXIST);
    $url = $DB->get_record('peoplealchemy', array('id' => $cm->instance), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
}
require_capability('mod/peoplealchemy:view', $context);
$PAGE->set_url('/mod/peoplealchemy/view.php');
if (empty($config)) {
    $config = get_config('peoplealchemy');
}
if (empty($config->networkkey)) {
    echo $OUTPUT->header();
    echo \html_writer::tag('h1', get_string('missingnetworkkey', 'mod_peoplealchemy'));
    echo \html_writer::tag('p', get_string('missingnetworkkeydesc', 'mod_peoplealchemy'));
    if (is_siteadmin($USER)) {
        $settings = new \moodle_url("$CFG->wwwroot/admin/settings.php", array('section' => 'modsettingpeoplealchemy'));
        echo \html_writer::link($settings, get_string('viewsettings', 'peoplealchemy'));
    }
    echo $OUTPUT->footer();
    die();
}
$origin = str_replace('https://', '', $CFG->wwwroot);
$params = array('target' => 'login',
'action' => 'pass',
'Network' => $config->networkkey,
'UserID' => $USER->username,
'FirstName' => $USER->firstname,
'LastName' => $USER->lastname,
'Email' => $USER->email,
'Group' => $origin);
$address = new moodle_url("$config->peoplealchemyurl/index.php", $params);
redirect($address);
