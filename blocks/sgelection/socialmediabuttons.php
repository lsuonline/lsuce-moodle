<div id="fb-root">
    <?php                 
        global $CFG;
    ?>

</div>

<script>
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&appId=715650648506222&version=v2.0";
        fjs.parentNode.insertBefore(js, fjs);
     }
     (document, 'script', 'facebook-jssdk'));
</script>

<div class = "socialmediabuttons">
    <div class ="facingbooksharebutton">
        <div class="fb-share-button" data-href= "<?php echo $CFG->wwwroot . '/blocks/sgelection/fb.php';?>"></div>    
    </div>
    <div class="twitterbutton">
        <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://sg.lsu.edu/elections" data-lang="en" data-text="I just voted in the LSU Student Government Elections! #lsusgelections">Tweet</a>
        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
    </div> 
    <div class='tumblrbutton'>
        <a href="http://www.tumblr.com/share/link?url=<?php echo urlencode('http://sg.lsu.edu/elections') ?>&name=<?php echo urlencode('LSU Student Government Election') ?>&description=<?php echo urlencode('I just voted in the LSU Student Government Elections!') ?>" title="Share on Tumblr" style="display:inline-block; text-indent:-9999px; overflow:hidden; width:81px; height:20px; background:url('http://platform.tumblr.com/v1/share_1.png') top left no-repeat transparent;">Share on Tumblr</a>
    </div>
</div>
