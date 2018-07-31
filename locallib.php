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
 * Private peoplealchemy module utility functions
 *
 * @package    mod_peoplealchemy
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/peoplealchemy/lib.php");

/**
 * This methods does weak peoplealchemy validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $peoplealchemy
 * @return bool true is seems valid, false if definitely not valid peoplealchemy
 */
function peoplealchemy_appears_valid_peoplealchemy($peoplealchemy) {
    if (preg_match('/^(\/|https?:|ftp:)/i', $peoplealchemy)) {
        // Note: this is not exact validation, we look for severely malformed peoplealchemys only.
        return (bool)preg_match('/^[a-z]+:\/\/([^:@\s]+:[^@\s]+@)?[a-z0-9_\.\-]+(:[0-9]+)?(\/[^#]*)?(#.*)?$/i', $peoplealchemy);
    } else {
        return (bool)preg_match('/^[a-z]+:\/\/...*$/i', $peoplealchemy);
    }
}

/**
 * Fix common peoplealchemy problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $peoplealchemy
 * @return string
 */
function peoplealchemy_fix_submitted_ref($peoplealchemy) {
    // Note: empty peoplealchemys are prevented in form validation.
    $peoplealchemy = trim($peoplealchemy);
    // Remove encoded entities - we want the raw URI here.
    $peoplealchemy = html_entity_decode($peoplealchemy, ENT_QUOTES, 'UTF-8');
    return $peoplealchemy;
}

/**
 * Return full peoplealchemy with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $peoplealchemy
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string peoplealchemy with & encoded as &amp;
 */
function peoplealchemy_get_full_peoplealchemy($peoplealchemy, $cm, $course, $config=null, $embed=null) {
    $parameters = empty($peoplealchemy->parameters) ? array() : unserialize($peoplealchemy->parameters);

    // Make sure there are no encoded entities, it is ok to do this twice.
    if ($embed) {
        $options = array('id' => $peoplealchemy->id, 'embed' => true);
    } else {
        $options = array('id' => $peoplealchemy->id);
    }
    $fullpeoplealchemy = new moodle_url("/mod/peoplealchemy/senduser.php", $options);

    if (preg_match('/^(\/|https?:|ftp:)/i', $fullpeoplealchemy) or preg_match('|^/|', $fullpeoplealchemy)) {
        // Encode extra chars in peoplealchemys - this does not make it always valid, but it helps with some UTF-8 problems.
        $allowed = "a-zA-Z0-9".preg_quote(';/?:@=&$_.+!*(),-#%', '/');
        $fullpeoplealchemy = preg_replace_callback("/[^$allowed]/", 'peoplealchemy_filter_callback', $fullpeoplealchemy);
    } else {
        // Encode special chars only.
        $fullpeoplealchemy = str_replace('"', '%22', $fullpeoplealchemy);
        $fullpeoplealchemy = str_replace('\'', '%27', $fullpeoplealchemy);
        $fullpeoplealchemy = str_replace(' ', '%20', $fullpeoplealchemy);
        $fullpeoplealchemy = str_replace('<', '%3C', $fullpeoplealchemy);
        $fullpeoplealchemy = str_replace('>', '%3E', $fullpeoplealchemy);
    }

    // Add variable peoplealchemy parameters.
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('peoplealchemy');
        }
        $paramvalues = peoplealchemy_get_variable_values($peoplealchemy, $cm, $course, $config);

        foreach ($parameters as $parse => $parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawpeoplealchemyencode($parse).'='.rawpeoplealchemyencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }
    }

    // Encode all & to &amp; entity.
    $fullpeoplealchemy = str_replace('&', '&amp;', $fullpeoplealchemy);
    $fullpeoplealchemy = $fullpeoplealchemy;
    return $fullpeoplealchemy;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function peoplealchemy_filter_callback($matches) {
    return rawpeoplealchemyencode($matches[0]);
}

/**
 * Print peoplealchemy header.
 * @param object $peoplealchemy
 * @param object $cm
 * @param object $course
 * @return void
 */
function peoplealchemy_print_header($peoplealchemy, $cm, $course) {
    global $PAGE, $OUTPUT;
    $PAGE->set_title($cm->id);
    $PAGE->set_title($course->shortname.': '.$peoplealchemy->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($peoplealchemy);
    echo $OUTPUT->header();
}

/**
 * Print peoplealchemy heading.
 * @param object $peoplealchemy
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function peoplealchemy_print_heading($peoplealchemy) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($peoplealchemy->name), 2);
}

/**
 * Print peoplealchemy introduction.
 * @param object $peoplealchemy
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function peoplealchemy_print_intro($peoplealchemy, $cm) {
    global $OUTPUT;
    if (trim(strip_tags($peoplealchemy->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'peoplealchemyintro');
        echo format_module_intro('peoplealchemy', $peoplealchemy, $cm->id);
        echo $OUTPUT->box_end();
    }
}


/**
 * Get the parameters that may be appended to peoplealchemy
 * @param object $config peoplealchemy module config options
 * @return array array describing opt groups
 */
function peoplealchemy_get_variable_options() {

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'peoplealchemy'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'peoplealchemy')] = array(
        'peoplealchemyinstance'     => 'id',
        'peoplealchemycmid'         => 'cmid',
        'peoplealchemyname'         => get_string('name'),
        'peoplealchemyidnumber'     => get_string('idnumbermod'),
    );
    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone1'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userpeoplealchemy'         => get_string('webpage'),
    );
    return $options;
}
