
jQuery(document).ready(function() {
    jQuery('#tozny_activate').on('click', function () {
        // User checked the activate tozny control.
        // Callback to WP to have WP create a new key for this WP user, then reply with a URL to the new key.
        // Read the new key URL from the callback's response and update the #tozny_activate_description contents to
        // display the QR.
        if (jQuery(this).attr('checked') === 'checked') {
            var request_data = {
                'action': 'create_tozny_user'
            };
            jQuery.ajax({
                url: ajax_object.ajax_url,
                data: request_data,
                dataType: 'json',
                success: function (response,_textStatus,_jqXHR) {
                    console.log(response);
                    if (response['success']) {
                        var img = '<img src="' + response['data']['secret_enrollment_qr_url'] +'" id="qr" class="center-block" style="height: 200px; width: 200px;" />';
                        var link =  '<a href="' + response['data']['secret_enrollment_url'] + '">'+img+'</a>'
                        jQuery('#tozny_activate_description').append('<div style="margin-top: 10px;">' + link + '</div>');
                    } else {
                        alert('Could not create a new Tozny user.');
                    }
                },
                error: function (_jqXHR,textStatus, errorThrown ) {
                    console.log('Could not create new Tozny user: '+ textStatus + " -- " +errorThrown);
                    alert('Could not complete request to create a new Tozny user.');
                }
            });
        }
        else {
            jQuery('#tozny_activate_description strong').empty();
        }
    });
});