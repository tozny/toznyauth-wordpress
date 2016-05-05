<?php
 /* Template applied into the ThickBox, when a user, from their profile page, selects the control to add a new device. */
?>
<table id="device_setup">
    <tbody>
    <tr>
        <td colspan="2" class="instruct">
            <h4><?php esc_html_e( '1. Download the app', 'toznyauth' ); ?></h4>
            <table id="app_icons">
                <tbody><tr>
                    <td class="apple">
                        <a target="_blank" href="https://itunes.apple.com/us/app/tozny/id855365899?mt=8">
                            <img src="<?php echo(esc_url(plugins_url('/images/apple-available.png', __FILE__))); ?>" alt="">
                        </a>
                    </td>
                    <td class="android">
                        <a target="_blank" href="https://play.google.com/store/apps/details?id=com.tozny.authenticator">
                            <img src="<?php echo(esc_url(plugins_url('/images/android-available.png', __FILE__))); ?>" alt="">
                        </a>
                    </td>
                </tr>
            </tbody></table>
        </td>
    </tr>

    <tr>
        <td colspan="2" class="instruct">
            <h4><?php esc_html_e( '2. Use the app to scan the QR code below', 'toznyauth' ); ?></h4>
            <div id="qr_container">
                <p><?php esc_html_e( "If you're on your mobile phone, simply click it.", 'toznyauth' ); ?></p>
                <a href="{{secret_enrollment_url}}">
                    <img src="{{secret_enrollment_qr_url}}" id="qr" />
                </a>
                <p><?php esc_html_e( "(Pssst - Don't share this QR code with anyone. It's unique to you.)", 'toznyauth' ); ?></p>
            </div>
        </td>
    </tr>

    </tbody>
</table>