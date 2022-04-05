M.qtype_easyonewman = {
    insert_structure_into_applet: function(Y, stagoreclip) {
        var textfieldid = 'id_answer_0';
        if (document.getElementById(textfieldid).value != '') {
            var idhand = 'pos';
            if (stagoreclip == '1') {
                idhand = 'epos';
            }
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");

            // UofL Fix ------------------
            // just in case the url is an issue, use host.
            var host = "http://"+window.location.hostname;

            for (var i = 0; i < 6; i++) {
                var elem = document.createElement("img");
                group = groups[i];
                trimgroup = group.substring(0, group.length - 1);
                if(trimgroup !=="") //UofL Disha Fix -> If answer is not given for specific div then do not create img
                {
                    // elem.setAttribute("src", host + "/question/type/easyonewman/pix/" + trimgroup + ".png");
                    elem.setAttribute("src", "type/easyonewman/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", trimgroup + "5");
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    elem.setAttribute("class", "yui3-dd-drop yui3-dd-draggable dragableimgslot");
                    document.getElementById(idhand + i).appendChild(elem);
                    document.getElementById("apos" + i).value = trimgroup + "5";
                }
                
            }
        }
    }
}
M.qtype_easyonewman.init_reload = function(Y, url, htmlid) {
    var handleSuccess = function(o) {
            Y.all('input[id^="apos"]').set('value', '');
            Y.all('input[id^="id_answer_"]').set('value', '');
            newman_template.innerHTML = o.responseText;
            M.qtype_easyonewman.insert_structure_into_applet(Y, document.getElementById('id_stagoreclip').value);
            M.qtype_easyonewman.dragndrop(Y,'1');            
            //div.innerHTML = "<li>JARL!!!</li>";
        }
    var handleFailure = function(o) { /*failure handler code*/
        }
    var callback = {
        success: handleSuccess,
        failure: handleFailure
    }
    var button = Y.one("#id_stagoreclip");
    button.on("change", function(e) {
        div = Y.YUI2.util.Dom.get(htmlid);
        Y.use('yui2-connection', function(Y) {
            newurl = url + document.getElementById('id_stagoreclip').value;
            Y.YUI2.util.Connect.asyncRequest('GET', newurl, callback);
        });
    });
};
M.qtype_easyonewman.dragndrop = function(Y, slot) {
    YUI().use('dd-drag', 'dd-constrain', 'dd-proxy', 'dd-drop', function(Y) {
        //Listen for all drag:drag events
        Y.DD.DDM.on('drag:drag', function(e) {
            //Get the last y point
            var y = e.target.lastXY[1];
            //is it greater than the lastY var?
            if (y < lastY) {
                //We are going up
                goingUp = true;
            } else {
                //We are going down.
                goingUp = false;
            }
            //Cache for next check
            lastY = y;
        });
        //Listen for all drag:start events
        Y.DD.DDM.on('drag:start', function(e) {
            //Get our drag object
            var drag = e.target;
            //Set some styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
        });
        //Listen for a drag:end events
        Y.DD.DDM.on('drag:end', function(e) {
            var drag = e.target;
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
        });
        Y.DD.DDM.on('drop:hit', function(e) {
            var drop = e.drop.get('node'),
                drag = e.drag.get('node');
            var flag = false;
        });
        //Listen for all drag:drophit events
        Y.DD.DDM.on('drag:drophit', function(e) {
            var drop = e.drop.get('node'),
                drag = e.drag.get('node');
            //UofL Disha Fix
            var dragId = drag.get('id');
            var dropId = drop.get('id');
            var parentDragId = drag.get('parentNode')?drag.get('parentNode').get('id'):'';
            var parentDropId = drop.get('parentNode')?drop.get('parentNode').get('id'):'';
            if(parentDragId.charAt(0) === 'd' && parentDropId.startsWith('divnew')) //when drag from images div to answer div
            {
                drop.get('childNodes').remove();
                drop.appendChild(drag);
                var idhand = dropId.charAt(0) === 'e' ?dropId.substr(1) :dropId;
                document.getElementById('a' + idhand).value = dragId;
            }
            else if((parentDragId.charAt(0) === 'p' || parentDragId.charAt(0) === 'e') && parentDropId.startsWith('divnew')) //when drag from answer div to answer div
            {
                drop.get('childNodes').remove();
                drop.appendChild(drag);
                var idhand = dropId.charAt(0) === 'e' ?dropId.substr(1) :dropId;
                document.getElementById('a' + idhand).value = dragId;
                var oldHiddenId = parentDragId.charAt(0) === 'e' ?parentDragId.substr(1) :parentDragId;
                document.getElementById('a' + oldHiddenId).value = ''; //clear answer hidden value
            }
            else if(parentDragId.charAt(0) === 'p' || parentDragId.charAt(0) === 'e')// when drag from answer div to images div
            {
                var idhand = parentDragId.charAt(0) === 'e' ?parentDragId.substr(1) :parentDragId;
                document.getElementById('a' + idhand).value = '';//clear answer hidden value
                drop.get('childNodes').remove();
                Y.one('#d'+dragId+'slot').set('innerHTML','');
                Y.one('#d'+dragId+'slot').appendChild(drag);
            }
            else
            {
                //nothing
                console.log('Not a valid place to drop...');
            }
        });
        //Static Vars
        var goingUp = false,
            lastY = 0;
        var nextsibling = '';
        var dragparentid = '';
        //Get the list of img's and make them draggable
        var lis = Y.Node.all('.dragableimgslot');
        lis.each(function(v, k) {
            var dd = new Y.DD.Drag({
                node: v,
                target: {
                    padding: '0 0 0 20'
                }
            }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false,
                cloneNode: true,
            }).plug(Y.Plugin.DDConstrained, {});
        });
        var uls = Y.Node.all('.dropablediv');
        uls.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });
    });
};
M.qtype_easyonewman.init_getanswerstring = function(Y, stagoreclip) {
    var handleSuccess = function(o) {};
    var handleFailure = function(o) { /*failure handler code*/
        };
    var callback = {
        success: handleSuccess,
        failure: handleFailure
    };
    Y.all(".id_insert").each(function(node) {
        node.on("click", function() {
            var idhand = 'pos';
            if (stagoreclip === '1') {
                idhand = 'epos';
            }
            var pos0 = document.getElementById('apos0').value;
            var pos1 = document.getElementById('apos1').value;
            var pos2 = document.getElementById('apos2').value;
            var pos3 = document.getElementById('apos3').value;
            var pos4 = document.getElementById('apos4').value;
            var pos5 = document.getElementById('apos5').value;
            pos0 = pos0.substring(0, pos0.length - 1) + '6';
            pos1 = pos1.substring(0, pos1.length - 1) + '6';
            pos2 = pos2.substring(0, pos2.length - 1) + '6';
            pos3 = pos3.substring(0, pos3.length - 1) + '6';
            pos4 = pos4.substring(0, pos4.length - 1) + '6';
            pos5 = pos5.substring(0, pos5.length - 1) + '6';
            
            /* UofL Disha Fix -> Existing code was fetching id of div instead of button. Insert from editor functionality was not working
            var buttonid = node.getAttribute("id"); */
            var buttonid = node.one('button').getAttribute("id");
            textfieldid = 'id_answer_' + buttonid.substr(buttonid.length - 1);
            document.getElementById(textfieldid).value = pos0 + "-" + pos1 + "-" + pos2 + "-" + pos3 + "-" + pos4 + "-" + pos5;
        });
    });
};
