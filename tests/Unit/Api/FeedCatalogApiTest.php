<?php
namespace AllI1D\Tests\Unit\Api;

use AllI1D\Api\FeedCatalogApi;
use AllI1D\Tests\UnitTestCase;

class FeedCatalogApiTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'rest_ensure_response' )->returnArg( 1 );
	}

	public function test_check_permissions_checks_alli1d_capability(): void {
		\Brain\Monkey\Functions\expect( 'current_user_can' )
			->once()
			->with( 'alli1d' )
			->andReturn( true );

		$api = new FeedCatalogApi( 'all-i1d/v1' );

		$this->assertTrue( $api->check_permissions() );
	}

	public function test_run_cron_schedules_the_feed_catalog_refresh_hook(): void {
		\Brain\Monkey\Functions\expect( 'wp_schedule_single_event' )
			->once()
			->with( \Mockery::type( 'int' ), 'alli1d_refresh_feed_catalog' );

		$api      = new FeedCatalogApi( 'all-i1d/v1' );
		$response = $api->run_cron();

		$this->assertTrue( $response['success'] );
	}

	public function test_get_routes_exposes_the_run_cron_route(): void {
		\Brain\Monkey\Functions\when( 'rest_url' )->alias( fn( $path ) => 'https://example.com/wp-json/' . $path );

		$api = new FeedCatalogApi( 'all-i1d/v1' );

		$this->assertSame(
			[ 'feed_catalog_run_cron' => 'https://example.com/wp-json/all-i1d/v1/feed-catalog/cron' ],
			$api->get_routes()
			);
	}
}
