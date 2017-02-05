M.local_cas_help_links = {};

M.local_cas_help_links.init_index = function(Y, userid) {
    $('#mform1').submit(function(event) {
        // get any validation errors from the url inputs
        var errors = M.local_cas_help_links.validate_url_input();

        // if errors, style appropriately, show error message, and prevent form submission
        if (errors.length) {
            $.each(errors, function(i, e) {
                $('#id_' + errors[i]).addClass('has-error');
            });

            // hide any success alerts
            $('.alert.alert-success').toggle(false);
            
            // show the notification header
            $('.error-notification-header.alert.alert-error').toggle(true);

            event.preventDefault();
        }
    });
};

M.local_cas_help_links.validate_url_input = function(Y) {
    var errors = [];

    // iterate through each URL text input
    $('input.url-input[type=text]').each(function(i, obj) {
        var input = $(this);

        // if invalid URL, add error object to the errors array
        if ( ! M.local_cas_help_links.is_valid_url(input.val())) {
            errors.push(input.prop('name'));
        }
    });

    return errors;
};

M.local_cas_help_links.is_valid_url = function(urlInput) {
    // if no URL is given, consider it valid
    if (urlInput.length == 0) {
        return true;
    }

    var match = urlInput.match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
    
    if (match == null) {
        return false;
    } else {
        return true;
    }
};
