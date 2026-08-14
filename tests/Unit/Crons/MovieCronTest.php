<?php
namespace AllI1D\Tests\Unit\Crons;

use AllI1D\Crons\MovieCron;
use AllI1D\Models\Movie;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Tests\UnitTestCase;

class MovieCronTest extends UnitTestCase {

	/**
	 * Captured $what array received by the last `alli1d_process_movie` call.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $captured_what = null;

	/**
	 * Value returned by the `alli1d_process_movie` filter for the current test.
	 *
	 * @var array<string, mixed>
	 */
	private array $filter_response = [ 'found' => false ];

	/**
	 * Set up Brain Monkey stubs shared by every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
		\Brain\Monkey\Functions\when( 'set_transient' )->justReturn( true );
		\Brain\Monkey\Functions\when( 'delete_transient' )->justReturn( true );
		\Brain\Monkey\Functions\when( 'do_action' )->justReturn( null );
		\Brain\Monkey\Functions\when( 'get_option' )->justReturn( Movie::DEFAULT_DIRECTORY );
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $value ) => rtrim( $value, '/' ) . '/' );

		\Brain\Monkey\Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'alli1d_process_movie' === $hook ) {
					$this->captured_what = $value;
					return $this->filter_response;
				}
				if ( 'alli1d_process_torrent' === $hook ) {
					$value['downloaded'] = false;
					return $value;
				}
				return $value;
			}
			);
	}

	/**
	 * Reset the MovieRepository singleton so `get_all_movies()` returns the
	 * given movies and `save_movie()` calls can be asserted against.
	 *
	 * @param array<int, Movie> $movies Movies returned by `get_all_movies()`.
	 * @return MovieRepository&\Mockery\MockInterface
	 */
	private function fake_repository_returning( array $movies ) {
		$ref = new \ReflectionProperty( MovieRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		$repository = \Mockery::mock( MovieRepository::class );
		$repository->shouldReceive( 'get_all_movies' )->andReturn( $movies );
		$ref->setValue( null, $repository );

		return $repository;
	}

	/**
	 * The `$what` array built by `MovieCron` must carry the movie's current
	 * `general_search_done` value into the `alli1d_process_movie` filter.
	 */
	public function test_what_array_sent_to_filter_contains_current_general_search_done_value(): void {
		$movie                 = new Movie(
			[
				'search_title'        => 'Movie A',
				'cover_image'         => 'https://example.com/cover.jpg',
				'general_search_done' => true,
			]
			);
		$this->filter_response = [ 'found' => false ];
		$repository            = $this->fake_repository_returning( [ $movie ] );
		$repository->shouldReceive( 'save_movie' )->once();

		MovieCron::process_movies();

		$this->assertTrue( $this->captured_what['general_search_done'] );
	}

	/**
	 * A general search was attempted, so `general_search_done` must become
	 * `true` after the filter call, regardless of what the filter returns —
	 * on the not-found path.
	 */
	public function test_general_search_done_becomes_true_after_the_filter_call_on_not_found_path(): void {
		$movie                 = new Movie(
			[
				'search_title'        => 'Movie A',
				'cover_image'         => 'https://example.com/cover.jpg',
				'general_search_done' => false,
			]
			);
		$this->filter_response = [ 'found' => false ];
		$repository            = $this->fake_repository_returning( [ $movie ] );
		$repository->shouldReceive( 'save_movie' )
			->once()
			->with( \Mockery::on( fn( Movie $saved ) => true === $saved->get_general_search_done() ) );

		MovieCron::process_movies();

		$this->assertTrue( $movie->get_general_search_done() );
	}

	/**
	 * Same as above, on the found/download path — `general_search_done` must
	 * also become `true` there, not just on the not-found path.
	 */
	public function test_general_search_done_becomes_true_after_the_filter_call_on_found_path(): void {
		$movie                 = new Movie(
			[
				'search_title'        => 'Movie A',
				'cover_image'         => 'https://example.com/cover.jpg',
				'general_search_done' => false,
			]
			);
		$this->filter_response = [
			'found'   => true,
			'results' => [ [ 'id' => 1 ] ],
		];
		$repository            = $this->fake_repository_returning( [ $movie ] );
		$repository->shouldReceive( 'save_movie' )
			->once()
			->with( \Mockery::on( fn( Movie $saved ) => true === $saved->get_general_search_done() ) );

		MovieCron::process_movies();

		$this->assertTrue( $movie->get_general_search_done() );
	}

	/**
	 * The filter's return value must not influence `general_search_done` at
	 * all — even an explicit `general_search_done => false` from the filter
	 * must not stop it from being set to `true`.
	 */
	public function test_filter_return_value_does_not_influence_general_search_done(): void {
		$movie                 = new Movie(
			[
				'search_title'        => 'Movie A',
				'cover_image'         => 'https://example.com/cover.jpg',
				'general_search_done' => false,
			]
			);
		$this->filter_response = [
			'found'               => false,
			'general_search_done' => false,
		];
		$repository            = $this->fake_repository_returning( [ $movie ] );
		$repository->shouldReceive( 'save_movie' )->once();

		MovieCron::process_movies();

		$this->assertTrue( $movie->get_general_search_done() );
	}

	/**
	 * The `$what` array built by `MovieCron` must carry the movie's `quality`
	 * preference into the `alli1d_process_movie` filter, alongside `audio_format`.
	 */
	public function test_what_array_sent_to_filter_contains_quality_preference(): void {
		$movie                 = new Movie(
			[
				'search_title'        => 'Movie A',
				'cover_image'         => 'https://example.com/cover.jpg',
				'quality'             => '1080p,2160p',
				'general_search_done' => false,
			]
			);
		$this->filter_response = [ 'found' => false ];
		$repository            = $this->fake_repository_returning( [ $movie ] );
		$repository->shouldReceive( 'save_movie' )->once();

		MovieCron::process_movies();

		$this->assertSame( '1080p,2160p', $this->captured_what['quality'] );
	}
}
