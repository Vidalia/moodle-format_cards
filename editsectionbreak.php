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
 * Adds or removes a section break.
 *
 * @package     format_cards
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

// Login and permissions check.
require_login($courseid, false);
$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$format = course_get_format($courseid);

// If we're removing a section break, we want to also delete the title key so if the break
// gets re-added it doesn't retain its old name.
if ($action == 'remove') {
    global $DB;

    $DB->delete_records('course_format_options',
        [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'format' => 'cards',
            'name' => 'sectionbreaktitle'
        ]
    );
}

$format->update_section_format_options([
    'id' => $sectionid,
    'sectionbreak' => $action === 'add'
]);

$redirect = new moodle_url(course_get_url($courseid), null, "sectionid-$sectionid-title");

redirect($redirect);
