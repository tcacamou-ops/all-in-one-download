<?php
namespace AllI1D\Tests\Unit\Models\Repositories;

use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Tests\Support\FeedCatalogFakeWpdb;
use AllI1D\Tests\UnitTestCase;

class FeedCatalogRepositoryTest extends UnitTestCase {

	private FeedCatalogFakeWpdb $wpdb;

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );

		global $wpdb;
		$wpdb       = new FeedCatalogFakeWpdb();
		$this->wpdb = $wpdb;

		$ref = new \ReflectionProperty( FeedCatalogRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	private function item( string $id, string $title, int $score = 10 ): array {
		return [
			'id'       => $id,
			'title'    => $title,
			'quality'  => '1080p',
			'language' => 'VOSTFR',
			'score'    => $score,
			'extra'    => [ 'seeders' => $score ],
		];
	}

	public function test_upsert_then_search_finds_item_by_title_substring(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'The.Matrix.1999.1080p' ) ] );

		$results = $repository->search( 'Matrix' );

		$this->assertCount( 1, $results );
		$this->assertSame( 'tr4ker', $results[0]['provider'] );
		$this->assertSame( '1', $results[0]['id'] );
		$this->assertSame( [ 'seeders' => 10 ], $results[0]['extra'] );
	}

	public function test_search_returns_empty_array_when_nothing_matches(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'The.Matrix.1999.1080p' ) ] );

		$this->assertSame( [], $repository->search( 'Inception' ) );
	}

	public function test_search_can_be_scoped_by_type_and_provider(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'Matrix.1080p' ) ] );
		$repository->upsert_items( 'tr4ker', 'tvshow', [ $this->item( '2', 'Matrix.S01E01' ) ] );
		$repository->upsert_items( 'c411', 'movie', [ $this->item( '3', 'Matrix.720p' ) ] );

		$this->assertCount( 1, $repository->search( 'Matrix', 'movie', 'tr4ker' ) );
		$this->assertCount( 2, $repository->search( 'Matrix', 'movie' ) );
		$this->assertCount( 3, $repository->search( 'Matrix' ) );
	}

	public function test_upserting_the_same_item_id_again_updates_instead_of_duplicating(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'Matrix.1080p', 5 ) ] );
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'Matrix.1080p', 42 ) ] );

		$results = $repository->search( 'Matrix' );

		$this->assertCount( 1, $results );
		$this->assertSame( 42, $results[0]['score'] );
	}

	public function test_purge_stale_removes_only_entries_not_seen_recently(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'Old.Item' ) ] );
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '2', 'Fresh.Item' ) ] );

		// Backdate the first item's last_seen_at beyond the TTL window.
		foreach ( $this->wpdb->rows as $id => $row ) {
			if ( '1' === $row['item_id'] ) {
				$this->wpdb->rows[ $id ]['last_seen_at'] = gmdate( 'Y-m-d H:i:s', time() - 1000 );
			}
		}

		$removed = $repository->purge_stale( 100 );

		$this->assertSame( 1, $removed );
		$this->assertSame( [], $repository->search( 'Old' ) );
		$this->assertCount( 1, $repository->search( 'Fresh' ) );
	}

	public function test_purge_by_type_removes_only_matching_type(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'A' ) ] );
		$repository->upsert_items( 'tr4ker', 'tvshow', [ $this->item( '2', 'B' ) ] );

		$removed = $repository->purge_by_type( 'movie' );

		$this->assertSame( 1, $removed );
		$this->assertSame( [], $repository->search( 'A' ) );
		$this->assertCount( 1, $repository->search( 'B' ) );
	}

	public function test_count_all_returns_the_total_number_of_indexed_items(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'A' ) ] );
		$repository->upsert_items( 'c411', 'tvshow', [ $this->item( '2', 'B' ) ] );

		$this->assertSame( 2, $repository->count_all() );
	}

	public function test_count_by_type_returns_counts_grouped_by_type(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'A' ), $this->item( '2', 'B' ) ] );
		$repository->upsert_items( 'c411', 'tvshow', [ $this->item( '3', 'C' ) ] );

		$this->assertSame( [ 'movie' => 2, 'tvshow' => 1 ], $repository->count_by_type() );
	}

	public function test_purge_by_provider_removes_only_matching_provider(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'A' ) ] );
		$repository->upsert_items( 'c411', 'movie', [ $this->item( '2', 'A' ) ] );

		$removed = $repository->purge_by_provider( 'tr4ker' );

		$this->assertSame( 1, $removed );
		$this->assertCount( 1, $repository->search( 'A' ) );
		$this->assertSame( 'c411', $repository->search( 'A' )[0]['provider'] );
	}

	public function test_truncate_all_removes_every_entry_regardless_of_provider_or_type(): void {
		$repository = FeedCatalogRepository::get_instance();
		$repository->upsert_items( 'tr4ker', 'movie', [ $this->item( '1', 'A' ) ] );
		$repository->upsert_items( 'c411', 'tvshow', [ $this->item( '2', 'B' ) ] );

		$removed = $repository->truncate_all();

		$this->assertSame( 2, $removed );
		$this->assertSame( 0, $repository->count_all() );
	}

	public function test_truncate_all_returns_zero_on_an_already_empty_table(): void {
		$repository = FeedCatalogRepository::get_instance();

		$this->assertSame( 0, $repository->truncate_all() );
	}
}
