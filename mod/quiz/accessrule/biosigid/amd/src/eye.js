import jQuery from 'jquery';
import biosightclient from 'quizaccess_biosigid/biosightclient';
import url from 'core/url';

export const init = (bsiUsername, pageUrl, bioDomain) => {
    var bs;
    var pageurl = pageUrl;
    var path = url.relativeUrl(pageurl, "");

    jQuery('body').append(`<div id=\"biosight_info\" style=\"position: fixed; z-index: 9999; top: 15px; left: 100px;
        padding: 5px; background-image: linear-gradient(lightblue, steelblue); box-shadow: 0px 0px 0px 8px rgba(128, 128, 128, 0.5);
        border-radius: 2px; height: 68px; font-family: Arial; font-weight: bold; font-size: 14px; text-align: center;
         cursor: move;\">BioSight-ID&trade;: <i class=\"fa fa-eye fa-4x\" style=\"vertical-align: middle;\"></div>`);

    function dragElement(elmnt) {
        var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        function dragMouseDown(e) {
            e = e || window.event;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }
        function elementDrag(e) {
            e = e || window.event;
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
            elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
        }
        function closeDragElement() {
            document.onmouseup = null;
            document.onmousemove = null;
        }
        elmnt.onmousedown = dragMouseDown;
    }
    dragElement(document.getElementById("biosight_info"));

    // Read the buttons in the page and remove Return to attempt.
    jQuery("button[type='submit']").each(function() {
        if (jQuery(this).text() == 'Return to attempt') {
            jQuery(this).remove();
        }
    });

    var biosight = function () {
        window.console.log(path);
        window.console.log('BioSight Loaded...');
        var rurl = 'https://' + bioDomain;
        biosightclient.findSession(rurl + '/biosight/findsession', bsiUsername, function (s) {

            if (s.status !== 'ok') {
                window.console.error('Cannot find BioSight session!');
                jQuery('#biosight_info i').css('color', 'orange');
                return;
            }

            bs = biosightclient(rurl + '/bioSightHub', s.session, function () {
                    window.console.log('BioSight Fail!');
                    jQuery('#biosight_info i').css('color', 'red');
                }, function () {
                    window.console.log('BioSight OK!');
                    jQuery('#biosight_info i').css('color', 'green');
                }, function () {
                    window.console.log('BioSight Started!');
                    jQuery('#biosight_info i').css('color', 'gray');
                    stopOnReviewPage();
                    beforeUnload();
                }
            );
        });
    };

    var waitForBioSight = function () {
        if (typeof biosightclient !== 'undefined') {
            biosight();
        } else {
            setTimeout(waitForBioSight, 100);
        }
    };
    waitForBioSight();

    function stopOnReviewPage() {
        window.console.log('Path ends with review? result:' + path.endsWith('review.php'));

        if (path.endsWith('review.php')) {
            if (typeof bs !== 'undefined') {
                jQuery('#biosight_info').remove();
                window.console.log('Review page: BioSight Stopped!');
                bs.stop();
            }
        }
    }

    // Stop biosight when student finishes.
    // The only option is to validate if a student is leaving the summary page.
    // In that case, we remove the floating element and stop the Biosight script.
    function beforeUnload() {
        jQuery(window).bind('beforeunload', function() {
            if (path.endsWith('summary.php') || path.endsWith('review.php')) {
                jQuery('#biosight_info').remove();
                if (typeof bs !== 'undefined') {
                    window.console.log('Before unload summary or review. BioSight Stopped!');
                    bs.stop();
                }
            }
        });
    }

};
