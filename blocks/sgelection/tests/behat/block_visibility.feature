@block @block_sgelection @javascript
Feature: Block Visibility
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
    # end set block to all My pages

      And I log out


    Scenario: Check block correctly visible
    # When first added, no one but admin should see the block.

        # Test admin. 
        And I log in as "admin"
        And I follow "My home"
        Then I should see "Student Gov't Election"
        And  I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I log out

        # Test facadv.
        And I log in as "facadv001"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I log out

        # Test commissioner.
        And I log in as "commissioner001"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I log out

        # Test students.
        And I log in as "test1"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I log out

    # When first configured, no one but admin and faculty advisor should see the block

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

        # Test admin.
        And I log in as "admin"
        And I follow "My home"
        Then I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I log out

        # Test facadv.
        And I log in as "facadv001"
        And I follow "My home"
        And I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I log out

        # Test commissioner.
        And I log in as "commissioner001"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I log out

        # Test students.
        And I log in as "test1"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I log out


    # when commissioner is selected, admin, facadv and commissioner should see the block.
    # Note that commissioner should NOT see the 'Configure' link.

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

        # Test admin.
        And I log in as "admin"
        And I follow "My home"
        Then I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I log out

        # Test facadv.
        And I log in as "facadv001"
        And I follow "My home"
        And I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I log out

        # Test commissioner.
        And I log in as "commissioner001"
        And I follow "My home"
        And I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should not see "Configure" in the "//div[contains(@class, 'block_sgelection')]/div" "xpath_element"
        And I log out

        # Test students.
        And I log in as "test1"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I log out

    # when election is created, only admin, facadv and commissioner should see 
    # the block, still- students don't see it until the election is open and 
    # only if they are eligible.

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
        And I log out
        # end create new election

        # Test admin.
        And I log in as "admin"
        And I follow "My home"
        Then I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I should see "Ballot for Fall [General Election]"
        And I log out

        # Test facadv.
        And I log in as "facadv001"
        And I follow "My home"
        And I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should see "Configure" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Configure')]" "xpath_element"
        And I should see "Ballot for Fall [General Election]"
        And I log out

        # Test commissioner.
        And I log in as "commissioner001"
        And I follow "My home"
        And I should see "Student Gov't Election"
        And I should see "Student Gov't Election" in the "//div[contains(@class, 'block_sgelection')]//div/h2" "xpath_element"
        And I should see "Create new election" in the "//div[contains(@class, 'block_sgelection')]//a[contains(text(), 'Create new election')]" "xpath_element"
        And I should not see "Configure" in the "//div[contains(@class, 'block_sgelection')]/div" "xpath_element"
        And I should see "Ballot for Fall [General Election]"
        And I log out

        # Test students.
        And I log in as "test1"
        And I follow "My home"
        And I should not see "Student Gov't Election"
        And I should not see "Ballot for Fall [General Election]"
        And I log out

    # Run cron, populating the hours table; then udpate the election start time
    # so that it will be open now.
    # students should now see the block
    # note that student 'test1' is enrolled in 11 credit hours (see enrolments/STUDENTS.xml)

        # Run cron
        And I log in as "admin"
        And I run cron
        And I log out

        # commissioner update election start
        And I log in as "commissioner001"
        And I follow "My home"
        And I click on "Ballot for Fall [General Election]" "link" in the "block_sgelection" "block"
        And I follow "Edit this Election"
        And I open the election
        And I log out

        # test that student can see link
        And I log in as "test1"
        And I follow "My home"
        And I should see "Ballot for Fall [General Election]"
        And I log out
         
