<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <title>Elections Redirect</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width">
    <meta property="fb:app_id" content="715650648506222" /> 
    <meta property="og:type"   content="website" /> 
    <meta property="og:title"  content="I just voted on LSU's Student Government Elections!" /> 
    <meta property="og:image"  content="<?php require_once('../../config.php'); global $CFG; echo $CFG->wwwroot ?>/theme/lsu/pix/vote_tigers_fit.png" />
    <meta property="og:url"    content="<?php echo $CFG->wwwroot ?>/blocks/sgelection/fb.php" />
    <meta http-equiv="refresh" content="2;url=http://sg.lsu.edu/elections" />
    </head>
    <body>
        <div>Redirecting you to the LSU Student Government Elections page.</div>
    </body>
</html>
