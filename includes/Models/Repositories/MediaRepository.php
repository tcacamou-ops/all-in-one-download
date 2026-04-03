<?php
namespace AllI1D\Models\Repositories;

use AllI1D\Models\Media;

class MediaRepository {
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
		$this->table_name = $wpdb->prefix . 'all_in_one_download_media';
	}

	/**
	 * Obtenir l'instance unique de MediaRepository.
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
	 * Créer la table pour stocker les URLs.
	 */
	public function create_table(): void {
		global $wpdb;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Creating table: ' . $this->table_name );
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            url VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Supprimer la table.
	 */
	public function drop_table(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name};" );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Dropped table: ' . $this->table_name );
	}

	/**
	 * Ajouter une URL dans la table.
	 *
	 * @param Media $url The Media to insert.
	 */
	public function insert_url( Media $url ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table_name,
			[ 'url' => $url->url ],
			[ '%s' ]
		);
	}

	/**
	 * Récupérer toutes les URLs.
	 *
	 * @return array<int, Media>
	 */
	public function get_all_urls(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT * FROM {$this->table_name}", ARRAY_A );
		return array_map(
			function ( $row ) {
				return new Media(
				[
					'id'  => (int) $row['id'],
					'url' => $row['url'],
				]
				);
			},
			$results ?? []
			);
	}

	/**
	 * Supprimer une URL.
	 *
	 * @param Media $url The Media to delete.
	 */
	public function delete_url( Media $url ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$this->table_name,
			[ 'id' => $url->id ],
			[ '%d' ]
		);
	}
}
