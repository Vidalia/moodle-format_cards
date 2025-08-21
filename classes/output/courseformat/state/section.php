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

declare(strict_types=1);

namespace format_cards\output\courseformat\state\section;

use core_availability\info_section;
use core_courseformat\base as course_format;
use dml_exception;
use format_cards;
use renderer_base;
use section_info;
use renderable;
use stdClass;
use context_course;

/**
 * Changes the default collapse or expand behaviour of subsections.
 *
 * @package    format_cards
 * @copyright  2025 University of Essex
 * @author     John Maydew <jdmayd@essex.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core_courseformat\output\local\state\section {

    /**
     * Exports the default state of the section. If the user hasn't set a preference
     * for whether a section should be expanded or not, default to the course format option.
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws dml_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        $state = parent::export_for_template($output);

        assert($this->format instanceof format_cards);

        $indexcollapsed = $this->format->get_format_option('subsectionscollapsed');
        $contentcollapsed = $this->format->get_format_option('subsectionscollapsed');
        $preferences = $this->format->get_sections_preferences();
        if (isset($preferences[$section->id])) {
            $sectionpreferences = $preferences[$this->section->id];
            if (!empty($sectionpreferences->contentcollapsed)) {
                $contentcollapsed = !$contentcollapsed;
            }
            if (!empty($sectionpreferences->indexcollapsed)) {
                $indexcollapsed = !$indexcollapsed;
            }
        }

        $state->contentcollapsed = $contentcollapsed;
        $state->indexcollapsed = $indexcollapsed;

        return $state;
    }

}
