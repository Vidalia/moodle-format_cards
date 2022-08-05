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

namespace format_cards\output\courseformat\content;

use coding_exception;
use context_course;
use core_geopattern;
use format_topics\output\courseformat\content\section as section_base;
use moodle_url;
use renderer_base;
use section_info;
use stdClass;
use stored_file;

/**
 * renders a course section
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /**
     * In-process cache of section images
     * @var stored_file[]
     */
    static $images = [];

    /**
     * Overrides and adds to template data generated by format_topic
     * @param renderer_base $output
     * @return stdClass Template data
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;

        // Grab the default template data
        $course = $this->format->get_course();
        $data = parent::export_for_template($output);

        // On the course main page, display this section as a card unless the
        // user is currently editing the page. Section #0 should never be
        // displayed as a card
        $isSingleSectionPage = $this->format->get_section_number() != 0;
        $showAsCard = !$isSingleSectionPage
            && !$PAGE->user_is_editing()
            && !$this->section->section == 0;

        $data->showascard = $showAsCard;
        $data->header->headerdisplaymultipage = !$isSingleSectionPage;

        // Need to add a clickable URL that points to the single_section page, not the
        // #section<n> anchor for the course main page
        $url = course_get_url(
            $this->section->course,
            $this->section->section,
            [ 'sr' => true ]
        );

        $data->header->url = $url->out(false);

        // Cards may be highlighted
        $data->highlighted = $course->marker == $this->section->section;

        // Don't show the "insert new topic" button after every section in editing mode
        $data->insertafter = false;

        if(!$showAsCard) {
            if($isSingleSectionPage) $data->header = false;
            return $data;
        }

        // Shorten the card's summary text, if applicable
        if(!empty($data->summary->summarytext)) {
            $data->summary->summarytext = shorten_text(
                content_to_text($data->summary->summarytext, $this->section->summaryformat),
                40,
                true,
                '&hellip;');
        }

        // Try and fetch the image
        $image = $this->get_section_image($this->section);
        if(!is_null($image)) {
            $data->header->image = moodle_url::make_pluginfile_url(
                $image->get_contextid(),
                $image->get_component(),
                $image->get_filearea(),
                $image->get_itemid(),
                $image->get_filepath(),
                $image->get_filename(),
                false
            )->out(false);
        } else {
            $pattern = new core_geopattern();
            $pattern->setColor($this->get_course_colour());
            $pattern->patternbyid($this->section->id);
            $data->header->image = $pattern->datauri();
        }

        return $data;
    }

    public function get_course_colour(): string {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolours = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];

        return $basecolours[$this->format->get_course()->id % 10];
    }

    /**
     * Fetch all the section images for the current course
     * @return stored_file[] Array of image files
     */
    public function get_section_images(): array {

        $course = $this->format->get_course();

        if(!array_key_exists($course->id, self::$images)) {
            $context = context_course::instance($course->id);
            $fileStorage = get_file_storage();

            try {
                $areaFiles = $fileStorage->get_area_files($context->id,
                    'format_cards',
                    FORMAT_CARDS_FILEAREA_IMAGE,
                    false,
                    'itemid, filepath, filename',
                    false
                );
            } catch (coding_exception $e) {
                return [];
            }

            self::$images[$course->id] = [];

            foreach($areaFiles as $file) {
                self::$images[$course->id][$file->get_itemid()] = $file;
            }
        }

        return self::$images[$course->id];
    }

    /**
     * Fetch the image file for a given section
     * @param section_info $section
     * @return stored_file|null
     */
    public function get_section_image(section_info $section): ?stored_file {
        $images = $this->get_section_images();

        if(array_key_exists($section->section, $images))
            return $images[$section->section];

        return null;
    }

}
