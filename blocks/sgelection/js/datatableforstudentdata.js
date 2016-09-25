  function datatable_for_student_data(Y, whichcolstouse, data1){
     data1 = JSON.parse(data1);
        var whichcolstouse = whichcolstouse;
        var cols = '';
     
      YUI().use("datatable-sort", function(Y) {
        var collegecols = [
            {key:"college", label:"Click to Sort by College", sortable:true},
            {key:"count", label:"Click to Sort by Count", sortable:true},
        ],
        majorcols = [
            {key:"major", label:"Click to Sort by Major", sortable:true},
            {key:"count", label:"Click to Sort by Count", sortable:true},
        ],
        yearcols = [
            {key:"year", label:"Click to Sort by Year", sortable:true},
            {key:"count", label:"Click to Sort by Count", sortable:true},
        ],
        courseloadcols = [
            {key:"courseload", label:"Click to Sort by Part / Full Time", sortable:true},
            {key:"count", label:"Click to Sort by Count", sortable:true},
        ],
        ipcols = [
            {key:"ip_address", label:"Click to Sort by IP Address / Area", sortable:true},
            {key:"count", label:"Click to Sort by Count", sortable:true},
        ],
        timecols = [
            {key:"time", label:"Click to Sort by Time", sortable:true},
            {key:"count", label:"Click to Sort by Count", sortable:true},
        ];
        if(whichcolstouse == 'college'){
            cols = collegecols;
        }else if(whichcolstouse == 'major'){
            cols = majorcols;
        }else if(whichcolstouse == 'year'){
            cols = yearcols;
        }else if(whichcolstouse == 'courseload'){
            cols = courseloadcols;
        }else if(whichcolstouse == 'ip'){
            cols = ipcols;
        }else if(whichcolstouse == 'time'){
            cols = timecols;
        }
        var data = data1,

        table = new Y.DataTable({
            columns: cols,
            data   : data,
            summary: "Contacts list",
            caption: " "
        }).render("#tophat");
    });
    
}
