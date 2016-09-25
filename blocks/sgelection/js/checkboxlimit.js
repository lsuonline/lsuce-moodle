function checkboxlimit(Y, checkgroup, limit, officenumber){

        var checkgroup=document.querySelectorAll('.candidate_office_'+checkgroup);
	var limit=limit;
        var officenumber=officenumber;
  YUI().use('node', function(Y) {

	for (var i=0; i<checkgroup.length; i++){
		checkgroup[i].onclick=function(){
		var checkedcount=0;
		for (var i=0; i<checkgroup.length; i++)
			checkedcount+=(checkgroup[i].checked)? 1 : 0;
		if (checkedcount>limit){
                        document.getElementById("hiddenCandidateWarningBox_"+officenumber).style.display="block";
			this.checked=false;
                        var makeBoxDisappear=setInterval(function() {boxdisappears()}, 5000);
                        function boxdisappears() {
                            document.getElementById("hiddenCandidateWarningBox_"+officenumber).style.display="none";
                        }		}
                else{
                        document.getElementById("hiddenCandidateWarningBox_"+officenumber).style.display="none";
                }
            }
	}
    });
}