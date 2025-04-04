@format @format_cards
Feature: Users view subsections on course page
  In order to use subsections
  As an user
  I need to view subsections on course page

  Background:
    Given I enable "subsection" "mod" plugin
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | category | numsections | initsections |
      | Course 1 | C1        | cards  | 0        | 3           | 1            |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity   | name                 | course | idnumber | section |
      | subsection | Subsection1          | C1     | sub1     | 1       |
      | page       | Page1 in Subsection1 | C1     | page11   | 4       |
      | subsection | Subsection2          | C1     | sub2     | 1       |
      | data       | New database         | C1     | data1    | 3       |
      | page       | New page             | C1     | page1    | 3       |

  @javascript @moodle_405_and_after
  Scenario: A user can view and navigate to a subsection displayed on a course page
    Given I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should see "Subsection1" in the "region-main" "region"
    When I click on "Subsection1" "link" in the "Subsection1" "activity"
    Then I should see "Page1 in Subsection1" in the "region-main" "region"
