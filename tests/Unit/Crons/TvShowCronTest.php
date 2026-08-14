<?php
namespace AllI1D\Tests\Unit\Crons;

use AllI1D\Crons\TvShowCron;
use AllI1D\Models\TvShow;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Tests\UnitTestCase;

class TvShowCronTest extends UnitTestCase {

	/**
	 * Captured $what arrays received by successive `alli1d_process_tvshow`
	 * calls, in call order (full-season attempt first, then next-episode).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $captured_whats = [];

	/**
	 * Queue of return values for successive `alli1d_process_tvshow` calls.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $filter_responses = [];

	/**
	 * Set up Brain Monkey stubs shared by every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->captured_whats   = [];
		$this->filter_responses = [];

		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
		\Brain\Monkey\Functions\when( 'set_transient' )->justReturn( true );
		\Brain\Monkey\Functions\when( 'delete_transient' )->justReturn( true );
		\Brain\Monkey\Functions\when( 'do_action' )->justReturn( null );
		\Brain\Monkey\Functions\when( 'get_option' )->justReturn( TvShow::DEFAULT_DIRECTORY );
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $value ) => rtrim( $value, '/' ) . '/' );
		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		\Brain\Monkey\Functions\when( 'sleep' )->justReturn( null );

		\Brain\Monkey\Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'alli1d_process_tvshow' === $hook ) {
					$this->captured_whats[] = $value;
					return array_shift( $this->filter_responses ) ?? [ 'found' => false ];
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
	 * Reset the TvShowRepository singleton so `get_all_tv_shows()` returns the
	 * given show and `save_tv_show()` calls can be asserted against.
	 *
	 * @param TvShow $tv_show The TvShow returned by `get_all_tv_shows()`.
	 * @return TvShowRepository&\Mockery\MockInterface
	 */
	private function fake_repository_returning( TvShow $tv_show ) {
		$ref = new \ReflectionProperty( TvShowRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		$repository = \Mockery::mock( TvShowRepository::class );
		$repository->shouldReceive( 'get_all_tv_shows' )->andReturn( [ $tv_show ] );
		$ref->setValue( null, $repository );

		return $repository;
	}

	/**
	 * Build a TvShow with one active season for the given last-episode value.
	 *
	 * @param array<int, array<int, bool>> $general_search_done Initial `general_search_done` map.
	 * @param int                          $lastepisode         Last episode recorded for the single active season.
	 * @return TvShow
	 */
	private function make_tv_show( array $general_search_done, int $lastepisode = 0, string $quality = 'any' ): TvShow {
		return new TvShow(
			[
				'search_title'        => 'Show A',
				'cover_image'         => 'https://example.com/cover.jpg',
				'quality'             => $quality,
				'general_search_done' => $general_search_done,
				'data'                => [
					'saison' => [
						[
							'id'          => 1,
							'status'      => 'actif',
							'lastepisode' => $lastepisode,
						],
					],
				],
			]
			);
	}

	/**
	 * Both the full-season and next-episode `$what` builds must carry the
	 * TvShow's current per-saison/episode `general_search_done` value into
	 * the filter.
	 */
	public function test_both_calls_carry_the_current_general_search_done_value(): void {
		$tv_show                = $this->make_tv_show( [ 1 => [ 0 => true, 1 => true ] ] );
		$this->filter_responses = [ [ 'found' => false ], [ 'found' => false ] ];
		$repository             = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )->once();

		TvShowCron::process_tv_shows();

		$this->assertCount( 2, $this->captured_whats );
		$this->assertTrue( $this->captured_whats[0]['general_search_done'] );
		$this->assertTrue( $this->captured_whats[1]['general_search_done'] );
	}

	/**
	 * Both the full-season and next-episode `$what` builds must carry the same
	 * `quality` CSV string — a preference is per-item, not per-attempt.
	 */
	public function test_both_calls_carry_the_same_quality_preference(): void {
		$tv_show                = $this->make_tv_show( [ 1 => [ 0 => true, 1 => true ] ], 0, '1080p,2160p' );
		$this->filter_responses = [ [ 'found' => false ], [ 'found' => false ] ];
		$repository             = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )->once();

		TvShowCron::process_tv_shows();

		$this->assertCount( 2, $this->captured_whats );
		$this->assertSame( '1080p,2160p', $this->captured_whats[0]['quality'] );
		$this->assertSame( '1080p,2160p', $this->captured_whats[1]['quality'] );
	}

	/**
	 * A successful full-season download takes the early-`continue` path and
	 * must clear the `general_search_done` entry for that saison/episode
	 * combo, since a fresh season starts a new search cycle. Only the first
	 * filter call should happen.
	 */
	public function test_full_season_download_success_resets_general_search_done_and_persists_on_early_continue_path(): void {
		$tv_show                = $this->make_tv_show( [ 1 => [ 0 => true ] ], 0 );
		$this->filter_responses = [
			[
				'found'   => true,
				'results' => [
					[
						'id'   => 1,
						'type' => 'torrent',
					],
				],
			],
		];
		$repository             = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => ! $saved->is_general_search_done( 1, 0 ) ) );

		\Brain\Monkey\Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'alli1d_process_tvshow' === $hook ) {
					$this->captured_whats[] = $value;
					return array_shift( $this->filter_responses ) ?? [ 'found' => false ];
				}
				// The download hand-off for a full-season match is dispatched
				// dynamically as `alli1d_process_{type}`.
				$value['downloaded'] = true;
				return $value;
			}
			);

		TvShowCron::process_tv_shows();

		$this->assertCount( 1, $this->captured_whats );
		$this->assertFalse( $tv_show->is_general_search_done( 1, 0 ) );
	}

	/**
	 * When the full-season attempt finds nothing, the next-episode attempt
	 * must mark `general_search_done` as true for that saison/episode combo
	 * after its own filter call, regardless of what the filter returns.
	 */
	public function test_general_search_done_becomes_true_after_the_next_episode_attempt(): void {
		$tv_show                = $this->make_tv_show( [], 0 );
		$this->filter_responses = [
			[ 'found' => false ],
			[ 'found' => false ],
		];
		$repository             = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => $saved->is_general_search_done( 1, 1 ) ) );

		TvShowCron::process_tv_shows();

		$this->assertCount( 2, $this->captured_whats );
		$this->assertTrue( $tv_show->is_general_search_done( 1, 1 ) );
	}

	/**
	 * A successful next-episode download must clear the `general_search_done`
	 * entry for that combo, since the next episode will need its own search.
	 */
	public function test_next_episode_download_success_resets_general_search_done_to_false(): void {
		$tv_show                = $this->make_tv_show( [], 0 );
		$this->filter_responses = [
			[ 'found' => false ],
			[
				'found'   => true,
				'results' => [ [ 'id' => 1 ] ],
			],
		];
		$repository             = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => ! $saved->is_general_search_done( 1, 1 ) ) );

		\Brain\Monkey\Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'alli1d_process_tvshow' === $hook ) {
					$this->captured_whats[] = $value;
					return array_shift( $this->filter_responses ) ?? [ 'found' => false ];
				}
				if ( 'alli1d_process_torrent' === $hook ) {
					$value['downloaded'] = true;
					return $value;
				}
				return $value;
			}
			);

		TvShowCron::process_tv_shows();

		$this->assertFalse( $tv_show->is_general_search_done( 1, 1 ) );
	}

	/**
	 * The filter's return value must not influence `general_search_done` at
	 * all — even an explicit `general_search_done => false` from the filter
	 * must not stop the combo from being marked done.
	 */
	public function test_filter_return_value_does_not_influence_general_search_done(): void {
		$tv_show                = $this->make_tv_show( [], 0 );
		$this->filter_responses = [
			[ 'found' => false ],
			[
				'found'               => false,
				'general_search_done' => false,
			],
		];
		$repository             = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )->once();

		TvShowCron::process_tv_shows();

		$this->assertTrue( $tv_show->is_general_search_done( 1, 1 ) );
	}
}
