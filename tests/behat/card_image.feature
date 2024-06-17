@format @format_cards @format_cards-card_image
Feature: Cards can have images
  To manage my course
  As a teacher
  I can upload images to be displayed on each card

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format  | numsections |
      | Course 1 | C1        | cards   | 1           |
    And I am logged in as "admin"

  @_file_upload @javascript
  Scenario: I can upload an image to a section
    When I am on "Course 1" course homepage with editing mode on
    And I edit the section "1"
    And I upload "course/format/cards/tests/fixtures/test_image.png" file to "Image" filemanager
    And I press "Save changes"
    And I am on "C1" course homepage with editing mode off
    Then "//div[contains(@style, 'test_image.png')]" "xpath_element" should exist in the "1" "format_cards > card"
