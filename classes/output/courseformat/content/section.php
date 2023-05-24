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
 * Renders a course section
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_cards\output\courseformat\content;

use completion_info;
use core_courseformat\base as course_format;
use format_cards\output\courseformat\content\section\sectionbreak;
use format_topics\output\courseformat\content\section as section_base;
use moodle_url;
use renderer_base;
use section_info;
use stdClass;

/**
 * Renders a course section
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /**
     * @var sectionbreak Section break renderer
     */
    protected $sectionbreak;

    /**
     * @param course_format $format
     * @param section_info $section
     */
    public function __construct(course_format $format, section_info $section) {
        parent::__construct($format, $section);

        $this->sectionbreak = new sectionbreak($format, $section);
    }

    public function get_template_name(\renderer_base $renderer): string {
        return $this->show_as_card()
            ? 'format_cards/local/content/section/card'
            : 'format_cards/local/content/section';
    }

    private function show_as_card() {
        $issinglesectionpage = $this->format->get_section_number() != 0;
        return !$issinglesectionpage
            && !$this->format->show_editor()
            && !$this->section->section == 0;
    }

    /**
     * Overrides and adds to template data generated by format_topic
     *
     * @param renderer_base $output
     * @return stdClass Template data
     */
    public function export_for_template(renderer_base $output): stdClass {

        // Grab the default template data.
        $course = $this->format->get_course();
        $data = parent::export_for_template($output);
        $data->classes = [];

        if (object_property_exists($data, "hiddenfromstudents")
            && $data->hiddenfromstudents) {
            $data->classes[] = "hiddenfromstudents";
        }

        // On the course main page, display this section as a card unless the
        // user is currently editing the page. Section #0 should never be
        // displayed as a card.
        $issinglesectionpage = $this->format->get_section_number() != 0;
        $showascard = $this->show_as_card();

        $data->showascard = $showascard;

        // Cards may be highlighted.
        $data->highlighted = $course->marker == $this->section->section;
        if ($data->highlighted) {
            $data->classes[] = "highlighted";
        }

        // Don't show the "insert new topic" button after every section in editing mode.
        $data->insertafter = false;

        $this->add_section_break($data, $output);

        if (!$showascard) {
            if ($issinglesectionpage) {
                $data->header = false;
            }
            return $data;
        }

        switch ($this->format->get_format_option('cardorientation')) {
            case FORMAT_CARDS_ORIENTATION_HORIZONTAL:
                $data->classes[] = "card-horizontal";
                break;
            case FORMAT_CARDS_ORIENTATION_SQUARE:
                $data->classes[] = "card-square";
                break;
        }

        // Add completion data.
        $completion = $this->get_section_completion();
        $data->completion = $completion;
        $data->hascompletion = !empty($completion);

        // Shorten the card's summary text, if applicable.
        if (!empty($data->summary->summarytext)) {
            if ($this->format->get_format_option('showsummary', $this->section) == FORMAT_CARDS_SHOWSUMMARY_SHOW) {
                if ($this->section->summaryformat == FORMAT_MARKDOWN) {
                    $data->summary->summarytext = markdown_to_html($data->summary->summarytext);
                }
                $data->summary->summarytext = shorten_text(
                    strip_tags(
                        $data->summary->summarytext,
                        '<b><i><u><strong><em><a>'
                    ),
                    250,
                    true,
                    '&hellip;');
            } else {
                $data->summary->summarytext = '';
            }
        }

        return $data;
    }

    /**
     * Grabs the completion info for this section
     *
     * @return array
     */
    private function get_section_completion(): array {

        // Can't do anything if completion is disabled, or we're a guest user.
        if (isguestuser() || !$this->format->get_course()->enablecompletion) {
            return [];
        }

        // Don't do anything if we don't want to view completion data.
        if ($this->format->get_format_option('showprogress') == FORMAT_CARDS_SHOWPROGRESS_HIDE) {
            return [];
        }

        $completioninfo = new completion_info($this->format->get_course());
        $modinfo = $this->section->modinfo;

        if (!array_key_exists($this->section->section, $modinfo->sections)) {
            return [];
        }

        // List of course module IDs for this section.
        $sectioncmids = $modinfo->sections[$this->section->section];

        $total = 0;
        $completed = 0;

        // Iterate through all the course module ID's that appear in this section.
        foreach ($sectioncmids as $cmid) {
            $cminfo = $modinfo->cms[$cmid];

            // Don't include the course module if it's not visible, or about to be deleted.
            if (!$cminfo->uservisible || $cminfo->deletioninprogress) {
                continue;
            }

            // Don't include the course module if completion tracking is disabled.
            if ($completioninfo->is_enabled($cminfo) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $total++;

            // Finally, figure out if the user has completed this course module.
            $completiondata = $completioninfo->get_data($cminfo, true);

            if (in_array(
                $completiondata->completionstate,
                [ COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS ]
            )) {
                $completed++;
            }
        }

        // Don't show completion data if there's nothing completable in this section.
        if ($total == 0) {
            return [];
        }

        $iscomplete = $total == $completed;
        $progressformat = $this->format->get_format_option('progressformat');
        $percentage = round(($completed / $total) * 100);

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage,
            'dashoffset' => 100 - $percentage,
            'iscomplete' => $iscomplete,
            'hasprogress' => $completed > 0,
            'showpercentage' => !$iscomplete && $progressformat == FORMAT_CARDS_PROGRESSFORMAT_PERCENTAGE,
            'showcount' => !$iscomplete && $progressformat == FORMAT_CARDS_PROGRESSFORMAT_COUNT
        ];
    }

    /**
     * Adds section break data, if available
     *
     * @param stdClass $data
     * @param renderer_base $output
     * @return void
     */
    private function add_section_break(stdClass $data, renderer_base $output): void {

        // Add the sectionbreak key if there's a break before this section.
        $data->sectionbreak = $this->sectionbreak->export_for_template($output);

        // Show the 'add section break' button in edit mode if there isn't already
        // a section break.
        if (!$data->sectionbreak && $this->format->show_editor(['moodle/course:update'])) {
            $data->addsectionbreak = [
                'url' => (new moodle_url(
                    '/course/format/cards/editsectionbreak.php',
                    [
                        'courseid' => $this->section->course,
                        'sectionid' => $this->section->id,
                        'action' => 'add'
                    ]
                ))->out(false)
            ];
        }
    }

}
