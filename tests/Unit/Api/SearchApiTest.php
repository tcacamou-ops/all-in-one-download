<?php
namespace AllI1D\Tests\Unit\Api;

use AllI1D\Api\SearchApi;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Tests\UnitTestCase;

/**
 * Minimal in-memory stand-in for $wpdb, sufficient for MovieRepository /
 * TvShowRepository's save/get_all methods used by SearchApi::select().
 */
class FakeWpdb {
	public string $prefix = 'wp_';
	public int $insert_id = 1;

	/** @var array<int, array<string, mixed>> */
	public array $rows = [];

	/** @var array<int, array<string, mixed>>|null Data passed to the last insert() call. */
	public ?array $last_inserted = null;

	/** @var array<int, array<string, mixed>>|null Data passed to the last update() call. */
	public ?array $last_updated = null;

	public function get_results( $query, $output = ARRAY_A ) {
		return $this->rows;
	}

	public function prepare( $query, ...$args ) {
		return $query;
	}

	public function esc_like( $value ) {
		return $value;
	}

	public function insert( $table, $data, $format = null ) {
		++$this->insert_id;
		$this->last_inserted = $data;
		return true;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$this->last_updated = $data;
		return true;
	}
}

class SearchApiTest extends UnitTestCase {

	/**
	 * Fake `alli1d_search_providers` callbacks registered for the current
	 * test, applied in order by the `apply_filters` stub below. Brain Monkey
	 * doesn't run a real WordPress hook chain, so `apply_filters` is aliased
	 * to actually invoke whatever callbacks the test registered here.
	 *
	 * @var callable[]
	 */
	private array $search_providers_filters = [];

	/**
	 * Fake callback for the dynamic `alli1d_download_selected_result_{provider}`
	 * hook, set per-test to simulate the chosen provider's download outcome.
	 *
	 * @var callable|null
	 */
	private $download_selected_result_callback = null;

	/**
	 * Fake callback for the `alli1d_process_torrent` hook (the Transmission
	 * hand-off), set per-test to simulate whether the download client
	 * accepted the torrent. Defaults to success so existing tests describing
	 * the happy path don't each need to wire it up.
	 *
	 * @var callable|null
	 */
	private $process_torrent_callback = null;

	protected function setUp(): void {
		parent::setUp();
		$this->search_providers_filters          = [];
		$this->download_selected_result_callback = null;
		$this->process_torrent_callback          = fn( $download_item ) => array_merge( $download_item, [ 'downloaded' => true ] );

		\Brain\Monkey\Functions\when( 'rest_ensure_response' )->returnArg( 1 );
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
		\Brain\Monkey\Functions\when( '__' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_html' )->returnArg();
		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );
		\Brain\Monkey\Functions\when( 'get_option' )->alias( fn( $key, $default = false ) => $default );
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $value ) => rtrim( $value, '/' ) . '/' );

		\Brain\Monkey\Functions\when( 'apply_filters' )->alias(
			function ( $hook, ...$args ) {
				if ( 'alli1d_search_providers' === $hook ) {
					[ $results, $criteria ] = $args;
					foreach ( $this->search_providers_filters as $callback ) {
						$results = $callback( $results, $criteria );
					}
					return $results;
				}
				if ( str_starts_with( $hook, 'alli1d_download_selected_result_' ) && null !== $this->download_selected_result_callback ) {
					return ( $this->download_selected_result_callback )( ...$args );
				}
				if ( 'alli1d_process_torrent' === $hook ) {
					return ( $this->process_torrent_callback )( $args[0] );
				}
				return $args[0] ?? null;
			}
			);
	}

	/**
	 * Reset the repository singletons with a fresh in-memory $wpdb fake.
	 * Only one repository is ever queried per `select()` call (movie xor
	 * tvshow), so a single seeded row set is enough.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows returned by the next `get_all_*` lookup.
	 * @return FakeWpdb
	 */
	private function reset_repositories( array $rows = [] ): FakeWpdb {
		global $wpdb;
		$wpdb       = new FakeWpdb();
		$wpdb->rows = $rows;

		foreach ( [ MovieRepository::class, TvShowRepository::class ] as $repository_class ) {
			$ref = new \ReflectionProperty( $repository_class, 'instance' );
			$ref->setAccessible( true );
			$ref->setValue( null, null );
		}

		return $wpdb;
	}

	private function make_row( array $overrides = [] ): array {
		return array_merge(
			[
				'id'           => 1,
				'title'        => 'Show',
				'search_title' => 'Show',
				'audio_format' => 'VOSTFR',
				'cover_image'  => 'https://example.com/cover.jpg',
				'status'       => 'actif',
				'data'         => wp_json_encode( [] ),
				'urls'         => wp_json_encode( [] ),
			],
			$overrides
			);
	}

	private function make_request( array $params ) {
		// Minimal stand-in for WP_REST_Request: only get_param() is used by SearchApi::search().
		return new class( $params ) {
			private array $params;

			public function __construct( array $params ) {
				$this->params = $params;
			}

			public function get_param( $key ) {
				return $this->params[ $key ] ?? null;
			}
		};
	}

	public function test_search_returns_empty_items_and_errors_when_no_provider_registered(): void {
		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->search(
			$this->make_request(
			[
				'type'  => 'movie',
				'title' => 'Inception',
			]
			)
			);

		$this->assertSame( [], $response['items'] );
		$this->assertSame( [], $response['errors'] );
	}

	/**
	 * Crux test: one provider's own filter callback catches its own exception
	 * internally and records it under errors[provider], while a second,
	 * unrelated provider's callback appends its items normally. Both must
	 * survive in the final accumulated result — SearchApi itself is not
	 * expected to catch provider exceptions (each provider guards itself).
	 */
	public function test_search_accumulates_items_from_one_provider_alongside_another_providers_error(): void {
		$this->search_providers_filters[] = function ( $results, $criteria ) {
			try {
				throw new \RuntimeException( 'provider_a unreachable' );
			} catch ( \Throwable $e ) {
				$results['errors']['provider_a'] = $e->getMessage();
			}
			return $results;
		};

		$this->search_providers_filters[] = function ( $results, $criteria ) {
			$results['items'][] = [
				'provider' => 'provider_b',
				'title'    => 'Movie.Title.2020.1080p.FRENCH',
				'quality'  => '1080p',
				'language' => 'FRENCH',
				'id'       => 'abc123',
				'score'    => 128,
				'extra'    => [],
			];
			return $results;
		};

		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->search(
			$this->make_request(
			[
				'type'  => 'movie',
				'title' => 'Inception',
			]
			)
			);

		$this->assertArrayHasKey( 'provider_a', $response['errors'] );
		$this->assertSame( 'provider_a unreachable', $response['errors']['provider_a'] );
		$this->assertCount( 1, $response['items'] );
		$this->assertSame( 'provider_b', $response['items'][0]['provider'] );
	}

	public function test_search_caps_results_to_ten_and_sorts_by_score_descending(): void {
		$this->search_providers_filters[] = function ( $results, $criteria ) {
			for ( $i = 0; $i < 15; $i++ ) {
				$results['items'][] = [
					'provider' => 'provider_a',
					'title'    => "Item $i",
					'quality'  => null,
					'language' => null,
					'id'       => (string) $i,
					'score'    => $i,
					'extra'    => [],
				];
			}
			return $results;
		};

		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->search(
			$this->make_request(
			[
				'type'  => 'movie',
				'title' => 'Inception',
			]
			)
			);

		$this->assertCount( 10, $response['items'] );
		$this->assertSame( 14, $response['items'][0]['score'] );
		$this->assertSame( 5, $response['items'][9]['score'] );
	}

	public function test_search_builds_criteria_from_request_params(): void {
		$captured_criteria = null;

		$this->search_providers_filters[] = function ( $results, $criteria ) use ( &$captured_criteria ) {
			$captured_criteria = $criteria;
			return $results;
		};

		$api = new SearchApi( 'all-i1d/v1' );
		$api->search(
			$this->make_request(
				[
					'type'         => 'tvshow',
					'title'        => 'Show',
					'saison'       => 2,
					'episode'      => 5,
					'audio_format' => 'VOSTFR',
				]
				)
			);

		$this->assertSame(
			[
				'type'         => 'tvshow',
				'title'        => 'Show',
				'saison'       => 2,
				'episode'      => 5,
				'audio_format' => 'VOSTFR',
			],
			$captured_criteria
			);
	}

	// -------------------------------------------------------------------------
	// select() — failure path
	// -------------------------------------------------------------------------

	public function test_select_returns_error_and_creates_no_fiche_when_provider_returns_null(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => null;
		$wpdb                                    = $this->reset_repositories();

		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Inception',
					'suivi'    => false,
				]
				)
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertNull( $wpdb->last_inserted );
		$this->assertNull( $wpdb->last_updated );
	}

	// -------------------------------------------------------------------------
	// select() — movie
	// -------------------------------------------------------------------------

	public function test_select_movie_updates_existing_movie_and_sets_downloaded(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'        => 'Inception',
					'search_title' => 'Inception',
					'status'       => 'actif',
				]
				),
			]
			);

		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Inception',
					'suivi'    => false,
				]
				)
			);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'torrent', $response['item']['type'] );
		$this->assertSame( '/downloads/x.torrent', $response['item']['path'] );
		$this->assertTrue( $response['item']['downloaded'] );
		$this->assertNull( $wpdb->last_inserted );
		$this->assertNotNull( $wpdb->last_updated );
		$this->assertSame( 'downloaded', $wpdb->last_updated['status'] );
	}

	public function test_select_movie_returns_error_and_keeps_actif_when_transmission_fails(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$this->process_torrent_callback          = fn( $download_item ) => array_merge( $download_item, [ 'downloaded' => false ] );
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'        => 'Inception',
					'search_title' => 'Inception',
					'status'       => 'actif',
				]
				),
			]
			);

		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Inception',
					'suivi'    => false,
				]
				)
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertNotNull( $wpdb->last_updated );
		$this->assertSame( 'actif', $wpdb->last_updated['status'] );
	}

	public function test_select_movie_creates_new_movie_when_none_exists(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories( [] );

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Brand New Movie',
					'suivi'    => false,
				]
				)
			);

		$this->assertNull( $wpdb->last_updated );
		$this->assertNotNull( $wpdb->last_inserted );
		$this->assertSame( 'Brand New Movie', $wpdb->last_inserted['title'] );
		$this->assertSame( 'downloaded', $wpdb->last_inserted['status'] );
	}

	public function test_select_movie_persists_quality_csv_on_new_item_creation(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories( [] );

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Brand New Movie',
					'suivi'    => false,
					'quality'  => [ '1080p', '2160p' ],
				]
				)
			);

		$this->assertNotNull( $wpdb->last_inserted );
		$this->assertSame( '1080p,2160p', $wpdb->last_inserted['quality'] );
	}

	public function test_select_movie_persists_default_quality_when_param_missing_on_new_item_creation(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories( [] );

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Brand New Movie',
					'suivi'    => false,
				]
				)
			);

		$this->assertNotNull( $wpdb->last_inserted );
		$this->assertSame( '1080p,2160p', $wpdb->last_inserted['quality'] );
	}

	public function test_select_tvshow_persists_quality_csv_on_new_item_creation(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories( [] );

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Brand New Show',
					'saison'   => 1,
					'episode'  => 1,
					'suivi'    => false,
					'quality'  => [ '1080p', '2160p' ],
				]
				)
			);

		$this->assertNotNull( $wpdb->last_inserted );
		$this->assertSame( '1080p,2160p', $wpdb->last_inserted['quality'] );
	}

	public function test_select_args_quality_enum_allows_any(): void {
		$api    = new SearchApi( 'all-i1d/v1' );
		$method = new \ReflectionMethod( SearchApi::class, 'get_select_args' );
		$method->setAccessible( true );
		$args = $method->invoke( $api );

		$this->assertContains( 'any', $args['quality']['items']['enum'] );
	}

	public function test_select_movie_persists_any_quality_on_new_item_creation(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories( [] );

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'movie',
					'title'    => 'Brand New Movie',
					'suivi'    => false,
					'quality'  => [ 'any' ],
				]
				)
			);

		$this->assertNotNull( $wpdb->last_inserted );
		$this->assertSame( 'any', $wpdb->last_inserted['quality'] );
	}

	public function test_select_tvshow_persists_any_quality_on_new_item_creation(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$wpdb                                    = $this->reset_repositories( [] );

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Brand New Show',
					'saison'   => 1,
					'episode'  => 1,
					'suivi'    => false,
					'quality'  => [ 'any' ],
				]
				)
			);

		$this->assertNotNull( $wpdb->last_inserted );
		$this->assertSame( 'any', $wpdb->last_inserted['quality'] );
	}

	// -------------------------------------------------------------------------
	// select() — tvshow, suivi = true
	// -------------------------------------------------------------------------

	public function test_select_tvshow_suivi_true_advances_lastepisode_and_keeps_saison_actif(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$data                                    = [
			'saison' => [
				[
					'id'          => 1,
					'status'      => 'actif',
					'lastepisode' => 2,
				],
			],
		];
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'  => 'Show',
					'status' => 'actif',
					'data'   => wp_json_encode( $data ),
				]
			),
			]
			);

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Show',
					'saison'   => 1,
					'episode'  => 3,
					'suivi'    => true,
				]
				)
			);

		$saved = json_decode( $wpdb->last_updated['data'], true );
		$this->assertSame( 3, $saved['saison'][0]['lastepisode'] );
		$this->assertSame( 'actif', $saved['saison'][0]['status'] );
		$this->assertSame( 'actif', $wpdb->last_updated['status'] );
	}

	public function test_select_tvshow_suivi_true_inserts_missing_saison(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$data                                    = [
			'saison' => [
				[
					'id'          => 1,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
			],
		];
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'  => 'Show',
					'status' => 'actif',
					'data'   => wp_json_encode( $data ),
				]
			),
			]
			);

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Show',
					'saison'   => 2,
					'episode'  => 1,
					'suivi'    => true,
				]
				)
			);

		$saved = json_decode( $wpdb->last_updated['data'], true );
		$this->assertCount( 2, $saved['saison'] );
		$this->assertSame( 2, $saved['saison'][1]['id'] );
		$this->assertSame( 1, $saved['saison'][1]['lastepisode'] );
	}

	// -------------------------------------------------------------------------
	// select() — tvshow, suivi = false (one-shot)
	// -------------------------------------------------------------------------

	public function test_select_tvshow_one_shot_sets_show_downloaded_when_only_saison(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$data                                    = [
			'saison' => [
				[
					'id'          => 1,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
			],
		];
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'  => 'Show',
					'status' => 'actif',
					'data'   => wp_json_encode( $data ),
				]
			),
			]
			);

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Show',
					'saison'   => 1,
					'episode'  => 1,
					'suivi'    => false,
				]
				)
			);

		$saved = json_decode( $wpdb->last_updated['data'], true );
		$this->assertSame( 'inactif', $saved['saison'][0]['status'] );
		$this->assertSame( 'downloaded', $wpdb->last_updated['status'] );
	}

	public function test_select_tvshow_one_shot_keeps_show_actif_when_other_saison_active(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$data                                    = [
			'saison' => [
				[
					'id'          => 1,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
				[
					'id'          => 2,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
			],
		];
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'  => 'Show',
					'status' => 'actif',
					'data'   => wp_json_encode( $data ),
				]
			),
			]
			);

		$api = new SearchApi( 'all-i1d/v1' );
		$api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Show',
					'saison'   => 1,
					'episode'  => 1,
					'suivi'    => false,
				]
				)
			);

		$saved = json_decode( $wpdb->last_updated['data'], true );
		$this->assertSame( 'inactif', $saved['saison'][0]['status'] );
		$this->assertSame( 'actif', $saved['saison'][1]['status'] );
		$this->assertSame( 'actif', $wpdb->last_updated['status'] );
	}

	public function test_select_tvshow_returns_error_and_keeps_saison_actif_when_transmission_fails(): void {
		$this->download_selected_result_callback = fn( $null, $result ) => [
			'type' => 'torrent',
			'path' => '/downloads/x.torrent',
		];
		$this->process_torrent_callback          = fn( $download_item ) => array_merge( $download_item, [ 'downloaded' => false ] );
		$data                                    = [
			'saison' => [
				[
					'id'          => 1,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
			],
		];
		$wpdb                                    = $this->reset_repositories(
			[
				$this->make_row(
				[
					'title'  => 'Show',
					'status' => 'actif',
					'data'   => wp_json_encode( $data ),
				]
			),
			]
			);

		$api      = new SearchApi( 'all-i1d/v1' );
		$response = $api->select(
			$this->make_request(
				[
					'provider' => 'tr4ker',
					'result'   => [ 'id' => 'abc' ],
					'type'     => 'tvshow',
					'title'    => 'Show',
					'saison'   => 1,
					'episode'  => 1,
					'suivi'    => false,
				]
				)
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
		$saved = json_decode( $wpdb->last_updated['data'], true );
		$this->assertSame( 'actif', $saved['saison'][0]['status'] );
	}
}
