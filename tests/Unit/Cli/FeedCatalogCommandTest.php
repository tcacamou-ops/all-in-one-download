<?php
namespace AllI1D\Tests\Unit\Cli;

use AllI1D\Cli\FeedCatalogCommand;
use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Tests\Support\FeedCatalogFakeWpdb;
use AllI1D\Tests\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/Support/WpCliStub.php';
require_once dirname( __DIR__, 3 ) . '/includes/feed-catalog-functions.php';

class FeedCatalogCommandTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );

		global $wpdb;
		$wpdb = new FeedCatalogFakeWpdb();

		$ref = new \ReflectionProperty( FeedCatalogRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		\WP_CLI::$messages = [];
	}

	public function test_flush_with_provider_only_purges_that_provider(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '1', 'title' => 'A', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'c411', 'movie', [ [ 'id' => '2', 'title' => 'A', 'score' => 1, 'extra' => [] ] ] );

		( new FeedCatalogCommand() )->flush( [], [ 'provider' => 'tr4ker' ] );

		$this->assertCount( 1, $repository->search( 'A' ) );
		$this->assertSame( 'c411', $repository->search( 'A' )[0]['provider'] );
		$this->assertSame( 'success', \WP_CLI::$messages[0]['level'] );
	}

	public function test_flush_with_type_only_purges_that_type(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '1', 'title' => 'A', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'tr4ker', 'tvshow', [ [ 'id' => '2', 'title' => 'B', 'score' => 1, 'extra' => [] ] ] );

		( new FeedCatalogCommand() )->flush( [], [ 'type' => 'movie' ] );

		$this->assertSame( [], $repository->search( 'A' ) );
		$this->assertCount( 1, $repository->search( 'B' ) );
	}

	public function test_flush_without_options_purges_only_stale_entries(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '1', 'title' => 'Stale', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '2', 'title' => 'Fresh', 'score' => 1, 'extra' => [] ] ] );

		global $wpdb;
		foreach ( $wpdb->rows as $id => $row ) {
			if ( '1' === $row['item_id'] ) {
				$wpdb->rows[ $id ]['last_seen_at'] = gmdate( 'Y-m-d H:i:s', time() - ( 2 * ALLI1D_FEED_CATALOG_STALE_TTL ) );
			}
		}

		( new FeedCatalogCommand() )->flush( [], [] );

		$this->assertSame( [], $repository->search( 'Stale' ) );
		$this->assertCount( 1, $repository->search( 'Fresh' ) );
	}

	public function test_refresh_triggers_the_broadcast_action(): void {
		\Brain\Monkey\Functions\expect( 'do_action' )->once()->with( 'alli1d_refresh_feed_catalog' );

		( new FeedCatalogCommand() )->refresh();

		$this->assertSame( 'success', \WP_CLI::$messages[0]['level'] );
	}

	public function test_status_reports_wired_providers_and_indexed_counts_by_type(): void {
		\Brain\Monkey\Functions\when( 'apply_filters' )->justReturn( [ 'tr4ker', 'c411' ] );

		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '1', 'title' => 'A', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'tr4ker', 'movie', [ [ 'id' => '2', 'title' => 'B', 'score' => 1, 'extra' => [] ] ] );
		$repository->upsert_items( 'c411', 'tvshow', [ [ 'id' => '3', 'title' => 'C', 'score' => 1, 'extra' => [] ] ] );

		( new FeedCatalogCommand() )->status();

		$messages = array_column( \WP_CLI::$messages, 'message' );
		$this->assertContains( '2 provider(s) wired to the feed catalog indexer.', $messages );
		$this->assertContains( '3 item(s) indexed in total.', $messages );
		$this->assertContains( '  - movie: 2', $messages );
		$this->assertContains( '  - tvshow: 1', $messages );
	}

	public function test_status_reports_zero_wired_providers_when_the_filter_is_never_hooked(): void {
		\Brain\Monkey\Functions\when( 'apply_filters' )->returnArg( 2 );

		( new FeedCatalogCommand() )->status();

		$messages = array_column( \WP_CLI::$messages, 'message' );
		$this->assertContains( '0 provider(s) wired to the feed catalog indexer.', $messages );
		$this->assertContains( '0 item(s) indexed in total.', $messages );
	}
}
