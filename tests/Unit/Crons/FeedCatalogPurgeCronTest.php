<?php
namespace AllI1D\Tests\Unit\Crons;

use AllI1D\Crons\FeedCatalogPurgeCron;
use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Tests\Support\FeedCatalogFakeWpdb;
use AllI1D\Tests\UnitTestCase;

require_once dirname( __DIR__, 3 ) . '/includes/feed-catalog-functions.php';

class FeedCatalogPurgeCronTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );

		global $wpdb;
		$wpdb = new FeedCatalogFakeWpdb();

		$ref = new \ReflectionProperty( FeedCatalogRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_schedule_cron_schedules_daily_event_when_not_already_scheduled(): void {
		\Brain\Monkey\Functions\when( 'wp_next_scheduled' )->justReturn( false );
		\Brain\Monkey\Functions\expect( 'wp_schedule_event' )
			->once()
			->with( \Mockery::any(), 'daily', 'alli1d_purge_feed_catalog' );

		FeedCatalogPurgeCron::schedule_cron();
	}

	public function test_unschedule_cron_removes_the_scheduled_event(): void {
		$timestamp = time() + 100;
		\Brain\Monkey\Functions\when( 'wp_next_scheduled' )->justReturn( $timestamp );
		\Brain\Monkey\Functions\expect( 'wp_unschedule_event' )
			->once()
			->with( $timestamp, 'alli1d_purge_feed_catalog' );

		FeedCatalogPurgeCron::unschedule_cron();
	}

	public function test_purge_feed_catalog_purges_stale_entries_and_logs_the_count(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '1', 'title' => 'Stale', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '2', 'title' => 'Fresh', 'score' => 1, 'extra' => [] ] ] );

		global $wpdb;
		foreach ( $wpdb->rows as $id => $row ) {
			if ( '1' === $row['item_id'] ) {
				$wpdb->rows[ $id ]['last_seen_at'] = gmdate( 'Y-m-d H:i:s', time() - ( 2 * ALLI1D_FEED_CATALOG_STALE_TTL ) );
			}
		}

		\Brain\Monkey\Functions\expect( 'do_action' )
			->once()
			->with( 'alli1d_log', \Mockery::on( fn( $message ) => str_contains( $message, '1' ) ), \Mockery::any(), \Mockery::any() );

		FeedCatalogPurgeCron::purge_feed_catalog();

		$this->assertSame( [], $repository->search( 'Stale' ) );
		$this->assertCount( 1, $repository->search( 'Fresh' ) );
	}
}
