@format @format_cards
Feature: Subsection support
  In order to use courses using the cards format
  As a user
  I can view and navigate through subsections

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
      | activity   | name                 | course | idnumber | section | visible |
      | subsection | Subsection1          | C1     | sub1     | 1       | 1       |
      | page       | Page1 in Subsection1 | C1     | page11   | 4       | 1       |
      | subsection | Subsection2          | C1     | sub2     | 1       | 0       |
      | page       | Page in Subsection2  | C1     | page22   | 5       | 1       |
      | subsection | Subsection3          | C1     | sub3     | 1       | 1       |
      | page       | Page in Subsection3  | C1     | page33   | 6       | 1       |
      | data       | New database         | C1     | data1    | 3       | 1       |
      | page       | New page             | C1     | page1    | 3       | 1       |

  @javascript @moodle_405_and_after
  Scenario: A user can view and navigate to a subsection displayed on a course page
    Given I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should not see "Subsection1" in the "region-main" "region"
    When I am on the "Course 1 > Section 1" "format_cards > section" page
    Then I should see "Subsection1" in the "region-main" "region"
    When I click on "Subsection1" "link" in the "Subsection1" "activity"
    Then I should see "Page1 in Subsection1" in the "region-main" "region"

  @javascript @moodle_405_and_after
  Scenario: The section navigation region for a subsection contains a button to go to the parent section
    Given the following config values are set as admin:
      | sectionnavigation | 4 | format_cards |
    And I log in as "student1"
    And I am on the "Course 1 > Section 1" "format_cards > section" page
    Then "[data-action=parentsection]" "css_element" should not exist in the "sectionnavigation-top" "region"
    And "[data-action=parentsection]" "css_element" should not exist in the "sectionnavigation-bottom" "region"
    When I click on "Subsection1" "link" in the "Subsection1" "activity"
    Then "Go to section Section 1" "icon" should exist in the "sectionnavigation-top" "region"
    And "Go to section Section 1" "icon" should exist in the "sectionnavigation-bottom" "region"
    When I click on "Go to section Section 1" "icon" in the "sectionnavigation-top" "region"
    And I click on "Subsection1" "link" in the "Subsection1" "activity"
    And I click on "Go to section Section 1" "icon" in the "sectionnavigation-bottom" "region"
    Then "Subsection1" "activity" should exist

  @javascript @moodle_405_and_after
  Scenario Outline: I can navigate between subsections
    Given the following config values are set as admin:
      | sectionnavigation | 4 | format_cards |
    And I log in as "<user>"
    When I am on the "Course 1 > Section 1" "format_cards > section" page
    Then "Subsection2" "activity" <should or not> be visible

    When I click on "Subsection1" "link" in the "Subsection1" "activity"
    Then ".prevsection [data-action=previoussection]" "css_element" should not exist
    And I click on "Go to section <subsection after subsection1>" "icon" in the "sectionnavigation-top" "region"
    Then "Page in <subsection after subsection1>" "activity" should exist in the "region-main" "region"

    When I am on the "Course 1 > Section 1" "format_cards > section" page
    And I click on "Subsection1" "link" in the "Subsection1" "activity"
    And I click on "Go to section <subsection after subsection1>" "icon" in the "sectionnavigation-bottom" "region"
    Then "Page in <subsection after subsection1>" "activity" should exist in the "region-main" "region"

    When I am on the "Course 1 > Section 1" "format_cards > section" page
    And I click on "Subsection3" "link" in the "Subsection3" "activity"
    Then ".nextsection [data-action=nextsection]" "css_element" should not exist
    And I click on "Go to section <subsection before subsection3>" "icon" in the "sectionnavigation-top" "region"
    Then "Page in <subsection before subsection3>" "activity" should exist in the "region-main" "region"

    When I am on the "Course 1 > Section 1" "format_cards > section" page
    And I click on "Subsection3" "link" in the "Subsection3" "activity"
    And I click on "Go to section <subsection before subsection3>" "icon" in the "sectionnavigation-bottom" "region"
    Then "Page in <subsection before subsection3>" "activity" should exist in the "region-main" "region"

    Examples:
      | user     | should or not | subsection after subsection1 | subsection before subsection3 |
      | teacher1 | should        | Subsection2                  | Subsection2                   |
      | student1 | should not    | Subsection3                  | Subsection1                   |
