@block_backadel
Feature: Backadel migrate admin page
  As a site administrator
  I need to see the migrate page with accurate row-count labels
  So that I can verify the catalogue entry count before running a migration scan

  Background:
    Given I log in as "admin"

  Scenario: Migrate page shows correct catalogue count label
    When I navigate to "Site administration > Plugins > Blocks > Run Migration" in site administration
    Then I should see "Backup files catalogued:"

  Scenario: Migrate page shows correct course records count label
    When I navigate to "Site administration > Plugins > Blocks > Run Migration" in site administration
    Then I should see "Course records indexed:"

  Scenario: Migrate page shows the run migration button
    When I navigate to "Site administration > Plugins > Blocks > Run Migration" in site administration
    Then I should see "Run migration scan now"
