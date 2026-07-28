<?php
namespace AllI1D\Tests\Unit\Models;

use AllI1D\Models\TvShow;
use AllI1D\Tests\UnitTestCase;

class TvShowTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		\Brain\Monkey\Functions\when( 'esc_url_raw' )->returnArg();
	}

	private function valid_attrs( array $overrides = [] ): array {
		return array_merge( [ 'cover_image' => 'https://example.com/cover.jpg' ], $overrides );
	}

	// -------------------------------------------------------------------------
	// Constructeur — valeurs par défaut
	// -------------------------------------------------------------------------

	public function test_constructor_default_id_is_null(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertNull( $show->get_id() );
	}

	public function test_constructor_default_title_is_empty_string(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( '', $show->get_title() );
	}

	public function test_constructor_default_audio_format_is_vostfr(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( 'VOSTFR', $show->get_audio_format() );
	}

	public function test_constructor_default_status_is_actif(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( 'actif', $show->get_status() );
	}

	public function test_constructor_default_data_is_empty_array(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( [], $show->get_data() );
	}

	public function test_constructor_default_urls_is_empty_array(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( [], $show->get_urls() );
	}

	// -------------------------------------------------------------------------
	// set_id / get_id
	// -------------------------------------------------------------------------

	public function test_set_id_accepts_positive_integer(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->set_id( 10 );
		$this->assertSame( 10, $show->get_id() );
	}

	public function test_set_id_accepts_null(): void {
		$show = new TvShow( $this->valid_attrs( [ 'id' => 1 ] ) );
		$show->set_id( null );
		$this->assertNull( $show->get_id() );
	}

	public function test_set_id_throws_for_zero(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new TvShow( $this->valid_attrs() ) )->set_id( 0 );
	}

	public function test_set_id_throws_for_negative(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new TvShow( $this->valid_attrs() ) )->set_id( -1 );
	}

	// -------------------------------------------------------------------------
	// set_title / get_title
	// -------------------------------------------------------------------------

	public function test_get_title_returns_sanitized_value(): void {
		$show = new TvShow( $this->valid_attrs( [ 'title' => 'Breaking Bad' ] ) );
		$this->assertSame( 'Breaking Bad', $show->get_title() );
	}

	// -------------------------------------------------------------------------
	// get_search_title
	// -------------------------------------------------------------------------

	public function test_get_search_title_falls_back_to_title_when_empty(): void {
		$show = new TvShow( $this->valid_attrs( [ 'title' => 'Breaking Bad' ] ) );
		$this->assertSame( 'Breaking Bad', $show->get_search_title() );
	}

	public function test_get_search_title_returns_own_value_when_set(): void {
		$show = new TvShow(
			$this->valid_attrs(
			[
				'title'        => 'Breaking Bad',
				'search_title' => 'breaking bad s01',
			]
			)
			);
		$this->assertSame( 'breaking bad s01', $show->get_search_title() );
	}

	// -------------------------------------------------------------------------
	// set_cover_image
	// -------------------------------------------------------------------------

	public function test_set_cover_image_accepts_valid_url(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->set_cover_image( 'https://example.com/new.jpg' );
		$this->assertSame( 'https://example.com/new.jpg', $show->get_cover_image() );
	}

	public function test_set_cover_image_throws_for_empty_string(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new TvShow( $this->valid_attrs() ) )->set_cover_image( '' );
	}

	public function test_set_cover_image_throws_for_invalid_url(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new TvShow( $this->valid_attrs() ) )->set_cover_image( 'not-a-url' );
	}

	// -------------------------------------------------------------------------
	// set_status / get_status
	// -------------------------------------------------------------------------

	public function test_set_status_accepts_actif(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->set_status( 'actif' );
		$this->assertSame( 'actif', $show->get_status() );
	}

	public function test_set_status_accepts_inactif(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->set_status( 'inactif' );
		$this->assertSame( 'inactif', $show->get_status() );
	}

	public function test_set_status_accepts_downloaded(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->set_status( 'downloaded' );
		$this->assertSame( 'downloaded', $show->get_status() );
	}

	public function test_set_status_throws_for_invalid_status(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new TvShow( $this->valid_attrs() ) )->set_status( 'invalid' );
	}

	// -------------------------------------------------------------------------
	// add_url
	// -------------------------------------------------------------------------

	public function test_add_url_appends_valid_url(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->add_url( 'https://example.com/ep01.mkv' );
		$this->assertContains( 'https://example.com/ep01.mkv', $show->get_urls() );
	}

	public function test_add_url_throws_for_invalid_url(): void {
		$this->expectException( \InvalidArgumentException::class );
		( new TvShow( $this->valid_attrs() ) )->add_url( 'not-a-url' );
	}

	public function test_add_url_does_not_duplicate_existing_url(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->add_url( 'https://example.com/ep01.mkv' );
		$show->add_url( 'https://example.com/ep01.mkv' );
		$this->assertCount( 1, $show->get_urls() );
	}

	// -------------------------------------------------------------------------
	// Saisons — init_data / get_saisons
	// -------------------------------------------------------------------------

	public function test_get_saisons_returns_empty_array_before_init(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( [], $show->get_saisons() );
	}

	public function test_init_data_creates_first_saison(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$saisons = $show->get_saisons();
		$this->assertCount( 1, $saisons );
		$this->assertSame( 1, $saisons[0]['id'] );
		$this->assertSame( 'actif', $saisons[0]['status'] );
		$this->assertSame( 0, $saisons[0]['lastepisode'] );
	}

	public function test_init_data_seeds_given_saison_and_episode(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data( 3, 7 );
		$saisons = $show->get_saisons();
		$this->assertCount( 1, $saisons );
		$this->assertSame( 3, $saisons[0]['id'] );
		$this->assertSame( 'actif', $saisons[0]['status'] );
		$this->assertSame( 7, $saisons[0]['lastepisode'] );
	}

	// -------------------------------------------------------------------------
	// add_saison
	// -------------------------------------------------------------------------

	public function test_add_saison_inserts_new_saison(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$show->add_saison( 2 );
		$saisons = $show->get_saisons();
		$this->assertCount( 2, $saisons );
		$this->assertSame( 2, $saisons[1]['id'] );
		$this->assertSame( 'actif', $saisons[1]['status'] );
		$this->assertSame( 0, $saisons[1]['lastepisode'] );
	}

	public function test_add_saison_is_idempotent_when_saison_already_exists(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$show->next_episode( 1, 5 );
		$show->enable_saison( 1, false );

		$show->add_saison( 1 );

		$saisons = $show->get_saisons();
		$this->assertCount( 1, $saisons );
		$this->assertSame( 5, $saisons[0]['lastepisode'] );
		$this->assertSame( 'inactif', $saisons[0]['status'] );
	}

	public function test_add_saison_on_empty_data_creates_saison(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->add_saison( 4 );
		$saisons = $show->get_saisons();
		$this->assertCount( 1, $saisons );
		$this->assertSame( 4, $saisons[0]['id'] );
	}

	// -------------------------------------------------------------------------
	// set_saisons
	// -------------------------------------------------------------------------

	public function test_set_saisons_stores_saisons_in_data(): void {
		$show    = new TvShow( $this->valid_attrs() );
		$saisons = [
			[
				'id'          => 1,
				'status'      => 'actif',
				'lastepisode' => 3,
			],
		];
		$show->set_saisons( $saisons );
		$this->assertSame( $saisons, $show->get_saisons() );
	}

	// -------------------------------------------------------------------------
	// next_saison
	// -------------------------------------------------------------------------

	public function test_next_saison_adds_new_saison_when_at_last(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$show->next_saison( 1 );
		$this->assertCount( 2, $show->get_saisons() );
	}

	public function test_next_saison_does_not_add_when_not_at_last(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->set_saisons(
			[
				[
					'id'          => 1,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
				[
					'id'          => 2,
					'status'      => 'actif',
					'lastepisode' => 0,
				],
			]
			);
		$show->next_saison( 1 );
		$this->assertCount( 2, $show->get_saisons() );
	}

	// -------------------------------------------------------------------------
	// enable_saison
	// -------------------------------------------------------------------------

	public function test_enable_saison_sets_status_to_inactif(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$show->enable_saison( 1, false );
		$saisons = $show->get_saisons();
		$this->assertSame( 'inactif', $saisons[0]['status'] );
	}

	public function test_enable_saison_sets_status_back_to_actif(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$show->enable_saison( 1, false );
		$show->enable_saison( 1, true );
		$saisons = $show->get_saisons();
		$this->assertSame( 'actif', $saisons[0]['status'] );
	}

	// -------------------------------------------------------------------------
	// next_episode
	// -------------------------------------------------------------------------

	public function test_next_episode_updates_lastepisode(): void {
		$show = new TvShow( $this->valid_attrs() );
		$show->init_data();
		$show->next_episode( 1, 5 );
		$saisons = $show->get_saisons();
		$this->assertSame( 5, $saisons[0]['lastepisode'] );
	}

	// -------------------------------------------------------------------------
	// get_download_directory
	// -------------------------------------------------------------------------

	public function test_get_download_directory_includes_title_and_saison(): void {
		\Brain\Monkey\Functions\when( 'get_option' )->justReturn( '/downloads/TvShows' );
		\Brain\Monkey\Functions\when( 'trailingslashit' )->alias( fn( $s ) => rtrim( $s, '/' ) . '/' );

		$show      = new TvShow( $this->valid_attrs( [ 'title' => 'Breaking Bad' ] ) );
		$directory = $show->get_download_directory( 1 );

		$this->assertStringContainsString( 'Breaking_Bad', $directory );
		$this->assertStringContainsString( '1', $directory );
	}

	// -------------------------------------------------------------------------
	// get_type
	// -------------------------------------------------------------------------

	public function test_get_type_returns_tvshow(): void {
		$show = new TvShow( $this->valid_attrs() );
		$this->assertSame( 'tvshow', $show->get_type() );
	}

	// -------------------------------------------------------------------------
	// Constantes statiques
	// -------------------------------------------------------------------------

	public function test_static_actif_equals_actif(): void {
		$this->assertSame( 'actif', TvShow::$actif );
	}

	public function test_static_inactif_equals_inactif(): void {
		$this->assertSame( 'inactif', TvShow::$inactif );
	}

	public function test_static_downloaded_equals_downloaded(): void {
		$this->assertSame( 'downloaded', TvShow::$downloaded );
	}
}
