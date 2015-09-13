@block @block_sgelection @javascript
Feature: AutoVote
  In order to verify the correctness of the vote count, I need to vote as many 
  users and check the results.
#TODO this should really be merged with the conditional_ballot_items feature

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
# end set faculty advisor

      And I log out
# begin configure block settings as fac advisor
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
# end configure block settings as fac advisor


  @javascript
  Scenario: Vote

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
      | Title of Resolution | res1 |
      | Resolution Text | This resolution is open only to Full-time students |
      And I click on "Restrict to Full Time?" "text"
      And I press "save_resolution"

      And I set the following fields to these values:
      | Title of Resolution | res2 |
      | Resolution Text | This resolution is open to all students |
      And I press "save_resolution"

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

      And I set the following fields to these values:
      | paws ID of Candidate | agri1 |
      | Office the Candidate is running for | College Council President [AGRI] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | agri2 |
      | Office the Candidate is running for | College Council President [AGRI] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | agri3 |
      | Office the Candidate is running for | College Council President [AGRI] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | badm1 |
      | Office the Candidate is running for | College Council President [BADM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | badm2 |
      | Office the Candidate is running for | College Council President [BADM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | badm3 |
      | Office the Candidate is running for | College Council President [BADM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | engr1 |
      | Office the Candidate is running for | College Council President [ENGR] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | engr2 |
      | Office the Candidate is running for | College Council President [ENGR] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | engr3 |
      | Office the Candidate is running for | College Council President [ENGR] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | grad1 |
      | Office the Candidate is running for | College Council President [GRAD] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | grad2 |
      | Office the Candidate is running for | College Council President [GRAD] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | grad3 |
      | Office the Candidate is running for | College Council President [GRAD] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | hse1 |
      | Office the Candidate is running for | College Council President [HSE] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | hse2 |
      | Office the Candidate is running for | College Council President [HSE] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | hse3 |
      | Office the Candidate is running for | College Council President [HSE] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | hss1 |
      | Office the Candidate is running for | College Council President [HSS] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | hss2 |
      | Office the Candidate is running for | College Council President [HSS] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | hss3 |
      | Office the Candidate is running for | College Council President [HSS] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | mcom1 |
      | Office the Candidate is running for | College Council President [MCOM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | mcom2 |
      | Office the Candidate is running for | College Council President [MCOM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | mcom3 |
      | Office the Candidate is running for | College Council President [MCOM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | mda1 |
      | Office the Candidate is running for | College Council President [MDA] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | mda2 |
      | Office the Candidate is running for | College Council President [MDA] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | mda3 |
      | Office the Candidate is running for | College Council President [MDA] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | sce1 |
      | Office the Candidate is running for | College Council President [SCE] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | sce2 |
      | Office the Candidate is running for | College Council President [SCE] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | sce3 |
      | Office the Candidate is running for | College Council President [SCE] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | sci1 |
      | Office the Candidate is running for | College Council President [SCI] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | sci2 |
      | Office the Candidate is running for | College Council President [SCI] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | sci3 |
      | Office the Candidate is running for | College Council President [SCI] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | ucac1 |
      | Office the Candidate is running for | College Council President [UCAC] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | ucac2 |
      | Office the Candidate is running for | College Council President [UCAC] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | ucac3 |
      | Office the Candidate is running for | College Council President [UCAC] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | ucfy1 |
      | Office the Candidate is running for | College Council President [UCFY] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | ucfy2 |
      | Office the Candidate is running for | College Council President [UCFY] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | ucfy3 |
      | Office the Candidate is running for | College Council President [UCFY] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | vetm1 |
      | Office the Candidate is running for | College Council President [VETM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | vetm2 |
      | Office the Candidate is running for | College Council President [VETM] |
      And I press "save_candidate"

      And I set the following fields to these values:
      | paws ID of Candidate | vetm3 |
      | Office the Candidate is running for | College Council President [VETM] |
      And I press "save_candidate"
# end setup candidates

      # open the election
      And I open the election
      And I log out

      # cron to pickup enrolled hours info for election
      And I log in as "admin"
      And I run cron 
      And I log out

# begin voting
      And I log in as "test1"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "adsn candidate1"
      And I should see "adsn candidate2"
      And I should see "adsn candidate3"
      And I click on "adsn candidate2" "text" in the "//div[@class='candidate' and .//label[text()='adsn candidate2']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for adsn candidate2"

      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 1"
      And I log out


      And I log in as "test2"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "agri candidate1"
      And I should see "agri candidate2"
      And I should see "agri candidate3"
      And I click on "agri candidate1" "text" in the "//div[@class='candidate' and .//label[text()='agri candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for agri candidate1"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 2"
      And I log out


      And I log in as "test3"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "badm candidate1"
      And I should see "badm candidate2"
      And I should see "badm candidate3"
      And I click on "badm candidate1" "text" in the "//div[@class='candidate' and .//label[text()='badm candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"



      And I press "Review Choices"
      Then I should see "You voted for badm candidate1"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 3"
      And I log out


      And I log in as "test4"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "engr candidate1"
      And I should see "engr candidate2"
      And I should see "engr candidate3"
      And I click on "engr candidate3" "text" in the "//div[@class='candidate' and .//label[text()='engr candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"



      And I press "Review Choices"
      Then I should see "You voted for engr candidate3"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 4"
      And I log out


      And I log in as "test5"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "grad candidate1"
      And I should see "grad candidate2"
      And I should see "grad candidate3"
      And I click on "grad candidate3" "text" in the "//div[@class='candidate' and .//label[text()='grad candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for grad candidate3"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 5"
      And I log out


      And I log in as "test6"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hse candidate1"
      And I should see "hse candidate2"
      And I should see "hse candidate3"
      And I click on "hse candidate3" "text" in the "//div[@class='candidate' and .//label[text()='hse candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for hse candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 6"
      And I log out


      And I log in as "test7"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hss candidate1"
      And I should see "hss candidate2"
      And I should see "hss candidate3"
      And I click on "hss candidate3" "text" in the "//div[@class='candidate' and .//label[text()='hss candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for hss candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 7"
      And I log out


      And I log in as "test8"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mcom candidate1"
      And I should see "mcom candidate2"
      And I should see "mcom candidate3"
      And I click on "mcom candidate3" "text" in the "//div[@class='candidate' and .//label[text()='mcom candidate3']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for mcom candidate3"

      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 8"
      And I log out


      And I log in as "test9"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mda candidate1"
      And I should see "mda candidate2"
      And I should see "mda candidate3"
      And I click on "mda candidate1" "text" in the "//div[@class='candidate' and .//label[text()='mda candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for mda candidate1"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 9"
      And I log out


      And I log in as "test10"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sce candidate1"
      And I should see "sce candidate2"
      And I should see "sce candidate3"
      And I click on "sce candidate2" "text" in the "//div[@class='candidate' and .//label[text()='sce candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sce candidate2"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 10"
      And I log out


      And I log in as "test11"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sci candidate1"
      And I should see "sci candidate2"
      And I should see "sci candidate3"
      And I click on "sci candidate2" "text" in the "//div[@class='candidate' and .//label[text()='sci candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sci candidate2"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 11"
      And I log out


      And I log in as "test12"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucac candidate1"
      And I should see "ucac candidate2"
      And I should see "ucac candidate3"
      And I click on "ucac candidate3" "text" in the "//div[@class='candidate' and .//label[text()='ucac candidate3']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucac candidate3"

      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 12"
      And I log out


      And I log in as "test13"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucfy candidate1"
      And I should see "ucfy candidate2"
      And I should see "ucfy candidate3"
      And I click on "ucfy candidate2" "text" in the "//div[@class='candidate' and .//label[text()='ucfy candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucfy candidate2"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 13"
      And I log out


      And I log in as "test14"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "vetm candidate1"
      And I should see "vetm candidate2"
      And I should see "vetm candidate3"
      And I click on "vetm candidate3" "text" in the "//div[@class='candidate' and .//label[text()='vetm candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for vetm candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 14"
      And I log out


      And I log in as "test15"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "adsn candidate1"
      And I should see "adsn candidate2"
      And I should see "adsn candidate3"
      And I click on "adsn candidate3" "text" in the "//div[@class='candidate' and .//label[text()='adsn candidate3']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for adsn candidate3"

      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 15"
      And I log out


      And I log in as "test16"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "agri candidate1"
      And I should see "agri candidate2"
      And I should see "agri candidate3"
      And I click on "agri candidate2" "text" in the "//div[@class='candidate' and .//label[text()='agri candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for agri candidate2"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 16"
      And I log out


      And I log in as "test17"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "badm candidate1"
      And I should see "badm candidate2"
      And I should see "badm candidate3"
      And I click on "badm candidate3" "text" in the "//div[@class='candidate' and .//label[text()='badm candidate3']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for badm candidate3"

      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 17"
      And I log out


      And I log in as "test18"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "engr candidate1"
      And I should see "engr candidate2"
      And I should see "engr candidate3"
      And I click on "engr candidate1" "text" in the "//div[@class='candidate' and .//label[text()='engr candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for engr candidate1"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 18"
      And I log out


      And I log in as "test19"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "grad candidate1"
      And I should see "grad candidate2"
      And I should see "grad candidate3"
      And I click on "grad candidate2" "text" in the "//div[@class='candidate' and .//label[text()='grad candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for grad candidate2"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 19"
      And I log out


      And I log in as "test20"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hse candidate1"
      And I should see "hse candidate2"
      And I should see "hse candidate3"
      And I click on "hse candidate1" "text" in the "//div[@class='candidate' and .//label[text()='hse candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for hse candidate1"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 20"
      And I log out


      And I log in as "test21"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hss candidate1"
      And I should see "hss candidate2"
      And I should see "hss candidate3"
      And I click on "hss candidate3" "text" in the "//div[@class='candidate' and .//label[text()='hss candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for hss candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 21"
      And I log out


      And I log in as "test22"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mcom candidate1"
      And I should see "mcom candidate2"
      And I should see "mcom candidate3"
      And I click on "mcom candidate1" "text" in the "//div[@class='candidate' and .//label[text()='mcom candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"



      And I press "Review Choices"
      Then I should see "You voted for mcom candidate1"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 22"
      And I log out


      And I log in as "test23"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mda candidate1"
      And I should see "mda candidate2"
      And I should see "mda candidate3"
      And I click on "mda candidate2" "text" in the "//div[@class='candidate' and .//label[text()='mda candidate2']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for mda candidate2"

      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 23"
      And I log out


      And I log in as "test24"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sce candidate1"
      And I should see "sce candidate2"
      And I should see "sce candidate3"
      And I click on "sce candidate1" "text" in the "//div[@class='candidate' and .//label[text()='sce candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sce candidate1"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 24"
      And I log out


      And I log in as "test25"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sci candidate1"
      And I should see "sci candidate2"
      And I should see "sci candidate3"
      And I click on "sci candidate3" "text" in the "//div[@class='candidate' and .//label[text()='sci candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sci candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 25"
      And I log out


      And I log in as "test26"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucac candidate1"
      And I should see "ucac candidate2"
      And I should see "ucac candidate3"
      And I click on "ucac candidate3" "text" in the "//div[@class='candidate' and .//label[text()='ucac candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucac candidate3"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 26"
      And I log out


      And I log in as "test27"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucfy candidate1"
      And I should see "ucfy candidate2"
      And I should see "ucfy candidate3"
      And I click on "ucfy candidate1" "text" in the "//div[@class='candidate' and .//label[text()='ucfy candidate1']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucfy candidate1"

      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 27"
      And I log out


      And I log in as "test28"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "vetm candidate1"
      And I should see "vetm candidate2"
      And I should see "vetm candidate3"
      And I click on "vetm candidate3" "text" in the "//div[@class='candidate' and .//label[text()='vetm candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for vetm candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 28"
      And I log out


      And I log in as "test29"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "adsn candidate1"
      And I should see "adsn candidate2"
      And I should see "adsn candidate3"
      And I click on "adsn candidate3" "text" in the "//div[@class='candidate' and .//label[text()='adsn candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for adsn candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 29"
      And I log out


      And I log in as "test30"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "agri candidate1"
      And I should see "agri candidate2"
      And I should see "agri candidate3"
      And I click on "agri candidate2" "text" in the "//div[@class='candidate' and .//label[text()='agri candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for agri candidate2"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 30"
      And I log out


      And I log in as "test31"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "badm candidate1"
      And I should see "badm candidate2"
      And I should see "badm candidate3"
      And I click on "badm candidate2" "text" in the "//div[@class='candidate' and .//label[text()='badm candidate2']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for badm candidate2"

      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 31"
      And I log out


      And I log in as "test32"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "engr candidate1"
      And I should see "engr candidate2"
      And I should see "engr candidate3"
      And I click on "engr candidate2" "text" in the "//div[@class='candidate' and .//label[text()='engr candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for engr candidate2"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 32"
      And I log out


      And I log in as "test33"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "grad candidate1"
      And I should see "grad candidate2"
      And I should see "grad candidate3"
      And I click on "grad candidate2" "text" in the "//div[@class='candidate' and .//label[text()='grad candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for grad candidate2"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 33"
      And I log out


      And I log in as "test34"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hse candidate1"
      And I should see "hse candidate2"
      And I should see "hse candidate3"
      And I click on "hse candidate3" "text" in the "//div[@class='candidate' and .//label[text()='hse candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"



      And I press "Review Choices"
      Then I should see "You voted for hse candidate3"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 34"
      And I log out


      And I log in as "test35"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hss candidate1"
      And I should see "hss candidate2"
      And I should see "hss candidate3"
      And I click on "hss candidate1" "text" in the "//div[@class='candidate' and .//label[text()='hss candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for hss candidate1"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 35"
      And I log out


      And I log in as "test36"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mcom candidate1"
      And I should see "mcom candidate2"
      And I should see "mcom candidate3"
      And I click on "mcom candidate1" "text" in the "//div[@class='candidate' and .//label[text()='mcom candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for mcom candidate1"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 36"
      And I log out


      And I log in as "test37"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mda candidate1"
      And I should see "mda candidate2"
      And I should see "mda candidate3"
      And I click on "mda candidate3" "text" in the "//div[@class='candidate' and .//label[text()='mda candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for mda candidate3"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 37"
      And I log out


      And I log in as "test38"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sce candidate1"
      And I should see "sce candidate2"
      And I should see "sce candidate3"
      And I click on "sce candidate3" "text" in the "//div[@class='candidate' and .//label[text()='sce candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sce candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 38"
      And I log out


      And I log in as "test39"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sci candidate1"
      And I should see "sci candidate2"
      And I should see "sci candidate3"
      And I click on "sci candidate1" "text" in the "//div[@class='candidate' and .//label[text()='sci candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sci candidate1"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 39"
      And I log out


      And I log in as "test40"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucac candidate1"
      And I should see "ucac candidate2"
      And I should see "ucac candidate3"
      And I click on "ucac candidate1" "text" in the "//div[@class='candidate' and .//label[text()='ucac candidate1']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucac candidate1"

      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 40"
      And I log out


      And I log in as "test41"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucfy candidate1"
      And I should see "ucfy candidate2"
      And I should see "ucfy candidate3"
      And I click on "ucfy candidate2" "text" in the "//div[@class='candidate' and .//label[text()='ucfy candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"



      And I press "Review Choices"
      Then I should see "You voted for ucfy candidate2"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 41"
      And I log out


      And I log in as "test42"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "vetm candidate1"
      And I should see "vetm candidate2"
      And I should see "vetm candidate3"
      And I click on "vetm candidate2" "text" in the "//div[@class='candidate' and .//label[text()='vetm candidate2']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for vetm candidate2"

      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 42"
      And I log out


      And I log in as "test43"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "adsn candidate1"
      And I should see "adsn candidate2"
      And I should see "adsn candidate3"
      And I click on "adsn candidate1" "text" in the "//div[@class='candidate' and .//label[text()='adsn candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for adsn candidate1"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 43"
      And I log out


      And I log in as "test44"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "agri candidate1"
      And I should see "agri candidate2"
      And I should see "agri candidate3"
      And I click on "agri candidate1" "text" in the "//div[@class='candidate' and .//label[text()='agri candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for agri candidate1"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 44"
      And I log out


      And I log in as "test45"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "badm candidate1"
      And I should see "badm candidate2"
      And I should see "badm candidate3"
      And I click on "badm candidate1" "text" in the "//div[@class='candidate' and .//label[text()='badm candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for badm candidate1"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 45"
      And I log out


      And I log in as "test46"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "engr candidate1"
      And I should see "engr candidate2"
      And I should see "engr candidate3"
      And I click on "engr candidate3" "text" in the "//div[@class='candidate' and .//label[text()='engr candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for engr candidate3"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 46"
      And I log out


      And I log in as "test47"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "grad candidate1"
      And I should see "grad candidate2"
      And I should see "grad candidate3"
      And I click on "grad candidate3" "text" in the "//div[@class='candidate' and .//label[text()='grad candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for grad candidate3"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted Yes on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 47"
      And I log out


      And I log in as "test48"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hse candidate1"
      And I should see "hse candidate2"
      And I should see "hse candidate3"
      And I click on "hse candidate1" "text" in the "//div[@class='candidate' and .//label[text()='hse candidate1']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for hse candidate1"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 48"
      And I log out


      And I log in as "test49"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "hss candidate1"
      And I should see "hss candidate2"
      And I should see "hss candidate3"
      And I click on "hss candidate3" "text" in the "//div[@class='candidate' and .//label[text()='hss candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for hss candidate3"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 49"
      And I log out


      And I log in as "test50"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mcom candidate1"
      And I should see "mcom candidate2"
      And I should see "mcom candidate3"
      And I click on "mcom candidate1" "text" in the "//div[@class='candidate' and .//label[text()='mcom candidate1']]" "xpath_element"

      And I should not see "res1"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"




      And I press "Review Choices"
      Then I should see "You voted for mcom candidate1"

      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 50"
      And I log out


      And I log in as "test51"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "mda candidate1"
      And I should see "mda candidate2"
      And I should see "mda candidate3"
      And I click on "mda candidate2" "text" in the "//div[@class='candidate' and .//label[text()='mda candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for mda candidate2"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 51"
      And I log out


      And I log in as "test52"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sce candidate1"
      And I should see "sce candidate2"
      And I should see "sce candidate3"
      And I click on "sce candidate2" "text" in the "//div[@class='candidate' and .//label[text()='sce candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"

      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for sce candidate2"
      Then I should see "You voted Abstain on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 52"
      And I log out


      And I log in as "test53"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "sci candidate1"
      And I should see "sci candidate2"
      And I should see "sci candidate3"
      And I click on "sci candidate2" "text" in the "//div[@class='candidate' and .//label[text()='sci candidate2']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"



      And I press "Review Choices"
      Then I should see "You voted for sci candidate2"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted Abstain on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 53"
      And I log out


      And I log in as "test54"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucac candidate1"
      And I should see "ucac candidate2"
      And I should see "ucac candidate3"
      And I click on "ucac candidate3" "text" in the "//div[@class='candidate' and .//label[text()='ucac candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucac candidate3"
      Then I should see "You voted No on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 54"
      And I log out


      And I log in as "test55"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I should see "ucfy candidate1"
      And I should see "ucfy candidate2"
      And I should see "ucfy candidate3"
      And I click on "ucfy candidate3" "text" in the "//div[@class='candidate' and .//label[text()='ucfy candidate3']]" "xpath_element"

      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I should see "Yes" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"
      And I click on "Yes" "text" in the "//div[@class='resolution' and .//h1[text()='res1']]" "xpath_element"
      And I click on "No" "text" in the "//div[@class='resolution' and .//h1[text()='res2']]" "xpath_element"


      And I press "Review Choices"
      Then I should see "You voted for ucfy candidate3"
      Then I should see "You voted Yes on resolution: res1"
      Then I should see "You voted No on resolution: res2"


      And I click on "Vote" "text"
      Then I should see "Thanks!"
      Then I should see "Number of votes cast so far 55"
      And I log out
# end voting

# begin commissioner goes to results page
      And I log in as "commissioner001"
      And I follow "My home"
      And I click on "Ballot for Fall [General Election]" "link"
      And I expand "SG Elections Admin" node
      And I expand "Results" node
      And I click on "Fall [General Election]" "link" in the "//li[p/span[text() = 'SG Elections Admin']]/ul/li[p/span[text() = 'Results']]" "xpath_element"
# end commissioner goes to results page

# begin verify results
      Then I should see "13" in the "//tr[td[text() = 'res1']]/td[2]" "xpath_element"
      Then I should see "11" in the "//tr[td[text() = 'res1']]/td[3]" "xpath_element"
      Then I should see "20" in the "//tr[td[text() = 'res1']]/td[4]" "xpath_element"
      Then I should see "14" in the "//tr[td[text() = 'res2']]/td[2]" "xpath_element"
      Then I should see "27" in the "//tr[td[text() = 'res2']]/td[3]" "xpath_element"
      Then I should see "14" in the "//tr[td[text() = 'res2']]/td[4]" "xpath_element"

      Then I should see "1" in the "adsn candidate1" "table_row"
      Then I should see "1" in the "adsn candidate2" "table_row"
      Then I should see "2" in the "adsn candidate3" "table_row"
      Then I should see "2" in the "agri candidate1" "table_row"
      Then I should see "2" in the "agri candidate2" "table_row"
      Then I should see "0" in the "agri candidate3" "table_row"
      Then I should see "2" in the "badm candidate1" "table_row"
      Then I should see "1" in the "badm candidate2" "table_row"
      Then I should see "1" in the "badm candidate3" "table_row"
      Then I should see "1" in the "engr candidate1" "table_row"
      Then I should see "1" in the "engr candidate2" "table_row"
      Then I should see "2" in the "engr candidate3" "table_row"
      Then I should see "0" in the "grad candidate1" "table_row"
      Then I should see "2" in the "grad candidate2" "table_row"
      Then I should see "2" in the "grad candidate3" "table_row"
      Then I should see "2" in the "hse candidate1" "table_row"
      Then I should see "0" in the "hse candidate2" "table_row"
      Then I should see "2" in the "hse candidate3" "table_row"
      Then I should see "1" in the "hss candidate1" "table_row"
      Then I should see "0" in the "hss candidate2" "table_row"
      Then I should see "3" in the "hss candidate3" "table_row"
      Then I should see "3" in the "mcom candidate1" "table_row"
      Then I should see "0" in the "mcom candidate2" "table_row"
      Then I should see "1" in the "mcom candidate3" "table_row"
      Then I should see "1" in the "mda candidate1" "table_row"
      Then I should see "2" in the "mda candidate2" "table_row"
      Then I should see "1" in the "mda candidate3" "table_row"
      Then I should see "1" in the "sce candidate1" "table_row"
      Then I should see "2" in the "sce candidate2" "table_row"
      Then I should see "1" in the "sce candidate3" "table_row"
      Then I should see "1" in the "sci candidate1" "table_row"
      Then I should see "2" in the "sci candidate2" "table_row"
      Then I should see "1" in the "sci candidate3" "table_row"
      Then I should see "1" in the "ucac candidate1" "table_row"
      Then I should see "0" in the "ucac candidate2" "table_row"
      Then I should see "3" in the "ucac candidate3" "table_row"
      Then I should see "1" in the "ucfy candidate1" "table_row"
      Then I should see "2" in the "ucfy candidate2" "table_row"
      Then I should see "1" in the "ucfy candidate3" "table_row"
      Then I should see "0" in the "vetm candidate1" "table_row"
      Then I should see "1" in the "vetm candidate2" "table_row"
      Then I should see "2" in the "vetm candidate3" "table_row"
# end verify results
