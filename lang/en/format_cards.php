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
 * Language strings
 *
 * @package     format_cards
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = "Cards format";
$string['pluginname_help'] = "Course format which displays sections as bootstrap cards";

$string['addsections'] = 'Add card';
$string['currentsection'] = 'This card';
$string['editsection'] = 'Edit card';
$string['editsectionname'] = 'Edit card name';
$string['deletesection'] = 'Delete card';
$string['newsectionname'] = 'New name for card {$a}';
$string['sectionname'] = 'Card';
$string['section0name'] = 'General';
$string['page-course-view-topics'] = 'Any course main page in topics format';
$string['page-course-view-topics-x'] = 'Any course page in topics format';
$string['hidefromothers'] = 'Hide card';
$string['showfromothers'] = 'Show card';
$string['noactivities'] = 'No activities';

$string['section:default'] = 'Card {$a}';
$string['section:completion:percentage'] = '{$a->percentage}%';
$string['section:completion:count'] = '{$a->completed}/{$a->total}';

$string['settings:name'] = 'Cards format settings';
$string['settings:defaults'] = 'Defaults';
$string['settings:defaults:description'] = 'Set the default value for new courses using the cards format';

$string['form:course:usedefault'] = 'Default ({$a})';
$string['form:course:section0'] = 'General section';
$string['form:course:section0_help'] = 'The general section is the first section in your course, which usually contains the course\'s announcements page. You can choose to have this visible either only on the course\'s main page, on top of the card deck, or visible on the main page and each individual section page.';
$string['form:course:section0:coursepage'] = 'Only show on the main course page';
$string['form:course:section0:allpages'] = 'Show on all pages, including individual sections';
$string['form:course:cardorientation'] = 'Card orientation';
$string['form:course:cardorientation:vertical'] = 'Vertical';
$string['form:course:cardorientation:horizontal'] = 'Horizontal';
$string['form:course:importgridimages'] = 'Import images from grid format';
$string['form:course:importgridimages_help'] = 'Copy across the images used for grid tiles and use them as images for each card';
$string['form:course:showsummary'] = 'Section summary';
$string['form:course:showsummary:show'] = 'Shown';
$string['form:course:showsummary:hide'] = 'Hidden';
$string['form:course:showsummary_help'] = 'Whether to show the section summary on cards';
$string['form:course:showprogress'] = 'Section progress';
$string['form:course:showprogress:description'] = 'Whether to display progress within each section on the card';
$string['form:course:showprogress:show'] = 'Shown';
$string['form:course:showprogress:hide'] = 'Hidden';
$string['form:course:progressformat'] = 'Display progress as';
$string['form:course:progressformat:count'] = 'A count of items';
$string['form:course:progressformat:percentage'] = 'A percentage';

$string['image'] = 'Image';
$string['editcard'] = 'Edit card';
$string['editimage:resizefailed'] = 'Failed to resize the selected image. The card will use the image at it\'s original size. You can try re-uploading the image later.';
$string['editimage:imported'] = 'Successfully imported {$a} image(s) from the grid format';
$string['privacy:metadata'] = 'The Card format plugin does not store any personal data.';
