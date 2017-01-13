M.local_cas_help_links = {
    hoveroverlay : null
};

M.local_cas_help_links.init_index = function(Y, userid) {
    console.log('it happened');

    $('.display-toggle').change(function() {
        console.log('toggled: ' + $(this).prop('checked'));
        // $('#console-event').html('Toggle: ' + $(this).prop('checked'))
    })

};

M.local_cas_help_links.testing = function(Y, userid, courses) {
    var courses = JSON.parse(courses);
    console.log(courses);

    $.each(courses, function(id, course){
        $(".course-container").append("<p>" + course.fullname + "</p>");
    });
     
    console.log('done');
};