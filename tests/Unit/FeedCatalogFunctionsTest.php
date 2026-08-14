<?php
namespace AllI1D\Tests\Unit;

use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Tests\Support\FeedCatalogFakeWpdb;
use AllI1D\Tests\UnitTestCase;

require_once dirname( __DIR__, 2 ) . '/includes/feed-catalog-functions.php';

/**
 * Non-regression coverage for the global helpers exposed to add-ons: a
 * catalog that has never been fed by any provider add-on (disabled,
 * uninstalled, or simply not yet refreshed) must not break
 * `alli1d_find_cached_catalog_items()` — it should return an empty array.
 */
class FeedCatalogFunctionsTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );

		global $wpdb;
		$wpdb = new FeedCatalogFakeWpdb();

		$ref = new \ReflectionProperty( FeedCatalogRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_find_returns_empty_array_when_catalog_never_indexed(): void {
		$this->assertSame( [], alli1d_find_cached_catalog_items( 'Matrix' ) );
	}

	public function test_index_then_find_returns_the_indexed_item(): void {
		alli1d_index_feed_catalog(
			'tr4ker',
			'movie',
			[
				[
					'id'       => '1',
					'title'    => 'The.Matrix.1999.1080p',
					'quality'  => '1080p',
					'language' => 'VOSTFR',
					'score'    => 10,
					'extra'    => [],
				],
			]
		);

		$results = alli1d_find_cached_catalog_items( 'Matrix', 'movie', 'tr4ker' );

		$this->assertCount( 1, $results );
		$this->assertSame( 'The.Matrix.1999.1080p', $results[0]['title'] );
	}
}
