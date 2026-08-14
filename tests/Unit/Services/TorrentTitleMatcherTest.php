<?php
namespace AllI1D\Tests\Unit\Services;

use AllI1D\Services\TorrentTitleMatcher;
use AllI1D\Tests\UnitTestCase;

class TorrentTitleMatcherTest extends UnitTestCase {

	private TorrentTitleMatcher $matcher;

	protected function setUp(): void {
		parent::setUp();

		// Reset the singleton so the constructor (which registers filters/actions
		// via Brain Monkey's real hook storage) runs fresh for every test.
		$ref = new \ReflectionProperty( TorrentTitleMatcher::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		$this->matcher = TorrentTitleMatcher::get_instance();
	}

	// -------------------------------------------------------------------------
	// matches — season/episode ("full season" sentinel)
	// -------------------------------------------------------------------------

	public function test_matches_accepts_single_episode_torrent_when_episode_context_is_zero(): void {
		// TvShowCron sends episode = 0 as a "full season, no specific episode
		// yet" sentinel. A per-episode torrent (no season pack released yet)
		// must still be accepted for the requested season.
		$this->assertTrue(
			$this->matcher->matches(
				true,
				[
					'torrent_name' => 'Ted.Lasso.S04E01.MULTi.1080p.WEB.EAC3.5.1.H264-GL0P',
					'title'        => 'Ted Lasso',
					'year'         => null,
					'saison'       => 4,
					'episode'      => 0,
				]
			)
		);
	}

	public function test_matches_rejects_wrong_season_when_episode_context_is_zero(): void {
		$this->assertFalse(
			$this->matcher->matches(
				true,
				[
					'torrent_name' => 'Ted.Lasso.S03E01.MULTi.1080p.WEB.EAC3.5.1.H264-GL0P',
					'title'        => 'Ted Lasso',
					'year'         => null,
					'saison'       => 4,
					'episode'      => 0,
				]
			)
		);
	}

	public function test_matches_still_enforces_a_real_episode_number(): void {
		$this->assertFalse(
			$this->matcher->matches(
				true,
				[
					'torrent_name' => 'Ted.Lasso.S04E02.MULTi.1080p.WEB.EAC3.5.1.H264-GL0P',
					'title'        => 'Ted Lasso',
					'year'         => null,
					'saison'       => 4,
					'episode'      => 1,
				]
			)
		);
	}

	// -------------------------------------------------------------------------
	// matches_quality — short-circuit behaviour
	// -------------------------------------------------------------------------

	public function test_matches_quality_short_circuits_to_false_when_is_match_already_false(): void {
		$result = $this->matcher->matches_quality(
			false,
			[
				'torrent_quality' => '1080p',
				'preference'      => '1080p',
			]
		);

		$this->assertFalse( $result );
	}

	public function test_matches_quality_accepts_any_preference_regardless_of_candidate(): void {
		$this->assertTrue(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => '480p',
					'preference'      => 'any',
				]
			)
		);
	}

	public function test_matches_quality_treats_empty_preference_as_any(): void {
		$this->assertTrue(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => null,
					'preference'      => '',
				]
			)
		);
	}

	public function test_matches_quality_treats_missing_preference_as_any(): void {
		$this->assertTrue(
			$this->matcher->matches_quality( true, [ 'torrent_quality' => '720p' ] )
		);
	}

	// -------------------------------------------------------------------------
	// matches_quality — exact-set membership
	// -------------------------------------------------------------------------

	public function test_matches_quality_accepts_a_tier_that_is_a_member_of_the_selected_set(): void {
		$this->assertTrue(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => '1080p',
					'preference'      => '1080p,2160p',
				]
			)
		);
	}

	public function test_matches_quality_accepts_4k_aliased_candidate_against_2160p_preference(): void {
		$this->assertTrue(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => '4k',
					'preference'      => '1080p,2160p',
				]
			)
		);
	}

	public function test_matches_quality_rejects_a_tier_not_in_the_selected_set(): void {
		$this->assertFalse(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => '720p',
					'preference'      => '1080p,2160p',
				]
			)
		);
	}

	public function test_matches_quality_rejects_middle_tier_for_a_non_contiguous_set(): void {
		// Preference deliberately skips 1080p: {720p, 2160p}. A 1080p candidate
		// must be rejected — proves this is set membership, not a >= comparison.
		$this->assertFalse(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => '1080p',
					'preference'      => '720p,2160p',
				]
			)
		);
	}

	public function test_matches_quality_accepts_both_ends_of_a_non_contiguous_set(): void {
		$context_720p = [
			'torrent_quality' => '720p',
			'preference'      => '720p,2160p',
		];
		$context_2160p = [
			'torrent_quality' => '2160p',
			'preference'      => '720p,2160p',
		];

		$this->assertTrue( $this->matcher->matches_quality( true, $context_720p ) );
		$this->assertTrue( $this->matcher->matches_quality( true, $context_2160p ) );
	}

	// -------------------------------------------------------------------------
	// matches_quality — unparseable candidate quality
	// -------------------------------------------------------------------------

	public function test_matches_quality_rejects_unparseable_candidate_against_a_specific_preference(): void {
		$this->assertFalse(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => null,
					'preference'      => '1080p,2160p',
				]
			)
		);
	}

	public function test_matches_quality_rejects_480p_candidate_against_a_specific_preference(): void {
		// 480p is parseable by TorrentMetadataParser::extract_quality() but is
		// never a valid selection/matchable target, so normalize_quality()
		// returns null for it and it is rejected like any other unparseable value.
		$this->assertFalse(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => '480p',
					'preference'      => '1080p,2160p',
				]
			)
		);
	}

	public function test_matches_quality_accepts_unparseable_candidate_against_any(): void {
		$this->assertTrue(
			$this->matcher->matches_quality(
				true,
				[
					'torrent_quality' => null,
					'preference'      => 'any',
				]
			)
		);
	}

	// -------------------------------------------------------------------------
	// matches_quality — rejection logging
	// -------------------------------------------------------------------------

	public function test_matches_quality_fires_rejection_action_with_quality_mismatch_reason_on_reject(): void {
		\Brain\Monkey\Actions\expectDone( 'alli1d_torrent_rejected' )
			->once()
			->with(
				\Mockery::on(
					function ( $context ) {
						return is_array( $context ) && 'quality_mismatch' === ( $context['reason'] ?? null );
					}
				)
			);

		$this->matcher->matches_quality(
			true,
			[
				'torrent_quality' => '720p',
				'preference'      => '1080p,2160p',
			]
		);
	}

	public function test_matches_quality_does_not_fire_rejection_action_on_accept(): void {
		\Brain\Monkey\Actions\expectDone( 'alli1d_torrent_rejected' )->never();

		$this->matcher->matches_quality(
			true,
			[
				'torrent_quality' => '1080p',
				'preference'      => '1080p,2160p',
			]
		);
	}
}
