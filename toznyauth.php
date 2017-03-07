<?php
/**
 * Plugin Name: Tozny
 * Description: Add Tozny as an authentication option to your WordPress blog. Tozny lets your users log in with their phone. Its one or two-factor authentication system is easy to use, easy to integrate, and cryptographically secure.
 * Version: 	1.1.6
 * Author:      TOZNY, LLC
 * Author URI:  https://www.tozny.com
 * Plugin URI:  https://www.tozny.com#wordpress
 * License:     GPLv2
 * Text Domain: toznyauth
*/

/**
 *  Copyright 2014 - 2017 Tozny, LLC  (email: info@tozny.com)
 */

/**
 * Stop direct calls to this page
 */
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) die('Sorry, you don&#39;t have direct access to this page.');

//=====================================================================
require_once 'lib/tozny-sdk/ToznyRemoteUserAPI.php';
require_once 'lib/tozny-sdk/ToznyRemoteRealmAPI.php';
require_once 'src/RemoteUserAPI.php';  // WordPress-specific subclass
require_once 'src/RemoteRealmAPI.php'; // WordPress-specific subclass
//=====================================================================



//=====================================================================
function create_tozny_user_callback ()
{

    $json_response = array(
        'error' => "Unauthorized.",
        'status' => 403
    );

    $user = wp_get_current_user();
    if (current_user_can('edit_user',$user->ID) && ('on' === get_option('tozny_allow_users_to_add_devices')) ) {
        $API_URL = get_option('tozny_api_url');
        $REALM_KEY_ID = get_option('tozny_realm_key_id');
        $REALM_KEY_SECRET = get_option('tozny_realm_key_secret');
        $realm_api = new Remote_Realm_API($REALM_KEY_ID, $REALM_KEY_SECRET, $API_URL);
        $tozny_user = null;

        try {
            # 1.  Get the email address from wprdpress
            # 2.  lookup email address on tozny, to see if the users exists already, and we need to add a new device.
            $tozny_user = $realm_api->userGetEmail($user->user_email);
        } catch (Exception $e) {
            $json_response = array(
                'error' => 'Error while retrieving user record for given email',
                'detail' => array(
                    'message'  =>  $e->getMessage(),
                    'wp_email' => $user->user_email
                ),
                'status' => 400
            );
        }

        if (!is_null($tozny_user)) {
            # 3a. if the user does not exist, call real.user_add, paint the QR_url
            if ($tozny_user) {
                $new_device = $realm_api->realmUserDeviceAdd($tozny_user['user_id']);
                if ($new_device['return'] === 'ok') {
                    $json_response = array(
                        'secret_enrollment_url'    => $new_device['secret_enrollment_url'],
                        'secret_enrollment_qr_url' => $new_device['secret_enrollment_qr_url'],
                        'status' => 200
                    );
                } else {
                    $json_response = array(
                        'error' => 'Error while creating a new device key for the given email',
                        'detail' => array(
                            'message'       => array_shift($new_device['errors']['error_message']),
                            'wp_email'      => $user->user_email,
                            'tozny_user_id' => $tozny_user['user_id']
                        ),
                        'status' => 400
                    );
                }
            } # 3b. if the user does exists in tozny, add a new user
            else {
                try {
                    $realm_fields = $realm_api->fieldsGet();
                    if ($realm_fields['return'] !== 'ok') {
                        $json_response = array(
                            'error'  => 'Error while creating a new Tozny user field info',
                            'detail' => array(
                                'message'  => array_shift($realm_fields['errors']['error_message']),
                                'wp_email' => $user->user_email
                            ),
                            'status' => 400
                        );
                    }
                    else {
                        $user_meta = array();
                        foreach ($realm_fields['results'] as $field) {
                            // this will set like "tozny_email" and stuff like that
                            if (!empty($field['maps_to'])) {
                                switch ($field['maps_to']) {
                                    case "tozny_email":
                                        $user_meta[$field['field']] = $user->user_email;
                                        break;
                                    case "tozny_username":
                                        $user_meta[$field['field']] = $user->user_login;
                                        break;
                                }
                            }
                        }
                        $tozny_user = $realm_api->userAdd('true', $user_meta);
                        if ($tozny_user['return'] !== 'ok') {
                            $json_response = array(
                                'error'  => 'Error while creating a new Tozny user record & device key',
                                'detail' => array(
                                    'message'  => array_shift($tozny_user['errors']['error_message']),
                                    'wp_email' => $user->user_email
                                ),
                                'status' => 400
                            );
                        }
                        else {
                            $json_response = array(
                                'secret_enrollment_url'    => $tozny_user['secret_enrollment_url'],
                                'secret_enrollment_qr_url' => $tozny_user['secret_enrollment_qr_url'],
                                'status' => 200
                            );
                        }
                    }
                } catch (Exception $e) {
                    $json_response = array(
                        'error'  => 'Error while creating new user record for given email',
                        'detail' => array(
                            'message'  => $e->getMessage(),
                            'wp_email' => $user->user_email
                        ),
                        'status' => 400
                    );
                }
            }
        }
    }

    ob_clean(); // SEE: http://codex.wordpress.org/AJAX_in_Plugins#Debugging
    header('Content-Type: application/json'); // wp_send_json_error not setting the JSON header >.<
    if ($json_response['status'] !== 200) {
        wp_send_json_error($json_response);
    } else {
        wp_send_json_success($json_response);
    }
}
//=====================================================================

//=====================================================================
/**
 * Summary.
 *
 * Description.
 * @param $user_id
 */
function update_extra_profile_fields($user_id) {
    if ( current_user_can('edit_user',$user_id) ) {
        if (get_user_meta($user_id, 'tozny_activate', true) !== 'on' && $_POST['tozny_activate'] === 'on') {
            update_user_meta($user_id, 'tozny_create_user', true);
        }
        else {
            update_user_meta($user_id, 'tozny_create_user', false);
        }
        update_user_meta($user_id, 'tozny_activate', sanitize_text_field($_POST['tozny_activate']));
    }
}

/**
 * Update Tozny profile fields
 *
 * No nonce check is required here as this is not a custom form but a default WordPress form and callback.
 *
 * @param WP_User $user
 */
function extra_profile_fields($user) {
    if (current_user_can('edit_user',$user->ID) && ('on' === get_option('tozny_allow_users_to_add_devices')) ) {
?>
        <h3><?php esc_html_e( 'Tozny', 'toznyauth' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><span><?php esc_html_e( 'Want to login using Tozny?', 'toznyauth' ); ?></span></th>
                <td><a href="#" id="tozny_activate"><?php esc_html_e( 'Click here to add a new device.', 'toznyauth' ); ?></a>
                    <script id="device_setup_template" type="text/html">
                    <?php include("device_setup.php"); ?>
                    </script>
                    <div id="enrollment_qr" style="display: none; margin:0; padding:0;"></div>
                </td>
            </tr>
        </table>
<?php
    }
}

/**
 * Summary.
 *
 * Description.
 * Reports if the given Tozny Realm credentials were used to successfully authenticate to the Tozny API servers.
 */
function test_realm_key() {
    if(isset($_GET['settings-updated']) && $_GET['settings-updated'])
    {
        global $REALM_KEY_TEST_SUCCESS;
        global $REALM_KEY_TEST_MESSAGE;

        $API_URL = get_option('tozny_api_url');
        $REALM_KEY_ID = get_option('tozny_realm_key_id');
        $REALM_KEY_SECRET = get_option('tozny_realm_key_secret');
        $realm_api = new Remote_Realm_API($REALM_KEY_ID,$REALM_KEY_SECRET,$API_URL);
        try {
            $resp = $realm_api->realmKeysGet();
            if (array_key_exists('return', $resp) && $resp['return'] === 'ok') {
                $REALM_KEY_TEST_SUCCESS = true;
                $REALM_KEY_TEST_MESSAGE = 'Realm key credentials look good!';
            }
            else {
                $e = array_shift($resp['errors']);
                $REALM_KEY_TEST_SUCCESS = false;
                $REALM_KEY_TEST_MESSAGE = "Error while testing realm key credentials with Tozny. More info: ".esc_html($e['error_message']);
            }
        }
        catch (Exception $e) {
            $REALM_KEY_TEST_SUCCESS = false;
            $REALM_KEY_TEST_MESSAGE = "Error while testing realm key credentials with Tozny. More info: ".esc_html($e->getMessage());
        }
    }
}


/**
 * Validates a Tozny login attempt.
 *
 * No nonce check is required for $_POST validation as this is loading on the login page and not using the POSTed data
 * within WordPress.
 */
function process_tozny_login_attempt() {

    global $error;

    $API_URL = get_option('tozny_api_url');
    $REALM_KEY_ID = get_option('tozny_realm_key_id');
    $REALM_KEY_SECRET = get_option('tozny_realm_key_secret');
    $ALLOW_USERS_TO_ADD_DEVICES = get_option('tozny_allow_users_to_add_devices');

    if (!empty($_POST['tozny_action'])) {
        $tozny_signature = $_POST['tozny_signature'];
        $tozny_signed_data = $_POST['tozny_signed_data'];
        $redirect_to = (array_key_exists('redirect_to', $_POST) && !empty($_POST['redirect_to'])) ? $_POST['redirect_to'] : '/';
        $realm_api = new Remote_Realm_API($REALM_KEY_ID, $REALM_KEY_SECRET, $API_URL);
        if ($realm_api->verifyLogin($tozny_signed_data, $tozny_signature)) {
            $fields = null;
            $data = null;
            $user = null;

            try {
                $rawCall = $realm_api->fieldsGet();
                if (array_key_exists('return', $rawCall) && $rawCall['return'] === 'ok') {
                    $fields = $rawCall['results'];
                } else {
                    $more_info = (array_key_exists('return', $rawCall) && $rawCall['return'] === 'error') ? print_r($rawCall['errors'], true) : "";
                    $error = esc_html__( 'Error while retrieving fields from Tozny.', 'tozauth' ) . esc_html($more_info);
                }
            } catch (Exception $e) {
                $error = esc_html__( 'Error while retrieving fields from Tozny. More info: ', 'tozauth' ) . esc_html($e->getMessage());
            }

            try {
                $data = $realm_api->decodeSignedData($tozny_signed_data);
            } catch (Exception $e) {
                $error = esc_html__( 'Error while decoding signed data from Tozny. More info: ', 'tozauth' ) . esc_html($e->getMessage());
            }

            try {
                $user = $realm_api->userGet($data['user_id']);
            } catch (Exception $e) {
                $error = esc_html__( 'Error while retrieving user data from Tozny. More info: ', 'tozauth' ) . esc_html($e->getMessage());
            }

            // Dude, where's your monad?
            if (!empty($fields) && !empty($data) && !empty($user) && empty($error)) {
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
                    wp_safe_redirect($redirect_to);
                } // We did not found a corresponding WordPress user
                else {
                    $error = esc_html__( 'Could not find a WordPress user with a matching username or email address. Please contact your administrator.', 'tozauth' );
                }

            }

        } else {
            $error = esc_html__( 'Session verification failed. Please contact your administrator.', 'tozauth' );
        }
    }
} // add_tozny_lib

/**
 * Summary.
 *
 * Description.
 * Adds the Tozny javascript inline. TODO: move this into an enqueued asset.
 */
function add_tozny_script() {
    $REALM_KEY_ID = get_option('tozny_realm_key_id');
?>
        <div id="qr_code_login" style="margin: 0 auto; text-align: center;"></div>

        <input type="hidden" name="realm_key_id" value="<?php echo(esc_attr($REALM_KEY_ID)); ?>">

<?php
}

/**
 * Summary.
 *
 * Description.
 * Builds the left-hand admin nav item & icon for the Tozny plugin.
 */
function tozny_create_menu() {
    add_menu_page(
        __( 'Tozny Plugin Settings', 'toznyauth' ),
        __( 'Tozny', 'toznyauth' ),
        'administrator',
        __FILE__,
        'tozny_settings_page',
        plugins_url( '/images/icon.png', __FILE__ )
    );
    add_action( 'admin_init', 'register_tozny_settings' );
}

/**
 * Summary.
 *
 * Description.
 * Registers the config settings used by the Tozny plugin, used on the tozny_settings_page()
 */
function register_tozny_settings() {
    register_setting( 'tozny-settings-group', 'tozny_realm_key_id' );
    register_setting( 'tozny-settings-group', 'tozny_realm_key_secret' );
    register_setting( 'tozny-settings-group', 'tozny_api_url' );
    register_setting( 'tozny-settings-group', 'tozny_allow_users_to_add_devices' );
    register_setting( 'tozny-settings-group', 'tozny_modal_on_load' );
}

/**
 * Summary.
 *
 * Description.
 * Loads the Tozny javascript and CSS assets.
 */
function tozny_login_enqueue_scripts () {
    $API_URL = get_option('tozny_api_url');
    $REALM_KEY_ID = get_option('tozny_realm_key_id');
    $MODAL_ON_LOAD = get_option('tozny_modal_on_load');

    wp_register_style('tozny_style','https://s3-us-west-2.amazonaws.com/tozny/production/interface/javascript/v2/tozny.css');
    wp_register_style('toznyauth_login_style', plugins_url('/styles/toznyauth_login.css', __FILE__));
    wp_enqueue_style('tozny_style');
    wp_enqueue_style('toznyauth_login_style');

    wp_register_script('jquery_tozny','https://s3-us-west-2.amazonaws.com/tozny/production/interface/javascript/v2/jquery.tozny.js',array('jquery'));
    wp_enqueue_script('jquery_tozny');

    wp_register_script('toznyauth_login_script', plugins_url('/scripts/toznyauth_login.js', __FILE__), array('jquery'));
    wp_enqueue_script('toznyauth_login_script');
    wp_localize_script('toznyauth_login_script', 'tozny_login_config', array(
        'type'              => 'login',
        'style'             => ($MODAL_ON_LOAD) ? 'modal' : 'button',
        'realm_key_id'      => $REALM_KEY_ID,
        'api_url'           => $API_URL . 'index.php',
        'loading_image'     => $API_URL . 'interface/javascript/images/loading.gif',
        'login_button_image'=> $API_URL . 'interface/javascript/images/click-to-login-black.jpg',
        'form_type'         => 'custom',
        'form_id'           => 'loginform',
        'login_button_hide' => true,
        'debug'             => false
    ));
}

/**
 * Summary.
 *
 * Description.
 * Loads the toznyauth javascript assets.
 */
function tozny_profile_enqueue_scripts ($hook) {

    if ($hook === 'profile.php') {
        $user = wp_get_current_user();
        wp_register_script('toznyauth_profile_script', plugins_url('/scripts/toznyauth_profile.js', __FILE__), array('jquery'));
        add_thickbox();
        wp_register_style('toznyauth_profile_style', plugins_url('/styles/toznyauth_profile.css', __FILE__), array('thickbox'));
        wp_enqueue_style('toznyauth_profile_style');
        wp_enqueue_script('toznyauth_profile_script');
        wp_localize_script('toznyauth_profile_script', 'ajax_object', array(
            'ajax_url'              => admin_url('admin-ajax.php'),
            'user_id'               => $user->ID,
            'bad_user_request'      => esc_html__( 'Could not complete request to create a new Tozny user.', 'toznyauth' ),
            'could_not_create'      => esc_html__( 'Could not create a new Tozny user.', 'toznyauth' ),
            'your_phone_is_the_key' => esc_html__( 'TOZNY: Your phone is the key.', 'toznyauth' ),
        ));
    }

}
//=====================================================================

/**
 * Summary.
 *
 * Description.
 * Retrieves the Tozny distinguished fields from the given $fields array
 *
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
            case 'tozny_username':
                if ($field['uniq'] === 'yes')
                    $dist['tozny_username'][$field['field']] = $field;
                break;
            case 'tozny_email':
                if ($field['uniq'] === 'yes')
                    $dist['tozny_email'][$field['field']] = $field;
                break;
        }
    }

    return $dist;
}

/**
 * Summary.
 *
 * Description.
 * Used to capture & report the Tozny settings for the plugin.
 */
function tozny_settings_page() {
    global $REALM_KEY_TEST_SUCCESS;
    global $REALM_KEY_TEST_MESSAGE;
    ?>
    <div class="wrap">
        <h2><?php esc_html_e( 'Tozny', 'toznyauth' ); ?></h2>

        <form method="post" action="options.php">
            <?php settings_fields( 'tozny-settings-group' ); ?>
            <?php do_settings_sections( 'tozny-settings-group' ); ?>
            <?php if (isset($REALM_KEY_TEST_MESSAGE) && isset($REALM_KEY_TEST_SUCCESS)): ?>
            <div id="message" class="<?php echo(($REALM_KEY_TEST_SUCCESS) ? 'updated' : 'error'); ?>">
                    <p><strong><?php echo(esc_html($REALM_KEY_TEST_MESSAGE)); ?></strong></p>
            </div>
            <?php endif; ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API URL', 'toznyauth' ); ?></th>
                    <td><input type="text" name="tozny_api_url" value="<?php $api_url = get_option('tozny_api_url'); echo(empty($api_url) ? 'https://api.tozny.com/' : esc_url($api_url)); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Realm Key ID', 'toznyauth' ); ?></th>
                    <td><input type="text" name="tozny_realm_key_id" value="<?php echo(esc_attr(get_option('tozny_realm_key_id'))); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Realm Key Secret', 'toznyauth' ); ?></th>
                    <td><input type="text" name="tozny_realm_key_secret" value="<?php echo(esc_attr(get_option('tozny_realm_key_secret'))); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Allow users to add devices?', 'toznyauth' ); ?></th>
                    <td><input type="checkbox" name="tozny_allow_users_to_add_devices" <?php checked(get_option('tozny_allow_users_to_add_devices'), 'on'); ?> /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Show modal on login-page load?', 'toznyauth' ); ?></th>
                    <td><input type="checkbox" name="tozny_modal_on_load" <?php checked(get_option('tozny_modal_on_load'), 'on'); ?> /></td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }
//=====================================================================


//=====================================================================
// WordPress hooks.
//=====================================================================
add_action('admin_enqueue_scripts', 'tozny_profile_enqueue_scripts');
add_action('login_enqueue_scripts', 'tozny_login_enqueue_scripts');
add_action('login_init','process_tozny_login_attempt');
add_action('login_form','add_tozny_script');
add_action('admin_menu','tozny_create_menu');
add_action('load-toplevel_page_toznyauth/toznyauth','test_realm_key');

# user editing their own profile page.
add_action('personal_options_update', 'update_extra_profile_fields');
add_action('profile_personal_options', 'extra_profile_fields' );

# Ajax callbacks.
add_action( 'wp_ajax_create_tozny_user', 'create_tozny_user_callback' );
//=====================================================================