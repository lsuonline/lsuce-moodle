@block @block_sgelection @javascript
Feature: Vote
  In order to verify the correctness of the vote count, I need to vote as many 
  users and check the results.

  Background: Create Election
    Given I log in as "admin"
      
      And I follow "My home"
      And I expand "Site administration" node
      And I expand "Plugins" node
      And I expand "Enrolments" node
      And I follow "Manage enrol plugins"
      And I click on "Enable" "link" in the "UES Enrollment" "table_row"

      And I configure ues
      And I initialize ues users
      And I run cron

      And I follow "My home"
      And I expand "Site administration" node
      And I expand "Plugins" node
      And I expand "Blocks" node
      And I follow "SGElection Block"
      And I set the following fields to these values:
         | Faculty Advisor | facadv001 |
         | Census cron window | 0 |
      And I press "Save changes"

      And I follow "Home"
      And I follow "Turn editing on"
      And I add the "sgelection" block
      And I configure the "block_sgelection" block
      And I set the following fields to these values:
         | Page contexts | Display throughout the entire site |
         | Default weight | -10 |
      And I press "Save changes"

      And I expand "Site administration" node
      And I expand "Appearance" node
      And I follow "Default My home page"
      And I configure the "block_sgelection" block
      And I set the following fields to these values:
         | Display on page types | My home page |
      And I press "Save changes"

      And I log out

      And I log in as "facadv001"
      And I follow "My home"
      And I should see "Create new election"
      And I should see "Configure"
      And I click on "Configure" "link" in the "block_sgelection" "block"
      And I set the following fields to these values:
         | Commissioner | commissioner001 |
         | Full Time | 12 |
         | Part Time | 1  |
         | Results Recipients | admin |
      And I press "Save changes"
      And I log out

      And I log in as "law002ft"
      And I follow "My home"
      And I should not see "STUDENT GOVT ELECTION"
      And I should not see "Create new election"
      And I should not see "ballots"
      And I should not see "Ballot"
      And I log out


  @javascript
  Scenario: Vote

      And I log in as "commissioner001"
      And I follow "My home"
      And I should see "Create new election"
      And I click on "Create new election" "link" in the "block_sgelection" "block"

      And the following elections exist:
         |Name | General Election |
         |id_thanksforvoting_editor   | Thanks! |
         |id_hours_census_start_minute| 0 |
         |id_start_date_day           | 1 |
         |id_end_date_day             | 2 |

      And I set the following fields to these values:
         | paws ID of Candidate | law003ft |
         | Office the Candidate is running for | College Council President [LAW] |
      And I press "save_candidate"

      And I set the following fields to these values:
         | paws ID of Candidate | law004ft |
         | Office the Candidate is running for | College Council President [LAW] |
      And I press "save_candidate"

      And I set the following fields to these values:
         | Title of Resolution | Resolution FT1 |
         | Resolution Text | This resolution is open only to Full-time students |
      And I click on "Restrict to Full Time?" "text"
      And I press "save_resolution"

      And I set the following fields to these values:
         | Title of Resolution | Resolution All1 |
         | Resolution Text | This resolution is open to all students |
      And I press "save_resolution"

      # open the election
      And I open the election
      And I log out

      And I log in as "admin"
      And I run cron
      And I log out

      And I log in as "law001pt"
      And I follow "My home"
      And I should see "Ballots cast so far"
      And I should see "Ballot for Fall [General Election]"
      And I follow "Ballot for Fall [General Election]"

      And I should see "law student003"
      And I should see "law student004"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element" should not exist

      And I click on "law student003" "text"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"

      And I press "Review Choices"
      Then I should see "You voted for law student003"

      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 1"
      And I log out


      
      And I log in as "law002ft"
      And I follow "My home"
      And I follow "Ballot for Fall [General Election]"
      And I should see "law student003"
      And I should see "law student004"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element"

      And I click on "law student003" "text"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element"
      
      And I press "Review Choices"
      Then I should see "You voted for law student003"

      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 2"
      And I log out







      And I log in as "law003ft"
      And I follow "My home"
      And I follow "Ballot for Fall [General Election]"

      And I should see "law student003"
      And I should see "law student004"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element"

      And I click on "law student003" "text" in the "//div[@class='candidate' and .//label[text()='law student003']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element"

      And I press "Review Choices"
      Then I should see "You voted for law student003"

      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 3"
      And I log out








      And I log in as "law004ft"
      And I follow "My home"
      And I follow "Ballot for Fall [General Election]"

      And I should see "law student003"
      And I should see "law student004"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element"

      And I click on "law student004" "text" in the "//div[@class='candidate' and .//label[text()='law student004']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution All1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='Resolution FT1']]" "xpath_element"

      And I press "Review Choices"
      Then I should see "You voted for law student004"

      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 4"
      And I log out




      And I log in as "admin"
      And I follow "My home"
      And I follow "Ballot for Fall [General Election]"
      And I expand "SG Elections Admin" node
      And I expand "Results" node
      And I click on "Fall [General Election]" "link" in the "//li[./p/span/text()='Results']/ul" "xpath_element"
      Then I should see "3" in the "law student003" "table_row"
      Then I should see "1" in the "law student004" "table_row"
      Then I should see "3" in the "Resolution FT1" "table_row"
      Then I should see "4" in the "Resolution All1" "table_row"
