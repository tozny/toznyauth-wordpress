<?php
/**
 * The Remote Tozny User API.
 *
 * This is the interface for the PHP Remote API for Tozny's login system.
 */

class Remote_User_API extends Tozny_Remote_User_API {
    /**
     * The Realm Key ID that this user is interacting with.
     * Usually a random string.
     *
     * @var string
     */
    protected $_realm_key_id;

    /**
     * @var string
     */
    protected $_api_url;

    public function __construct( $in_realm_key_id, $in_api_url = null ) {
        if ( $in_api_url ) {
            $this->_api_url = trailingslashit( $in_api_url );
        } else {
            $apiTmp = getenv("API_URL");
            if ($apiTmp != false) {
                $this->_api_url = trailingslashit( $apiTmp );
            } else {
                //TODO: Error
            }
        }

        parent::__construct( $in_realm_key_id, $this->_api_url );
    }

    /**
     * Get the QR code for the add_complete call
     *
     * @param string  $user_temp_key
     *
     * @return string A string representing a PNG of the QR code. Use imagecreatefromstring to convert this to an image resource.
     */
    function qrAddComplete( $user_temp_key ) {
        $args = array(
            'method'        => 'user.qr_add_complete',
            'user_temp_key' => $user_temp_key,
            'realm_key_id'  => $this->_realm_key_id
        );
        $url = $this->_api_url . "?" . http_build_query( $args );
        $strImg = wp_remote_get( $url );

        if ( is_wp_error( $strImg ) ) {
            return $strImg;
        } else {
            return wp_remote_retrieve_body( $strImg );
        }
    }

    /**
     * Get the QR code representing the supplied login_challenge
     *
     * @param string  $challenge The cryptographic challenge
     *
     * @return string A string representing a PNG of the QR code. Use imagecreatefromstring to convert this to an image resource.
     */
    function qrLoginChallengeRaw( $challenge ) {
        $args = array(
            'method'        => 'user.qr_login_challenge',
            'challenge'     => $challenge['challenge'],
            'session_id'    => $challenge['session_id'],
            'realm_key_id'  => $this->_realm_key_id
        );
        $url = $this->_api_url . "?" . http_build_query($args);
        $strImg = wp_remote_get( $url );

        if ( is_wp_error( $strImg ) ) {
            return $strImg;
        } else {
            return wp_remote_retrieve_body( $strImg );
        }
    }

    /**
     * Internal function to convert an array into a query and issue it
     * then decode the results.
     *
     * @param array   $args an associative array for the call
     * @return array either with the response or an error
     */
    function rawCall( array $args ) {
        $url = $this->_api_url . "?" . http_build_query( $args );
        $encodedResult = wp_remote_get( $url );

        if ( is_wp_error( $encodedResult ) ) {
            return $encodedResult;
        } else {
            return json_decode( wp_remote_retrieve_body( $encodedResult ), true );
        }
    }
}