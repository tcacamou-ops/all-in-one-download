<?php
namespace AllI1D\Tests\Unit\Filters;

use AllI1D\Filters\FeedCatalogStatus;
use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Tests\Support\FeedCatalogFakeWpdb;
use AllI1D\Tests\UnitTestCase;

require_once dirname( __DIR__, 3 ) . '/includes/feed-catalog-functions.php';

class FeedCatalogStatusTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );
		\Brain\Monkey\Functions\when( '__' )->returnArg();

		global $wpdb;
		$wpdb = new FeedCatalogFakeWpdb();

		$ref = new \ReflectionProperty( FeedCatalogRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_process_status_reports_error_when_no_provider_is_wired(): void {
		\Brain\Monkey\Functions\when( 'apply_filters' )->justReturn( [] );

		$status = FeedCatalogStatus::process_status( [] );

		$this->assertSame( 0, $status['Feed Catalog']['wired_providers'] );
		$this->assertSame( 0, $status['Feed Catalog']['total_indexed'] );
		$this->assertArrayHasKey( 'error', $status['Feed Catalog'] );
		$this->assertArrayNotHasKey( 'status', $status['Feed Catalog'] );
	}

	public function test_process_status_reports_connected_and_counts_by_type_when_providers_are_wired(): void {
		\Brain\Monkey\Functions\when( 'apply_filters' )->justReturn( [ 'tr4ker', 'c411' ] );

		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '1', 'title' => 'A', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'c411', 'tvshow', [ [ 'id' => '2', 'title' => 'B', 'score' => 1, 'extra' => [] ] ] );

		$status = FeedCatalogStatus::process_status( [] );

		$this->assertSame( 2, $status['Feed Catalog']['wired_providers'] );
		$this->assertSame( 2, $status['Feed Catalog']['total_indexed'] );
		$this->assertSame( 1, $status['Feed Catalog']['movies_indexed'] );
		$this->assertSame( 1, $status['Feed Catalog']['tvshows_indexed'] );
		$this->assertSame( 'connected', $status['Feed Catalog']['status'] );
		$this->assertArrayNotHasKey( 'error', $status['Feed Catalog'] );
	}

	public function test_register_modal_adds_the_feed_catalog_entry_with_rendered_html(): void {
		\Brain\Monkey\Functions\when( 'apply_filters' )->justReturn( [] );
		\Brain\Monkey\Functions\when( 'esc_html' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_html__' )->returnArg();

		$modals = FeedCatalogStatus::register_modal( [] );

		$this->assertArrayHasKey( 'Feed Catalog', $modals );
		$this->assertSame( 'Feed Catalog Indexer Status', $modals['Feed Catalog']['title'] );
		$this->assertStringContainsString( 'Items indexed in total', $modals['Feed Catalog']['html'] );
	}
}
