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
 * Renders a section break.
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_cards\output\courseformat\content\section;

use core\output\inplace_editable;
use core\output\named_templatable;
use core_courseformat\base as course_format;
use lang_string;
use moodle_url;
use renderable;
use renderer_base;
use section_info;

/**
 * Renders a section break.
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sectionbreak extends inplace_editable implements named_templatable, renderable {

    /** @var course_format the course format */
    protected course_format $format;

    /** @var section_info the section object */
    private section_info $section;

    /** @var bool editable if the title is editable */
    protected $editable;

    /**
     * @var bool Whether there is a section break
     */
    protected bool $hasbreak;

    /**
     * @var string The section break's title
     */
    protected string $breaktitle;

    /**
     * @param course_format $format the course format
     * @param section_info $section the section info
     * @param bool|null $editable force editable value
     */
    public function __construct(
        course_format $format,
        section_info $section,
        ?bool $editable = null
    ) {
        $this->format = $format;
        $this->section = $section;

        if ($editable === null) {
            $editable = $format->show_editor();
        }
        $this->editable = $editable;

        $options = $format->get_format_options($this->section);

        $this->hasbreak = $options['sectionbreak'];
        $this->breaktitle = $options['sectionbreaktitle'];

        // Setup inplace editable.
        parent::__construct(
            'format_cards',
            'sectionbreak',
            $section->id,
            $this->editable,
            $this->breaktitle,
            $this->breaktitle,
            new lang_string('section:break:edit', 'format_cards'),
            new lang_string('section:break', 'format_cards')
        );
    }

    /**
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'core/inplace_editable';
    }

    /**
     * Export template context.
     *
     * @param renderer_base $output
     * @return array|boolean
     */
    public function export_for_template(renderer_base $output) {
        if (!$this->hasbreak) {
            return false;
        }

        $data = parent::export_for_template($output);
        $data['id'] = $this->section->id;
        $data['deletebreakurl'] = (new moodle_url(
            '/course/format/cards/editsectionbreak.php',
            [
                'courseid' => $this->section->course,
                'sectionid' => $this->section->id,
                'action' => 'remove'
            ]
        ))->out(false);
        return $data;
    }

}
