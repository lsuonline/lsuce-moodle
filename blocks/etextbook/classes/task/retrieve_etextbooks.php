<?php
namespace block_etextbook\task;
global $CFG;

class retrieve_etextbooks extends \core\task\scheduled_task
{

    public $found_count = 0;

    public $notfound_count = 0;

    public function get_name()
    {
        return get_string('retrieve_etextbooks', 'block_etextbook');
    }

    public function execute()
    {
        global $DB, $USER;
        $librarylink = get_config('etextbook', 'Library_link');
        $books = simplexml_load_file($librarylink);

        $libraryadmin = get_config('etextbook', 'Library_admin');
        $fakeuser = new \stdClass();
        $fakeuser->id = 9999999;
        $fakeuser->email = $libraryadmin;
        $fakeuser->mailformat = 1;
        $fakeuser->username = get_string("library_admin_email_username", "block_etextbook");
        $subject = get_string("subject", 'block_etextbook');
        $email_message = $found = $notfound = "";
        $brk = "<br />";
        if($books == false){
            $email_message .=  $brk . " FILE FROM LIBRARY XML WAS NOT ACCEPTED AS XML" . $brk;
        }

        else if(!isset($books->book->field_ebook_url)){
            $email_message .= $brk . " DATA DOES NOT CONTAIN A field_ebook_url NODE " . $brk
            . " likely the XML file is formatted or was recieved incorrectly " .  $brk;
        }

        else if($books->count() < 1){
            $email_message .= $brk . " BOOK COUNT WAS LESS THAN 1 " . $brk;
        }
        else {
            $email_message .= $brk . get_string("number_books", "block_etextbook") . $brk . $books->count() . $brk;
            $DB->execute("TRUNCATE TABLE {block_etextbook}");
        }
        $tbook = new \stdClass();

        foreach ($books as $book) {


            $tbook->book_url =          (string)$book->field_ebook_url;
            $tbook->img_url =           (string)$book->field_ebook_image;
            $tbook->title =             (string)$book->field_ebook_title;
            $tbook->dept =              (string)$book->field_ebook_subject;
            $tbook->course_title =      (string)$book->field_course_title;
            $tbook->course_number =     (string)$book->field_course_number;
            $tbook->section =           (string)$book->field_ebook_section;
            $tbook->instructor =        (string)$book->Instructor;
            $tbook->term =              (string)$book->Term;
            $termswitcharoo =           explode(" ", $tbook->term);
            if (count($termswitcharoo) == 2) {
                $tbook->term =              $termswitcharoo[1] . " " . $termswitcharoo[0];
            } else {
                $tbook->term =              $termswitcharoo[2] . " " . $termswitcharoo[0] . " " . $termswitcharoo[1];
            }
            $tbook->found =             false; // will get switched to true if found in DB lookup

            if(strlen($tbook->section) > 1){
                $sections = explode(',', ($tbook->section));
                $sections = array_map('trim', explode(',', $tbook->section));

                foreach($sections as $section){
                    $tbook->section = $section;
                    $found_container = $this->merge_courses_with_books($tbook)['found'];
                    $notfound_container = $this->merge_courses_with_books($tbook)['notfound'];
                    if($found_container != " "){
                        var_dump($found_container);
                        $found .=       $brk . $found_container . "   -  Multiple Sections - FOUND ";
                    }
                    if($notfound_container != " "){
                        var_dump($notfound_container);
                        $notfound .=    $brk . $notfound_container . "   -  Multiple Sections - NOTFOUND ";
                    }


                }
            }
            else{
                $found_container = $this->merge_courses_with_books($tbook)['found'];
                $notfound_container = $this->merge_courses_with_books($tbook)['notfound'];
                if($found_container != " ") {

                    $found .= $brk . $found_container . "  -  One Section - FOUND ";
                    echo "\n inside one section found container \n";
                }
                if($notfound_container != " ") {

                    $notfound .= $brk . $notfound_container . "  -  One Section - NOTFOUND ";
                    echo "\n inside one section notfound container \n";

                }
            }
        }
        //$found .= $brk . $found;

        $email_message .= $brk . get_string("email_found_books", "block_etextbook") . $this->found_count . $brk . $found
            . $brk . " --------------------------------------------- " . $brk . get_string("not_found", 'block_etextbook') . $this->notfound_count . $brk .  $notfound;

        $emailsent = email_to_user($fakeuser, $USER, $subject, $email_message, $email_message);
        if ( ! $emailsent) {
            $warnings[] = get_string("no_email_address", 'block_etextbook');
        }
    }
    public function merge_courses_with_books($tbook){
        global $DB;
        $notfound = " ";
        $found = " ";
        $tbook->courseid = "";
        $coursenameregexp = $tbook->term . ' ' . $tbook->dept . ' ' . $tbook->course_number . ' ' . str_pad($tbook->section, 3, "0", STR_PAD_LEFT);

        echo "\n\n section is " . $tbook->section;
        $coursenameregexp = $tbook->term . ' ' . $tbook->dept . ' ' . $tbook->course_number . ' ' . str_pad($tbook->section, 3, "0", STR_PAD_LEFT);
        echo " \n \n " . $coursenameregexp;

        $sqlt = "SELECT DISTINCT(c.id)
                     FROM {enrol_ues_semesters} sem
                     INNER JOIN {enrol_ues_sections} sec ON sec.semesterid = sem.id
                     INNER JOIN {enrol_ues_courses} cou ON cou.id = sec.courseid
                     INNER JOIN {course} c ON c.idnumber = sec.idnumber
                     WHERE sec.idnumber IS NOT NULL
                     AND c.idnumber IS NOT NULL
                     AND sec.idnumber <> ''
                     AND c.idnumber <> ''
                     AND CONCAT(sem.year, ' ', sem.name, ' ', cou.department, ' ', cou.cou_number, ' ', sec.sec_number) = :coursename";

        if($records = $DB->get_record_sql($sqlt, array('coursename' => $coursenameregexp))){
            $tbook->courseid = $records->id;
            $DB->insert_record('block_etextbook', $tbook);
            $found =  $tbook->dept . " " . $tbook->course_number;

            $tbook->found = true;
            $this->found_count++;
        }
        else{
            $notfound .= $coursenameregexp;
            $this->notfound_count++;
        }

        $books = array();
        $books['found']    = $found;
        $books['notfound'] = $notfound;
        return $books;
    }

}

