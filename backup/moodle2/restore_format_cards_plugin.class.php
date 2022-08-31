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
 * Specialised restore logic for format_cards. Handles restoring images used for each card
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_cards_plugin extends restore_format_plugin {

    /**
     * The structure format_cards adds to the backup file is completely irrelevant, as the format doesn't add its
     * own tables and just re-uses data from the course_sections, course_section_options, and files tables
     *
     * @return restore_path_element[]
     */
    public function define_section_plugin_structure() {

        $this->add_related_files('format_cards', FORMAT_CARDS_FILEAREA_IMAGE, null);
        return [ new restore_path_element('cards', $this->get_pathfor('/cards')) ];
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function process_cards($data) {
        // no-op
    }

    /**
     * When a section gets restored the card image file records are restored using the old itemid, which
     * refers to the id of the section from the course the backup was created from
     * We need to do some extra steps to make sure restored images get put back in the right place
     *
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public function after_restore_section() {
        global $DB;
        $data = $this->connectionpoint->get_data();

        if(!isset($data['path'])
            || $data['path'] != "/section"
            || !isset($data['tags']['id']))
            return;

        $oldSectionId = $data['tags']['id'];
        $oldSectionNum = $data['tags']['number'];

        $newCourseId = $this->step->get_task()->get_courseid();
        $newSectionId = $DB->get_field('course_sections', 'id', [
            'course' => $newCourseId,
            'section' => $oldSectionNum
        ]);

        if(!$newSectionId)
            return;

        self::move_section_image($newCourseId, $oldSectionId, $newSectionId);
    }

    /**
     * Given a course ID and the ID of the restored section, move any restored card images to the
     * correct section
     *
     * @throws stored_file_creation_exception
     * @throws file_exception
     * @throws coding_exception
     */
    private static function move_section_image(int $newCourseId, int $oldSectionId, int $newSectionId) {
        $fileStorage = get_file_storage();
        $context = context_course::instance($newCourseId);

        // Did we copy an image for the new section?
        $restoredImage = $fileStorage->get_area_files(
            $context->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            $oldSectionId,
            'itemid, filepath, filename',
            false,
            0,
            0,
            1
        );

        // Nothing to do if no images were restored
        if(empty($restoredImage))
            return;

        $restoredImage = reset($restoredImage);

        // Are there any existing images for this new section?
        $existingImage = $fileStorage->get_area_files(
            $context->id,
            'format_cards',
            FORMAT_CARDS_FILEAREA_IMAGE,
            $newSectionId,
            'itemid, filepath, filename',
            false,
            0,
            0,
            1
        );

        // If there's an existing image, we need to remove it first
        if(!empty($existingImage)) {
            $existingImage = reset($existingImage);

            // If this is the same ID we're restoring into the same course,
            // do don't delete anything
            if($restoredImage->get_id() == $existingImage->get_id())
                return;

            // If the IDs are different but the content is the same, delete all the restored
            //
            if($restoredImage->get_contenthash() == $existingImage->get_contenthash()) {
                $fileStorage->delete_area_files($context->id,
                    'format_cards',
                    FORMAT_CARDS_FILEAREA_IMAGE,
                    $oldSectionId
                );
                return;
            }

            $existingImage->delete();
        }

        $movedImage = $fileStorage->create_file_from_storedfile(
            [
                'itemid' => $newSectionId
            ],
            $restoredImage
        );

        // If the section IDs are the same, just delete the extra image we restored
        if($oldSectionId == $newSectionId) {
            $restoredImage->delete();
        }
        // Otherwise, delete all file records for the old section we restored to
        // keep things tidy
        else {
            $fileStorage->delete_area_files($context->id,
                'format_cards',
                FORMAT_CARDS_FILEAREA_IMAGE,
                $oldSectionId
            );
        }
    }
}