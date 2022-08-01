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
 * Displays the image editor
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use format_cards\forms\cardimage;

global $CFG, $PAGE, $OUTPUT;
require_once __DIR__ . "/../../../config.php";
require_once "$CFG->dirroot/course/format/lib.php";
require_once "$CFG->libdir/gdlib.php";
require_once "$CFG->libdir/classes/output/notification.php";

$courseid = required_param('course', PARAM_INT);
$sectionid = required_param('section', PARAM_INT);

require_login($courseid, false);

$format = course_get_format($courseid);
$course = $format->get_course();

$context = context_course::instance($courseid);
$url = new moodle_url('/course/format/cards/editimage.php', [ 'course' => $courseid, 'section' => $sectionid ]);

$PAGE->set_title(get_string('editimagefor', 'format_cards', get_section_name($course, $sectionid)));
$PAGE->set_heading(get_string('editimagefor', 'format_cards', get_section_name($course, $sectionid)));
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->add_body_class('limitedwidth');

$PAGE->navbar->add(
    get_section_name($course, $sectionid),
    course_get_url($course, $sectionid)
);

$PAGE->navbar->add(
    get_string('editimage', 'format_cards')
);

$form = new cardimage($url);
$draftImageId = file_get_submitted_draft_itemid('image');

file_prepare_draft_area(
    $draftImageId,
    $context->id,
    'format_cards',
    FORMAT_CARDS_FILEAREA_IMAGE,
    $sectionid
);

if($form->is_cancelled()) {
    redirect(course_get_url($courseid));
}

if($data = $form->get_data()) {
    file_save_draft_area_files(
        $data->image,
        $context->id,
        'format_cards',
        FORMAT_CARDS_FILEAREA_IMAGE,
        $sectionid
    );

    if($format instanceof format_cards) {
        try {
            $format->resize_card_image($sectionid);
        } catch (moodle_exception $e) {
            redirect(course_get_url($courseid), get_string('editimage:resizefailed', 'format_cards'), null, notification::NOTIFY_WARNING);
        }
    }

    redirect(course_get_url($courseid));
}

$data = new stdClass;
$data->image = $draftImageId;

$form->set_data($data);
$form->add_action_buttons();

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();