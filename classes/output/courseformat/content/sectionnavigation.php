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
 * Renders course section navigation
 *
 * @package     format_cards
 * @copyright   2024 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_cards\output\courseformat\content;

use cm_info;
use context_course;
use core\exception\coding_exception;
use core_courseformat\output\local\content\sectionnavigation as sectionnavigation_base;
use moodle_exception;
use renderer_base;
use section_info;
use stdClass;

/**
 * Renders course section navigation
 *
 * @package     format_cards
 * @copyright   2024 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sectionnavigation extends sectionnavigation_base {

    /**
     * Get the template path
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_cards/local/content/sectionnavigation';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $CFG, $DB;

        $data = parent::export_for_template($output);

        // Inject courseurl into the template data if we don't already have it.
        if (!object_property_exists($data, 'courseurl')) {
            $format = $this->format;
            $course = $format->get_course();

            $displayoption = $this->format->get_format_option('sectionnavigationhome');
            $data->showcoursehome = $displayoption == FORMAT_CARDS_SECTIONNAVIGATIONHOME_SHOW;

            $data->courseurl = course_get_url($course, null, [ 'navigation' => true ])->out();
        }

        if ($data->courseurl == $data->previousurl) {
            $data->showcoursehome = true;
            $data->hasprevious = false;
        }

        // Moodle 4.5 introduces subsections, which add another layer of navigation.
        // If we're currently _in_ a subsection, then we need to be able to navigate 'up' out of it.
        if ($CFG->version >= 2024100700) {
            $this->add_subsection_data($data);
        }

        return $data;
    }

    /**
     * Given the cm_info for a subsection, get the course_section it refers to.
     *
     * @param cm_info $cminfo
     * @return section_info
     */
    protected function get_sectioninfo_from_subsection(cm_info $cminfo): section_info {
        if ($cminfo->modname !== 'subsection') {
            throw new coding_exception("Course module with id $cminfo->id is not a subsection");
        }

        $modinfo = $this->format->get_modinfo();

        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->component !== 'mod_subsection') {
                continue;
            }

            if ((int) $section->itemid === (int) $cminfo->instance) {
                return $section;
            }
        }

        throw new coding_exception("Course module with id $cminfo->id couldn't be matched to a course_section");
    }

    /**
     * If the current section is a subsection, adds additional navigation data
     *
     * @param stdClass $data
     * @return void
     * @throws moodle_exception
     */
    protected function add_subsection_data(stdClass &$data): void {
        $data->hasparent = false;
        $modinfo = $this->format->get_modinfo();

        $currentsection = $modinfo->get_section_info($this->sectionno, MUST_EXIST);

        // Nothing to do if the current section isn't a delegated mod_subsection.
        if (!$currentsection->is_delegated() || $currentsection->component !== 'mod_subsection') {
            return;
        }

        $parentsection = false;
        $ourposition = -1;

        foreach ($modinfo->get_section_info_all() as $sectioninfo) {

            // Ignore section #0.
            if ((int) $sectioninfo->sectionnum === 0) {
                continue;
            }

            // Does this section contain a mod_subsection where the instanceid matches the itemid of the
            // subsection we're currently in?
            foreach ($sectioninfo->get_sequence_cm_infos() as $index => $sibling) {
                if ((int) $sibling->instance !== (int) $currentsection->itemid) {
                    continue;
                }

                $ourposition = $index;
            }

            if ($ourposition === -1) {
                continue;
            }

            $data->hasparent = $sectioninfo->uservisible;
            $data->parenturl = $this->format->get_view_url($sectioninfo);
            $data->parentname = $sectioninfo->name;
            $data->parenthidden = !$sectioninfo->visible;

            $parentsection = $sectioninfo;
            break;
        }

        if ($parentsection === false || $ourposition === -1) {
            throw new coding_exception("Section with ID $currentsection->id is an orphaned subsection");
        }

        // If we're in a subsection, the previous and next links only make sense if the current section's
        // immediate siblings are also subsections.

        $data->hasprevious = false;
        $data->hasnext = false;

        // Search back in the sequence to find a subsection we can view.
        for ($i = $ourposition - 1; $i >= 0; $i--) {
            $cminfo = $parentsection->get_sequence_cm_infos()[$i];

            // Not a subsection, stop searching.
            if ($cminfo->modname !== 'subsection') {
                break;
            }

            $section = $this->get_sectioninfo_from_subsection($cminfo);

            if ($section->uservisible) {
                $data->hasprevious = true;
                $data->previousurl = $this->format->get_view_url($section);
                $data->previousname = $section->name;
                $data->previoushidden = !$section->visible;

                break;
            }
        }

        // Search forward in the sequence to find the next subsection we can view.
        for ($i = $ourposition + 1; $i < count($parentsection->get_sequence_cm_infos()); $i++) {
            $cminfo = $parentsection->get_sequence_cm_infos()[$i];

            // Not a subsection, stop searching.
            if ($cminfo->modname !== 'subsection') {
                break;
            }

            $section = $this->get_sectioninfo_from_subsection($cminfo);

            if ($section->uservisible) {
                $data->hasnext = true;
                $data->nexturl = $this->format->get_view_url($section);
                $data->nextname = $section->name;
                $data->nexthidden = !$section->visible;

                break;
            }
        }
    }
}
