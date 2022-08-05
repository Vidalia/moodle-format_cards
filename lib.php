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
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

use core\output\notification;

require_once "$CFG->dirroot/course/format/topics/lib.php";

define('FORMAT_CARDS_FILEAREA_IMAGE', 'image');
define('FORMAT_CARDS_SECTION0_COURSEPAGE', 0);
define('FORMAT_CARDS_SECTION0_ALLPAGES', 1);

/**
 * Course format main class
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_cards extends format_topics {

    /**
     * Always force the course to display on multiple pages
     * @return bool|stdClass|null
     */
    public function get_course() {
        $course = parent::get_course();

        $course->coursedisplay = COURSE_DISPLAY_MULTIPAGE;

        return $course;
    }

    public function uses_indentation(): bool {
        return true;
    }

    /**
     * Gets a list of user options for this course format
     * @param bool $foreditform
     * @return array|array[]|false
     */
    public function course_format_options($foreditform = false) {
        $options = parent::course_format_options($foreditform);

        // We always show one section per page
        $options['coursedisplay']['default'] = COURSE_DISPLAY_MULTIPAGE;

        $options['section0'] = [
            'default' => FORMAT_CARDS_SECTION0_COURSEPAGE,
            'type' => PARAM_INT,
            'label' => new lang_string('form:course:section0', 'format_cards'),
            'help' => 'form:course:section0',
            'help_component' => 'format_cards',
            'element_type' => 'select',
            'element_attributes' => [
                [
                    FORMAT_CARDS_SECTION0_COURSEPAGE => new lang_string('form:course:section0:coursepage', 'format_cards'),
                    FORMAT_CARDS_SECTION0_ALLPAGES => new lang_string('form:course:section0:allpages', 'format_cards')
                ]
            ]
        ];

        return $options;
    }

    public function create_edit_form_elements(&$mform, $forsection = false)
    {
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if($this->course_has_grid_images()) {
            $elements[] = $mform->addElement('checkbox', 'importgridimages', get_string('form:course:importgridimages', 'format_cards'));
            $mform->addHelpButton('importgridimages', 'form:course:importgridimages', 'format_cards');
        }

        return $elements;
    }

    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;

        $changes = parent::update_course_format_options($data, $oldcourse);

        if(!$data->importgridimages || !$this->course_has_grid_images())
            return $changes;

        // This is a pseudo-element
        $data->importgridimages = false;

        $gridImages = $DB->get_records('format_grid_icon', [ 'courseid' => $this->courseid ]);

        $fileStorage = get_file_storage();
        $courseContext = context_course::instance($this->courseid);
        $existingImages = $fileStorage->get_area_files(
            $courseContext->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            false,
            'itemid, filepath, filename',
            false
        );

        $added = 0;
        $displayedResizeError = false;

        foreach($gridImages as $gridImage) {
            if(!$gridImage->image)
                continue;

            $gridImageFile = $fileStorage->get_file(
                $courseContext->id,
                'course',
                'section',
                $gridImage->sectionid,
                '/gridimage/',
                "{$gridImage->displayedimageindex}_$gridImage->image"
            );

            if(!$gridImageFile) {
                debugging("Couldn't get grid format image {$gridImage->displayedimageindex}_$gridImage->image", DEBUG_DEVELOPER);
                continue;
            }

            try {
                $newFile = $fileStorage->create_file_from_storedfile([
                    'contextid' => $courseContext->id,
                    'component' => 'format_cards',
                    'filearea' => FORMAT_CARDS_FILEAREA_IMAGE,
                    'filepath' => '/'
                ], $gridImageFile);

                $added++;
            } catch (file_exception $e) {
                continue;
            }

            if(!$newFile) {
                debugging("Failed to create new format_cards image from grid for section $gridImage->sectionid", DEBUG_DEVELOPER);
                continue;
            }

            $existingImagesForSection = array_filter($existingImages, function($file) use ($gridImageFile) {
                return $file->sectionid == $file->get_itemid();
            });

            try {
                $this->resize_card_image($gridImage->sectionid);
            } catch (moodle_exception $e) {
                if(!$displayedResizeError) {
                    \core\notification::add(get_string('editimage:resizefailed', 'format_cards'), notification::NOTIFY_WARNING);
                    $displayedResizeError = true;
                }
            }

            foreach($existingImagesForSection as $existingSectionImage) {
                $existingSectionImage->delete();
            }

        }

        \core\notification::add(get_string('editimage:imported', 'format_cards', $added), notification::NOTIFY_SUCCESS);
    }


    /**
     * Returns the default display name for a section which doesn't have
     * a user defined title
     * @param stdClass|section_info $section The section
     * @return lang_string|string A default name for the given section
     * @throws coding_exception
     */
    public function get_default_section_name($section) {
        $default = parent::get_default_section_name($section);

        if(!empty(trim($default)))
            return $default;

        return get_string('section:default', 'format_cards', $section->section);
    }

    /**
     * format_topics::get_view_url() returns the course URL with an HTML anchor pointing to the selected
     * section, if there is one. format_cards always uses single section per page mode, so we always want
     * a URL with the section number as a query parameter
     * @param null|int|stdClass|section_info $section The selected section
     * @param array $options
     * @return moodle_url
     * @throws moodle_exception
     */
    public function get_view_url($section, $options = []) {

        if(!empty($options['navigation']))
            return null;

        $base = new moodle_url("/course/view.php", [ 'id' => $this->get_course()->id ]);

        if(!$section)
            return $base;

        if(is_object($section) || $section instanceof section_info)
            $section = $section->section;

        $base->param('section', $section);

        return $base;
    }

    /**
     * @return bool True if the course had images for format_grid sections
     */
    public function course_has_grid_images() {
        global $DB;

        // Definitely no images if format_grid isn't installed
        if(!in_array('grid', get_sorted_course_formats()))
            return false;

        return $DB->record_exists('format_grid_icon', [ 'courseid' => $this->get_course()->id ]);
    }

    /**
     * @return bool True if the course has one or more section images
     * @throws dml_exception
     */
    public function course_has_card_images(): bool {
        global $DB;

        $course = $this->get_course();
        $context = context_course::instance($course->id);

        return $DB->record_exists(
            'files',
            [
                'component' => 'format_cards',
                'filearea' => FORMAT_CARDS_FILEAREA_IMAGE,
                'contextid' => $context->id
            ]
        );
    }

    /**
     * @param int|stdClass $section Section ID or class
     * @return void
     */
    public function resize_card_image($section) {
        global $CFG;

        require_once "$CFG->libdir/gdlib.php";

        if(is_object($section))
            $section = $section->section;

        $course = $this->get_course();
        $context = context_course::instance($course->id);
        $fileStorage = get_file_storage();

        // First, grab the file
        $images = $fileStorage->get_area_files(
            $context->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            false,
            'itemid, filepath, filename',
            false
        );
        $originalImage = array_filter($images, function($image) use ($section) {
            return $image->get_itemid() == $section && !empty($image->get_mimetype());
        });

        //echo "<pre>" . json_encode($originalImage, JSON_PRETTY_PRINT) . "</pre>";

        if(empty($originalImage))
            return;

        /** @var stored_file $originalImage */
        $originalImage = reset($originalImage);

        $tempFilepath = $originalImage->copy_content_to_temp('format_cards', 'sectionimage_');

        $resized = resize_image($tempFilepath, null, 360, false);

        if(!$resized)
            throw new moodle_exception('failedtoresize', 'format_cards');

        $originalImage->delete();

        try {
            $resizedImage = $fileStorage->create_file_from_string(
                [
                    'contextid' => $originalImage->get_contextid(),
                    'component' => $originalImage->get_component(),
                    'filearea' => $originalImage->get_filearea(),
                    'itemid' => $originalImage->get_itemid(),
                    'filepath' => $originalImage->get_filepath(),
                    'filename' => $originalImage->get_filename()
                ], $resized
            );
            $originalImage->delete();
        } finally {
            unlink($tempFilepath);
        }
    }
}

/**
 * @param $itemtype
 * @param $itemid
 * @param $newvalue
 * @return \core\output\inplace_editable|void
 * @throws dml_exception
 */
function format_cards_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once "$CFG->dirroot/course/lib.php";

    if(in_array($itemtype, [ 'sectionname', 'sectionnamenl' ])) {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'cards'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

function format_cards_pluginfile($course, $courseModule, $context, $filearea, $args, $forcedownload, array $options = []) {
    if($context->contextlevel != CONTEXT_COURSE && $context->contextlevel != CONTEXT_SYSTEM)
        send_file_not_found();

    if($filearea != FORMAT_CARDS_FILEAREA_IMAGE)
        send_file_not_found();

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if(!$args)
        $filepath = '/';
    else
        $filepath = '/' . implode('/', $args) . '/';

    $fileStorage = get_file_storage();
    $file = $fileStorage->get_file($context->id, 'format_cards', $filearea, $itemid, $filepath, $filename);
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}