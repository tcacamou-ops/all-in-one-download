<?php
namespace AllI1D\Tests\Unit\Models\Repositories;

use AllI1D\Models\Movie;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Tests\Support\MovieFakeWpdb;
use AllI1D\Tests\UnitTestCase;

class MovieRepositoryTest extends UnitTestCase {

	private MovieFakeWpdb $wpdb;

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();

		global $wpdb;
		$wpdb       = new MovieFakeWpdb();
		$this->wpdb = $wpdb;

		$ref = new \ReflectionProperty( MovieRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_reset_all_general_search_done_resets_every_movie_to_false(): void {
		$repository = MovieRepository::get_instance();

		$movie = new Movie(
			[
				'title'               => 'The Matrix',
				'search_title'        => 'matrix',
				'audio_format'        => 'VOSTFR',
				'cover_image'         => 'https://example.com/cover.jpg',
				'status'              => 'actif',
				'data'                => [],
				'urls'                => [],
				'general_search_done' => true,
			]
		);
		$repository->save_movie( $movie );

		$affected = $repository->reset_all_general_search_done();

		$this->assertSame( 1, $affected );
		$reloaded = $repository->get_by_id( $movie->get_id() );
		$this->assertFalse( $reloaded->get_general_search_done() );
	}

	public function test_reset_all_general_search_done_returns_zero_on_an_empty_table(): void {
		$repository = MovieRepository::get_instance();

		$this->assertSame( 0, $repository->reset_all_general_search_done() );
	}

	public function test_save_movie_persists_quality_csv_on_insert(): void {
		$repository = MovieRepository::get_instance();

		$movie = new Movie(
			[
				'title'        => 'The Matrix',
				'search_title' => 'matrix',
				'audio_format' => 'VOSTFR',
				'quality'      => '1080p,2160p',
				'cover_image'  => 'https://example.com/cover.jpg',
				'status'       => 'actif',
			]
		);
		$repository->save_movie( $movie );

		$reloaded = $repository->get_by_id( $movie->get_id() );
		$this->assertSame( '1080p,2160p', $reloaded->get_quality() );
	}

	public function test_save_movie_persists_quality_csv_on_update(): void {
		$repository = MovieRepository::get_instance();

		$movie = new Movie(
			[
				'title'        => 'The Matrix',
				'search_title' => 'matrix',
				'audio_format' => 'VOSTFR',
				'cover_image'  => 'https://example.com/cover.jpg',
				'status'       => 'actif',
			]
		);
		$repository->save_movie( $movie );

		$movie->set_quality( '720p' );
		$repository->save_movie( $movie );

		$reloaded = $repository->get_by_id( $movie->get_id() );
		$this->assertSame( '720p', $reloaded->get_quality() );
	}
}
