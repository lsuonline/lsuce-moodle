@block_backadel
Feature: Backadel search page status filter
  As a site administrator
  I need to filter courses by backup status
  So that I can find only courses in a specific state

  Background:
    Given I log in as "admin"

  Scenario: Unfiltered search page shows instruction notice instead of table rows
    When I navigate to "Site administration > Plugins > Blocks > Course Search" in site administration
    Then I should see "Search Results"
    And I should see "Use the filters above to search for courses"
    And I should not see "Nothing to display"

  Scenario: Filtering by FAIL status with no FAIL records returns zero rows
    When I visit "/blocks/backadel/search.php?status=FAIL"
    Then I should see "Search Results"
    And I should not see "Use the filters above"
    And I should see "Nothing to display"

  Scenario: Filtering by BACKUP status with no BACKUP records returns zero rows
    When I visit "/blocks/backadel/search.php?status=BACKUP"
    Then I should see "Search Results"
    And I should see "Nothing to display"
    And I should not see "Use the filters above"

  Scenario: Filtering by FAIL status does not expose SUCCESS-only courses
    Given the following "courses" exist:
      | fullname              | shortname | category |
      | Only A Success Course | OASC      | 0        |
    When I visit "/blocks/backadel/search.php?status=FAIL"
    Then I should see "Search Results"
    And I should not see "Only A Success Course"
    And I should see "Nothing to display"
