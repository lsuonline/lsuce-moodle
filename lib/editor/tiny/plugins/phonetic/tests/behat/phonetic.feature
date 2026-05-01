@editor @editor_tiny @tiny_phonetic
Feature: Phonetic symbol picker
  As an author I need to insert IPA symbols into content

  @javascript
  Scenario: Open the phonetic picker and insert a symbol
    Given I log in as "admin"
    And I am on site homepage
    And I click on the "Phonetic symbol picker" button for the TinyMCE editor
    Then I should see "Pulmonic Consonants"
    When I click on the first ".tiny_phonetic_btn" "css_element"
    Then the field "tiny_phonetic_text" should not match "^$"
    And I click on "Save Phonetic" "button"

  @javascript
  Scenario: Combining tab shows Base Symbol input
    Given I log in as "admin"
    And I am on site homepage
    And I click on the "Phonetic symbol picker" button for the TinyMCE editor
    And I click on "Combining" "link"
    Then I should see "Base Symbol"

  @javascript
  Scenario: Combining symbol with base character is composed correctly
    Given I log in as "admin"
    And I am on site homepage
    And I click on the "Phonetic symbol picker" button for the TinyMCE editor
    And I click on "Combining" "link"
    And I set the field "tiny_phonetic_base_symbol" to "a"
    And I click on the first ".tiny_phonetic_btn" "css_element"
    And I click on "Save Phonetic" "button"
