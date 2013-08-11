<?php
$DS = DIRECTORY_SEPARATOR;
global $CFG;
require_once $CFG->dirroot.$DS.'grade'.$DS.'import'.$DS.'smart'.$DS.'classes.php';
require_once $CFG->dirroot.$DS.'grade'.$DS.'import'.$DS.'smart'.$DS.'lib.php';

class smart_matcher_testcase extends advanced_testcase{ //fix class name
    //preg_match returns 0 on 'not found', 1 on 'found', false on error
    
    public function test_lsuid2_matcher(){
        $this->assertEquals(1,smart_is_lsuid2(890000000));   
        $this->assertEquals(0,smart_is_lsuid2(990000000));
        $this->assertEquals(0,smart_is_lsuid2(9900000004444));
        $this->assertEquals(0,smart_is_lsuid2(890000));
    }
    
    public function test_good_mec_lsuid_matcher(){
        
        $goodMecids = array(
            123890000000,
            999899999999
        );
        foreach($goodMecids as $gmi){
            $this->assertEquals(1,smart_is_mec_lsuid($gmi), sprintf("\ninput should have passed: %s\n",$gmi));
        }
    }
    
    public function test_bad_mec_lsuid_matcher(){
        
        $badMecids = array(
            123129999999, /* 4th and 5th digits should be '89' */
            'abc899999999', //first 3 digits should be numeric
            'uihkgjsd',
            1234,
            890000001,
            ''
        );
        foreach($badMecids as $bmi){
            $this->assertEquals(0,smart_is_mec_lsuid($bmi), sprintf("\ninput should NOT have passed: %s\n",$bmi));
        }
    }
    
    public function test_bad_grade_matcher(){
        $grades = array(
            
            8956942564256,
            '100!',
            0,
            0.0,
            100.0,
            10,
            111,
            'hello world',
            'abc',
        );
        foreach($grades as $g){
            $this->assertEquals(0,smart_is_grade($g), sprintf("\ninput should NOT have passed: %s\n",$g));
        } 
    }
    
    public function test_good_grade_matcher(){
        $grades = array(
            100.00,
            90.00,
            10.00,
            1.00,
            0.00,
            .00,
            .5,
            .55,
            1,
            10,
            99,
            100,
            111,
            'asdf'
        );
        foreach($grades as $g){
            $this->assertEquals(1,smart_is_grade($g), sprintf("\ninput should have passed: %s\n",$g));
        } 
    }
    
    public function test_smart_is_anon_num_good(){
        $numbers = array(1234,9879,1111,0000,);
        foreach($numbers as $n){
            $this->assertEquals(1,smart_is_grade($n), sprintf("\ninput should have passed: %s\n",$n));
        } 
    }
    
    public function test_smart_is_anon_num_bad(){
        $numbers = array('asdf',12,1353452);
        foreach($numbers as $n){
            $this->assertEquals(0,smart_is_grade($n), sprintf("\ninput should NOT have passed: %s\n",$n));
        } 
    }
    
    public function test_smart_is_pawsid(){
        //orig func doc:
        // Checks whether or not a string is a valid pawsid. It must be 1-16 and contain
        // only alphanumeric characters including hyphens.
        $good = array(
            1234567890123456,
            '123456789012-456',
        );
        foreach($good as $id){
            $this->assertEquals(1,smart_is_pawsid($id), sprintf("\ninput should have passed: %s\n",$id));
        }
        
        $bad = array(
            123456789012,
            '-123456789012456',
        );
        foreach($bad as $id){
            $this->assertEquals(0,smart_is_pawsid($id), sprintf("\ninput should NOT have passed: %s\n",$id));
        }
    }
    
    
    public function test_smart_is_keypadid(){
        //orig func doc:
        // Checks whether or not a string is a valid pawsid. It must be 1-16 and contain
        // only alphanumeric characters including hyphens.
        $good = array(
            'asdfgh',
            '123456',
            '123asd'
        );
        foreach($good as $id){
            $this->assertEquals(1,smart_is_keypadid($id), sprintf("\ninput should have passed: %s\n",$id));
        }
        
        $bad = array(
            'mnbzxc',
            '1234567',
            '1q2w2e3',
        );
        foreach($bad as $id){
            $this->assertEquals(0,smart_is_pawsid($id), sprintf("\ninput should NOT have passed: %s\n",$id));
        }
    }
}
?>
