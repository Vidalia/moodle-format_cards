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

namespace format_cards\output\courseformat\content\section;

use context_course;
use format_topics\output\courseformat\content\section\controlmenu as controlmenu_base;
use moodle_url;

/**
 * Renders the control menu for a section
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {

    /** @var course_format the course format class */
    protected $format;

    /** @var section_info the course section class */
    protected $section;

    /**
     * Generate the edit control items of a section.
     *
     * This method must remain public until the final deprecation of section_edit_control_items.
     *
     * @return array of edit control items
     */
    public function section_control_items() {

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        $controls = parent::section_control_items();

        // Can't edit the image for section #0
        if($this->section->section == 0)
            return $controls;

        $controls['editimage'] = [
            'url' => new moodle_url(
                '/course/format/cards/editimage.php',
                [
                    'course' => $course->id,
                    'section' => $section->section
                ]
            ),
            'icon' => 'e/insert_edit_image',
            'name' => get_string('editimage', 'format_cards'),
            'attr' => [
                'class' => 'format_card_editimage',
            ]
        ];

        return $controls;

    }
}
