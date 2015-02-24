<?php
 /* Template applied into the ThickBox, when a user, from their profile page, selects the control to add a new device. */
?>
<table id="device_setup">
    <tbody>
    <tr>
        <td colspan="2" class="instruct">
            <h4>1. Download the app</h4>
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
            <h4>2. Use the app to scan the QR code below</h4>
            <div id="qr_container">
                <p>If you're on your mobile phone, simply click it.</p>
                <a href="{{secret_enrollment_url}}">
                    <img src="{{secret_enrollment_qr_url}}" id="qr">
                </a>
                <p>(Pssst - Don't share this QR code with anyone. It's unique to you.)</p>
            </div>
        </td>
    </tr>

    </tbody>
</table>