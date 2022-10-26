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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . "/../../lib.php");

/**
 * Backs up courses that use the cards format. Ensures that card images are included in the backup
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_cards_plugin extends backup_format_plugin {

    /**
     * Defines the backup structure for format_cards
     *
     * @return backup_plugin_element
     * @throws base_element_struct_exception
     */
    protected function define_section_plugin_structure() {

        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'cards');

        // Create a nested element under each backed up section, this is just a dummy container.
        $wrapper = new backup_nested_element('cards', [ 'id' ], [ 'section' ]);
        $wrapper->set_source_table('course_sections', [ 'id' => backup::VAR_SECTIONID ]);

        // Annotate files in the format_cards/image filearea for this course's context ID
        // The itemid doesn't get mapped to the new section id, if it changes.
        $wrapper->annotate_files(
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            null
        );

        $plugin->add_child($wrapper);
        return $plugin;
    }

}
