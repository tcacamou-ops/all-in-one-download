<?php

namespace AllI1D\Helpers;

class Crypto {

    public static function encrypt( string $value ): string {
        if ( '' === $value ) {
            return '';
        }
        $key = hash( 'sha256', AUTH_KEY, true );
        $iv  = random_bytes( 16 );
        $enc = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        return base64_encode( $iv . $enc );
    }

    public static function decrypt( string $stored ): string {
        if ( '' === $stored ) {
            return '';
        }
        $raw = base64_decode( $stored, true );
        if ( false === $raw || strlen( $raw ) < 17 ) {
            return '';
        }
        $key = hash( 'sha256', AUTH_KEY, true );
        $iv  = substr( $raw, 0, 16 );
        $enc = substr( $raw, 16 );
        $dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        return false === $dec ? '' : $dec;
    }
}
