<?php

namespace AllI1D\Helpers;

class Crypto {

	/**
	 * Prefix identifying values encrypted with the authenticated AES-256-GCM format.
	 */
	private const GCM_PREFIX = 'gcm1:';

	/**
	 * Length (in bytes) of the GCM nonce/IV.
	 */
	private const GCM_IV_LENGTH = 12;

	/**
	 * Length (in bytes) of the GCM authentication tag.
	 */
	private const GCM_TAG_LENGTH = 16;

	/**
	 * Encrypt a value using authenticated AES-256-GCM encryption.
	 *
	 * @param string $value The plaintext value to encrypt.
	 * @return string
	 */
	public static function encrypt( string $value ): string {
		if ( '' === $value ) {
			return '';
		}
		$key = hash( 'sha256', AUTH_KEY, true );
		$iv  = random_bytes( self::GCM_IV_LENGTH );
		$tag = '';
		$enc = openssl_encrypt( $value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LENGTH );
		if ( false === $enc ) {
			return '';
		}
		return self::GCM_PREFIX . base64_encode( $iv . $tag . $enc );
	}

	/**
	 * Decrypt a value previously encrypted with self::encrypt().
	 *
	 * Supports both the current authenticated AES-256-GCM format and, as a
	 * fallback, the legacy non-authenticated AES-256-CBC format, so that
	 * credentials stored before the GCM migration keep working.
	 *
	 * @param string $stored The stored (encrypted) value.
	 * @return string The decrypted value, or an empty string if invalid/tampered.
	 */
	public static function decrypt( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}

		if ( 0 === strpos( $stored, self::GCM_PREFIX ) ) {
			return self::decrypt_gcm( substr( $stored, strlen( self::GCM_PREFIX ) ) );
		}

		// Ancien format non authentifié (AES-256-CBC) — conservé uniquement pour
		// pouvoir déchiffrer les valeurs déjà stockées avant la migration vers GCM.
		return self::decrypt_legacy_cbc( $stored );
	}

	/**
	 * Decrypt a value encoded in the authenticated AES-256-GCM format.
	 *
	 * @param string $stored Base64-encoded payload (iv || tag || ciphertext), without the format prefix.
	 * @return string
	 */
	private static function decrypt_gcm( string $stored ): string {
		$raw        = base64_decode( $stored, true );
		$min_length = self::GCM_IV_LENGTH + self::GCM_TAG_LENGTH;
		if ( false === $raw || strlen( $raw ) < $min_length ) {
			return '';
		}
		$key = hash( 'sha256', AUTH_KEY, true );
		$iv  = substr( $raw, 0, self::GCM_IV_LENGTH );
		$tag = substr( $raw, self::GCM_IV_LENGTH, self::GCM_TAG_LENGTH );
		$enc = substr( $raw, $min_length );
		$dec = openssl_decrypt( $enc, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		return false === $dec ? '' : $dec;
	}

	/**
	 * Decrypt a value encoded in the legacy, non-authenticated AES-256-CBC format.
	 *
	 * @param string $stored Base64-encoded payload (iv || ciphertext).
	 * @return string
	 */
	private static function decrypt_legacy_cbc( string $stored ): string {
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
