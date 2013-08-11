<?php
$DS = DIRECTORY_SEPARATOR;
global $CFG;
require_once $CFG->dirroot.$DS.'grade'.$DS.'import'.$DS.'smart'.$DS.'classes.php';


class smart_autodiscover_filetype extends advanced_testcase{

    private function resultMsg($unit,$not){
        $ret = "The following input should";
        $ret.= $not ? " NOT " : '';
        $ret.= "have passed! :\n\n%s";
        return sprintf($ret,$unit);
    }
    /**
     * Grade file with comma-separated values keyed with keypadid
     * 170E98,30
     * 1718C0,80
     */
    public function test_SmartFileKeypadidCSV(){
        $file = "170E98,30\n1718C0,80\n1718C0,80\n";
        $this->assertInstanceOf('SmartFileKeypadidCSV', smart_autodiscover_filetype($file),$this->resultMsg($file, false));
        
        $badfile1 = "170E98,30\n2345hjgu,80\n";
        $badfile2 = "170E98,30\n1718C0,I went to the store for some milk\n";
        
        $this->assertTrue(!smart_autodiscover_filetype($badfile1), $this->resultMsg($badfile1, true));
        $this->assertTrue(!smart_autodiscover_filetype($badfile2), $this->resultMsg($badfile2, true));
        
        
    }
    
    /**
     * Fixed width grade file.
     * 89XXXXXXX 100.00
     * 89XXXXXXX 090.00
     */
    public function test_SmartFileFixed(){
        $rnd1 = rand(0,9999999);
        $rnd2 = rand(0,9999999);
        $file = "89{$rnd1} 100.00\n89{$rnd2} 090.00\n";
        $this->assertInstanceOf('SmartFileFixed', smart_autodiscover_filetype($file));
        
        //should fail
        $badfile = array(
                    "69{$rnd1} 100.00\n89{$rnd2} 090.00\n",
                    "89abcDEFG 100.00\n89{$rnd2} 090.00\n",
                    "89{$rnd1} 100.00\n89{$rnd2} 090\n",
                    "89{$rnd1} 100.00\n89{$rnd2}  90.00\n",
                        );
        foreach($badfile as $bf){
            $this->assertTrue(!smart_autodiscover_filetype($bf), sprintf("The following string should NOT have passed:\n%s", $bf));
        }
    }
    
    /**
     * Insane Fixed width grade file.
     * 89XXXXXXX anything you want in here 100.00
     * 89XXXXXXX i mean anything 090.00
     */
    public function test_SmartFileInsane(){
        $rnd1 = rand(0,9999999);
        $rnd2 = rand(0,9999999);
        $file = "89{$rnd1} Hello dolly! 100.00\n89{$rnd2} It's so nice to have you back where you belong...090.00\n";

        $this->assertInstanceOf('SmartFileInsane', smart_autodiscover_filetype($file));
        
        //should fail
        $badfile = array();
        $badfile[] = "89XXXXXXX anything you want in here 100.00\n89XXXXXXX 89 is not numeric!! 100.00\n";
        $badfile[] = "89{$rnd1} Hello dolly! 100.00\n89{$rnd1} there is no grade at the end of the line!!!\n";
        $badfile[] = "89{$rnd1} Hello dolly! 100.00\n67{$rnd1} 89-number is not correct100.00";

        foreach($badfile as $bf){
            $this->assertTrue(!smart_autodiscover_filetype($bf), sprintf("The following string should NOT have passed:\n%s", $bf));
        }
    }
    
    /**
     * Grade file from the Measurement and Evaluation Center
     * XXX89XXXXXXX 100.00
     + XXX89XXXXXXX  90.00
     */
    public function test_SmartFileMEC(){

        $mecids = array(
            123890000000,
            999899999999
        );

        assert(is_int($mecids[1]));
        assert(strlen($mecids[1]) == 12);
        
        $file = "{$mecids[1]} 100.00\n{$mecids[0]} 090.00\n";
        $this->assertInstanceOf('SmartFileMEC', smart_autodiscover_filetype($file));

        //should fail
        $badMecid = array(
            123129999999, /* 4th and 5th digits should be '89' */
            'abc899999999', //first 3 digits should be numeric
            ); 
        $badfile = array();
        $badfile[] = "{$mecids[1]} 100.00\n{$badMecid[0]} 090.00\n";
        $badfile[] = "{$mecids[1]} 100.00\n{$badMecid[1]} 090.00\n";
        $badfile[] = "890000000 100.00\n{$badMecid[1]} 090.00\n";
        $badfile[] = "{$mecids[1]} Hello dolly! 100.00\n{$mecids[0]} 090.00\n";
        

        foreach($badfile as $bf){
            $this->assertTrue(!smart_autodiscover_filetype($bf), sprintf("The following string should NOT have passed:\n%s", $bf));
        }
    }
    

    /**
     * Grade file for LAW students being graded with an anonymous number
     * XXXX,100.00
     * XXXX, 90.00
     */
    public function test_SmartFileAnonymous(){
        $goodLawIds = array(1234,4564,7897);
        $badLawIds  = array(890000000,'a123','asfg',123456);
        
        $goodGrades = array(100.00, 90.00, 0.00);
        $badGrades  = array(100, 'A', 1000);
        
        $goodFile = '';
        foreach($goodLawIds as $gli){
            foreach($goodGrades as $gg){
                $goodFile.= $gli.", ".$gg."\n";
            }
        }
        $this->assertInstanceOf('SmartFileAnonymous', smart_autodiscover_filetype($goodFile));
        
        
        //now the bad
        foreach($badLawIds as $bli){
            $badFile=$bli.", ".$goodGrades[0]."\n";
            $this->assertTrue(!smart_autodiscover_filetype($badFile), sprintf("The following string should NOT have passed:\n%s", $badFile));
            mtrace("\n".$badFile);
            unset($badFile);
        }
    }
}
?>
