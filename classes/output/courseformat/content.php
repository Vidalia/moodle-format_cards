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

namespace format_cards\output\courseformat;


use coding_exception;
use format_topics\output\courseformat\content as content_base;
use renderer_base;

/**
 * Course content renderer
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * If the user is editing the page, just use the default renderer for format_topics
     * Otherwise, override the renderer to add our own sections onto the page
     * @param renderer_base $renderer
     * @return string
     * @throws coding_exception
     */
    public function get_template_name(renderer_base $renderer): string {
        global $PAGE;

        if($PAGE->user_is_editing())
            return parent::get_template_name($renderer);

        return "format_cards/local/content";
    }

    public function export_for_template(renderer_base $output) {

        // Is this a single section page?
        $singleSection = $this->format->get_section_number();

        $data = parent::export_for_template($output);

        if(!$singleSection)
            return $data;

        if($this->format->get_format_options()['section0'] == FORMAT_CARDS_SECTION0_COURSEPAGE)
            $data->initialsection = '';

        return $data;
    }

}