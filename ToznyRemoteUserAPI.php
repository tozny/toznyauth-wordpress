<?php
/**
 * ToznyRemoteUserAPI.php
 *
 * @package default
 */


/**
 * The Remote Tozny User API.
 *
 * This is the interface for the PHP Remote API for Tozny's login system.
 *
 * PHP version 5
 *
 * LICENSE: Copyright Tozny LLC, All Rights Reserved
 *
 * @category   Security
 * @package    Tozny
 * @author     Isaac Potoczny-Jones <ijones@tozny.com>
 * @copyright  2013 Tozny LLC
 * @version    git: $Id$
 * @link       https://www.tozny.com
 * @since      File available since Release 1.0
 */

/**
 * The Remote Tozny User API
 *
 * This is the interface for the PHP Remote User API for Tozny's login system.
 *
 * @category   Security
 * @package    Tozny
 * @author     Isaac Potoczny-Jones <ijones@tozny.com>
 * @copyright  2013 Tozny LLC
 * @link       https://www.tozny.com
 * @since      Class available since Release 1.0
 */
class Tozny_Remote_User_API
{

    /**
     * The Realm Key ID that this user is interacting with.
     * Usually a random string.
     *
     * @access private
     * @var string
     */
    private $_realm_key_id;

    /**
     * The Challenge package, once loginChallenge has been called.
     *
     * @access private
     * @var Tozny_Challenge
     */
    private $_challenge;
    private $_ocra_wrapper;
    private $_api_url;

    const DEFAULT_OCRA_SUITE = "OCRA-1:HOTP-SHA1-6:QH10-S";


    /**
     * Build this class based on the remote site's key ID.
     *
     * @param unknown $in_realm_key_id
     * @param unknown $in_api_url      (optional)
     */
    function __construct( $in_realm_key_id, $in_api_url = NULL)
    {
        # locate the tozny-common library on the include path.
        $paths = explode(PATH_SEPARATOR, get_include_path());
        $foundCommon = false;
        foreach ($paths as $path) {
            if (file_exists($path . '/OCRAWrapper.php')) {
                $foundCommon = true;
                break;
            }
        }

        # if we couldnt find it, see if it's packaged as a library for distribution
        if (!$foundCommon) {
            if (file_exists(__DIR__.'/tozny_common/OCRAWrapper.php')) {
                set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/tozny_common');
                $foundCommon = true;
            }
        }

        # if we couldnt find it, add the /var/www/library/tozny_common directory exists and is readable, then add it to the include path.
        if (!$foundCommon) {
            if (!file_exists('/var/www/library/tozny_common/OCRAWrapper.php')) {
                throw new Exception(sprintf("Could not locate Tozny Common library. Is it on the include path and readable? include_path: %s", get_include_path()));
            }
            set_include_path(get_include_path() . PATH_SEPARATOR . '/var/www/library/tozny_common');
        }

        require_once 'OCRAWrapper.php';

        $this->_realm_key_id = $in_realm_key_id;
        $this->_ocra_wrapper = new Tiqr_OCRAWrapper(self::DEFAULT_OCRA_SUITE);

        if ($in_api_url) {
            $this->_api_url = $in_api_url;
        } else {
            $apiTmp = getenv("API_URL");
            if ($apiTmp != false) {
                $this->_api_url = $apiTmp;
            } else {
                //TODO: Error
            }
        }
    }


    /**
     * Returns data about this realm.
     *
     * @return Array with realm_id, logo_url, info_url, display_name.
     */
    function realmGet()
    {
        $decodedValue = $this->rawCall(array('method' => 'user.realm_get', 'realm_key_id'  => $this->_realm_key_id));
        return $decodedValue;
    }


    /**
     * Return the login challenge for this realm. Can return an error
     * if the realm does not exist.
     *
     * @return Tozny_Challenge | error
     */
    function loginChallenge()
    {
        $decodedValue = $this->rawCall (array('method' => 'user.login_challenge','realm_key_id' => $this->_realm_key_id));
        //TODO: Handle error
        $this->_challenge = $decodedValue;
        return $decodedValue;
    }


    /**
     * Performs the login action by generating a response to the challenge.
     * Requires that login_challenge is called first.
     *
     * @param Tozny_User The user for this login.
     * if the login was successful | error
     * @param unknown $user
     * @return array result, which is a signed payload
     */
    function loginResult($user)
    {
        return $this->loginResultRaw ($user, $this->_challenge);
    }


    /**
     * Like login_result, but doesn't require that login_challenge be called.
     *
     * @param Tozny_User The user for this login.
     * @param Tozny_Challenge The challenge
     * if the login was successful | error
     * @param unknown $user
     * @param unknown $challenge
     * @return array result, which is a signed payload
     */
    function loginResultRaw($user, $challenge, $type = 'HMAC')
    {
        $args = array(
            'method'       => 'user.login_result',
            'realm_key_id' => $this->_realm_key_id,
            'user_id'      => $user['user_id'],
            'user_key_id'  => $user['user_key_id'],
            'session_id'   => $challenge['session_id']
        );

        $response = '';

        if ($type == 'HMAC') {
            $response = $this->_ocra_wrapper->calculateResponse(
                $user['user_secret'],
                $challenge['challenge'],
                $challenge['session_id']
            );
        }
        else if ($type == 'RSA') {
            $payload = $this->base64UrlEncode(json_encode(array(
                'challenge'  => $challenge['challenge'],
                'session_id' => $challenge['session_id'],
                'expires_at' => time() + 60 * 5 // server-side check is 5 min
            )));

            if (!openssl_sign(
                $payload,
                $signature,
                $user['user_secret'],
                OPENSSL_ALGO_SHA256
            )) {return false;}

            $envelope['signed_data'] = $payload;
            $envelope['signature']   = $this->base64UrlEncode($signature);

            $response = json_encode($envelope);

            $args['login_type'] = 'RSA';
        }

        if ($response == '') {
            return false;
        }

        $args['response'] = $response;
        //TODO: If null, return an error
        $signed_data = $this->rawCall($args);
        //TODO: Handle errors
        return $signed_data;
    }


    /**
     * Like login_result, but doesn't require that login_challenge be called.
     *
     * @param Tozny_User The user for this login.
     * @param Tozny_Challenge The challenge
     * if the login was successful | error
     * @param unknown $user
     * @param unknown $challenge
     * @return array result, which is a signed payload
     */
    function questionResultRaw($user, $challenge, $answer, $type = 'HMAC')
    {
        $args = array(
            'method'       => 'user.question_result',
            'realm_key_id' => $this->_realm_key_id,
            'user_id'      => $user['user_id'],
            'user_key_id'  => $user['user_key_id'],
            'session_id'   => $challenge['session_id'],
            'answer'       => $answer
        );

        $response = '';

        if ($type == 'HMAC') {
            $response = $this->_ocra_wrapper->calculateResponse(
                $user['user_secret'],
                $challenge['challenge'],
                $challenge['session_id']
            );
        }
        else if ($type == 'RSA') {
            $payload = $this->base64UrlEncode(json_encode(array(
                'challenge'  => $challenge['challenge'],
                'session_id' => $challenge['session_id'],
                'expires_at' => time() + 60 * 5 // server-side check is 5 min
            )));

            if (!openssl_sign(
                $payload,
                $signature,
                $user['user_secret'],
                OPENSSL_ALGO_SHA256
            )) {return false;}

            $envelope['signed_data'] = $payload;
            $envelope['signature']   = $this->base64UrlEncode($signature);

            $response = json_encode($envelope);

            $args['login_type'] = 'RSA';
        }

        if ($response == '') {
            return false;
        }

        $args['response'] = $response;
        //TODO: If null, return an error
        $signed_data = $this->rawCall($args);
        //TODO: Handle errors
        return $signed_data;
    }



    /**
     * Add this user to the given realm.
     *
     * @param string  $defer    (optional) Whether to use deferred enrollment. Defaults false.
     * @param unknown $metadata (optional)
     * @return The Tozny_API_User object if successful.
     */
    function userAdd($defer = 'false', $metadata = NULL, $pub_key = NULL)
    {
        $args = array(
            'method'       => 'user.user_add',
            'defer'        => $defer,
            'pub_key'      => $pub_key,
            'realm_key_id' => $this->_realm_key_id
        );

        if (!empty($metadata)) {
            $extras = self::base64UrlEncode(json_encode($metadata));
            $args['extra_fields'] = $extras;
        }

        $user_arr = $this->rawCall($args);
        //TODO: Handle errors

        return $user_arr;
    }


    /**
     * For deferred user enrollment, complete the enrollment
     *
     * @param string  $user_temp_key The temporary user key
     * @return The new user data.
     */
    function userAddComplete($user_temp_key)
    {
        $newUser = $this->rawCall(array('method' => 'user.user_add_complete', 'user_temp_key' => $user_temp_key  , 'realm_key_id' => $this->_realm_key_id));
        return $newUser;
    }


    /**
     * Check whether this session is expired, failed, or succeeded.
     *
     * @param string  $session_id
     * @return The status json object.
     */
    function checkSessionStatus($session_id)
    {
        $check = $this->rawCall (array('method' => 'user.check_session_status', 'session_id' => $session_id, 'realm_key_id' => $this->_realm_key_id));
        return $check;
    }


    /**
     * Get the QR code for the add_complete call
     *
     * @param string  $user_temp_key
     * @return A string representing a PNG of the QR code. Use imagecreatefromstring to convert this to an image resource.
     */
    function qrAddComplete($user_temp_key)
    {
        $args = array(
            'method'        => 'user.qr_add_complete',
            'user_temp_key' => $user_temp_key,
            'realm_key_id'  => $this->_realm_key_id
        );
        $url = $this->_api_url . "?" . http_build_query($args);
        $strImg = file_get_contents($url);
        return $strImg;
    }


    /**
     * Get the QR code representing the login_challenge from previously
     * callin guser.login_challenge
     *
     * @return A string representing a PNG of the QR code. Use imagecreatefromstring to convert this to an image resource.
     */
    function qrLoginChallenge()
    {
        return $this->qrLoginChallengeRaw($this->_challenge);
    }


    /**
     * Get the QR code representing the supplied login_challenge
     *
     * @param string  $challenge The cryptographic challenge
     * @return A string representing a PNG of the QR code. Use imagecreatefromstring to convert this to an image resource.
     */
    function qrLoginChallengeRaw($challenge)
    {
        $args = array('method' => 'user.qr_login_challenge',
            'challenge'     => $challenge['challenge'],
            'session_id'    => $challenge['session_id'],
            'realm_key_id'  => $this->_realm_key_id
        );
        $url = $this->_api_url . "?" . http_build_query($args);
        $strImg = file_get_contents($url);
        return $strImg;
    }


    /**
     * Internal function to convert an array into a query and issue it
     * then decode the results.
     *
     * @param array   $args an associative array for the call
     * @return array either with the response or an error
     */
    function rawCall(array $args)
    {
        $url = $this->_api_url . "?" . http_build_query($args);
        $encodedResult = file_get_contents($url);
        return json_decode($encodedResult, true);
    }


    /**
     * encode according to rfc4648 for url-safe base64 encoding
     *
     *
     * @param string  $data The data to encode
     * @return The encoded data
     */
    static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

}// Tozny_Remote_User_API class

?>
