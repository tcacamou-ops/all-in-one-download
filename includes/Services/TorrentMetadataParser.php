<?php
/**
 * Shared quality/language extraction from a raw torrent/release title.
 *
 * @package AllI1D
 */

namespace AllI1D\Services;

class TorrentMetadataParser {

	/**
	 * Quality tags, most specific first so e.g. "2160p" isn't shadowed by a
	 * looser pattern. Reuses the same vocabulary as
	 * `TorrentTitleMatcher::NOISE_TAGS` rather than hand-rolling a second list.
	 *
	 * @var string[]
	 */
	private const QUALITY_TAGS = [
		'2160p',
		'4k',
		'1080p',
		'720p',
		'480p',
	];

	/**
	 * Language tags, checked in order.
	 *
	 * @var string[]
	 */
	private const LANGUAGE_TAGS = [
		'truefrench',
		'vostfr',
		'subfrench',
		'french',
		'multi',
		'vff',
		'vf2',
		'vfi',
		'vfq',
	];

	/**
	 * Extract the quality tag (e.g. "1080p") from a release title.
	 *
	 * @param string $release_title Raw release/torrent title.
	 * @return string|null
	 */
	public function extract_quality( string $release_title ): ?string {
		return $this->find_tag( $release_title, self::QUALITY_TAGS );
	}

	/**
	 * Extract the language tag (e.g. "VOSTFR") from a release title.
	 *
	 * @param string $release_title Raw release/torrent title.
	 * @return string|null
	 */
	public function extract_language( string $release_title ): ?string {
		$tag = $this->find_tag( $release_title, self::LANGUAGE_TAGS );

		return null === $tag ? null : strtoupper( $tag );
	}

	/**
	 * Find the first matching tag (word-boundary, case-insensitive) in a title.
	 *
	 * @param string   $release_title Raw release/torrent title.
	 * @param string[] $tags          Ordered list of tags to look for.
	 * @return string|null
	 */
	private function find_tag( string $release_title, array $tags ): ?string {
		foreach ( $tags as $tag ) {
			if ( preg_match( '/\b' . preg_quote( $tag, '/' ) . '\b/i', $release_title ) ) {
				return $tag;
			}
		}

		return null;
	}
}
