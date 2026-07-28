<?php
namespace AllI1D\Tests\Unit\Services;

use AllI1D\Services\CoverImageUploader;
use AllI1D\Tests\UnitTestCase;

class CoverImageUploaderTest extends UnitTestCase {

	/**
	 * Temp directory used as the fake wp_upload_dir() basedir for this test run.
	 *
	 * @var string
	 */
	private string $upload_basedir;

	protected function setUp(): void {
		parent::setUp();

		$this->upload_basedir = sys_get_temp_dir() . '/alli1d-cover-image-tests-' . uniqid();
		mkdir( $this->upload_basedir, 0777, true );

		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $value ) => rtrim( $value, '/' ) . '/' );
		\Brain\Monkey\Functions\when( 'wp_delete_file' )->alias(
			function ( $path ) {
				if ( is_file( $path ) ) {
					unlink( $path );
				}
			}
			);
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

	private function make_uploaded_file( string $contents = 'fake-image-bytes', string $name = 'cover.jpg', int $size = null ): array {
		$tmp_path = tempnam( sys_get_temp_dir(), 'alli1d-upload-' );
		file_put_contents( $tmp_path, $contents );

		return [
			'name'     => $name,
			'type'     => 'image/jpeg',
			'tmp_name' => $tmp_path,
			'error'    => UPLOAD_ERR_OK,
			'size'     => $size ?? strlen( $contents ),
		];
	}

	public function test_store_writes_file_with_deterministic_name_and_returns_url(): void {
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'jpg' ] );

		$url = CoverImageUploader::store( $this->make_uploaded_file(), 'movie', 42 );

		$this->assertSame( 'http://example.com/wp-content/uploads/alli1d-covers/movie-42.jpg', $url );
		$this->assertFileExists( $this->upload_basedir . '/alli1d-covers/movie-42.jpg' );
		$this->assertSame( 'fake-image-bytes', file_get_contents( $this->upload_basedir . '/alli1d-covers/movie-42.jpg' ) );
	}

	public function test_store_replaces_existing_cover_for_same_fiche(): void {
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'jpg' ] );

		CoverImageUploader::store( $this->make_uploaded_file( 'first-version' ), 'movie', 42 );
		CoverImageUploader::store( $this->make_uploaded_file( 'second-version' ), 'movie', 42 );

		$files = glob( $this->upload_basedir . '/alli1d-covers/movie-42.*' );
		$this->assertCount( 1, $files );
		$this->assertSame( 'second-version', file_get_contents( $files[0] ) );
	}

	public function test_store_removes_old_file_when_extension_changes(): void {
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'jpg' ] );
		CoverImageUploader::store( $this->make_uploaded_file( 'jpg-version', 'cover.jpg' ), 'movie', 42 );

		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'png' ] );
		CoverImageUploader::store( $this->make_uploaded_file( 'png-version', 'cover.png' ), 'movie', 42 );

		$files = glob( $this->upload_basedir . '/alli1d-covers/movie-42.*' );
		$this->assertCount( 1, $files );
		$this->assertStringEndsWith( '.png', $files[0] );
	}

	public function test_store_rejects_disallowed_file_type(): void {
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'exe' ] );

		$this->expectException( \InvalidArgumentException::class );
		CoverImageUploader::store( $this->make_uploaded_file( 'x', 'virus.exe' ), 'movie', 42 );
	}

	public function test_store_rejects_file_exceeding_max_size(): void {
		$this->expectException( \InvalidArgumentException::class );
		CoverImageUploader::store( $this->make_uploaded_file( 'x', 'cover.jpg', 6 * 1024 * 1024 ), 'movie', 42 );
	}

	public function test_store_rejects_upload_with_error_code(): void {
		$file          = $this->make_uploaded_file();
		$file['error'] = UPLOAD_ERR_PARTIAL;

		$this->expectException( \InvalidArgumentException::class );
		CoverImageUploader::store( $file, 'movie', 42 );
	}

	public function test_delete_if_exists_removes_matching_file(): void {
		\Brain\Monkey\Functions\when( 'wp_check_filetype_and_ext' )->justReturn( [ 'ext' => 'jpg' ] );
		CoverImageUploader::store( $this->make_uploaded_file(), 'tvshow', 7 );

		$this->assertFileExists( $this->upload_basedir . '/alli1d-covers/tvshow-7.jpg' );

		CoverImageUploader::delete_if_exists( 'tvshow', 7 );

		$this->assertFileDoesNotExist( $this->upload_basedir . '/alli1d-covers/tvshow-7.jpg' );
	}

	public function test_delete_if_exists_is_a_no_op_when_nothing_matches(): void {
		$this->expectNotToPerformAssertions();
		CoverImageUploader::delete_if_exists( 'movie', 999 );
	}
}
