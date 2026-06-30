<?php
namespace AllI1D\Tests\Unit\Models\Repositories;

use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Tests\UnitTestCase;

class TvShowRepositoryFilterTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'esc_html' )->returnArg();
		global $wpdb;
		$wpdb         = new \stdClass();
		$wpdb->prefix = 'wp_';

		// Reset singleton so the constructor runs fresh with the mocked $wpdb.
		$ref = new \ReflectionProperty( TvShowRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_get_all_tv_shows_throws_for_invalid_field(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid filter field' );
		TvShowRepository::get_instance()->get_all_tv_shows( [ 'id) OR 1=1 --' => [ '=', '1' ] ] );
	}

	public function test_get_all_tv_shows_throws_for_invalid_operator(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid operator' );
		TvShowRepository::get_instance()->get_all_tv_shows( [ 'status' => [ 'DROP', 'actif' ] ] );
	}

	public function test_get_all_tv_shows_accepts_all_whitelisted_fields(): void {
		$whitelisted = [ 'id', 'title', 'search_title', 'audio_format', 'cover_image', 'status', 'data', 'urls' ];
		foreach ( $whitelisted as $field ) {
			// Reset singleton for each iteration.
			$ref = new \ReflectionProperty( TvShowRepository::class, 'instance' );
			$ref->setAccessible( true );
			$ref->setValue( null, null );

			$threw = false;
			try {
				TvShowRepository::get_instance()->get_all_tv_shows( [ $field => [ '=', 'x' ] ] );
			} catch ( \InvalidArgumentException $e ) {
				if ( str_contains( $e->getMessage(), 'Invalid filter field' ) ) {
					$threw = true;
				}
			} catch ( \Throwable $e ) {
				// wpdb->get_results not available — field validation passed.
			}
			$this->assertFalse( $threw, "Field '$field' should be whitelisted but was rejected." );
		}
	}
}
