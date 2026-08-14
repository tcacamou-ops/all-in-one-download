<?php
namespace AllI1D\Tests\Unit\Helpers;

use AllI1D\Helpers\Crypto;
use AllI1D\Tests\UnitTestCase;

class CryptoTest extends UnitTestCase {

	public function test_encrypt_then_decrypt_round_trips(): void {
		$value = 'super-secret-value';

		$encrypted = Crypto::encrypt( $value );

		$this->assertNotSame( $value, $encrypted );
		$this->assertStringStartsWith( 'gcm1:', $encrypted );
		$this->assertSame( $value, Crypto::decrypt( $encrypted ) );
	}

	public function test_encrypt_of_empty_string_returns_empty_string(): void {
		$this->assertSame( '', Crypto::encrypt( '' ) );
		$this->assertSame( '', Crypto::decrypt( '' ) );
	}

	public function test_tampered_gcm_ciphertext_fails_to_decrypt(): void {
		$encrypted = Crypto::encrypt( 'another-secret' );
		$payload   = base64_decode( substr( $encrypted, strlen( 'gcm1:' ) ), true );

		// Flip a byte in the ciphertext portion (after iv + tag) to simulate tampering.
		$payload[ strlen( $payload ) - 1 ] = chr( ord( $payload[ strlen( $payload ) - 1 ] ) ^ 0xFF );
		$tampered                          = 'gcm1:' . base64_encode( $payload );

		$this->assertSame( '', Crypto::decrypt( $tampered ) );
	}

	public function test_legacy_cbc_values_are_still_decryptable(): void {
		$value         = 'legacy-secret';
		$key           = hash( 'sha256', AUTH_KEY, true );
		$iv            = random_bytes( 16 );
		$enc           = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		$legacy_stored = base64_encode( $iv . $enc );

		$this->assertSame( $value, Crypto::decrypt( $legacy_stored ) );
	}
}
