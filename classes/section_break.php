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

namespace format_cards;

use core\persistent;
use dml_exception;
use section_info;
use stdClass;

/**
 * Section break db persistence object.
 *
 * @package     format_cards
 * @copyright   2023 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_break extends persistent {

    /**
     * DB table name
     */
    const TABLE = 'format_cards_break';

    /**
     * Defines object properties
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'courseid' => [
                'type' => PARAM_INT
            ],
            'section' => [
                'type' => PARAM_INT
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => ''
            ]
        ];
    }

    /**
     * Get all the section breaks for a course
     *
     * @param int $courseid    The course ID
     * @param array $filters   Additional filters
     * @param string $sort     Field to sort by
     * @param string $order    Sort Ascending or Descending
     * @param int $skip        How many records to skip
     * @param int $limit       Total records to return, or 0 to return all
     * @return section_break[]
     */
    public static function get_breaks_for_course(int      $courseid,
                                                 array    $filters = [],
                                                 string   $sort = '',
                                                 string   $order = 'ASC',
                                                 int      $skip = 0,
                                                 int      $limit = 0): array {
        $filters['courseid'] = $courseid;
        return parent::get_records($filters, $sort, $order, $skip, $limit);
    }

    /**
     * Get the section break for a given section ID, if available
     *
     * @param int $sectionid The section ID
     * @return section_break|null
     * @throws dml_exception
     */
    public static function get_break_for_section_id(int $sectionid): ?section_break {
        global $DB;

        $records = $DB->get_records_sql('SELECT section_break.*
FROM {format_cards_break} section_break
JOIN {course_sections} section
    ON section_break.courseid = section.course
    AND section_break.section = section.section
WHERE section.id = ?',
        [ $sectionid ]);

        if (empty($records)) {
            return null;
        }

        return new section_break(0, $records[0]);
    }

    /**
     * Get the section break for a given section, if available
     *
     * @param section_info|stdClass $section
     * @return void
     */
    public static function get_break_for_section($section): ?section_break {
        $record = self::get_record(
            [ 'courseid' => $section->course, 'section' => $section->section ],
            IGNORE_MISSING
        );

        if ($record !== false) {
            return $record;
        }

        return null;
    }

}
