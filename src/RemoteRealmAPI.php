<?php
/**
 * The Remote Tozny API.
 *
 * This is the interface for the PHP Remote API for Tozny's login system.
 */

class Remote_Realm_API extends Tozny_Remote_Realm_API {
    /**
     * @var string
     */
    protected $_realm;

    /**
     * @var string
     */
    protected $_api_url;
    
    /**
     * Internal function to convert an array into a query and issue it
     * then decode the results. Includes generation of the nonce and
     * signing of the message
     *
     * @param array $args an associative array for the call
     * 
     * @return array either with the response or an error
     */
    function rawCall(array $args)
    {
        $args['nonce'] = $this->_generateNonce();
        $args['expires_at'] = time() + (5 * 60);

        // key id is optional for convenience
        if ( ! isset( $args['realm_key_id'] ) ) {
            $args['realm_key_id'] = $this->_realm['realm_key_id'];
        }

        $sigArr = $this->_encodeAndSignArr( json_encode($args), $this->_realm['realm_priv_key'] );
        $encodedResult = wp_remote_get( $this->_api_url . "?" . http_build_query( $sigArr ) );
        if ( is_wp_error( $encodedResult ) ) {
            return $encodedResult;
        } else {
            return json_decode( wp_remote_retrieve_body( $encodedResult ), true );
        }
    }
}