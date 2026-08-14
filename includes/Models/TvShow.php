<?php
/**
 * TvShow model class.
 *
 * @package AllI1D
 */

namespace AllI1D\Models;

use AllI1D\Services\TorrentMetadataParser;

class TvShow {
	/**
	 * The TvShow ID, null for new objects.
	 *
	 * @var int|null
	 */
	private ?int $id;

	/**
	 * The TvShow title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * The search title used to find the TvShow.
	 *
	 * @var string
	 */
	private string $search_title;

	/**
	 * The audio format.
	 *
	 * @var string
	 */
	private string $audio_format = '';

	/**
	 * The video quality preference, either 'any' or a CSV list of tiers.
	 *
	 * @var string
	 */
	private string $quality = '';

	/**
	 * The cover image URL.
	 *
	 * @var string
	 */
	private string $cover_image = '';

	/**
	 * The TvShow status.
	 *
	 * @var string
	 */
	private string $status = '';

	/**
	 * Additional data for the TvShow.
	 *
	 * @var array<string, mixed>
	 */
	private array $data = [];

	/**
	 * Download URLs for the TvShow.
	 *
	 * @var array<int, string>
	 */
	private array $urls = [];

	/**
	 * Maximum season number.
	 *
	 * @var int|null
	 */
	private ?int $max_saison = null;

	/**
	 * Saison/episode combos for which a general API search has already been
	 * performed, keyed as [saison_id => [episode_id => true, ...], ...]. A
	 * combo not yet searched simply has no entry here.
	 *
	 * @var array<int, array<int, bool>>
	 */
	private array $general_search_done = [];

	public const DEFAULT_DIRECTORY = '/downloads/TvShows';

	private const VALID_STATUSES = [ 'actif', 'inactif', 'downloaded' ];

	/**
	 * Active status constant.
	 *
	 * @var string
	 */
	public static $actif = 'actif';

	/**
	 * Inactive status constant.
	 *
	 * @var string
	 */
	public static $inactif = 'inactif';

	/**
	 * Downloaded status constant.
	 *
	 * @var string
	 */
	public static $downloaded = 'downloaded';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $attributes Initial attributes for the TvShow.
	 */
	public function __construct( array $attributes = [] ) {
		$this->set_id( $attributes['id'] ?? null );
		$this->set_title( $attributes['title'] ?? '' );
		$this->set_search_title( $attributes['search_title'] ?? '' );
		$this->set_audio_format( $attributes['audio_format'] ?? 'VOSTFR' );
		$this->set_quality( $attributes['quality'] ?? TorrentMetadataParser::DEFAULT_QUALITY );
		$this->set_cover_image( $attributes['cover_image'] ?? '' );
		$this->set_status( $attributes['status'] ?? self::$actif );
		$this->set_data( $attributes['data'] ?? [] );
		$this->set_urls( $attributes['urls'] ?? [] );
		$this->set_general_search_done( $attributes['general_search_done'] ?? [] );
	}

	/**
	 * Set the TvShow ID.
	 *
	 * @param int|null $id The TvShow ID.
	 * @return $this
	 * @throws \InvalidArgumentException If ID is not valid.
	 */
	public function set_id( ?int $id ) {
		if ( null !== $id && $id <= 0 ) {
			throw new \InvalidArgumentException( 'Invalid ID. Must be a positive integer or null.' );
		}
		$this->id = $id;
		return $this;
	}

	/**
	 * Set the TvShow title.
	 *
	 * @param string $title The title.
	 * @return $this
	 */
	public function set_title( string $title ) {
		$this->title = sanitize_text_field( $title );
		return $this;
	}

	/**
	 * Set the search title.
	 *
	 * @param string $search_title The search title.
	 * @return $this
	 */
	public function set_search_title( string $search_title ) {
		$this->search_title = sanitize_text_field( $search_title );
		return $this;
	}

	/**
	 * Set the audio format.
	 *
	 * @param string $audio_format The audio format.
	 * @return $this
	 */
	public function set_audio_format( string $audio_format ) {
		$this->audio_format = sanitize_text_field( $audio_format );
		return $this;
	}

	/**
	 * Set the video quality preference.
	 *
	 * @param string $quality The quality preference ('any' or a CSV list of tiers).
	 * @return $this
	 */
	public function set_quality( string $quality ) {
		$this->quality = sanitize_text_field( $quality );
		return $this;
	}

	/**
	 * Set the cover image URL.
	 *
	 * @param string $cover_image The cover image URL.
	 * @return $this
	 * @throws \InvalidArgumentException If URL is invalid.
	 */
	public function set_cover_image( string $cover_image ) {
		if ( ! filter_var( $cover_image, FILTER_VALIDATE_URL ) ) {
			throw new \InvalidArgumentException( 'Invalid cover image URL.' );
		}
		$this->cover_image = esc_url_raw( $cover_image );
		return $this;
	}

	/**
	 * Set the status.
	 *
	 * @param string $status The status value.
	 * @return $this
	 * @throws \InvalidArgumentException If status is invalid.
	 */
	public function set_status( string $status ) {
		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			throw new \InvalidArgumentException( 'Invalid status. Allowed values are: actif, inactif.' );
		}
		$this->status = $status;
		return $this;
	}

	/**
	 * Set additional data.
	 *
	 * @param array<string, mixed> $data The data array.
	 * @return $this
	 */
	public function set_data( array $data ) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Set download URLs.
	 *
	 * @param array<int, string> $urls The URLs array.
	 * @return $this
	 */
	public function set_urls( array $urls ) {
		$this->urls = $urls;
		return $this;
	}

	/**
	 * Set the full general-search-done map.
	 *
	 * @param array<int, array<int, bool>> $general_search_done The saison/episode map.
	 * @return $this
	 */
	public function set_general_search_done( array $general_search_done ) {
		$this->general_search_done = $general_search_done;
		return $this;
	}

	/**
	 * Mark whether a general API search has been performed for a given
	 * saison/episode combo. Marking as not done removes the entry, since
	 * absence is what signals "not yet searched".
	 *
	 * @param int  $saison  The saison number.
	 * @param int  $episode The episode number.
	 * @param bool $done    Whether the search was performed.
	 * @return $this
	 */
	public function mark_general_search_done( int $saison, int $episode, bool $done = true ) {
		if ( $done ) {
			$this->general_search_done[ $saison ][ $episode ] = true;
		} elseif ( isset( $this->general_search_done[ $saison ] ) ) {
			unset( $this->general_search_done[ $saison ][ $episode ] );
			if ( empty( $this->general_search_done[ $saison ] ) ) {
				unset( $this->general_search_done[ $saison ] );
			}
		}
		return $this;
	}

	/**
	 * Set saisons data.
	 *
	 * @param array<int, mixed> $saisons The saisons array.
	 * @return $this
	 * @throws \InvalidArgumentException If saisons is not an array.
	 */
	public function set_saisons( array $saisons ) {
		if ( ! is_array( $saisons ) ) {
			throw new \InvalidArgumentException( 'Saisons must be an array.' );
		}
		$this->data['saison'] = $saisons;
		return $this;
	}

	/**
	 * Get the TvShow ID.
	 *
	 * @return int|null
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Get the TvShow title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the search title, fallback to title if empty.
	 *
	 * @return string
	 */
	public function get_search_title(): string {
		if ( empty( $this->search_title ) ) {
			return $this->get_title();
		}
		return $this->search_title;
	}

	/**
	 * Get the audio format.
	 *
	 * @return string
	 */
	public function get_audio_format(): string {
		return $this->audio_format;
	}

	/**
	 * Get the video quality preference.
	 *
	 * @return string
	 */
	public function get_quality(): string {
		return $this->quality;
	}

	/**
	 * Get the cover image URL.
	 *
	 * @return string
	 */
	public function get_cover_image(): string {
		return $this->cover_image;
	}

	/**
	 * Get the status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get additional data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Get download URLs.
	 *
	 * @return array<int, string>
	 */
	public function get_urls(): array {
		return $this->urls;
	}

	/**
	 * Get the full general-search-done map.
	 *
	 * @return array<int, array<int, bool>>
	 */
	public function get_general_search_done(): array {
		return $this->general_search_done;
	}

	/**
	 * Whether a general API search has already been performed for a given
	 * saison/episode combo.
	 *
	 * @param int $saison  The saison number.
	 * @param int $episode The episode number.
	 * @return bool
	 */
	public function is_general_search_done( int $saison, int $episode ): bool {
		return ! empty( $this->general_search_done[ $saison ][ $episode ] );
	}

	/**
	 * Add a download URL.
	 *
	 * @param string $url The URL to add.
	 * @return $this
	 * @throws \InvalidArgumentException If URL is invalid.
	 */
	public function add_url( string $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new \InvalidArgumentException( 'Invalid URL.' );
		}
		$new_url = esc_url_raw( $url );
		if ( ! in_array( $new_url, $this->urls, true ) ) {
			$this->urls[] = $new_url;
		}
		return $this;
	}

	/**
	 * Initialize default data structure with a starting saison.
	 *
	 * @param int $saison       The saison number to seed. Defaults to 1.
	 * @param int $last_episode The last episode already downloaded. Defaults to 0.
	 * @return $this
	 */
	public function init_data( int $saison = 1, int $last_episode = 0 ) {
		$this->data = [
			'saison' => [
				0 => [
					'id'          => $saison,
					'status'      => self::$actif,
					'lastepisode' => $last_episode,
				],
			],
		];
		return $this;
	}

	/**
	 * Idempotently insert a saison entry at an arbitrary index.
	 * If the saison already exists, its state is left untouched.
	 *
	 * @param int $id The saison number to add.
	 * @return $this
	 */
	public function add_saison( int $id ) {
		if ( isset( $this->data['saison'] ) && is_array( $this->data['saison'] ) ) {
			foreach ( $this->data['saison'] as $saison_data ) {
				if ( isset( $saison_data['id'] ) && (int) $saison_data['id'] === $id ) {
					return $this;
				}
			}
		} else {
			$this->data['saison'] = [];
		}
		$this->data['saison'][] = [
			'id'          => $id,
			'status'      => self::$actif,
			'lastepisode' => 0,
		];
		$this->max_saison       = null;
		return $this;
	}

	/**
	 * Get all saisons.
	 *
	 * @return array<int, mixed>
	 */
	public function get_saisons() {
		if ( isset( $this->data['saison'] ) ) {
			return $this->data['saison'];
		}
		return [];
	}

	/**
	 * Get the maximum saison number.
	 *
	 * @return int
	 */
	private function get_max_saison() {
		if ( null === $this->max_saison ) {
			$saisons = $this->get_saisons();
			if ( empty( $saisons ) ) {
				return 0;
			}
			$this->max_saison = max( array_column( $saisons, 'id' ) );
		}
		return $this->max_saison;
	}

	/**
	 * Advance to the next saison if current is the last.
	 *
	 * @param int $current_saison The current saison number.
	 * @return $this
	 */
	public function next_saison( int $current_saison ) {
		if ( $this->get_max_saison() === $current_saison ) {
			++$this->max_saison;
			$this->data['saison'][] = [
				'id'          => $this->max_saison,
				'status'      => self::$actif,
				'lastepisode' => 0,
			];
		}
		return $this;
	}

	/**
	 * Enable or disable a saison.
	 *
	 * @param int  $saison The saison number.
	 * @param bool $enable Whether to enable or disable.
	 * @return $this
	 */
	public function enable_saison( int $saison, bool $enable = true ) {
		$status = $enable ? self::$actif : self::$inactif;
		if ( isset( $this->data['saison'] ) && is_array( $this->data['saison'] ) ) {
			foreach ( $this->data['saison'] as &$saison_data ) {
				if ( isset( $saison_data['id'] ) && $saison_data['id'] === $saison ) {
					$saison_data['status'] = $status;
					break;
				}
			}
			unset( $saison_data ); // Break reference.
		}
		return $this;
	}

	/**
	 * Update last episode for a saison.
	 *
	 * @param int $saison          The saison number.
	 * @param int $current_episode The current episode number.
	 * @return $this
	 */
	public function next_episode( int $saison, int $current_episode ) {
		if ( isset( $this->data['saison'] ) && is_array( $this->data['saison'] ) ) {
			foreach ( $this->data['saison'] as $index => $saison_data ) {
				if ( isset( $saison_data['id'] ) && (int) $saison_data['id'] === $saison ) {
					$this->data['saison'][ $index ]['lastepisode'] = $current_episode;
					break;
				}
			}
		}
		return $this;
	}

	/**
	 * Get the download directory path for a specific saison.
	 *
	 * @param int $saison The saison number.
	 * @return string
	 */
	public function get_download_directory( int $saison ): string {
		$tv_show_directory = get_option( 'tv_show_directory', self::DEFAULT_DIRECTORY );
		$tv_show_name      = preg_replace( '/[^a-zA-Z0-9_-]/', '', str_replace( ' ', '_', $this->get_title() ) );
		return trailingslashit( implode( '/', [ $tv_show_directory, $tv_show_name, $saison ] ) );
	}

	/**
	 * Get the media type identifier.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'tvshow';
	}
}
