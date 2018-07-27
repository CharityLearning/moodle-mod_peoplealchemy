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
 * People Alchemy module admin settings and defaults
 *
 * @package    mod_peoplealchemy
 * @copyright  2018 Josh Willcock  {@link http://josh.cloud}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    // General settings.
    $settings->add(new admin_setting_configtext('peoplealchemy/peoplealchemyurl',
    get_string('peoplealchemyurl', 'peoplealchemy'),
    get_string('peoplealchemyurldesc', 'peoplealchemy'),
    'https://alchemyassistant.com', PARAM_URL));
    $settings->add(new admin_setting_configpasswordunmask('peoplealchemy/networkkey',
    get_string('networkkey', 'peoplealchemy'),
    get_string('networkkeydesc', 'peoplealchemy'),
     '', PARAM_TEXT));
}
