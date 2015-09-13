@block @block_sgelection @javascript
Feature: Page security
  In order to ensure the integrity of the election, I need to prevent unauthorizxed access

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
    And I run cron
    And I log out


Scenario: Login as users with various attributes

    ###########################################################
    # ensure students have correct access to restricted pages #
    ###########################################################

    # for test user information, see .xml under <block_root>/tests/behat/enrolments/
    # test29 is full time student in ADSN, curric: CHE
    Given I log in as "test29"

    # admin page
    When  I go to "/blocks/sgelection/admin.php"
    Then I should not see "Election Tool Administration"
    And I should see "Course overview"

    # ballot page
    When  I go to "/blocks/sgelection/ballot.php?election_id=1"
    Then I should not see "Edit this Election"
    And I should see "2014 Fall at Main [General Election] Ballot"

    # candidates page
    When  I go to "/blocks/sgelection/candidates.php?election_id=1"
    Then I should not see "Create a New Candidate"
    And I should see "Course overview"

    # commissioner page
    When  I go to "/blocks/sgelection/commissioner.php"
    Then I should not see "Create New Election"
    And I should see "Course overview"

    # delete page
    # When  I go to "/blocks/sgelection/delete.php"
    # Then I should not see "Election Tool Administration"
    # And I should see "Course overview"

    # lookup voter page
    When  I go to "/blocks/sgelection/lookupvoter.php?election_id=1"
    Then I should not see "Check vote status"
    And I should see "Course overview"

    # officelist page
    When  I go to "/blocks/sgelection/officelist.php"
    Then I should not see "Create New Office"
    And I should see "Course overview"

    # offices page
    When  I go to "/blocks/sgelection/offices.php"
    Then I should not see "Create New Office"
    And I should see "Course overview"

    # resolutions page
    When  I go to "/blocks/sgelection/resolutions.php?election_id=1"
    Then I should not see "Create New Resolution"
    And I should see "Course overview"

    # results page
    When  I go to "/blocks/sgelection/results.php?election_id=1"
    Then I should not see "Election Report"
    And I should see "Course overview"

    # done
    And I log out

    #######################################################
    # ensure Admin has correct access to restricted pages #
    #######################################################
    And I log in as "admin"


    # admin page
    When  I go to "/blocks/sgelection/admin.php"
    Then I should see "Election Tool Administration"
    

    # ballot page
    When  I go to "/blocks/sgelection/ballot.php?election_id=1"
    Then I should see "Edit this Election"
    And I should see "2014 Fall at Main [General Election] Ballot"

    # candidates page
    When  I go to "/blocks/sgelection/candidates.php?election_id=1"
    Then I should see "Create a New Candidate"
    

    # commissioner page
    When  I go to "/blocks/sgelection/commissioner.php"
    Then I should see "Create New Election"
    

    # delete page
    # When  I go to "/blocks/sgelection/delete.php"
    # Then I should see"Election Tool Administration"
    # 

    # lookup voter page
    When  I go to "/blocks/sgelection/lookupvoter.php?election_id=1"
    Then I should see "Check vote status"
    

    # officelist page
    When  I go to "/blocks/sgelection/officelist.php"
    Then I should see "Create New Office"
    

    # offices page
    When  I go to "/blocks/sgelection/offices.php"
    Then I should see "Create New Office"
    

    # resolutions page
    When  I go to "/blocks/sgelection/resolutions.php?election_id=1"
    Then I should see "Create New Resolution"
    

    # results page
    When  I go to "/blocks/sgelection/results.php?election_id=1"
    Then I should see "Election Report"

    #################################################################
    # ensure faculty advisor has correct access to restricted pages #
    #################################################################
    And I log in as "facadv001"


    # admin page
    When  I go to "/blocks/sgelection/admin.php"
    Then I should see "Election Tool Administration"
    

    # ballot page
    When  I go to "/blocks/sgelection/ballot.php?election_id=1"
    Then I should see "Edit this Election"
    And I should see "2014 Fall at Main [General Election] Ballot"

    # candidates page
    When  I go to "/blocks/sgelection/candidates.php?election_id=1"
    Then I should see "Create a New Candidate"
    

    # commissioner page
    When  I go to "/blocks/sgelection/commissioner.php"
    Then I should see "Create New Election"
    

    # delete page
    # When  I go to "/blocks/sgelection/delete.php"
    # Then I should see"Election Tool Administration"
    # 

    # lookup voter page
    When  I go to "/blocks/sgelection/lookupvoter.php?election_id=1"
    Then I should see "Check vote status"
    

    # officelist page
    When  I go to "/blocks/sgelection/officelist.php"
    Then I should see "Create New Office"
    

    # offices page
    When  I go to "/blocks/sgelection/offices.php"
    Then I should see "Create New Office"
    

    # resolutions page
    When  I go to "/blocks/sgelection/resolutions.php?election_id=1"
    Then I should see "Create New Resolution"
    

    # results page
    When  I go to "/blocks/sgelection/results.php?election_id=1"
    Then I should see "Election Report"
    

    ##############################################################
    # ensure commissioner has correct access to restricted pages #
    ##############################################################
    And I log in as "commissioner001"

    # admin page
    When  I go to "/blocks/sgelection/admin.php"
    Then I should not see "Election Tool Administration"
    And I should see "Course overview"
    

    # ballot page
    When  I go to "/blocks/sgelection/ballot.php?election_id=1"
    Then I should see "Edit this Election"
    And I should see "2014 Fall at Main [General Election] Ballot"

    # candidates page
    When  I go to "/blocks/sgelection/candidates.php?election_id=1"
    Then I should see "Create a New Candidate"
    

    # commissioner page
    When  I go to "/blocks/sgelection/commissioner.php"
    Then I should see "Create New Election"
    

    # delete page
    # When  I go to "/blocks/sgelection/delete.php"
    # Then I should see"Election Tool Administration"
    # 

    # lookup voter page
    When  I go to "/blocks/sgelection/lookupvoter.php?election_id=1"
    Then I should see "Check vote status"
    

    # officelist page
    When  I go to "/blocks/sgelection/officelist.php"
    Then I should see "Create New Office"
    

    # offices page
    When  I go to "/blocks/sgelection/offices.php"
    Then I should see "Create New Office"
    

    # resolutions page
    When  I go to "/blocks/sgelection/resolutions.php?election_id=1"
    Then I should see "Create New Resolution"
    

    # results page
    When  I go to "/blocks/sgelection/results.php?election_id=1"
    Then I should see "Election Report"