M.qtype_easyofischer = {
    insert_easyofischer_applet: function(Y, topnode, numofstereo, feedback, readonly, stripped_answer_id, slot) {
        var inputdiv = Y.one(topnode);
        inputdiv.ancestor('form').on('submit', function() {
            var iterations = 2 * numofstereo + 2;
            var arr = new Array(iterations);
            for (var i = 0; i < iterations; i++) {
                if (document.getElementById('apos' + i + slot).value !== '') {
                    arr[i] = document.getElementById('apos' + i + slot).value;
                    arr[i] = arr[i].substring(0, arr[i].length - 1) + '6';
                } else {
                   // arr[i] = 'h6'
                }
            }
            textfieldid = topnode + ' input.answer';
            orderstring = arr.join("-");
            Y.one(topnode + ' input.answer').set('value', orderstring);
            Y.one(topnode + ' input.mol').set('value', orderstring);
        }, this);
    }
}
M.qtype_easyofischer2 = {
    insert_structure_into_applet: function(Y, slot, numofstereo, moodleroot) {
        var textfieldid = 'my_answer' + slot;
        if (document.getElementById(textfieldid) && document.getElementById(textfieldid).value !== '') {
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");
            if (numofstereo === '1') {
                idhand = 'epos';
            }
            for (var i = 0; i <= groups.length - 1; i++) {
                // U of L Masoum -- when the answer is empty
                group = groups[i] === "6" ? "" :groups[i];
                if(group !== "") // U of L Masoum -- If answer is not given for specific div then do not create img
                {
                    var elem = document.createElement("img");
                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyofischer/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    document.getElementById("pos" + i + slot).appendChild(elem);
                }
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    }
}
M.qtype_easyofischer.init_showmyresponse = function(Y, moodle_version, slot, numofstereo, moodleroot) {
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
    var refreshBut = Y.one("#myresponse" + slot, slot, numofstereo);
    refreshBut.on("click", function() {
        var textfieldid = 'my_answer' + slot; //U of L Masoum -- changing response for more than 1 questions on review page.
        if (document.getElementById(textfieldid) && document.getElementById(textfieldid).value != '') {
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");
            var positions = 2 * numofstereo + 2;
            for (var i = 0; i < positions; i++) {
                group = groups[i];
                var elem = document.createElement("img");
                div = document.getElementById('pos' + i + slot);
                div.innerHTML = '';
                if(group !=="")// U of L Masoum --  If answer is not given for specific div then do not create img
                {
                    var elem = document.createElement("img");
                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyofischer/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    document.getElementById("pos" + i + slot).appendChild(elem);
                }
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    });
};
M.qtype_easyofischer.init_showcorrectanswer = function(Y, moodle_version, slot, numofstereo, moodleroot) {
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
    var refreshBut = Y.one("#correctanswer" + slot, slot, numofstereo);
    refreshBut.on("click", function() {
        var textfieldid = 'correct_answer' + slot;
        if (document.getElementById(textfieldid) && document.getElementById(textfieldid).value !== '') {
            var s = document.getElementById(textfieldid).value;
            var groups = s.split("-");
            var positions = 2 * numofstereo + 2;
            for (var i = 0; i < positions; i++) {
                var elem = document.createElement("img");
                div = document.getElementById('pos' + i + slot);
                div.innerHTML = '';
                // U of L Masoum -- when the answer is empty
                group = groups[i] === "6" ? "" :groups[i];
                trimgroup = group.substring(0, group.length - 1);
                elem.setAttribute("src", moodleroot + "/question/type/easyofischer/pix/" + trimgroup + ".png");
                elem.setAttribute("id", group);
                elem.setAttribute("height", "30");
                elem.setAttribute("width", "40");
                document.getElementById("pos" + i + slot).appendChild(elem);
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    });
};
M.qtype_easyofischer.dragndrop = function(Y, slot) {
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

            //U Of L Listen to drag:drophit events
            var dragId = drag.get('id');
            var dropId = drop.get('id');
            var parentDragId = drag.get('parentNode')?drag.get('parentNode').get('id'):'';
            var parentDropId = drop.get('parentNode')?drop.get('parentNode').get('id'):'';
            if(parentDragId.charAt(0) === 'd' && parentDropId.startsWith('divnew')) //when drag from images div to answer div
            {
                drop.get('childNodes').remove();
                drop.appendChild(drag);
                document.getElementById('a' + dropId).value = dragId;
            }
            else if((parentDragId.charAt(0) === 'p' || parentDragId.charAt(0) === 'e') && parentDropId.startsWith('divnew')) //when drag from answer div to answer div
            {
                drop.get('childNodes').remove();
                drop.appendChild(drag);
                document.getElementById('a' + dropId).value = dragId;
                document.getElementById('a' + parentDragId).value = ''; //clear answer hidden value
            }
            else if(parentDragId.charAt(0) === 'p' || parentDragId.charAt(0) === 'e')// when drag from answer div to images div
            {
                document.getElementById('a' + parentDragId).value = '';//clear answer hidden value
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
        var lis = Y.Node.all('.dragableimg'+ slot);
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
        var uls = Y.Node.all('.dropablediv'+ slot);
        uls.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });
    });
};
M.qtype_easyofischer.insert_easyofischer_answer_pagenavigation = function(Y, slot, moodleroot, numofstereo, usageid) {
        // var topnode = 'div.que.easyofischer#q'+slot;
        var topnode = 'div.que.easyofischer#question-'+usageid+'-'+slot;
        var answer = Y.one(topnode + ' input.answer').get('value');
        //U of L Masoum -- differentiates the answers for numofstereo
        if(numofstereo == 1){
            var selectanswer = "---";
        } else if(numofstereo == 2){
            var selectanswer = "-----";
        } else if (numofstereo == 3){
            var selectanswer = "-------";

        } else if (numofstereo == 4){
            var selectanswer = "---------";
        }

        if (answer !== "" && answer !== selectanswer) {
            var groups = answer.split("-");
           
            for (var i = 0; i < groups.length; i++) {
                group = groups[i];
                if(group !=="")
                {
                    var elem = document.createElement("img");
                    trimgroup = group.substring(0, group.length - 1);
                    elem.setAttribute("src", moodleroot + "/question/type/easyofischer/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", group);
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    elem.setAttribute("class", "yui3-dd-drop yui3-dd-draggable dragableimg"+slot);
                    document.getElementById('pos' + i + slot).appendChild(elem);
                }
                document.getElementById("apos" + i + slot).value = group;
            }
        }
    }
