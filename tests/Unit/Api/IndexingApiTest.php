<?php
namespace AllI1D\Tests\Unit\Api;

use AllI1D\Api\IndexingApi;
use AllI1D\Models\Repositories\FeedCatalogRepository;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Tests\UnitTestCase;

class IndexingApiTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'rest_ensure_response' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		foreach ( [ FeedCatalogRepository::class, MovieRepository::class, TvShowRepository::class ] as $class ) {
			$ref = new \ReflectionProperty( $class, 'instance' );
			$ref->setAccessible( true );
			$ref->setValue( null, null );
		}
		parent::tearDown();
	}

	/**
	 * Inject a stand-in singleton instance for a repository, bypassing its
	 * private constructor and $wpdb dependency entirely.
	 *
	 * @template T
	 * @param class-string<T> $class The repository class.
	 * @param T                $instance The stand-in instance.
	 */
	private function set_singleton_instance( string $class, $instance ): void {
		$ref = new \ReflectionProperty( $class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, $instance );
	}

	public function test_check_permissions_checks_alli1d_capability(): void {
		\Brain\Monkey\Functions\expect( 'current_user_can' )
			->once()
			->with( 'alli1d' )
			->andReturn( true );

		$api = new IndexingApi( 'all-i1d/v1' );

		$this->assertTrue( $api->check_permissions() );
	}

	public function test_get_routes_exposes_the_reset_route(): void {
		\Brain\Monkey\Functions\when( 'rest_url' )->alias( fn( $path ) => 'https://example.com/wp-json/' . $path );

		$api = new IndexingApi( 'all-i1d/v1' );

		$this->assertSame(
			[ 'indexing_reset' => 'https://example.com/wp-json/all-i1d/v1/indexing/reset' ],
			$api->get_routes()
		);
	}

	public function test_reset_orchestrates_the_three_repository_resets_and_returns_counts(): void {
		$feed_catalog = new class() extends FeedCatalogRepository {
			public function __construct() {}
			public function truncate_all(): int {
				return 3;
			}
		};
		$movie_repository = new class() extends MovieRepository {
			public function __construct() {}
			public function reset_all_general_search_done(): int {
				return 5;
			}
		};
		$tv_show_repository = new class() extends TvShowRepository {
			public function __construct() {}
			public function reset_all_general_search_done(): int {
				return 2;
			}
		};

		$this->set_singleton_instance( FeedCatalogRepository::class, $feed_catalog );
		$this->set_singleton_instance( MovieRepository::class, $movie_repository );
		$this->set_singleton_instance( TvShowRepository::class, $tv_show_repository );

		$api      = new IndexingApi( 'all-i1d/v1' );
		$response = $api->reset();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 3, $response['catalog_removed'] );
		$this->assertSame( 5, $response['movies_reset'] );
		$this->assertSame( 2, $response['tv_shows_reset'] );
	}
}
