<?php
namespace AllI1D;

use AllI1D\Api\MediaApi;
use AllI1D\Api\TvShowApi;
use AllI1D\Api\MovieApi;
use AllI1D\Api\ListingApi;
use AllI1D\Api\LogsApi;
use AllI1D\Api\SearchApi;
use AllI1D\Api\CoverImageApi;
use AllI1D\Api\FeedCatalogApi;
use AllI1D\Api\IndexingApi;

class Api {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	public static $instance = null;

	/**
	 * REST API route namespace.
	 *
	 * @var string
	 */
	public static $route_namespace = 'all-i1d/v1';

	/**
	 * Media API instance.
	 *
	 * @var \AllI1D\Api\MediaApi
	 */
	public MediaApi $media_api;

	/**
	 * TvShow API instance.
	 *
	 * @var \AllI1D\Api\TvShowApi
	 */
	public TvShowApi $tv_show_api;

	/**
	 * Movie API instance.
	 *
	 * @var \AllI1D\Api\MovieApi
	 */
	public MovieApi $movie_api;

	/**
	 * Listing API instance.
	 *
	 * @var \AllI1D\Api\ListingApi
	 */
	public ListingApi $listing_api;

	/**
	 * Logs API instance.
	 *
	 * @var \AllI1D\Api\LogsApi
	 */
	public LogsApi $logs_api;

	/**
	 * Search API instance.
	 *
	 * @var \AllI1D\Api\SearchApi
	 */
	public SearchApi $search_api;

	/**
	 * Cover image API instance.
	 *
	 * @var \AllI1D\Api\CoverImageApi
	 */
	public CoverImageApi $cover_image_api;

	/**
	 * Feed catalog API instance.
	 *
	 * @var \AllI1D\Api\FeedCatalogApi
	 */
	public FeedCatalogApi $feed_catalog_api;

	/**
	 * Indexing API instance.
	 *
	 * @var \AllI1D\Api\IndexingApi
	 */
	public IndexingApi $indexing_api;

	/**
	 * Obtenir l'instance unique de Api.
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
	 * Constructor.
	 */
	public function __construct() {
		// Enregistre l'api media.
		$this->media_api        = new MediaApi( self::$route_namespace );
		$this->tv_show_api      = new TvShowApi( self::$route_namespace );
		$this->movie_api        = new MovieApi( self::$route_namespace );
		$this->listing_api      = new ListingApi( self::$route_namespace );
		$this->logs_api         = new LogsApi( self::$route_namespace );
		$this->search_api       = new SearchApi( self::$route_namespace );
		$this->cover_image_api  = new CoverImageApi( self::$route_namespace );
		$this->feed_catalog_api = new FeedCatalogApi( self::$route_namespace );
		$this->indexing_api     = new IndexingApi( self::$route_namespace );
	}

	/**
	 * Get API data for localization.
	 *
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		$data = [
			'nonce'  => \wp_create_nonce( 'wp_rest' ),
			'routes' => $this->get_routes(),
		];
		return $data;
	}

	/**
	 * Get all registered routes.
	 *
	 * @return array<string, string>
	 */
	public function get_routes(): array {
		$routes = [];
		$routes = array_merge( $this->media_api->get_routes(), $routes );
		$routes = array_merge( $this->tv_show_api->get_routes(), $routes );
		$routes = array_merge( $this->movie_api->get_routes(), $routes );
		$routes = array_merge( $this->listing_api->get_routes(), $routes );
		$routes = array_merge( $this->logs_api->get_routes(), $routes );
		$routes = array_merge( $this->search_api->get_routes(), $routes );
		$routes = array_merge( $this->cover_image_api->get_routes(), $routes );
		$routes = array_merge( $this->feed_catalog_api->get_routes(), $routes );
		$routes = array_merge( $this->indexing_api->get_routes(), $routes );
		return $routes;
	}
}
