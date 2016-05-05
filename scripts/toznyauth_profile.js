
jQuery( document ).ready(function() {
    jQuery( document.getelementById( 'tozny_activate' ) ).on( 'click', function () {
            var request_data = {
                'action': 'create_tozny_user'
            };
            jQuery.ajax( {
                url: ajax_object.ajax_url,
                data: request_data,
                dataType: 'json',
                success: function (response,_textStatus,_jqXHR) {
                    'console' in window && console.log(response);
                    if (response['success']) {
                        var template = jQuery( document.getElementById( 'device_setup_template' ) ).html();
                        var data = response['data'];
                        for (var field in data) {
                            if (data.hasOwnProperty(field)) {
                                template = template.replace(new RegExp('\\{\\{' + field + '\\}\\}', 'g'), data[field]);
                            }
                        }
                        jQuery( document.getElementById( 'enrollment_qr' ) ).empty().append(template);
                        tb_show('TOZNY: Your phone is the key.', '#TB_inline?=true&height=540&width=590&inlineId=enrollment_qr');
                    } else {
                        alert('Could not create a new Tozny user.');
                    }
                },
                error: function (_jqXHR,textStatus, errorThrown ) {
                    'console' in window && console.log('Could not create new Tozny user: '+ textStatus + " -- " +errorThrown);
                    alert('Could not complete request to create a new Tozny user.');
                }
            } );
            return false;
    } );
} );