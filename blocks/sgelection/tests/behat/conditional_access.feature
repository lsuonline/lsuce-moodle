@block @block_sgelection @javascript
Feature: Conditional Access
  In order to verify the correctness of the vote count, I need to vote as many 
  users and check the results.

  Background: Create Election
    Given I log in as "admin"

    # begin get enrollment
    # enable UES
    And I follow "My home"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Enrolments" node
    And I follow "Manage enrol plugins"
    And I click on "Enable" "link" in the "UES Enrollment" "table_row"
    # end enable UES

    And I configure ues
    And I initialize ues users
    And I run cron
    # end get enrollment

    # begin set block to all My pages
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
    # end set block to all My pages

    # begin configure block
    And I log in as "admin"
    And I follow "My home"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Blocks" node
    And I follow "SGElection Block"
    And I set the following fields to these values:
       | Faculty Advisor | facadv001 |
       | Census cron window | 0 |
    And I press "Save changes"
    And I log out
    # end configure block

    # begin configure block settings as fac advisor
    And I log in as "facadv001"
    And I follow "My home"
    And I click on "Configure" "link" in the "block_sgelection" "block"
    And I set the following fields to these values:
       | Commissioner | commissioner001 |
       | Full Time | 12 |
       | Part Time | 1  |
       | Results Recipients | admin |
       | Excluded Curriculum Code | NREM |
    And I press "Save changes"
    And I log out
    # end configure block settings as fac advisor

    # begin create new election
    And I log in as "commissioner001"
    And I follow "My home"
    And I should see "Create new election"
    And I click on "Create new election" "link" in the "block_sgelection" "block"
    # @see behat_block_sgelection::theFollowingElectionsExist
    And the following elections exist:
        |Name | General Election |
        |id_thanksforvoting_editor   | Thanks! |
        |id_hours_census_start_minute| 0 |
        |id_start_date_day           | 1 |
        |id_end_date_day             | 2 |
    # end create new election

    # begin setup candidates
    And I set the following fields to these values:
    | paws ID of Candidate | adsn1 |
    | Office the Candidate is running for | College Council President [ADSN] |
    And I press "save_candidate"

    And I set the following fields to these values:
    | paws ID of Candidate | adsn2 |
    | Office the Candidate is running for | College Council President [ADSN] |
    And I press "save_candidate"

    And I set the following fields to these values:
    | paws ID of Candidate | adsn3 |
    | Office the Candidate is running for | College Council President [ADSN] |
    And I press "save_candidate"
    # begin setup candidates

    # open the election
    And I open the election
    And I log out

    # run cron to get enrolled hours
    And I log in as "admin"
    And I wait "10" seconds
    And I run cron
    And I log out


Scenario: Login as users with various attributes
    # for test user information, see .xml under <block_root>/tests/behat/enrolments/
    # test29 is full time student in ADSN, curric: CHE
    Given I log in as "test29"
    When  I follow "My home"
    Then  I should see "Student Gov't Election"
    And I log out

    # for test user information, see .xml under <block_root>/tests/behat/enrolments/
    # test1 is part time student in ADSN, curric: NREM
    Given I log in as "test1"
    When  I follow "My home"
    Then  I should not see "Student Gov't Election"
    And I log out

    # for test user information, see .xml under <block_root>/tests/behat/enrolments/
    # test3 is part time student in BADM, curric: CMST
    # NB this is not implemented yet;
    # @todo handle the case where there's nothing the user can vote on
    #
    # Given I log in as "test3"
    # When  I follow "My home"
    # Then  I should not see "Student Gov't Election"
    # And I log out

    # for test user information, see .xml under <block_root>/tests/behat/enrolments/
    # nohours1 is enrolled for 0 credit hours (simulating NOT-enrolled) 
    # college: ADSN, curric: SPAN
    Given I log in as "nohours1"
    When  I follow "My home"
    Then  I should not see "Student Gov't Election"
    And I log out

    # for test user information, see .xml under <block_root>/tests/behat/enrolments/
    # phd1 is enrolled for 3 credit hours in a high-level course, #9000
    # college: ADSN, curric: CSC
    Given I log in as "phd1"
    When  I follow "My home"
    Then  I should see "Student Gov't Election"
