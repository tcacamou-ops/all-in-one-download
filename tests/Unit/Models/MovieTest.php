<?php
namespace AllI1D\Tests\Unit\Models;

use AllI1D\Models\Movie;
use AllI1D\Tests\UnitTestCase;

class MovieTest extends UnitTestCase {

	/**
	 * Mocks WP functions utilisées par Movie et retourne un tableau
	 * d'attributs minimaux valides (cover_image obligatoire).
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
	}

	private function valid_attrs( array $overrides = [] ): array {
		return array_merge( [ 'cover_image' => 'https://example.com/cover.jpg' ], $overrides );
	}

	// -------------------------------------------------------------------------
	// Constructeur — valeurs par défaut
	// -------------------------------------------------------------------------

	public function test_constructor_default_id_is_null(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertNull( $movie->get_id() );
	}

	public function test_constructor_default_title_is_empty_string(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( '', $movie->get_title() );
	}

	public function test_constructor_default_audio_format_is_vostfr(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( 'VOSTFR', $movie->get_audio_format() );
	}

	public function test_constructor_default_status_is_actif(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( 'actif', $movie->get_status() );
	}

	public function test_constructor_default_data_is_empty_array(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( [], $movie->get_data() );
	}

	public function test_constructor_default_urls_is_empty_array(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( [], $movie->get_urls() );
	}

	// -------------------------------------------------------------------------
	// set_id / get_id
	// -------------------------------------------------------------------------

	public function test_set_id_accepts_positive_integer(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_id( 42 );
		$this->assertSame( 42, $movie->get_id() );
	}

	public function test_set_id_accepts_null(): void {
		$movie = new Movie( $this->valid_attrs( [ 'id' => 1 ] ) );
		$movie->set_id( null );
		$this->assertNull( $movie->get_id() );
	}

	public function test_set_id_throws_for_zero(): void {
		$this->expectException( \InvalidArgumentException::class );
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_id( 0 );
	}

	public function test_set_id_throws_for_negative(): void {
		$this->expectException( \InvalidArgumentException::class );
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_id( -5 );
	}

	// -------------------------------------------------------------------------
	// set_title / get_title
	// -------------------------------------------------------------------------

	public function test_get_title_returns_sanitized_value(): void {
		$movie = new Movie( $this->valid_attrs( [ 'title' => 'Inception' ] ) );
		$this->assertSame( 'Inception', $movie->get_title() );
	}

	// -------------------------------------------------------------------------
	// get_search_title
	// -------------------------------------------------------------------------

	public function test_get_search_title_falls_back_to_title_when_empty(): void {
		$movie = new Movie( $this->valid_attrs( [ 'title' => 'Inception' ] ) );
		$this->assertSame( 'Inception', $movie->get_search_title() );
	}

	public function test_get_search_title_returns_own_value_when_set(): void {
		$movie = new Movie(
			$this->valid_attrs(
			[
				'title'        => 'Inception',
				'search_title' => 'inception 2010',
			]
			)
			);
		$this->assertSame( 'inception 2010', $movie->get_search_title() );
	}

	// -------------------------------------------------------------------------
	// set_audio_format / get_audio_format
	// -------------------------------------------------------------------------

	public function test_get_audio_format_returns_set_value(): void {
		$movie = new Movie( $this->valid_attrs( [ 'audio_format' => 'VF' ] ) );
		$this->assertSame( 'VF', $movie->get_audio_format() );
	}

	// -------------------------------------------------------------------------
	// set_cover_image
	// -------------------------------------------------------------------------

	public function test_set_cover_image_accepts_valid_url(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_cover_image( 'https://example.com/new.jpg' );
		$this->assertSame( 'https://example.com/new.jpg', $movie->get_cover_image() );
	}

	public function test_set_cover_image_throws_for_empty_string(): void {
		$this->expectException( \InvalidArgumentException::class );
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_cover_image( '' );
	}

	public function test_set_cover_image_throws_for_invalid_url(): void {
		$this->expectException( \InvalidArgumentException::class );
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_cover_image( 'not-a-url' );
	}

	// -------------------------------------------------------------------------
	// set_status / get_status
	// -------------------------------------------------------------------------

	public function test_set_status_accepts_actif(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_status( 'actif' );
		$this->assertSame( 'actif', $movie->get_status() );
	}

	public function test_set_status_accepts_inactif(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_status( 'inactif' );
		$this->assertSame( 'inactif', $movie->get_status() );
	}

	public function test_set_status_accepts_downloaded(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_status( 'downloaded' );
		$this->assertSame( 'downloaded', $movie->get_status() );
	}

	public function test_set_status_throws_for_invalid_status(): void {
		$this->expectException( \InvalidArgumentException::class );
		$movie = new Movie( $this->valid_attrs() );
		$movie->set_status( 'invalid' );
	}

	// -------------------------------------------------------------------------
	// set_data / get_data
	// -------------------------------------------------------------------------

	public function test_get_data_returns_set_array(): void {
		$data  = [ 'key' => 'value' ];
		$movie = new Movie( $this->valid_attrs( [ 'data' => $data ] ) );
		$this->assertSame( $data, $movie->get_data() );
	}

	// -------------------------------------------------------------------------
	// set_urls / get_urls
	// -------------------------------------------------------------------------

	public function test_get_urls_returns_set_array(): void {
		$urls  = [ 'https://example.com/file.mkv' ];
		$movie = new Movie( $this->valid_attrs( [ 'urls' => $urls ] ) );
		$this->assertSame( $urls, $movie->get_urls() );
	}

	// -------------------------------------------------------------------------
	// add_url
	// -------------------------------------------------------------------------

	public function test_add_url_appends_valid_url(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->add_url( 'https://example.com/file.mkv' );
		$this->assertContains( 'https://example.com/file.mkv', $movie->get_urls() );
	}

	public function test_add_url_throws_for_invalid_url(): void {
		$this->expectException( \InvalidArgumentException::class );
		$movie = new Movie( $this->valid_attrs() );
		$movie->add_url( 'not-a-url' );
	}

	public function test_add_url_does_not_duplicate_existing_url(): void {
		$movie = new Movie( $this->valid_attrs() );
		$movie->add_url( 'https://example.com/file.mkv' );
		$movie->add_url( 'https://example.com/file.mkv' );
		$this->assertCount( 1, $movie->get_urls() );
	}

	// -------------------------------------------------------------------------
	// get_type
	// -------------------------------------------------------------------------

	public function test_get_type_returns_movie(): void {
		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( 'movie', $movie->get_type() );
	}

	// -------------------------------------------------------------------------
	// get_download_directory
	// -------------------------------------------------------------------------

	public function test_get_download_directory_uses_get_option(): void {
		\Brain\Monkey\Functions\when( 'get_option' )->justReturn( '/downloads/Movies' );
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $s ) => rtrim( $s, '/' ) . '/' );

		$movie = new Movie( $this->valid_attrs() );
		$this->assertSame( '/downloads/Movies/', $movie->get_download_directory() );
	}

	// -------------------------------------------------------------------------
	// Constantes statiques
	// -------------------------------------------------------------------------

	public function test_static_actif_equals_actif(): void {
		$this->assertSame( 'actif', Movie::$actif );
	}

	public function test_static_inactif_equals_inactif(): void {
		$this->assertSame( 'inactif', Movie::$inactif );
	}

	public function test_static_downloaded_equals_downloaded(): void {
		$this->assertSame( 'downloaded', Movie::$downloaded );
	}
}
