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

use core\notification;
use core\output\inplace_editable;
use format_cards\forms\editcard_form;

require_once "$CFG->dirroot/course/format/topics/lib.php";

define('FORMAT_CARDS_FILEAREA_IMAGE', 'image');
define('FORMAT_CARDS_USEDEFAULT', 0);
define('FORMAT_CARDS_SECTION0_COURSEPAGE', 1);
define('FORMAT_CARDS_SECTION0_ALLPAGES', 2);
define('FORMAT_CARDS_ORIENTATION_VERTICAL', 1);
define('FORMAT_CARDS_ORIENTATION_HORIZONTAL', 2);

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
     *
     * @return bool|stdClass|null
     */
    public function get_course() {
        $course = parent::get_course();

        $course->coursedisplay = COURSE_DISPLAY_MULTIPAGE;

        return $course;
    }

    /**
     * Gets a list of user options for this course format
     *
     * @param bool $foreditform
     * @return array|array[]|false
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_format_options($foreditform = false) {
        $options = parent::course_format_options($foreditform);

        $defaults = get_config('format_cards');

        // We always show one section per page
        $options['coursedisplay']['default'] = COURSE_DISPLAY_MULTIPAGE;

        $section0options = [
            FORMAT_CARDS_SECTION0_COURSEPAGE => new lang_string('form:course:section0:coursepage', 'format_cards'),
            FORMAT_CARDS_SECTION0_ALLPAGES => new lang_string('form:course:section0:allpages', 'format_cards')
        ];

        $options['section0'] = [
            'default' => FORMAT_CARDS_USEDEFAULT,
            'type' => PARAM_INT,
            'label' => new lang_string('form:course:section0', 'format_cards'),
            'help' => 'form:course:section0',
            'help_component' => 'format_cards',
            'element_type' => 'select',
            'element_attributes' => [
                array_merge(
                    [ FORMAT_CARDS_USEDEFAULT => new lang_string('form:course:usedefault', 'format_cards', $section0options[$defaults->section0]) ],
                    $section0options
                )
            ],
        ];

        $orientationoptions = [
            FORMAT_CARDS_ORIENTATION_VERTICAL => new lang_string('form:course:cardorientation:vertical', 'format_cards'),
            FORMAT_CARDS_ORIENTATION_HORIZONTAL => new lang_string('form:course:cardorientation:horizontal', 'format_cards')
        ];

        $options['cardorientation'] = [
            'default' => FORMAT_CARDS_USEDEFAULT,
            'type' => PARAM_INT,
            'label' => new lang_string('form:course:cardorientation', 'format_cards'),
            'element_type' => 'select',
            'element_attributes' => [
                array_merge(
                    [ FORMAT_CARDS_USEDEFAULT => new lang_string('form:course:usedefault', 'format_cards', $orientationoptions[$defaults->cardorientation]) ],
                    $orientationoptions
                )
            ]
        ];

        return $options;
    }

    /**
     * Modify the edit section form to include controls for editing
     * the image for a section
     *
     * @param $action
     * @param $customdata
     * @return editcard_form
     */
    public function editsection_form($action, $customdata = array()) {
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }

        $form = new editcard_form($action, $customdata);

        $draftimageid = file_get_submitted_draft_itemid('image');
        file_prepare_draft_area(
            $draftimageid,
            context_course::instance($this->get_courseid())->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            $customdata['cs']->id
        );

        $form->set_data([ 'image' => $draftimageid ]);

        return $form;
    }

    /**
     * When the section form is changed, make sure any uploaded
     * images are saved properly
     *
     * @throws coding_exception
     */
    public function update_section_format_options($data) {
        $changes = parent::update_section_format_options($data);

        file_save_draft_area_files(
            $data['image'],
            context_course::instance($this->get_courseid())->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            $data['id']
        );

        // Try and resize the image. It's no big deal if this fails -- we still
        // have the image, it'll just affect page load times
        try {
            $this->resize_card_image($data['id']);
        } catch (moodle_exception $e) {
            notification::add(
                get_string('editimage:resizefailed', 'format_cards'),
                notification::WARNING
            );
        }

        return $changes;
    }


    /**
     * Append the "importgridimages" checkbox directly to the form.
     * We do this rather than using {@see self::course_format_options()} to prevent "importgridimages" being saved
     * as an actual option.
     *
     * @param MoodleQuickForm $mform The form
     * @param bool $forsection
     * @return array
     * @throws coding_exception
     */
    public function create_edit_form_elements(&$mform, $forsection = false): array {
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if($this->course_has_grid_images() && !$forsection) {
            $elements[] = $mform->addElement('checkbox', 'importgridimages', get_string('form:course:importgridimages', 'format_cards'));
            $mform->addHelpButton('importgridimages', 'form:course:importgridimages', 'format_cards');
        }

        return $elements;
    }

    /**
     * Update course format options from form data.
     * Primarily just calls the parent method to do the actual saving, but additionally
     * imports images from format_grid if selected
     * @param $data
     * @param $oldcourse
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;

        $changes = parent::update_course_format_options($data, $oldcourse);

        if(!$data->importgridimages || !$this->course_has_grid_images())
            return $changes;

        // This is a pseudo-element. Set it to false here so that we don't save it in the database
        $data->importgridimages = false;

        $gridimages = $DB->get_records('format_grid_icon', [ 'courseid' => $this->courseid ]);

        $storage = get_file_storage();
        $context = context_course::instance($this->courseid);
        $existingimages = $storage->get_area_files(
            $context->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            false,
            'itemid, filepath, filename',
            false
        );

        $added = 0;
        $hasdisplayedresizeerror = false;

        foreach($gridimages as $gridimage) {
            if(!$gridimage->image)
                continue;

            $gridimagefile = $storage->get_file(
                $context->id,
                'course',
                'section',
                $gridimage->sectionid,
                '/gridimage/',
                "{$gridimage->displayedimageindex}_$gridimage->image"
            );

            if(!$gridimagefile) {
                debugging("Couldn't get grid format image {$gridimage->displayedimageindex}_$gridimage->image", DEBUG_DEVELOPER);
                continue;
            }

            try {
                $newfile = $storage->create_file_from_storedfile([
                    'contextid' => $context->id,
                    'component' => 'format_cards',
                    'filearea' => FORMAT_CARDS_FILEAREA_IMAGE,
                    'itemid' => $gridimage->sectionid,
                    'filepath' => '/'
                ], $gridimagefile);

                $added++;
                $changes = true;
            } catch (file_exception $e) {
                continue;
            }

            if(!$newfile) {
                debugging("Failed to create new format_cards image from grid for section $gridimage->sectionid", DEBUG_DEVELOPER);
                continue;
            }

            $existingsectionimages = array_filter($existingimages, function($file) use ($gridimage) {
                return $gridimage->sectionid == $file->get_itemid();
            });

            try {
                $this->resize_card_image($gridimage->sectionid);
            } catch (moodle_exception $e) {
                if(!$hasdisplayedresizeerror) {
                    notification::add(get_string('editimage:resizefailed', 'format_cards'), notification::NOTIFY_WARNING);
                    $hasdisplayedresizeerror = true;
                }
            }

            foreach($existingsectionimages as $existingsectionimage) {
                $existingsectionimage->delete();
            }

        }

        notification::add(
            get_string('editimage:imported', 'format_cards', $added),
            notification::SUCCESS
        );
        return $changes;
    }

    /**
     * Fetch a format option from the settings. If it's one of the options that can have an admin provided default,
     * use that unless it's been overridden for this course
     *
     * @param string $name Option key
     * @param null|int|section_info|stdClass $section The section this option applies to, or 0 for the whole course
     * @return mixed The option's valie
     * @throws dml_exception
     */
    public function get_format_option(string $name, $section = null) {
        $options = $this->get_format_options($section);
        $defaults = get_config('format_cards');
        
        $value = $options[$name];

        if(!object_property_exists($defaults, $name))
            return $value;

        if($value != FORMAT_CARDS_USEDEFAULT)
            return $value;

        return $defaults->$name;
    }

    /**
     * Returns the default display name for a section which doesn't have
     * a user defined title
     *
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
     *
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
     * If this course previously used format_grid, check to see if there are any images
     * that we might want to import
     *
     * @return bool True if the course had images for format_grid sections
     * @throws dml_exception
     */
    public function course_has_grid_images(): bool {
        global $DB;

        // Definitely no images if format_grid isn't installed
        if(!in_array('grid', get_sorted_course_formats()))
            return false;

        $course = $this->get_course();
        if(!$course)
            return false;

        if(!isset($course->id))
            return false;

        return $DB->record_exists('format_grid_icon', [ 'courseid' => $course->id ]);
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
     * Attempt to resize the image uploaded for a card
     *
     * @param int|stdClass $section Section ID or class
     * @return void
     * @throws coding_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function resize_card_image($section) {
        global $CFG;

        require_once "$CFG->libdir/gdlib.php";

        if(is_object($section))
            $section = $section->id;

        $course = $this->get_course();
        $context = context_course::instance($course->id);
        $storage = get_file_storage();

        // First, grab the file
        $images = $storage->get_area_files(
            $context->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            false,
            'itemid, filepath, filename',
            false
        );
        $originalimage = array_filter($images, function($image) use ($section) {
            return $image->get_itemid() == $section && !empty($image->get_mimetype());
        });

        if(empty($originalimage))
            return;

        /** @var stored_file $originalimage */
        $originalimage = reset($originalimage);

        $tempfilepath = $originalimage->copy_content_to_temp('format_cards', 'sectionimage_');

        $resized = resize_image($tempfilepath, null, 500, false);

        if(!$resized)
            throw new moodle_exception('failedtoresize', 'format_cards');

        $originalimage->delete();

        try {
            $storage->create_file_from_string(
                [
                    'contextid' => $originalimage->get_contextid(),
                    'component' => $originalimage->get_component(),
                    'filearea' => $originalimage->get_filearea(),
                    'itemid' => $originalimage->get_itemid(),
                    'filepath' => $originalimage->get_filepath(),
                    'filename' => $originalimage->get_filename()
                ], $resized
            );
            $originalimage->delete();
        } finally {
            unlink($tempfilepath);
        }
    }
}

/**
 * Allow for the section name to be edited in-place
 *
 * @param $itemtype
 * @param $itemid
 * @param $newvalue
 * @return inplace_editable|void
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

/**
 * Serves files for format_cards
 *
 * @param $course
 * @param $coursemodule
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return void
 * @throws coding_exception
 */
function format_cards_pluginfile($course, $coursemodule, $context, $filearea, $args, $forcedownload, array $options = []) {
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

    $filestorage = get_file_storage();
    $file = $filestorage->get_file($context->id, 'format_cards', $filearea, $itemid, $filepath, $filename);
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}