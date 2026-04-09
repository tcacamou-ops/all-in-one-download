<?php
/**
 * TvShow model class.
 *
 * @package AllI1D
 */

namespace AllI1D\Models;

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
		$this->set_cover_image( $attributes['cover_image'] ?? '' );
		$this->set_status( $attributes['status'] ?? self::$actif );
		$this->set_data( $attributes['data'] ?? [] );
		$this->set_urls( $attributes['urls'] ?? [] );
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
	 * Initialize default data structure with first saison.
	 *
	 * @return $this
	 */
	public function init_data() {
		$this->data = [
			'saison' => [
				0 => [
					'id'          => 1,
					'status'      => self::$actif,
					'lastepisode' => 0,
				],
			],
		];
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
				if ( isset( $saison_data['id'] ) && $saison_data['id'] === $saison ) {
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
