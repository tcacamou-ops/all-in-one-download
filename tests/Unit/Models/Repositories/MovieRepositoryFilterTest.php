<?php
namespace AllI1D\Tests\Unit\Models\Repositories;

use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Tests\UnitTestCase;

class MovieRepositoryFilterTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'esc_html' )->returnArg();
		global $wpdb;
		$wpdb         = new \stdClass();
		$wpdb->prefix = 'wp_';

		// Reset singleton so the constructor runs fresh with the mocked $wpdb.
		$ref = new \ReflectionProperty( MovieRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_get_all_movies_throws_for_invalid_field(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid filter field' );
		MovieRepository::get_instance()->get_all_movies( [ 'id) OR 1=1 --' => [ '=', '1' ] ] );
	}

	public function test_get_all_movies_throws_for_invalid_operator(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid operator' );
		MovieRepository::get_instance()->get_all_movies( [ 'status' => [ 'DROP', 'actif' ] ] );
	}

	public function test_get_all_movies_accepts_all_whitelisted_fields(): void {
		$whitelisted = [ 'id', 'title', 'search_title', 'audio_format', 'quality', 'cover_image', 'status', 'data', 'urls' ];
		foreach ( $whitelisted as $field ) {
			// Reset singleton for each iteration.
			$ref = new \ReflectionProperty( MovieRepository::class, 'instance' );
			$ref->setAccessible( true );
			$ref->setValue( null, null );

			$threw = false;
			try {
				MovieRepository::get_instance()->get_all_movies( [ $field => [ '=', 'x' ] ] );
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
