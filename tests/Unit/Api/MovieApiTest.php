<?php
namespace AllI1D\Tests\Unit\Api;

use AllI1D\Api\MovieApi;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Tests\UnitTestCase;

class MovieApiTest extends UnitTestCase {

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
	 * Reset the MovieRepository singleton with a fake $wpdb that records
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

		$ref = new \ReflectionProperty( MovieRepository::class, 'instance' );
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

	public function test_delete_movie_calls_repository_with_correct_id(): void {
		$wpdb = $this->reset_repository_with_fake_wpdb();

		$api      = new MovieApi( 'all-i1d/v1' );
		$response = $api->delete_movie( $this->make_request( [ 'movieId' => 42 ] ) );

		$this->assertSame( 42, $wpdb->deleted_id );
		$this->assertTrue( $response['success'] );
	}

	public function test_delete_movie_returns_error_for_missing_id(): void {
		$this->reset_repository_with_fake_wpdb();

		$api      = new MovieApi( 'all-i1d/v1' );
		$response = $api->delete_movie( $this->make_request( [] ) );

		$this->assertInstanceOf( \WP_Error::class, $response );
	}

	public function test_delete_movie_returns_error_for_non_positive_id(): void {
		$this->reset_repository_with_fake_wpdb();

		$api      = new MovieApi( 'all-i1d/v1' );
		$response = $api->delete_movie( $this->make_request( [ 'movieId' => -1 ] ) );

		$this->assertInstanceOf( \WP_Error::class, $response );
	}
}
