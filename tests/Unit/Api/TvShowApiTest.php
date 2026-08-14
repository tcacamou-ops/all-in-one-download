<?php
namespace AllI1D\Tests\Unit\Api;

use AllI1D\Api\TvShowApi;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Models\TvShow;
use AllI1D\Tests\UnitTestCase;

class TvShowApiTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'rest_ensure_response' )->returnArg( 1 );
		\Brain\Monkey\Functions\when( '__' )->returnArg();
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $value ) => rtrim( $value, '/' ) . '/' );
		\Brain\Monkey\Functions\when( 'wp_upload_dir' )->justReturn(
			[
				'basedir' => sys_get_temp_dir(),
				'baseurl' => 'http://example.com/wp-content/uploads',
			]
			);
	}

	/**
	 * Reset the TvShowRepository singleton with a fake $wpdb that records
	 * the arguments passed to delete().
	 *
	 * @return object The fake $wpdb, exposing a public `$deleted_id`.
	 */
	private function reset_repository_with_fake_wpdb(): object {
		global $wpdb;
		$wpdb = new class() {
			public string $prefix = 'wp_';
			public $deleted_id = null;

			public function delete( $table, $where, $format = null ) {
				$this->deleted_id = $where['id'] ?? null;
				return 1;
			}
		};

		$ref = new \ReflectionProperty( TvShowRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		return $wpdb;
	}

	private function make_request( array $params ) {
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

	public function test_delete_tv_show_calls_repository_with_correct_id(): void {
		$wpdb = $this->reset_repository_with_fake_wpdb();

		$api      = new TvShowApi( 'all-i1d/v1' );
		$response = $api->delete_tv_show( $this->make_request( [ 'tvShowId' => 7 ] ) );

		$this->assertSame( 7, $wpdb->deleted_id );
		$this->assertTrue( $response['success'] );
	}

	public function test_delete_tv_show_returns_error_for_missing_id(): void {
		$this->reset_repository_with_fake_wpdb();

		$api      = new TvShowApi( 'all-i1d/v1' );
		$response = $api->delete_tv_show( $this->make_request( [] ) );

		$this->assertInstanceOf( \WP_Error::class, $response );
	}

	public function test_delete_tv_show_returns_error_for_non_positive_id(): void {
		$this->reset_repository_with_fake_wpdb();

		$api      = new TvShowApi( 'all-i1d/v1' );
		$response = $api->delete_tv_show( $this->make_request( [ 'tvShowId' => 0 ] ) );

		$this->assertInstanceOf( \WP_Error::class, $response );
	}

	public function test_check_delete_permissions_returns_true_for_alli1d_admin(): void {
		\Brain\Monkey\Functions\expect( 'current_user_can' )
			->once()
			->with( 'alli1d_admin' )
			->andReturn( true );

		$api = new TvShowApi( 'all-i1d/v1' );

		$this->assertTrue( $api->check_delete_permissions() );
	}

	public function test_check_delete_permissions_returns_false_when_missing_capability(): void {
		\Brain\Monkey\Functions\expect( 'current_user_can' )
			->once()
			->with( 'alli1d_admin' )
			->andReturn( false );

		$api = new TvShowApi( 'all-i1d/v1' );

		$this->assertFalse( $api->check_delete_permissions() );
	}

	public function test_check_permissions_still_checks_alli1d_capability(): void {
		\Brain\Monkey\Functions\expect( 'current_user_can' )
			->once()
			->with( 'alli1d' )
			->andReturn( true );

		$api = new TvShowApi( 'all-i1d/v1' );

		$this->assertTrue( $api->check_permissions() );
	}

	// -------------------------------------------------------------------------
	// set_tv_show — tvShowQuality[] handling
	// -------------------------------------------------------------------------

	/**
	 * Reset the TvShowRepository singleton with a Mockery double returning the
	 * given TvShow for `get_tv_show_by_id()`, so `save_tv_show()` calls can be
	 * asserted against.
	 *
	 * @return TvShowRepository&\Mockery\MockInterface
	 */
	private function fake_repository_returning( TvShow $tv_show ) {
		$ref = new \ReflectionProperty( TvShowRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		$repository = \Mockery::mock( TvShowRepository::class );
		$repository->shouldReceive( 'get_tv_show_by_id' )->andReturn( $tv_show );
		$ref->setValue( null, $repository );

		return $repository;
	}

	private function make_tv_show(): TvShow {
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();

		return new TvShow( [ 'cover_image' => 'https://example.com/cover.jpg' ] );
	}

	public function test_set_tv_show_intersects_tv_show_quality_array_against_selectable_qualities(): void {
		$tv_show    = $this->make_tv_show();
		$repository = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => '1080p,2160p' === $saved->get_quality() ) );

		$api = new TvShowApi( 'all-i1d/v1' );
		$api->set_tv_show(
			$this->make_request(
				[
					'tvShowStatus'  => 'actif',
					'tvShowSeasons' => [],
					'tvShowQuality' => [ '1080p', '2160p', 'garbage' ],
				]
				)
			);
	}

	public function test_set_tv_show_collapses_empty_quality_array_to_default(): void {
		$tv_show    = $this->make_tv_show();
		$repository = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => '1080p,2160p' === $saved->get_quality() ) );

		$api = new TvShowApi( 'all-i1d/v1' );
		$api->set_tv_show(
			$this->make_request(
				[
					'tvShowStatus'  => 'actif',
					'tvShowSeasons' => [],
					'tvShowQuality' => [],
				]
				)
			);
	}

	public function test_set_tv_show_collapses_missing_quality_param_to_default(): void {
		$tv_show    = $this->make_tv_show();
		$repository = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => '1080p,2160p' === $saved->get_quality() ) );

		$api = new TvShowApi( 'all-i1d/v1' );
		$api->set_tv_show(
			$this->make_request(
				[
					'tvShowStatus'  => 'actif',
					'tvShowSeasons' => [],
				]
				)
			);
	}

	public function test_set_tv_show_keeps_explicit_any_as_any(): void {
		$tv_show    = $this->make_tv_show();
		$repository = $this->fake_repository_returning( $tv_show );
		$repository->shouldReceive( 'save_tv_show' )
			->once()
			->with( \Mockery::on( fn( TvShow $saved ) => 'any' === $saved->get_quality() ) );

		$api = new TvShowApi( 'all-i1d/v1' );
		$api->set_tv_show(
			$this->make_request(
				[
					'tvShowStatus'  => 'actif',
					'tvShowSeasons' => [],
					'tvShowQuality' => [ 'any' ],
				]
				)
			);
	}
}
