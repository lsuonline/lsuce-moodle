@tiny_accordion
Feature: Accordion style presets
  As an admin I can configure named style presets for accordions
  As an author I can pick a style preset when editing an accordion

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Admin can add and save a style preset
    Given I log in as "admin"
    And I navigate to "Plugins > Text editors > Accordion" in site administration
    Then I should see "Accordion style presets"
    When I click on "Add preset" "button"
    And I set the field with xpath "//tbody[@data-presets-tbody]//tr[not(@data-template-row)]//input[@data-field='label']" to "Blue header"
    And I set the field with xpath "//tbody[@data-presets-tbody]//tr[not(@data-template-row)]//input[@data-field='detailsclass']" to "accordion-blue"
    And I set the field with xpath "//tbody[@data-presets-tbody]//tr[not(@data-template-row)]//input[@data-field='summaryclass']" to "accordion-header-blue"
    And I click on "Save changes" "button"
    Then I should see "Changes saved"
    And I should see "Accordion style presets"

  @javascript
  Scenario: Author sees preset dropdown in accordion attributes modal
    Given the following config values are set as admin:
      | stylepresets | [{"label":"Blue header","detailsclass":"accordion-blue","detailsstyle":"","summaryclass":"accordion-header-blue","summarystyle":""}] | tiny_accordion |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Text and media area" to section "1"
    And I click on the "Insert accordion" button in the editor
    And I select the accordion in the editor
    And I click on the "Accordion attributes" button in the editor
    Then I should see "Style preset"
    And the "Style preset" select box should contain "Blue header"
    And the "Style preset" select box should contain "— None —"
    And the "Style preset" select box should contain "Custom (enter classes manually)"

  @javascript
  Scenario: Selecting a preset applies classes to the accordion
    Given the following config values are set as admin:
      | stylepresets | [{"label":"Blue header","detailsclass":"accordion-blue","detailsstyle":"","summaryclass":"accordion-header-blue","summarystyle":""}] | tiny_accordion |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Text and media area" to section "1"
    And I click on the "Insert accordion" button in the editor
    And I select the accordion in the editor
    And I click on the "Accordion attributes" button in the editor
    When I set the field "Style preset" to "Blue header"
    And I click on "Apply" "button"
    Then the accordion details element should have class "accordion-blue"
    And the accordion summary element should have class "accordion-header-blue"
