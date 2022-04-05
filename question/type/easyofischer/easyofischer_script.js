M.qtype_easyofischer = {
    insert_structure_into_applet: function(Y, numofstereo) {
        var textfieldid = 'id_answer_0';
        if (document.getElementById(textfieldid).value !== '') {

            var s = document.getElementById(textfieldid).value;
            var positions = 2 * numofstereo + 2;
            var groups = s.split("-");
            var curlength = groups.length;
            // Adjust for changes in num of stereo if needed.
            if (curlength < positions) {
                for (var i = 1; i <= (positions - curlength); i++) {
                   groups.push('h6')
                }
            }
            var host = "http://"+window.location.hostname;

            for (var i = 0; i < positions; i++) {
                var elem = document.createElement("img");
                group = groups[i];
                trimgroup = group.substring(0, group.length - 1);
                if (trimgroup !== ""){ // U of L Masoum --  If answer is not given for specific div then do not create img
                    elem.setAttribute("src", "type/easyofischer/pix/" + trimgroup + ".png");
                    elem.setAttribute("id", trimgroup+"5");
                    elem.setAttribute("height", "30");
                    elem.setAttribute("width", "40");
                    elem.setAttribute("class", "yui3-dd-drop yui3-dd-draggable dragableimgslot");
                    document.getElementById("pos"+ i).appendChild(elem);
                    //document.getElementById("apos" + i).value = group;
                    document.getElementById("apos" + i).value = trimgroup+"5";
                }
            }
        }
    }
}
M.qtype_easyofischer.init_reload = function(Y, url, htmlid) {
    var handleSuccess = function(o) {
            Y.all('input[id^="apos"]').set('value', '');
            Y.all('input[id^="id_answer_"]').set('value', '');
            fischer_template.innerHTML = o.responseText;
            M.qtype_easyofischer.insert_structure_into_applet(Y, document.getElementById('id_numofstereo').value);
            M.qtype_easyofischer.dragndrop(Y, '1');
        }
    var handleFailure = function(o) { /*failure handler code*/
        }
    var callback = {
        success: handleSuccess,
        failure: handleFailure
    }
    var button = Y.one("#id_numofstereo");
    button.on("change", function(e) {
        div = Y.YUI2.util.Dom.get(htmlid);
        Y.use('yui2-connection', function(Y) {
            newurl = url + document.getElementById('id_numofstereo').value;
            Y.YUI2.util.Connect.asyncRequest('GET', newurl, callback);
        });
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
            var dragId = drag.get('id');
            var dropId = drop.get('id');

            var parentDragId = drag.get('parentNode')?drag.get('parentNode').get('id'):'';
            var parentDropId = drop.get('parentNode')?drop.get('parentNode').get('id'):'';
            if(parentDragId.charAt(0) === 'd' && parentDropId.startsWith('divnew'))
            {
                drop.get('childNodes').remove();
                drop.appendChild(drag);
                document.getElementById('a' + dropId).value = dragId;

            }
            else if ((parentDragId.charAt(0) === 'p' || parentDragId.charAt(0) === 'e') && parentDropId.startsWith('divnew')){
                drop.get('childNodes').remove();
                drop.appendChild(drag);
                document.getElementById('a' + dropId).value = dragId;
                document.getElementById('a' + parentDragId).value = ''; //clear answer hidden value
            }
            else if(parentDragId.charAt(0) === 'p' || parentDragId.charAt(0) === 'e')// when drag from answer div to images div
            {
                document.getElementById('a' + parentDragId).value = '';//clear answer hidden value
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
        var uls = Y.Node.all('.dropabledivslot');
        uls.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });
    });
};

M.qtype_easyofischer.init_getanswerstring = function(Y, numofstereo) {
    var handleSuccess = function(o) {};
    var handleFailure = function(o) { /*failure handler code*/
        };
    var callback = {
        success: handleSuccess,
        failure: handleFailure
    };
    Y.all(".id_insert").each(function(node) {
        node.on("click", function() {
            numofstereo = document.getElementById('id_numofstereo').value;
            //U of L Masoum -- fetching id of div instead of button. Insert from editor functionality was not working
             var buttonid = node.one('button').getAttribute("id");

            var answerstring = '';
            var iterations = 2 * numofstereo + 2;
            var arr = new Array(iterations);
            for (var i = 0; i < iterations; i++) {
                if (document.getElementById('apos' + i).value != '') {
                    arr[i] = document.getElementById('apos' + i).value;
                    arr[i] = arr[i].substring(0, arr[i].length - 1) + '6';
                } else {
                    // arr[i] = 'h6'
                }
            }
            textfieldid = 'id_answer_' + buttonid.substr(buttonid.length - 1);
            document.getElementById(textfieldid).value = arr.join("-");
        });
    });
};
