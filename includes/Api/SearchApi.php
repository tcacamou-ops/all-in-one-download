<?php
/**
 * Search API endpoint.
 *
 * @package AllI1D
 */

namespace AllI1D\Api;

use AllI1D\Interfaces\Api;
use AllI1D\Models\Movie;
use AllI1D\Models\TvShow;
use AllI1D\Models\Repositories\MovieRepository;
use AllI1D\Models\Repositories\TvShowRepository;
use WP_Error;

class SearchApi implements Api {

	/**
	 * Placeholder cover image used when creating a fiche from a manual search
	 * selection, since the picked result carries no artwork of its own.
	 *
	 * @var string
	 */
	private const DEFAULT_COVER_IMAGE = 'https://via.placeholder.com/300x450.png?text=No+Cover';

	/**
	 * The route namespace.
	 *
	 * @var string
	 */
	private $route_namespace;

	/**
	 * The current namespace segment.
	 *
	 * @var string
	 */
	private $current_namespace = 'search';

	/**
	 * Constructor.
	 *
	 * @param string $route_namespace The route namespace.
	 */
	public function __construct( string $route_namespace ) {
		$this->route_namespace = $route_namespace;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Get the full namespace for this API.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->route_namespace . '/' . $this->current_namespace;
	}

	/**
	 * Check if the current user has permission.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'alli1d' );
	}

	/**
	 * Get the registered routes.
	 *
	 * @return array<string, string>
	 */
	public function get_routes(): array {
		return [
			'search'        => rest_url( $this->get_namespace() ),
			'search_select' => rest_url( $this->get_namespace() . '/select' ),
		];
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->current_namespace,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'search' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);

		register_rest_route(
			$this->route_namespace,
			$this->current_namespace . '/select',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'select' ],
				'permission_callback' => [ $this, 'check_permissions' ],
			]
			);
	}

	/**
	 * Aggregate a keyword search across all active providers.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function search( $request ) {
		$criteria = [
			'type'         => $request->get_param( 'type' ),
			'title'        => $request->get_param( 'title' ),
			'saison'       => $request->get_param( 'saison' ),
			'episode'      => $request->get_param( 'episode' ),
			'audio_format' => $request->get_param( 'audio_format' ),
		];

		$results = apply_filters(
			'alli1d_search_providers',
			[
				'items'  => [],
				'errors' => [],
			],
			$criteria
			);

		$items  = is_array( $results['items'] ?? null ) ? $results['items'] : [];
		$errors = is_array( $results['errors'] ?? null ) ? $results['errors'] : [];

		// Defensively re-sort by score and cap to top 10: don't fully trust
		// each provider respected the "top 10, sorted by relevance" contract.
		usort(
			$items,
			static function ( $a, $b ) {
				return ( $b['score'] ?? 0 ) <=> ( $a['score'] ?? 0 );
			}
			);
		$items = array_slice( $items, 0, 10 );

		return rest_ensure_response(
			[
				'items'  => $items,
				'errors' => $errors,
			]
			);
	}

	/**
	 * Select a specific provider result, trigger its download and create/update
	 * the corresponding fiche (Movie or TvShow).
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function select( $request ) {
		$provider = (string) $request->get_param( 'provider' );
		$result   = (array) $request->get_param( 'result' );
		$type     = (string) $request->get_param( 'type' );
		$title    = (string) $request->get_param( 'title' );
		$saison   = (int) $request->get_param( 'saison' );
		$episode  = (int) $request->get_param( 'episode' );
		$suivi    = (bool) $request->get_param( 'suivi' );

		$download_item = apply_filters( "alli1d_download_selected_result_{$provider}", null, $result );

		if ( null === $download_item ) {
			return new WP_Error( 'download_failed', __( 'Le téléchargement du résultat sélectionné a échoué.', 'all-in-one-download' ), [ 'status' => 500 ] );
		}

		if ( 'movie' === $type ) {
			$downloaded = $this->select_movie( $title, $request, $download_item );
		} else {
			$downloaded = $this->select_tv_show( $title, $saison, $episode, $suivi, $request, $download_item );
		}

		if ( ! $downloaded ) {
			return new WP_Error( 'transmission_failed', __( 'Le fichier a été récupéré mais son envoi vers le client de téléchargement a échoué.', 'all-in-one-download' ), [ 'status' => 500 ] );
		}

		return rest_ensure_response(
			[
				'success' => true,
				'item'    => $download_item,
			]
			);
	}

	/**
	 * Create or update the Movie fiche for a selected result, then hand the
	 * downloaded torrent off to the configured download client via the
	 * `alli1d_process_torrent` filter (the same hook used by MovieCron).
	 *
	 * @param string               $title The movie title.
	 * @param \WP_REST_Request     $request The REST request (used for the audio_format param).
	 * @param array<string, mixed> $download_item The item returned by the provider's download filter, completed
	 *                                        in place with `dest_directory` and the final `downloaded` outcome.
	 * @return bool Whether the download client accepted the torrent.
	 */
	private function select_movie( string $title, $request, array &$download_item ): bool {
		$movie_repository = MovieRepository::get_instance();
		$existing_movies  = $movie_repository->get_all_movies( [ 'title' => [ '=', $title ] ] );

		if ( count( $existing_movies ) > 0 ) {
			$movie = $existing_movies[0];
		} else {
			$movie = new Movie(
				[
					'title'        => $title,
					'search_title' => $title,
					'cover_image'  => self::DEFAULT_COVER_IMAGE,
				]
				);
			$movie->set_id( null );

			$audio_format = (string) $request->get_param( 'audio_format' );
			if ( '' !== $audio_format ) {
				$movie->set_audio_format( $audio_format );
			}
		}

		$download_item['downloaded']     = false;
		$download_item['dest_directory'] = $movie->get_download_directory();
		$download_item                   = apply_filters( 'alli1d_process_torrent', $download_item );
		$downloaded                      = true === ( $download_item['downloaded'] ?? false );

		$movie->set_status( $downloaded ? Movie::$downloaded : Movie::$actif );
		$movie_repository->save_movie( $movie );

		return $downloaded;
	}

	/**
	 * Create or update the TvShow fiche for a selected result, applying the
	 * suivi/one-shot season bookkeeping, then hand the downloaded torrent off
	 * to the configured download client via the `alli1d_process_torrent`
	 * filter (the same hook used by TvShowCron).
	 *
	 * @param string               $title The tv show title.
	 * @param int                  $saison The picked saison number.
	 * @param int                  $episode The picked episode number.
	 * @param bool                 $suivi Whether the season should keep being tracked.
	 * @param \WP_REST_Request     $request The REST request (used for the audio_format param).
	 * @param array<string, mixed> $download_item The item returned by the provider's download filter, completed
	 *                                        in place with `dest_directory` and the final `downloaded` outcome.
	 * @return bool Whether the download client accepted the torrent.
	 */
	private function select_tv_show( string $title, int $saison, int $episode, bool $suivi, $request, array &$download_item ): bool {
		$tv_show_repository = TvShowRepository::get_instance();
		$existing_tv_shows  = $tv_show_repository->get_all_tv_shows( [ 'title' => [ '=', $title ] ] );

		if ( count( $existing_tv_shows ) > 0 ) {
			$tv_show = $existing_tv_shows[0];
			$tv_show->add_saison( $saison );
		} else {
			$tv_show = new TvShow(
				[
					'title'        => $title,
					'search_title' => $title,
					'cover_image'  => self::DEFAULT_COVER_IMAGE,
				]
				);
			$tv_show->set_id( null );

			$audio_format = (string) $request->get_param( 'audio_format' );
			if ( '' !== $audio_format ) {
				$tv_show->set_audio_format( $audio_format );
			}

			$tv_show->init_data( $saison, $episode );
		}

		$download_item['downloaded']     = false;
		$download_item['dest_directory'] = $tv_show->get_download_directory( $saison );
		$download_item                   = apply_filters( 'alli1d_process_torrent', $download_item );
		$downloaded                      = true === ( $download_item['downloaded'] ?? false );

		if ( ! $downloaded ) {
			// Keep the season active so the auto-search cron retries it later.
			$tv_show->enable_saison( $saison, true );
		} elseif ( $suivi ) {
			$tv_show->enable_saison( $saison, true );
			$tv_show->next_episode( $saison, $episode );
		} else {
			$tv_show->enable_saison( $saison, false );

			$has_other_active_saison = false;
			foreach ( $tv_show->get_saisons() as $saison_data ) {
				if ( (int) $saison_data['id'] !== $saison && TvShow::$actif === $saison_data['status'] ) {
					$has_other_active_saison = true;
					break;
				}
			}

			if ( ! $has_other_active_saison ) {
				$tv_show->set_status( TvShow::$downloaded );
			}
		}

		$tv_show_repository->save_tv_show( $tv_show );

		return $downloaded;
	}
}
