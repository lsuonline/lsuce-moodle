###Test Coverage:

vote_limits.feature

- Ensure offices that are restricted to n offices allow no more than n candidates 
  to be selected for that office



###vote.feature

- Small, fast (1min) general simulation of an election. 
  - 4 users: 1 parttime, 3 fulltime
  - 2 resolutions: 1 is restricted to fulltime students
  - Checks vote tallies at the end


###page_security.feature

- Ensure that secure pages are not available to non-privileged users, 
  even if navigated directly. 


###conditional_ballot_items.feature

- check that resolutions for full- and part-time students appear correctly


###conditional_access.feature

- ensure that:
  - fulltime, non-excluded users can access the block
  - fulltime users from excluded curric codes cannot use the block
  - users with 0 enrolled hours for the semester cannot use the block
  - that enrolled users in high-level courses (eg > 8000) can use the block, even if the course was not created


###block_visibility.feature

- ensure that:
  - at the various stages of configuration, users can only access the block when appropriate
  - checks made at the following stages:
    - block is initially added
    - faculty advisor is assigned
    - commissioner is assigned
    - election is created
    - election is open


###autovote.feature

- large-scale simulation of an election with vote count verification at the end
  - for each college, 
    - there is one office available: College Council President [college]
    - there are 3 candidates running
    - there are 55 voters distributed across all colleges