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
 * Manage click events for card availability badges
 *
 * @copyright   2022 University of Essex
 * @author      John Maydew <jdmayd@essex.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialises availability badges.
 *
 * The availability badges use bootstrap's popovers to display availability information
 * for sections. However, these are contained within an <a /> tag that the user clicks to actually
 * navigate to a section.
 *
 * To allow a user to click the badge to view the availability information, without navigating them to the section,
 * we need to disable the click event for the <a /> tag if the user has actually clicked on the badge
 *
 * @param {string} target The containing DOM element ID
 */
export const init = (target) => {

    const main = document.getElementById(target);
    const availabilityBadges = Array.from(main.getElementsByClassName('section-availability'));

    availabilityBadges.forEach(function(element) {
        let parent = element.closest('a');

        if (parent === null) {
            return;
        }

        parent.addEventListener('click', function(event) {

            // We might have to search up the DOM tree just in case the event target is an element within
            // the badge, for example the icon or any text.
            if (event.target.classList.contains('section-availability')
                || event.target.closest('.section-availability') !== null) {
                event.preventDefault();
                return false;
            }

            return true;
        });

        element.classList.remove('hidden');
    });
};
