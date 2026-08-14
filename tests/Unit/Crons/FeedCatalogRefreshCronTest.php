<?php
namespace AllI1D\Tests\Unit\Crons;

use AllI1D\Crons\FeedCatalogRefreshCron;
use AllI1D\Tests\UnitTestCase;

class FeedCatalogRefreshCronTest extends UnitTestCase {

	public function test_schedule_cron_schedules_hourly_event_when_not_already_scheduled(): void {
		\Brain\Monkey\Functions\when( 'wp_next_scheduled' )->justReturn( false );
		\Brain\Monkey\Functions\expect( 'wp_schedule_event' )
			->once()
			->with( \Mockery::any(), 'hourly', 'alli1d_refresh_feed_catalog' );

		FeedCatalogRefreshCron::schedule_cron();
	}

	public function test_schedule_cron_does_not_reschedule_when_already_scheduled(): void {
		\Brain\Monkey\Functions\when( 'wp_next_scheduled' )->justReturn( time() + 100 );
		\Brain\Monkey\Functions\expect( 'wp_schedule_event' )->never();

		FeedCatalogRefreshCron::schedule_cron();
	}

	public function test_unschedule_cron_removes_the_scheduled_event(): void {
		$timestamp = time() + 100;
		\Brain\Monkey\Functions\when( 'wp_next_scheduled' )->justReturn( $timestamp );
		\Brain\Monkey\Functions\expect( 'wp_unschedule_event' )
			->once()
			->with( $timestamp, 'alli1d_refresh_feed_catalog' );

		FeedCatalogRefreshCron::unschedule_cron();
	}
}
