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
 * People Alchemy module API.
 *
 * @package    mod_peoplealchemy
 * @copyright  2017 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in People Alchemy module.
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function peoplealchemy_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Returns all other caps used in module.
 * @return array
 */
function peoplealchemy_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function peoplealchemy_reset_userdata($data) {
    return array();
}
/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function peoplealchemy_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function peoplealchemy_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add clearlesson instance.
 * @param object $data
 * @param object $mform
 * @return int new url instance id
 */
function peoplealchemy_add_instance($data, $mform) {
    global $CFG, $DB;
    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $data->displayoptions = serialize($displayoptions);

    $data->timemodified = time();
    $data->id = $DB->insert_record('peoplealchemy', $data);

    return $data->id;
}

/**
 * Update url instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function peoplealchemy_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/clearlesson/locallib.php');
    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);
    $data->externalref = peoplealchemy_fix_submitted_ref($data->externalref);
    $data->timemodified = time();
    $data->id           = $data->instance;
    $DB->update_record('clearlesson', $data);
    return true;
}

/**
 * Delete clearlesson instance.
 * @param int $id
 * @return bool true
 */
function peoplealchemy_delete_instance($id) {
    global $DB;
    if (!$url = $DB->get_record('clearlesson', array('id' => $id))) {
        return false;
    }
    // Note: all context files are deleted automatically.
    $DB->delete_records('clearlesson', array('id' => $url->id));
    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function peoplealchemy_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    if (!$peoplealchemy = $DB->get_record('peoplealchemy', array('id' => $coursemodule->instance),
    'id, name, intro, introformat')) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $peoplealchemy->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('url', $peoplealchemy, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function peoplealchemy_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array('mod-peoplealchemy-*' => get_string('page-mod-peoplealchemy-x', 'peoplealchemy'));
    return $modulepagetype;
}

/**
 * Export Clear Lesson resource contents.
 *
 * @return array of file content
 */
function peoplealchemy_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/clearlesson/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $urlrecord = $DB->get_record('clearlesson', array('id' => $cm->instance), '*', MUST_EXIST);
    $fullurl = str_replace('&amp;', '&', peoplealchemy_get_full_url($urlrecord, $cm, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return null;
    }

    $url = array();
    $url['type'] = 'clearlesson';
    $url['filename']     = clean_param(format_string($urlrecord->name), PARAM_FILE);
    $url['filepath']     = null;
    $url['filesize']     = 0;
    $url['fileurl']      = $fullurl;
    $url['timecreated']  = null;
    $url['timemodified'] = $urlrecord->timemodified;
    $url['sortorder']    = null;
    $url['userid']       = null;
    $url['author']       = null;
    $url['license']      = null;
    $contents[] = $url;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function peoplealchemy_dndupload_register() {
    return array('types' => array(
        array('identifier' => 'url', 'message' => get_string('createurl', 'url'))
    ));
}
/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function peoplealchemy_dndupload_handle($uploadinfo) {
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>'.$uploadinfo->displayname.'</p>';
    $data->introformat = FORMAT_HTML;
    $data->externalurl = clean_param($uploadinfo->content, PARAM_URL);
    $data->timemodified = time();
    // Set the display options to the site defaults.
    $config = get_config('clearlesson');
    $data->display = $config->display;
    $data->popupwidth = $config->popupwidth;
    $data->popupheight = $config->popupheight;
    $data->printintro = $config->printintro;
    return peoplealchemy_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $url        url object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function peoplealchemy_view($clearlessonref, $course, $cm, $context) {
    global $DB, $USER;
    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $clearlessonref->id
    );

    $event = \mod_clearlesson\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('clearlesson', $clearlessonref);
    $event->trigger();

    $newview = new \stdClass();
    $newview->userid = $USER->id;
    $newview->clearlessonid = $clearlessonref->id;
    $newview->timemodified = time();
    $DB->insert_record('peoplealchemy_track', $newview);

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function peoplealchemy_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}
function peoplealchemy_redirect_post($data, array $headers = null) {
    $pluginconfig = get_config('clearlesson');
    $curl = new \curl;
    if (!empty($headers)) {
        foreach ($headers as $key => $header) {
            $curl->setHeader("$key:$header");
        }
    }
    $endpoint = new \moodle_url($pluginconfig->clearlessonurl.'/api/v1/userlogin');
    $response = json_decode($curl->post($endpoint, $data));
    if (isset($response->success)) {
        $url = new \moodle_url($response->authUrl);
        redirect($url);
    } else {
        if (debugging()) {
            var_dump($response);
        }
        throw new \moodle_exception(get_string('invalidresponse', 'clearlesson'));
    }
}
function peoplealchemy_build_url($url, $pluginconfig) {
    if (substr($pluginconfig->clearlessonurl, -1) != '/') {
        $pluginconfig->clearlessonurl .= '/';
    }
    if ($url->display == 1 AND $url->type == 'play') {
        $url->type = 'soloplay';
    }
    $url = $pluginconfig->clearlessonurl.$url->type.'/'.$url->externalref;
    return $url;
}
function peoplealchemy_set_header($pluginconfig) {
    return array('Content-Type' => 'application/jose',
    'Authorization' => 'APIKEY '.$pluginconfig->apikey,
    'alg' => 'HS256');
}

function peoplealchemy_set_body($pluginconfig, $url) {
    GLOBAL $CFG, $USER;
    $userinfofields = array();
    foreach ($USER as $key => $value) {
        if (!empty($value)) {
            if (substr($key, 0, 14) == 'profile_field_') {
                $userinfofields[$key] = $value;
            }
        }
    }
    return array('APIKEY' => $pluginconfig->apikey,
    'origin' => $CFG->wwwroot,
    'firstName' => $USER->firstname,
    'email' => $USER->email,
    'lastName' => $USER->lastname,
    'date' => gmdate("Y-m-d\TH:i:s\Z"),
    'redirectUrl' => $url,
    'userInfoFields' => $userinfofields);
}
