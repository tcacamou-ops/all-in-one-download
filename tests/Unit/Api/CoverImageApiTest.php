<?php
namespace AllI1D\Tests\Unit\Api;

use AllI1D\Api\CoverImageApi;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Tests\UnitTestCase;

class CoverImageApiTest extends UnitTestCase {

	private string $upload_basedir;

	protected function setUp(): void {
		parent::setUp();

		$this->upload_basedir = sys_get_temp_dir() . '/alli1d-cover-image-api-tests-' . uniqid();
		mkdir( $this->upload_basedir, 0777, true );

		\Brain\Monkey\Functions\when( 'rest_ensure_response' )->returnArg( 1 );
		\Brain\Monkey\Functions\when( '__' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $value ) => rtrim( $value, '/' ) . '/' );
		\Brain\Monkey\Functions\when( 'wp_mkdir_p' )->alias(
			function ( $dir ) {
				if ( ! is_dir( $dir ) ) {
					mkdir( $dir, 0777, true );
				}
				return true;
			}
			);
		\Brain\Monkey\Functions\when( 'wp_upload_dir' )->alias(
			fn() => [
				'basedir' => $this->upload_basedir,
				'baseurl' => 'http://example.com/wp-content/uploads',
			]
			);
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'jpg' ] );
		\Brain\Monkey\Functions\when( 'wp_delete_file' )->alias(
			function ( $path ) {
				if ( is_file( $path ) ) {
					unlink( $path );
				}
			}
			);
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->recursive_delete( $this->upload_basedir );
	}

	private function recursive_delete( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( glob( $dir . '/*' ) ?: [] as $path ) {
			is_dir( $path ) ? $this->recursive_delete( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}

	/**
	 * Minimal in-memory stand-in for $wpdb: seeds one row and records update()/insert() calls.
	 */
	private function reset_repositories( array $rows ): object {
		global $wpdb;
		$wpdb = new class( $rows ) {
			public string $prefix = 'wp_';
			public array $rows;
			public $last_updated = null;

			public function __construct( array $rows ) {
				$this->rows = $rows;
			}

			public function get_results( $query, $output = ARRAY_A ) {
				return $this->rows;
			}

			public function get_row( $query, $output = ARRAY_A ) {
				return $this->rows[0] ?? null;
			}

			public function prepare( $query, ...$args ) {
				return $query;
			}

			public function update( $table, $data, $where, $format = null, $where_format = null ) {
				$this->last_updated = $data;
				return true;
			}
		};

		foreach ( [ MovieRepository::class, TvShowRepository::class ] as $repository_class ) {
			$ref = new \ReflectionProperty( $repository_class, 'instance' );
			$ref->setAccessible( true );
			$ref->setValue( null, null );
		}

		return $wpdb;
	}

	private function make_row( array $overrides = [] ): array {
		return array_merge(
			[
				'id'           => 1,
				'title'        => 'Item',
				'search_title' => 'Item',
				'audio_format' => 'VOSTFR',
				'cover_image'  => 'https://example.com/old-cover.jpg',
				'status'       => 'actif',
				'data'         => json_encode( [] ),
				'urls'         => json_encode( [] ),
			],
			$overrides
			);
	}

	private function make_uploaded_file(): array {
		$tmp_path = tempnam( sys_get_temp_dir(), 'alli1d-upload-' );
		file_put_contents( $tmp_path, 'fake-image-bytes' );

		return [
			'name'     => 'cover.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => $tmp_path,
			'error'    => UPLOAD_ERR_OK,
			'size'     => 17,
		];
	}

	private function make_request( array $params, array $files = [] ) {
		return new class( $params, $files ) {
			private array $params;
			private array $files;

			public function __construct( array $params, array $files ) {
				$this->params = $params;
				$this->files  = $files;
			}

			public function get_param( $key ) {
				return $this->params[ $key ] ?? null;
			}

			public function get_file_params() {
				return $this->files;
			}
		};
	}

	public function test_upload_rejects_invalid_type_param(): void {
		$api      = new CoverImageApi( 'all-i1d/v1' );
		$response = $api->upload_cover_image(
			$this->make_request( [ 'type' => 'invalid', 'itemId' => 1 ], [ 'cover_image' => $this->make_uploaded_file() ] )
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
	}

	public function test_upload_rejects_missing_file(): void {
		$api      = new CoverImageApi( 'all-i1d/v1' );
		$response = $api->upload_cover_image(
			$this->make_request( [ 'type' => 'movie', 'itemId' => 1 ], [] )
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
	}

	public function test_upload_returns_not_found_when_fiche_does_not_exist(): void {
		$this->reset_repositories( [] );

		$api      = new CoverImageApi( 'all-i1d/v1' );
		$response = $api->upload_cover_image(
			$this->make_request( [ 'type' => 'movie', 'itemId' => 999 ], [ 'cover_image' => $this->make_uploaded_file() ] )
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
	}

	public function test_upload_updates_movie_cover_image(): void {
		$wpdb = $this->reset_repositories( [ $this->make_row() ] );

		$api      = new CoverImageApi( 'all-i1d/v1' );
		$response = $api->upload_cover_image(
			$this->make_request( [ 'type' => 'movie', 'itemId' => 1 ], [ 'cover_image' => $this->make_uploaded_file() ] )
			);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'http://example.com/wp-content/uploads/alli1d-covers/movie-1.jpg', $response['cover_image'] );
		$this->assertSame( 'http://example.com/wp-content/uploads/alli1d-covers/movie-1.jpg', $wpdb->last_updated['cover_image'] );
	}

	public function test_upload_updates_tvshow_cover_image(): void {
		$wpdb = $this->reset_repositories( [ $this->make_row() ] );

		$api      = new CoverImageApi( 'all-i1d/v1' );
		$response = $api->upload_cover_image(
			$this->make_request( [ 'type' => 'tvshow', 'itemId' => 1 ], [ 'cover_image' => $this->make_uploaded_file() ] )
			);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'http://example.com/wp-content/uploads/alli1d-covers/tvshow-1.jpg', $wpdb->last_updated['cover_image'] );
	}

	public function test_upload_rejects_invalid_file_type(): void {
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'exe' ] );
		$this->reset_repositories( [ $this->make_row() ] );

		$api      = new CoverImageApi( 'all-i1d/v1' );
		$response = $api->upload_cover_image(
			$this->make_request( [ 'type' => 'movie', 'itemId' => 1 ], [ 'cover_image' => $this->make_uploaded_file() ] )
			);

		$this->assertInstanceOf( \WP_Error::class, $response );
	}
}
