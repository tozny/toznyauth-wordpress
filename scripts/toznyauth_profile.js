
jQuery( document ).ready(function() {
    jQuery( document.getElementById( 'tozny_activate' ) ).on( 'click', function () {
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
                        tb_show( ajax_object.your_phone_is_the_key, '#TB_inline?=true&height=540&width=590&inlineId=enrollment_qr' );
                    } else {
                        alert( ajax_object.could_not_create );
                    }
                },
                error: function (_jqXHR,textStatus, errorThrown ) {
                    'console' in window && console.log( ajax_object.could_not_create + ' ' + textStatus + " -- " + errorThrown );
                    alert( ajax_object.bad_user_request );
                }
            } );
            return false;
    } );
} );