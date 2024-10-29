<?php

namespace UNIFIED_SOFTWARE\BANKVAL;

class Crypto {

    private static $instance = null;
    private function __construct() {}

    public static function getInstance() {
        if ( self::$instance  == null ) {
            self::$instance = new Crypto();
        }

        return self::$instance;
    }

    function get_encryption_key() {
        $keyName = get_option( 'unified_software_encryption_key' ); 
        if ( $keyName && defined( $keyName ) ) {
                return constant( $keyName );
        }
    
        foreach ( array(
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY'
        ) as $key ) {
            if ( defined( $key ) ) {
                update_option( 'unified_software_encryption_key', $key );
                return constant( $key );
            }
        }
    
        return false;
    }
    
    function encrypt( $message, $key ) {
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
        $encrypted = openssl_encrypt( $message, 'aes-256-cbc', $key, 0, $iv );
        $ciphertext = $encrypted . ':' . base64_encode( $iv );
        return $ciphertext;
    }
    
    function decrypt( $ciphertext, $key ) {
        $parts = explode( ':', $ciphertext );
        $encrypted = $parts[0];
        $iv = base64_decode( $parts[1] );
        $message = openssl_decrypt( $encrypted, 'aes-256-cbc', $key, 0, $iv );
        return $message;
    }

    function decrypt_arr( $ciphertext_arr, $key ) {
        foreach ($ciphertext_arr as $name => $value ) {
            $ciphertext_arr[$name] = $this->decrypt( $value, $key );
        }

        return $ciphertext_arr;
    }
}
?>