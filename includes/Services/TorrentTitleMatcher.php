<?php
/**
 * Centralized torrent/title matching service.
 *
 * @package AllI1D
 */

namespace AllI1D\Services;

use AllI1D\Actions\Logs;

class TorrentTitleMatcher {

	/**
	 * Default minimum similarity percentage (0-100) required between the
	 * normalized requested title and the normalized torrent name.
	 */
	public const DEFAULT_TITLE_MATCH_THRESHOLD = 65.0;

	/**
	 * Tolerance, in years, applied when comparing the requested year against
	 * a year found in the torrent name.
	 */
	private const YEAR_TOLERANCE = 1;

	/**
	 * Common quality/language/release tags stripped before comparing titles.
	 * The list intentionally stays short: it only needs to remove tokens
	 * that would otherwise dilute the similarity score, not to be exhaustive.
	 *
	 * @var string[]
	 */
	private const NOISE_TAGS = [
		'vff',
		'vf2',
		'vfi',
		'vfq',
		'vostfr',
		'truefrench',
		'french',
		'multi',
		'subfrench',
		'1080p',
		'720p',
		'2160p',
		'4k',
		'480p',
		'x264',
		'x265',
		'h264',
		'h265',
		'hevc',
		'web-dl',
		'webdl',
		'webrip',
		'bluray',
		'blu-ray',
		'bdrip',
		'brrip',
		'dvdrip',
		'hdtv',
		'hdlight',
		'remux',
	];

	/**
	 * Leading articles removed from the requested title only (never from the
	 * torrent name, since the torrent name is compared as-is aside from noise
	 * tags/year removal).
	 *
	 * @var string[]
	 */
	private const LEADING_ARTICLES = [ 'the', 'a', 'an', 'le', 'la', 'les' ];

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Registers the matching filter and the rejection logger.
	 */
	private function __construct() {
		add_filter( 'alli1d_torrent_matches_title', [ $this, 'matches' ], 10, 2 );
		add_action( 'alli1d_torrent_rejected', [ $this, 'log_rejection' ], 10, 1 );
	}

	/**
	 * Filter callback for `alli1d_torrent_matches_title`.
	 *
	 * @param bool                                                                                                 $is_match Current match state (default true).
	 * @param array{torrent_name?: string, title?: string, year?: int|null, saison?: int|null, episode?: int|null} $context  Match context.
	 * @return bool
	 */
	public function matches( $is_match, array $context ): bool {
		// Respect an already-negative verdict from an earlier filter callback.
		if ( false === $is_match ) {
			return false;
		}

		$torrent_name = (string) ( $context['torrent_name'] ?? '' );
		$title        = (string) ( $context['title'] ?? '' );
		$year         = isset( $context['year'] ) ? (int) $context['year'] : null;
		$saison       = isset( $context['saison'] ) ? (int) $context['saison'] : null;
		$episode      = isset( $context['episode'] ) ? (int) $context['episode'] : null;

		if ( '' === trim( $torrent_name ) || '' === trim( $title ) ) {
			return true;
		}

		if ( ! $this->title_matches( $title, $torrent_name ) ) {
			$this->reject( $torrent_name, $title, 'title_mismatch' );
			return false;
		}

		if ( null !== $saison || null !== $episode ) {
			if ( ! $this->season_episode_matches( $torrent_name, $saison, $episode ) ) {
				$this->reject( $torrent_name, $title, 'season_episode_mismatch' );
				return false;
			}
		}

		if ( null !== $year ) {
			if ( ! $this->year_matches( $torrent_name, $year ) ) {
				$this->reject( $torrent_name, $title, 'year_mismatch' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Compare the requested title against the torrent name using a similarity
	 * percentage. Both strings are normalized first (accents stripped, noise
	 * tags/year removed, lowercased). `similar_text()` is used directly on
	 * the two normalized strings rather than trying to locate the "closest"
	 * substring: normalization already removes the release-group/quality
	 * noise that would otherwise pad the torrent name, so a direct percentage
	 * comparison is both simpler and reliable enough for the 65% default
	 * threshold.
	 *
	 * @param string $title        Requested title.
	 * @param string $torrent_name Raw torrent name.
	 * @return bool
	 */
	private function title_matches( string $title, string $torrent_name ): bool {
		$normalized_title   = $this->normalize_title( $title );
		$normalized_torrent = $this->normalize_torrent_name( $torrent_name );

		if ( '' === $normalized_title || '' === $normalized_torrent ) {
			return true;
		}

		similar_text( $normalized_title, $normalized_torrent, $percent );

		/**
		 * Filters the minimum title similarity percentage required for a
		 * torrent to be considered a match.
		 *
		 * @param float $threshold Minimum percentage (0-100). Default 65.
		 */
		$threshold = (float) apply_filters( 'alli1d_torrent_title_match_threshold', self::DEFAULT_TITLE_MATCH_THRESHOLD );

		return $percent >= $threshold;
	}

	/**
	 * Normalize a requested title for comparison: strip accents, lowercase,
	 * collapse punctuation/whitespace, and drop a single leading article.
	 *
	 * @param string $title Raw title.
	 * @return string
	 */
	private function normalize_title( string $title ): string {
		$normalized = $this->to_ascii_lowercase( $title );
		$normalized = $this->collapse_whitespace( $normalized );
		$normalized = $this->strip_leading_article( $normalized );

		return trim( $normalized );
	}

	/**
	 * Normalize a torrent name for comparison: strip accents, lowercase,
	 * remove a parenthesized/bracketed year, remove quality/language noise
	 * tags, and collapse punctuation/whitespace.
	 *
	 * @param string $torrent_name Raw torrent name.
	 * @return string
	 */
	private function normalize_torrent_name( string $torrent_name ): string {
		$normalized = $this->to_ascii_lowercase( $torrent_name );

		// Remove a year, parenthesized or not (e.g. "(2019)" or "2019").
		$normalized = (string) preg_replace( '/[\(\[]?(19|20)\d{2}[\)\]]?/', ' ', $normalized );

		// Remove common season/episode markers so they don't affect title similarity.
		$normalized = (string) preg_replace( '/\bs\d{1,2}[\s._-]*e\d{1,3}\b/i', ' ', $normalized );
		$normalized = (string) preg_replace( '/\b\d{1,2}x\d{1,3}\b/i', ' ', $normalized );
		$normalized = (string) preg_replace( '/\bsaison\s*\d{1,2}\b/i', ' ', $normalized );
		$normalized = (string) preg_replace( '/\bseason\s*\d{1,2}\b/i', ' ', $normalized );
		$normalized = (string) preg_replace( '/\bs\d{1,2}\b/i', ' ', $normalized );

		// Remove noise tags (word-boundary based, case-insensitive since already lowercased).
		foreach ( self::NOISE_TAGS as $tag ) {
			$normalized = (string) preg_replace( '/\b' . preg_quote( $tag, '/' ) . '\b/', ' ', $normalized );
		}

		$normalized = $this->collapse_whitespace( $normalized );

		return trim( $normalized );
	}

	/**
	 * Transliterate to ASCII and lowercase a string.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	private function to_ascii_lowercase( string $value ): string {
		if ( function_exists( 'remove_accents' ) ) {
			$value = remove_accents( $value );
		} elseif ( function_exists( 'iconv' ) ) {
			$transliterated = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $value );
			if ( false !== $transliterated ) {
				$value = $transliterated;
			}
		}

		return strtolower( $value );
	}

	/**
	 * Replace punctuation with spaces and collapse repeated whitespace.
	 *
	 * @param string $value Input string.
	 * @return string
	 */
	private function collapse_whitespace( string $value ): string {
		$value = (string) preg_replace( '/[^a-z0-9]+/', ' ', $value );
		return (string) preg_replace( '/\s+/', ' ', $value );
	}

	/**
	 * Remove a single leading article (le/la/les/the/a/an) from a normalized title.
	 *
	 * @param string $normalized_title Already normalized (lowercase, no punctuation) title.
	 * @return string
	 */
	private function strip_leading_article( string $normalized_title ): string {
		return (string) preg_replace( '/^(' . implode( '|', self::LEADING_ARTICLES ) . ')\s+/', '', $normalized_title );
	}

	/**
	 * Check season/episode coherence against recognizable patterns in the
	 * torrent name. If no season/episode pattern is found at all, the
	 * torrent is not rejected on this basis (e.g. season packs may only
	 * carry a title, no explicit marker).
	 *
	 * @param string   $torrent_name Raw torrent name.
	 * @param int|null $saison       Requested season number.
	 * @param int|null $episode      Requested episode number.
	 * @return bool
	 */
	private function season_episode_matches( string $torrent_name, ?int $saison, ?int $episode ): bool {
		$lower = strtolower( $torrent_name );

		$patterns = [
			// S01E02, S1E2, S01.E02, S01-E02, S01_E02.
			'/s(\d{1,2})[\s._-]*e(\d{1,3})/i',
			// 1x02.
			'/(\d{1,2})x(\d{1,3})/i',
			// saison 1 episode 2 / season 1 episode 2.
			'/(?:saison|season)\s*(\d{1,2})\s*(?:episode|ep|e)\s*(\d{1,3})/i',
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $lower, $matches ) ) {
				$found_season  = (int) $matches[1];
				$found_episode = (int) $matches[2];

				if ( null !== $saison && $found_season !== $saison ) {
					return false;
				}
				if ( null !== $episode && $found_episode !== $episode ) {
					return false;
				}

				// A season/episode pair matched (or wasn't requested): coherent.
				return true;
			}
		}

		// Season-only pack, e.g. "S01" or "Saison 1", with no episode marker.
		if ( null !== $saison ) {
			if ( preg_match( '/\bs(\d{1,2})\b/i', $lower, $matches ) || preg_match( '/\bsaison\s*(\d{1,2})\b/i', $lower, $matches ) || preg_match( '/\bseason\s*(\d{1,2})\b/i', $lower, $matches ) ) {
				return (int) $matches[1] === $saison;
			}
		}

		// No recognizable season/episode pattern at all: don't reject.
		return true;
	}

	/**
	 * Check the requested year against a year found in the torrent name,
	 * within a ±1 year tolerance. If no year is found in the torrent name,
	 * this criterion doesn't invalidate the match.
	 *
	 * @param string $torrent_name Raw torrent name.
	 * @param int    $year         Requested year.
	 * @return bool
	 */
	private function year_matches( string $torrent_name, int $year ): bool {
		if ( ! preg_match( '/(19\d{2}|20\d{2})/', $torrent_name, $matches ) ) {
			return true;
		}

		$found_year = (int) $matches[1];

		return abs( $found_year - $year ) <= self::YEAR_TOLERANCE;
	}

	/**
	 * Fire the rejection action for a failed match.
	 *
	 * @param string $torrent_name Raw torrent name.
	 * @param string $title        Requested title.
	 * @param string $reason       Short rejection reason code.
	 */
	private function reject( string $torrent_name, string $title, string $reason ): void {
		do_action(
			'alli1d_torrent_rejected',
			[
				'torrent_name' => $torrent_name,
				'title'        => $title,
				'reason'       => $reason,
			]
		);
	}

	/**
	 * Action callback for `alli1d_torrent_rejected`: writes a log line to the
	 * films or series log, based on whichever context fields are present.
	 *
	 * @param array{torrent_name?: string, title?: string, reason?: string} $context Rejection context.
	 */
	public function log_rejection( array $context ): void {
		$torrent_name = (string) ( $context['torrent_name'] ?? '' );
		$title        = (string) ( $context['title'] ?? '' );
		$reason       = (string) ( $context['reason'] ?? 'unknown' );

		$destination = 'season_episode_mismatch' === $reason ? Logs::SERIES_LOG : Logs::FILMS_LOG;

		$message = sprintf(
			'Torrent rejected [%s] - title: "%s" - torrent: "%s"',
			$reason,
			$title,
			$torrent_name
		);

		do_action( 'alli1d_log', $message, Logs::WARNING, $destination );
	}
}
