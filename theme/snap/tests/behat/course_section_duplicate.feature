# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
#
# Tests deleting sections in snap.
#
# @package    theme_snap
# @copyright  2024 Open LMS.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course @theme_snap_course_section
Feature: When the moodle theme is set to Snap, teachers can duplicate sections inside the same course.

    Background:
        Given the following "course" exists:
            | fullname         | Course 1 |
            | shortname        | C1       |
            | category         | 0        |
            | enablecompletion | 1        |
            | numsections      | 4        |
        And the following "activities" exist:
            | activity | name              | intro                       | course | idnumber | section |
            | assign   | Activity sample 1 | Test assignment description | C1     | sample1  | 1       |
            | book     | Activity sample 2 | Test book description       | C1     | sample2  | 1       |
            | choice   | Activity sample 3 | Test choice description     | C1     | sample3  | 2       |
        And I log in as "admin"

    @javascript
    Scenario: Duplicate a section
        Given I am on the course main page for "C1"
        And I follow "Topic 1"
        And I click on "#extra-actions-dropdown-1" "css_element"
        And I click on "#section-1 .snap-duplicate" "css_element"
        Then I should see "Topic 5"
        And I follow "Topic 2"
        Then I should see "Activity sample 2"

    @javascript
    Scenario: Duplicate a named section
        Given I am on the course main page for "C1"
        And I follow "Topic 1"
        And I click on "[title='Edit section']" "css_element"
        And I set the field "Section name" to "New name"
        And I press "Save changes"
        And I follow "New name"
        And I click on "#extra-actions-dropdown-1" "css_element"
        And I click on "#section-1 .snap-duplicate" "css_element"
        And I follow "New name (copy)"
        Then I should see "Activity sample 2"