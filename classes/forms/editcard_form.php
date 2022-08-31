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

namespace format_cards\forms;

use editsection_form;
use moodleform;

global $CFG;

require_once "$CFG->libdir/formslib.php";

/**
 * Moodle form for managing a section's image
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editcard_form extends editsection_form {

    public function definition() {

        $form = $this->_form;
        
        parent::definition();

        $form->addElement('header', 'cardimage', get_string('editimage', 'format_cards'));
        $form->setExpanded('cardimage', true);

        $form->addElement(
            'filemanager',
            'image',
            get_string('image', 'format_cards'),
            null,
            [
                'subdirs' => 0,
                'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']
            ]
        );

    }
}