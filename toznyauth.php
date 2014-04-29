<?php
/*
Plugin Name: Tozny
Description: Add Tozny as an authentication option to your WordPress.
Version: 	0.0.1
Author: SEQRD, LLC
Author URI: http://www.tozny.com
Plugin URI: http://www.tozny.com
License: Other
Text Domain: toznyauth
*/

/*  Copyright 2014 - 2014 SEQRD, LLC  (email: info@tozny.com)
 */

/**
 * Stop direct calls to this page
 */
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) die('Sorry, you don&#39;t have direct access to this page.');

//=====================================================================
require_once 'ToznyRemoteUserAPI.php';
require_once 'ToznyRemoteRealmAPI.php';
//=====================================================================


//=====================================================================
// Wordpress hook callback functions.
//=====================================================================
add_action('login_head','add_tozny_lib');
add_action('login_form','add_tozny_script');
add_action('admin_menu', 'tozny_create_menu');
add_action('load-toplevel_page_toznyauth/toznyauth','test_realm_key');
//=====================================================================

function test_realm_key() {
    if(isset($_GET['settings-updated']) && $_GET['settings-updated'])
    {
        global $REALM_KEY_TEST_SUCCESS;
        global $REALM_KEY_TEST_MESSAGE;

        $API_URL = get_option('tozny_api_url');
        $REALM_KEY_ID = get_option('tozny_realm_key_id');
        $REALM_KEY_SECRET = get_option('tozny_realm_key_secret');
        $realm_api = new Tozny_Remote_Realm_API($REALM_KEY_ID,$REALM_KEY_SECRET,$API_URL);
        try {
            $resp = $realm_api->realmKeysGet();
            if (array_key_exists('return', $resp) && $resp['return'] === 'ok') {
                $REALM_KEY_TEST_SUCCESS = true;
                $REALM_KEY_TEST_MESSAGE = 'Realm key credentials look good!';
            }
            else {
                $e = array_shift($resp['errors']);
                $REALM_KEY_TEST_SUCCESS = false;
                $REALM_KEY_TEST_MESSAGE = "Error while testing realm key credentials with Tozny. More info: ".$e['error_message'];
            }
        }
        catch (Exception $e) {
            $REALM_KEY_TEST_SUCCESS = false;
            $REALM_KEY_TEST_MESSAGE = "Error while testing realm key credentials with Tozny. More info: ".$e->getMessage();
        }
    }
}

function add_tozny_lib() {

    global $error;

    $API_URL = get_option('tozny_api_url');
    $REALM_KEY_ID = get_option('tozny_realm_key_id');
    $REALM_KEY_SECRET = get_option('tozny_realm_key_secret');
    $ALLOW_USERS_TO_ADD_DEVICES = get_option('tozny_allow_users_to_add_devices');

    if (!empty($_POST['tozny_action'])) {
        $tozny_signature   = $_POST['tozny_signature'];
        $tozny_signed_data = $_POST['tozny_signed_data'];
        $redirect_to = (array_key_exists('redirect_to', $_POST) && !empty($_POST['redirect_to'])) ? $_POST['redirect_to'] : '/';
        $realm_api = new Tozny_Remote_Realm_API($REALM_KEY_ID,$REALM_KEY_SECRET,$API_URL);
        if ($realm_api->verifyLogin($tozny_signed_data,$tozny_signature)) {
            $fields = null;
            $data   = null;
            $user   = null;

            try {
                $rawCall = $realm_api->fieldsGet();
                if (array_key_exists('return', $rawCall) && $rawCall['return'] === 'ok') {
                    $fields = $rawCall['results'];
                } else {
                    $more_info = (array_key_exists('return', $rawCall) && $rawCall['return'] === 'error') ? print_r($rawCall['errors'], true) : "";
                    $error = $error = "Error while retrieving fields from Tozny.".$more_info;
                }
            }
            catch (Exception $e) {
                $error = "Error while retrieving fields from Tozny. More info: ".$e->getMessage();
            }

            try { $data = $realm_api->decodeSignedData($tozny_signed_data); }
            catch (Exception $e) {
                $error = "Error while decoding signed data from Tozny. More info: ".$e->getMessage();
            }

            try { $user = $realm_api->userGet($data['user_id']); }
            catch (Exception $e) {
                $error = "Error while retrieving user data from Tozny. More info: ".$e->getMessage();
            }

            // Dude, where's your monad?
            if ( !empty($fields) && !empty($data) && !empty($user) && empty($error)) {
                $wp_user = null;
                $distinguished_fields = distinguished($fields);
                foreach ($distinguished_fields as $distinguished_name => $fields) {
                    foreach ($fields as $field_name => $field) {
                        if (array_key_exists($field_name, $user['meta'])) {
                            switch ($distinguished_name) {
                                case 'tozny_username':
                                    $wp_user = get_user_by('login', $user['meta'][$field_name]);
                                    if ($wp_user) break 3;
                                    break;
                                case 'tozny_email':
                                    $wp_user = get_user_by('email', $user['meta'][$field_name]);
                                    if ($wp_user) break 3;
                                    break;
                            }
                        }
                    }
                }
                // We found a corresponding WordPress user
                if ($wp_user) {
                    wp_set_auth_cookie($wp_user->ID);
                    wp_set_current_user($wp_user->ID);
                    wp_redirect($redirect_to);
                }
                // We did not found a corresponding WordPress user
                else {
                    $error = "Could not find a Wordpress user with a matching username or email address. Please contact your administrator.";
                }

            }

        } else {
            $error = 'Session verification failed. Please contact your administrator.';
        }
    }
    displayToznyJavaScript($API_URL);
} // add_tozny_lib


function add_tozny_script() {

    global $error;

    $API_URL = get_option('tozny_api_url');
    $REALM_KEY_ID = get_option('tozny_realm_key_id');

    try {
        $userApi = new Tozny_Remote_User_API($REALM_KEY_ID, $API_URL);
        $challenge = $userApi->loginChallenge();
        displayToznyForm(
            $API_URL,
            $REALM_KEY_ID,
            $challenge['session_id'],
            $challenge['qr_url'],
            $challenge['mobile_url']
        );
    } catch (Exception $e) {
        $error = "An error occurred while attempting to generate a Tozny login challenge. More Info: ". $e->getMessage();
    }
}

function tozny_create_menu() {
    add_menu_page('Tozny Plugin Settings', 'Tozny', 'administrator', __FILE__, 'tozny_settings_page',plugins_url('/images/icon.png', __FILE__));

    add_action( 'admin_init', 'register_tozny_settings' );
}


function register_tozny_settings() {
    register_setting( 'tozny-settings-group', 'tozny_realm_key_id' );
    register_setting( 'tozny-settings-group', 'tozny_realm_key_secret' );
    register_setting( 'tozny-settings-group', 'tozny_api_url' );
    register_setting( 'tozny-settings-group', 'tozny_allow_users_to_add_devices' );
}
//=====================================================================

/**
 * @param $fields
 * @return array An Array containing the given fields, keyed first by their tozny distinguished field name, then by the individual field names.
 */
function distinguished($fields) {
    $dist = array(
        'tozny_username' => array(),
        'tozny_email'    => array()
    );

    foreach ($fields as $field) {
        switch($field['maps_to']) {
            case "tozny_username":
                if ($field['uniq'] === 'yes')
                    $dist['tozny_username'][$field['field']] = $field;
                break;
            case "tozny_email":
                if ($field['uniq'] === 'yes')
                    $dist['tozny_email'][$field['field']] = $field;
                break;
        }
    }

    return $dist;
}

//=====================================================================
// HTML display functions.
//=====================================================================
function displayToznyJavaScript ($api_url) {
?>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="<?= $api_url . 'interface/javascript/jquery.tozny.js' ?>"></script>
<?php
}


function displayToznyForm($api_url, $realm_key_id, $session_id, $qr_url, $mobile_url) {
?>
    <div id="qr_code_login"></div>

    <input type="hidden" name="realm_key_id" value="<?= htmlspecialchars($realm_key_id) ?>">

    <script type="text/javascript">
        $(document).ready(function() {
            $('#qr_code_login').tozny({
                'type'              : 'verify',
                'realm_key_id'      : '<?= $realm_key_id ?>',
                'session_id'        : '<?= $session_id ?>',
                'qr_url'            : '<?= $qr_url ?>',
                'api_url'           : '<?= $api_url . 'index.php' ?>',
                'loading_image'     : '<?= $api_url ?>interface/javascript/images/loading.gif',
                'login_button_image': '<?= $api_url ?>interface/javascript/images/click-to-login-black.jpg',
                'mobile_url'        : '<?= $mobile_url ?>',
                'form_type'         : 'custom',
                'form_id'           : 'loginform',
                'debug'             : true
            });

        });
    </script>

<?php
}


function tozny_settings_page() {
    global $REALM_KEY_TEST_SUCCESS;
    global $REALM_KEY_TEST_MESSAGE;
    ?>
    <div class="wrap">
        <h2>Tozny</h2>

        <form method="post" action="options.php">
            <?php settings_fields( 'tozny-settings-group' ); ?>
            <?php do_settings_sections( 'tozny-settings-group' ); ?>
            <?php if (isset($REALM_KEY_TEST_MESSAGE) && isset($REALM_KEY_TEST_SUCCESS)): ?>
            <div id="update_status" style="background-color: <?= ($REALM_KEY_TEST_SUCCESS) ? "green" : "red" ?>; color:white; padding: 10px;">
                <span><?= $REALM_KEY_TEST_MESSAGE ?></span>
            </div>
            <?php endif; ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API URL</th>
                    <td><input type="text" name="tozny_api_url" value="<?= get_option('tozny_api_url') ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Realm Key ID</th>
                    <td><input type="text" name="tozny_realm_key_id" value="<?= get_option('tozny_realm_key_id') ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Realm Key Secret</th>
                    <td><input type="text" name="tozny_realm_key_secret" value="<?= get_option('tozny_realm_key_secret') ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Allow users to add devices?</th>
                    <td><input type="checkbox" name="tozny_allow_users_to_add_devices" <?php if ( 'on' == get_option('tozny_allow_users_to_add_devices') ) echo 'checked="checked"'; ?> /></td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }
//=====================================================================