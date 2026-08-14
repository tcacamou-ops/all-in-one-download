<?php
namespace AllI1D\Tests\Unit\Models\Repositories;

use AllI1D\Models\Repositories\TvShowRepository;
use AllI1D\Models\TvShow;
use AllI1D\Tests\Support\TvShowFakeWpdb;
use AllI1D\Tests\UnitTestCase;

class TvShowRepositoryTest extends UnitTestCase {

	private TvShowFakeWpdb $wpdb;

	protected function setUp(): void {
		parent::setUp();

		\Brain\Monkey\Functions\when( 'wp_json_encode' )->alias( fn( $value ) => json_encode( $value ) );
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();

		global $wpdb;
		$wpdb       = new TvShowFakeWpdb();
		$this->wpdb = $wpdb;

		$ref = new \ReflectionProperty( TvShowRepository::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	public function test_reset_all_general_search_done_resets_every_tv_show_to_an_empty_map(): void {
		$repository = TvShowRepository::get_instance();

		$tv_show = new TvShow(
			[
				'title'               => 'Breaking Bad',
				'search_title'        => 'breaking bad',
				'audio_format'        => 'VOSTFR',
				'cover_image'         => 'https://example.com/cover.jpg',
				'status'              => 'actif',
				'data'                => [],
				'urls'                => [],
				'general_search_done' => [ 1 => [ 1 => true ] ],
			]
		);
		$repository->save_tv_show( $tv_show );

		$affected = $repository->reset_all_general_search_done();

		$this->assertSame( 1, $affected );
		$reloaded = $repository->get_tv_show_by_id( $tv_show->get_id() );
		$this->assertSame( [], $reloaded->get_general_search_done() );
	}

	public function test_reset_all_general_search_done_returns_zero_on_an_empty_table(): void {
		$repository = TvShowRepository::get_instance();

		$this->assertSame( 0, $repository->reset_all_general_search_done() );
	}

	public function test_save_tv_show_persists_quality_csv_on_insert(): void {
		$repository = TvShowRepository::get_instance();

		$tv_show = new TvShow(
			[
				'title'        => 'Breaking Bad',
				'search_title' => 'breaking bad',
				'audio_format' => 'VOSTFR',
				'quality'      => '1080p,2160p',
				'cover_image'  => 'https://example.com/cover.jpg',
				'status'       => 'actif',
			]
		);
		$repository->save_tv_show( $tv_show );

		$reloaded = $repository->get_tv_show_by_id( $tv_show->get_id() );
		$this->assertSame( '1080p,2160p', $reloaded->get_quality() );
	}

	public function test_save_tv_show_persists_quality_csv_on_update(): void {
		$repository = TvShowRepository::get_instance();

		$tv_show = new TvShow(
			[
				'title'        => 'Breaking Bad',
				'search_title' => 'breaking bad',
				'audio_format' => 'VOSTFR',
				'cover_image'  => 'https://example.com/cover.jpg',
				'status'       => 'actif',
			]
		);
		$repository->save_tv_show( $tv_show );

		$tv_show->set_quality( '720p' );
		$repository->save_tv_show( $tv_show );

		$reloaded = $repository->get_tv_show_by_id( $tv_show->get_id() );
		$this->assertSame( '720p', $reloaded->get_quality() );
	}
}
