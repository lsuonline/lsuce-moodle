<?php
$string['pluginname'] = 'SGElection Block';
$string['sgelection'] = 'Student Gov\'t Election';
$string['sgelection:addinstance'] = 'Add a new Student Government Election block';
$string['sgelection:myaddinstance'] = 'Add a new Student Government Election to the My Moodle page';
$string['blockstring'] = 'What should the block say';
$string['vote'] = 'Vote';
$string['review_vote'] = 'Review Choices';
$string['textfields'] = 'Text Fields';
$string['admin_page_header'] = 'Student Gov\'t Admin Page';
$string['sgelectionsettings'] = 'SG Election Settings';
$string['editpage'] = 'Edit Page';
$string['blocktitle'] = 'Title of Block';
$string['pagetitle'] = 'Page Title';
$string['displayedhtml'] = 'Displayed HTML';
$string['picturefields'] = 'Picture Fields';
$string['displaypicture'] = "Display Picture";
$string['red'] = 'red';
$string['blue'] = 'blue';
$string['green'] = 'green';
$string['pictureselect'] = 'Picture Select';
$string['picturedesc'] = 'Picture Description';
$string['displaydate'] = 'Display Date';

//block_sge...
$string['create_election'] = 'Create Election';
$string['configure'] = 'Configure';

// Election
$string['election_fullname'] = '{$a->sem} [{$a->name}]';
$string['election_shortname'] = '{$a->sem} [{$a->name}]';
$string['create_election']    = 'Create new election';
$string['thanks_for_voting'] = 'Thanks for voting!';
$string['election_summary'] = 'Election summary: {$a}';
$string['readonly'] = "This election is closed";

// Candidates
$string['candidates_pagetitle'] = 'Add or Edit a Candidate';
$string['create_new_candidate'] = 'Create a New Candidate';
$string['paws_id_of_candidate'] = 'paws ID of Candidate';
$string['office_candidate_is_running_for'] = 'Office the Candidate is running for';
$string['affiliation'] = 'Affiliation';

// Ballot
$string['ballot_page_header'] = '{$a} Ballot';
$string['preview_ballot'] = 'Preview Ballot';
$string['preview'] = 'Preview';

// Resolutions
$string['abstain'] = 'Abstain';
$string['title_of_resolution'] = 'Title of Resolution';
$string['create_new_resolution'] = 'Create New Resolution';
$string['resolution_text'] = 'Resolution Text';
$string['resolution_page_header'] = 'Resolution Page';
$string['for'] = 'For';
$string['against'] = 'Against';
$string['resolution'] = 'Resolution';
$string['restrict_to_fulltime'] = 'Restrict to Full Time?';
$string['link_to_fulltext'] = 'Link to Full Text';

// Offices
$string['office_page_header'] = 'Office Page Header';
$string['create_new_office'] = 'Create New Office';
$string['title_of_office'] = 'Title of Office';
$string['number_of_openings'] = 'Number of Openings';
$string['limit_to_college'] = 'Limit to College';
$string['select_up_to'] = 'Select no more than {$a}';
$string['weight'] = 'Weight';

// Candidate Table
$string['id'] = 'id';
$string['userid'] = 'userid';
$string['office'] = 'office';
$string['affiliation'] = 'affiliation/VP';
$string['election_id'] = 'election_id';

// Administration
$string['commissioner'] = 'Commissioner';
$string['fulltime'] = 'Full Time';
$string['parttime'] = 'Part Time';
$string['results_recips'] = 'Results Recipients';
$string['results_interval'] = "Results Email Interval";
$string['results_interval_help'] = "Specify an interval (in minutes) that must elapse between email summaries";;
$string['election_tool_administration'] = ' Election Tool Administration';
$string['excluded_curriculum_code'] = 'Excluded Curriculum Code';

// Commissioner Page / building election object
$string['start_date'] = 'Start Date';
$string['end_date'] = 'End Date';
$string['semester'] = 'Semester';
$string['name'] = 'Name';
$string['new_election_options'] = 'Create New Election';
$string['ballot'] ='ballot';
$string['hours_census_start'] = 'Date to pull student eligibility data';
$string['hours_census_start_help'] = 'This time setting defines the first second in which Moodle may calculate enrollment eligibility (enrolled hours) for users.';
$string['thanks_for_voting_message'] = 'Message you want displayed after student votes';
$string['common_college_offices'] = "Offices for each college in this election";
$string['test_users'] = "Test Users";

// errors
$string['err_user_nonexist'] = 'User {$a} does not exist.';
$string['err_user_nonunique'] = 'User {$a->username} already running for office {$a->office} in the {$a->semestername} election (election id {$a->eid}).';
$string['err_resolution_title_nonunique'] = 'A resolution with this title already exists.';
$string['err_election_nonunique'] = 'An election called <em>{$a}</em> already exists';
$string['err_start_end_disorder'] = 'Start date {$a->start} must occur before end date {$a->end}.';
$string['err_office_name_nonunique'] = "An Office with this name already exists";
$string['err_user_notfulltime'] = "Commissioner has to be fulltime";
$string['err_census_start_too_soon'] = 'Census start time must be set after the election earliest_start date ({$a->earliest}) and at least {$a->window} hours before election start time.';
$string['err_start_end_outofbounds'] = 'Election start and end dates must fall within the acceptable range as defined by the Moodle administrator. [{$a->earliest} - {$a->latest}]';
$string['err_election_future_start'] = 'In order to allow time for the enrollment census to run, election can be set to start no sooner than {$a}.';
$string['err_census_future_start']   = 'Census cannot start in the past.';
$string['err_notenrolled'] ='Must be enrolled to vote';
$string['err_pollsclosed'] = 'polls are not open yet';
$string['err_notevenparttime'] = 'You need to be at least a parttime student to vote';
$string['nopreviewpermission'] = 'Only the SG Commissioner can preview the ballot.';
$string['err_alreadyvoted'] = 'You have already voted in this election';
$string['err_missingmeta'] = 'Your user profile is missing required information: {$a}';
$string['err_ineligible'] = 'Either your major (curric_code) or your part-time status renders you ineligible to vote in this election';
$string['err_toomanycands'] = 'Too Many Candidates Selected';
$string['err_toomanycandsjs'] = 'You have selected too many candidates, please select at most {$a}';
$string['err_deletedependencies'] = 'Votes have been cast for this candidate, cannot delete.';
$string['err_deletedependenciesres'] = 'Votes have been cast for this resolution, cannot delete.';
$string['err_deletedependenciesoff'] = 'There are candidates for this office. Please delete them first.';

//Exceptions
$string['exc_nocourseload'] = 'Courseload must be specified when preview mode is selected';
$string['exc_invalidid'] = '{$a} is not a valid election id';

//review page
$string['you_voted_for'] = 'You voted for <strong>{$a->firstname} {$a->lastname}</strong>';
$string['office_title'] = '<h1> {$a->name} </h1>';
$string['you_voted_on_res'] = 'You voted <strong>{$a->value}</strong> on resolution: <strong>{$a->name}</strong>';
$string['resolution'] = 'Resolution';
$string['resolutions'] = 'Resolutions';
//results
$string['results_page_header'] = 'Results';
$string['resultsreport'] = 'Election Report';

// voter
$string['ptorft'] = 'Part Time or Full Time';

//admin settings
$string['facadv'] = 'Faculty Advisor';
$string['facadv_desc'] = 'Username of the SG Faculty Advisor';
$string['earliest_start'] = '#days after semester start';
$string['earliest_start_desc'] = 'How many days after the semester starts is it ok to begin an election ?';
$string['latest_end'] = '#days before grades due';
$string['latest_end_desc'] = 'How many days before the semester ends is it ok to end an election ?';
$string['census_window'] = 'Census cron window';
$string['census_window_desc'] = 'How many hours before an election can we allow the census period to begin? NB that cron must have a chance to run between election census start and election start.';

//lookup user page
$string['didvote'] = 'did vote';
$string['didntvote'] = 'did not vote';
$string['lookupuser'] = 'Lookup User';
$string['paws_id_of_student'] = 'PAWS ID of student';
$string['check_to_see'] = 'Check to see if a specific student has voted';
//logging
$string['defaultlogmessage'] = 'User (id {$a->userid}) {$a->action} {$a->target} (id {$a->objectid}).';
$string['candidatelogmessage'] = 'User (id {$a->userid}) {$a->action} {$a->target} (id {$a->objectid}) with userid {$a->relateduserid}.';

//events
$string['eventelectioncreated'] = 'Election Created';
$string['eventelectionupdated'] = 'Election Updated';
$string['censuscompleted'] = 'Election Census Complete';
$string['censuscompleted_msg'] = 'Enrolled credit hours were calculated for election id {$a->objectid}';

//lookupvoter
$string['check_vote_status'] = 'Check vote status for election:   {$a}';

//misc
$string['savesuccess'] = 'changes saved';
