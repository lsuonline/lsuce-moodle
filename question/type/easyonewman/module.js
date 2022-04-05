M.qtype_easyonewman = {
    insert_easyonewman_applet: function(Y, topnode, feedback, readonly, stripped_answer_id, slot) {
        var inputdiv = Y.one(topnode);
        inputdiv.ancestor('form').on('submit', function() {
            var pos0 = document.getElementById('apos0' + slot).value;
            var pos1 = document.getElementById('apos1' + slot).value;
            var pos2 = document.getElementById('apos2' + slot).value;
            var pos3 = document.getElementById('apos3' + slot).value;
            var pos4 = document.getElementById('apos4' + slot).value;
            var pos5 = document.getElementById('apos5' + slot).value;
            textfieldid = topnode + ' input.answer';
            orderstring = pos0 + "-" + pos1 + "-" + pos2 + "-" + pos3 + "-" + pos4 + "-" + pos5;
            Y.one(topnode + ' input.answer').set('value', orderstring);
            Y.one(topnode + ' input.mol').set('value', orderstring);
        }, this);
    }
}
M.qtype_easyonewman2 = {
    insert_structure_into_applet: function(Y, slot, moodleroot, stagoreclip) {
        var textfieldid = 'my_answer' + slot;
        if (document.getElementById(textfieldid).value !== '') {
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");
            var idhand = 'pos';
            if (stagoreclip === '1') {
                idhand = 'epos';
            }
            for (var i = 0; i < 6; i++) {
                //document.write(cars[i] + "<br>");
                
                group = groups[i];
                if(group !=="") //UofL Disha Fix -> If answer is not given for specific div then do not create img
                {
                    var elem = document.createElement("img");
                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyonewman/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    document.getElementById(idhand + i + slot).appendChild(elem);
                }
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    }
}
M.qtype_easyonewman.init_showmyresponse = function(Y, moodle_version, slot, moodleroot, stagoreclip) {
    var handleSuccess = function(o) {};
    var handleFailure = function(o) { /*failure handler code*/
        };
    var callback = {
        success: handleSuccess,
        failure: handleFailure
    };
    if (moodle_version >= 2012120300) { //Moodle 2.4 or higher
        YAHOO = Y.YUI2;
    }
    var idhand = 'pos';
    if (stagoreclip === '1') {
        idhand = 'epos';
    }
    var refreshBut = Y.one("#myresponse" + slot, slot);
    refreshBut.on("click", function() {
        //var textfieldid = 'my_answer1';  UofL Disha Fix -> Existing code does not change response for more than 1 questions on review page.
        var textfieldid = 'my_answer' + slot;
        if (document.getElementById(textfieldid) && document.getElementById(textfieldid).value !== '') {
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");
            for (var i = 0; i < 6; i++) {
                group = groups[i];
                div = document.getElementById(idhand + i + slot);
                div.innerHTML = '';
                if(group !=="") //UofL Disha Fix -> If answer is not given for specific div then do not create img
                {
                    var elem = document.createElement("img");
                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyonewman/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");       
                    document.getElementById(idhand + i + slot).appendChild(elem);
                }                
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    });
};
M.qtype_easyonewman.init_showcorrectanswer = function(Y, moodle_version, slot, moodleroot, stagoreclip) {
    var handleSuccess = function(o) {};
    var handleFailure = function(o) { /*failure handler code*/
        };
    var callback = {
        success: handleSuccess,
        failure: handleFailure
    };
    if (moodle_version >= 2012120300) { //Moodle 2.4 or higher
        YAHOO = Y.YUI2;
    }
    var idhand = 'pos';
    if (stagoreclip === '1') {
        idhand = 'epos';
    }
    var refreshBut = Y.one("#correctanswer" + slot, slot);
    refreshBut.on("click", function() {
        var textfieldid = 'correct_answer' + slot;
        if (document.getElementById(textfieldid) && document.getElementById(textfieldid).value !== '') {
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");
            for (var i = 0; i < 6; i++) {           
                div = document.getElementById(idhand + i + slot);
                div.innerHTML = '';
                group = groups[i] === "6" ? "" :groups[i];
                if(group !=="" ) //UofL Disha Fix -> If answer is not given for specific div then do not create img
                {
                    var elem = document.createElement("img");
                    //delete existing image

                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyonewman/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    document.getElementById(idhand + i + slot).appendChild(elem);
                }
                document.getElementById("apos" + i + slot).value = group;
            }
        }
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
                Y.one('#d'+dragId+slot).set('innerHTML','');
                Y.one('#d'+dragId+slot).appendChild(drag);
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
        var lis = Y.Node.all('.dragableimg'+slot);
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
        var uls = Y.Node.all('.dropablediv'+slot);
        uls.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });
    });
};
M.qtype_easyonewman.insert_easyonewman_answer_pagenavigation = function(Y, slot, moodleroot, stagoreclip, usageid) {
        // var topnode = 'div.que.easyonewman#q' + slot;
        var topnode = 'div.que.easyonewman#question-' + usageid + '-' + slot;
        var answer = Y.one(topnode + ' input.answer').get('value');
        
        if (answer !== "" && answer !== '-----') {
            var groups = answer.split("-");
            var idhand = 'pos';
            if (stagoreclip === '1') {
                idhand = 'epos';
            }
            for (var i = 0; i < 6; i++) {
                group = groups[i];
                if(group !=="") //UofL Disha Fix -> If answer is not given for specific div then do not create img
                {
                    var elem = document.createElement("img");
                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyonewman/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    elem.setAttribute("class", "yui3-dd-drop yui3-dd-draggable dragableimg"+slot);
                    document.getElementById(idhand + i + slot).appendChild(elem);
                }
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    }