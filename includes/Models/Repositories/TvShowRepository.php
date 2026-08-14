<?php
/**
 * TvShow repository class.
 *
 * @package AllI1D
 */

namespace AllI1D\Models\Repositories;

use AllI1D\Models\TvShow;
use AllI1D\Services\TorrentMetadataParser;

class TvShowRepository {

	private const ALLOWED_FILTER_FIELDS = [
		'id',
		'title',
		'search_title',
		'audio_format',
		'quality',
		'cover_image',
		'status',
		'data',
		'urls',
		'general_search_done',
	];

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'all_in_one_download_tv_shows';
	}

	/**
	 * Get singleton instance.
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
	 * Create the database table.
	 */
	public function create_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$default_quality = TorrentMetadataParser::DEFAULT_QUALITY;
		$sql             = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            search_title VARCHAR(255) NOT NULL,
            audio_format VARCHAR(50) NOT NULL,
            quality VARCHAR(20) NOT NULL DEFAULT '{$default_quality}',
            cover_image VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            data LONGTEXT NOT NULL,
            urls LONGTEXT NOT NULL,
            general_search_done LONGTEXT NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the database table.
	 */
	public function drop_table(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name};" );
	}

	/**
	 * Decode the `general_search_done` column into its map shape, falling
	 * back to an empty map for rows still holding the legacy `0`/`1` scalar
	 * (pre-dating the column's TINYINT-to-LONGTEXT migration) or invalid JSON.
	 *
	 * @param string $value The raw column value.
	 * @return array<int, array<int, bool>>
	 */
	private static function decode_general_search_done( string $value ): array {
		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Save a TvShow (insert or update).
	 *
	 * @param TvShow $tv_show The TvShow to save.
	 */
	public function save_tv_show( TvShow $tv_show ): void {
		global $wpdb;

		if ( $tv_show->get_id() ) {
			// Effectuer une mise à jour si l'ID existe.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->table_name,
				[
					'title'               => $tv_show->get_title(),
					'search_title'        => $tv_show->get_search_title(),
					'audio_format'        => $tv_show->get_audio_format(),
					'quality'             => $tv_show->get_quality(),
					'cover_image'         => $tv_show->get_cover_image(),
					'status'              => $tv_show->get_status(),
					'data'                => wp_json_encode( $tv_show->get_data() ),
					'urls'                => wp_json_encode( $tv_show->get_urls() ),
					'general_search_done' => wp_json_encode( $tv_show->get_general_search_done() ),
				],
				[ 'id' => $tv_show->get_id() ],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			// Insérer une nouvelle série TV si l'ID n'existe pas.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->table_name,
				[
					'title'               => $tv_show->get_title(),
					'search_title'        => $tv_show->get_search_title(),
					'audio_format'        => $tv_show->get_audio_format(),
					'quality'             => $tv_show->get_quality(),
					'cover_image'         => $tv_show->get_cover_image(),
					'status'              => $tv_show->get_status(),
					'data'                => wp_json_encode( $tv_show->get_data() ),
					'urls'                => wp_json_encode( $tv_show->get_urls() ),
					'general_search_done' => wp_json_encode( $tv_show->get_general_search_done() ),
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);

			// Mettre à jour l'ID de l'objet avec l'ID généré par la base de données.
			$tv_show->set_id( $wpdb->insert_id );
		}
	}

	/**
	 * Get all TvShows matching optional filters.
	 *
	 * @param array<string, array<int, string>> $filters Optional filters array.
	 * @return array<int, TvShow>
	 * @throws \InvalidArgumentException If an invalid operator is used.
	 */
	public function get_all_tv_shows( array $filters = [] ): array {
		global $wpdb;

		$where_clauses = [];
		$query_params  = [];

		foreach ( $filters as $field => [$operator, $value] ) {
			if ( ! in_array( $field, self::ALLOWED_FILTER_FIELDS, true ) ) {
				throw new \InvalidArgumentException( esc_html( "Invalid filter field: $field." ) );
			}
			if ( ! in_array( $operator, [ '=', '!=', 'LIKE' ], true ) ) {
				throw new \InvalidArgumentException( esc_html( "Invalid operator: $operator. Allowed operators are '=', '!=', 'LIKE'." ) );
			}

			$where_clauses[] = "{$field} {$operator} %s";
			$query_params[]  = 'LIKE' === $operator ? '%' . $wpdb->esc_like( $value ) . '%' : $value;
		}

		$where_sql = $where_clauses ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
		$query     = "SELECT * FROM {$this->table_name} {$where_sql}";
		if ( $query_params ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $wpdb->prepare( $query, $query_params ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $query, ARRAY_A );
		}

		return array_map(
			function ( $row ) {
				return new TvShow(
				[
					'id'                  => (int) $row['id'],
					'title'               => $row['title'],
					'search_title'        => $row['search_title'],
					'audio_format'        => $row['audio_format'],
					'quality'             => $row['quality'] ?? TorrentMetadataParser::DEFAULT_QUALITY,
					'cover_image'         => $row['cover_image'],
					'status'              => $row['status'],
					'data'                => json_decode( $row['data'], true ),
					'urls'                => json_decode( $row['urls'], true ),
					'general_search_done' => self::decode_general_search_done( $row['general_search_done'] ?? '' ),
				]
				);
			},
			$results ?? []
			);
	}

	/**
	 * Delete a TvShow by ID.
	 *
	 * @param int $id The TvShow ID.
	 */
	public function delete_tv_show( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Check if a URL already exists.
	 *
	 * @param string $url The URL to check.
	 * @return bool
	 */
	public function exists_by_url( string $url ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(*) FROM {$this->table_name} WHERE urls LIKE %s",
			'%' . $wpdb->esc_like( $url ) . '%'
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $query ) > 0;
	}

	/**
	 * Get a TvShow by ID.
	 *
	 * @param int $id The TvShow ID.
	 * @return TvShow|null
	 */
	public function get_tv_show_by_id( int $id ): ?TvShow {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), ARRAY_A );

		if ( $row ) {
			return new TvShow(
				[
					'id'                  => (int) $row['id'],
					'title'               => $row['title'],
					'search_title'        => $row['search_title'],
					'audio_format'        => $row['audio_format'],
					'quality'             => $row['quality'] ?? TorrentMetadataParser::DEFAULT_QUALITY,
					'cover_image'         => $row['cover_image'],
					'status'              => $row['status'],
					'data'                => json_decode( $row['data'], true ),
					'urls'                => json_decode( $row['urls'], true ),
					'general_search_done' => self::decode_general_search_done( $row['general_search_done'] ?? '' ),
				]
				);
		}

		return null;
	}

	/**
	 * Remettre `general_search_done` à une map vide pour toutes les séries.
	 *
	 * @return int Le nombre de lignes affectées.
	 */
	public function reset_all_general_search_done(): int {
		global $wpdb;
		$empty = wp_json_encode( [] ); // '[]'
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare( "UPDATE {$this->table_name} SET general_search_done = %s", $empty );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->query( $query );
	}
}
