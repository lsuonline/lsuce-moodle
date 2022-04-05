
var ulethLandingBlock = {
    // ==========================================================
    // variabls for this object
    // ==========================================================
    buildTermBlock: function (results, instance) {

        var this_instance = null,
            instance_name = null,
            this_url = null,
            this_html = null,
            course_count = 0,
            i = 0,
            result = "";

        result = JSON.parse(results);
        course_count = result.data.length;

        // console.log("buildTermBlock() -> What is the result: ", result);
        // console.log("buildTermBlock() -> What is the course count: " + course_count);

        for (i = 0; i < course_count; i++) {
        }

        return;

        if (this.storedData.legacy) {
            this_html = results;
        } else {
            // for some stupid reason an instance will occasionally add a back tick ` to the json obj, so let's remove it
            results = (results.length && results[0] == '`') ? results.slice(1) : results;
            // if it's legacy code and it wasn't detected the result will be html
            // so if it fails on the parse then just copy it over
            try {
                result = $.parseJSON(results);
                this_html = result.html;
            } catch (e) {
                this_html = results;
            }
        }

        instance_name = '#lb_expand_term_details_' + this_instance;
        this_url = $(instance_name).data('url');

        if (this_html === "" || this_html.length < 5 || this_html === "\nno user found" || this_html === "no user found") {
            this_html += '<div class="box coursebox"><a href="' + this_url + '">You are not registered in any courses here, click here to view</a></div>';
        }
        $(instance_name).html("");
        $(instance_name).html(this_html);

    },

    callStartups: function () {

        $('.accordion-heading').click(function (e) {
            var oTarget = $(e.target).parent();
            var oIcon = $('i', $('a[data-item-id="' + oTarget.attr('data-item-id') + '"]'));
            oIcon.toggleClass('icon-chevron-right icon-chevron-down ', 200);
        });

        // ASSIGNMENTS and QUIZZES
        $(".lb_assign_quiz_header").click(function () {
            $header = $(this);
            //getting the next element
            $content = $header.next();
            //open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
            $content.slideToggle(200, function () {
                // do something with the toggle if you want here
            });
        });
    },
};

document.onreadystatechange = function () {
    var state = document.readyState;
    if (state === 'complete') {
        ulethLandingBlock.callStartups();
    }
};