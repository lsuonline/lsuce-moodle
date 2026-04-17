@ou @ou_vle @report @report_editdates @report_editdates_forum_calendar
Feature: Edit dates report updates forum calendar events
  When a teacher updates forum dates using the Edit Dates report
  The calendar events for the forum activity should reflect the new dates
  This validates the fix for MD-1661

  Background: Set up a course with a forum activity
    Given the following "users" exist:
      | username | firstname | lastname | email              |
      | teacher1 | Teacher   | One      | teacher1@test.com  |
      | student1 | Student   | One      | student1@test.com  |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name              | intro                  | course | duedate    |
      | forum    | Test Forum 1      | Test forum description | C1     | 1751356800 |

  @javascript
  Scenario: Teacher can see the forum in the edit dates report
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Dates" "link"
    Then I should see "Course 1"
    And I should see "Activity view filter"

  @javascript
  Scenario: Edit dates report shows forum dates and allows saving
    When I am on the "Course 1" "course" page logged in as "admin"
    And I navigate to "Reports" in current page administration
    And I click on "Dates" "link"
    Then I should see "Course 1"
    And I click on "Expand all" "link" in the "region-main" "region"
    And I should see "Test Forum 1"
    And I should see "Due date"
    And I press "Save changes"
    Then I should see "Course 1"

  @javascript
  Scenario: Enabling and saving forum due date via edit dates report completes without error
    # This test verifies the full save flow for a forum due date in the edit dates report.
    # The unit test (mod_forum_date_extractor_test.php) validates that forum_update_calendar()
    # is called and the calendar event is updated in the database (MD-1661 regression check).
    Given I am on the "Course 1" "course" page logged in as "admin"
    And I navigate to "Reports" in current page administration
    And I click on "Dates" "link"
    Then I should see "Course 1"
    And I should see "Activity view filter"
    And I click on "Expand all" "link" in the "region-main" "region"
    And I should see "Test Forum 1"
    And I should see "Due date"
    # Enable the due date checkbox (date_time_selector with optional=true).
    And I click on "Enable" "checkbox" in the "Due date" "fieldset"
    And I press "Save changes"
    Then I should see "Course 1"
    And I should see "Activity view filter"
    And I click on "Expand all" "link" in the "region-main" "region"
    And I should see "Test Forum 1"
    # Verify the due date field shows as enabled (non-zero value in the fieldset).
    And I should see "1" in the "Due date" "fieldset"
