<?php
namespace AllI1D\Models\Repositories;

use AllI1D\Models\Movie;

class MovieRepository {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;
	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'all_in_one_download_movies';
	}

	/**
	 * Singleton.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Créer la table pour stocker les films.
	 */
	public function create_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            search_title VARCHAR(255) NOT NULL,
            audio_format VARCHAR(50) NOT NULL,
            cover_image VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            data LONGTEXT NOT NULL,
            urls LONGTEXT NOT NULL,
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
	}

	/**
	 * Ajouter ou mettre à jour un film.
	 *
	 * @param Movie $movie The Movie to save.
	 */
	public function save_movie( Movie $movie ): void {
		global $wpdb;

		if ( $movie->get_id() ) {
			// Mise à jour.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->table_name,
				[
					'title'        => $movie->get_title(),
					'search_title' => $movie->get_search_title(),
					'audio_format' => $movie->get_audio_format(),
					'cover_image'  => $movie->get_cover_image(),
					'status'       => $movie->get_status(),
					'data'         => wp_json_encode( $movie->get_data() ),
					'urls'         => wp_json_encode( $movie->get_urls() ),
				],
				[ 'id' => $movie->get_id() ],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			// Insertion.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->table_name,
				[
					'title'        => $movie->get_title(),
					'search_title' => $movie->get_search_title(),
					'audio_format' => $movie->get_audio_format(),
					'cover_image'  => $movie->get_cover_image(),
					'status'       => $movie->get_status(),
					'data'         => wp_json_encode( $movie->get_data() ),
					'urls'         => wp_json_encode( $movie->get_urls() ),
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);

			// Mettre à jour l'ID de l'objet.
			$movie->set_id( $wpdb->insert_id );
		}
	}

	/**
	 * Récupérer tous les films avec des filtres optionnels.
	 *
	 * @param array<string, array<int, string>> $filters Un tableau associatif de filtres. Chaque clé est le nom d'un champ et la valeur est un tableau contenant l'opérateur et la valeur.
	 * @return Movie[] Un tableau d'objets Movie.
	 * @throws \InvalidArgumentException Si un opérateur invalide est fourni.
	 */
	public function get_all_movies( array $filters = [] ): array {
		global $wpdb;

		$where_clauses = [];
		$query_params  = [];

		// Construire les clauses WHERE en fonction des filtres.
		foreach ( $filters as $field => [$operator, $value] ) {
			if ( ! in_array( $operator, [ '=', '!=', 'LIKE' ], true ) ) {
				throw new \InvalidArgumentException( esc_html( "Invalid operator: $operator. Allowed operators are '=', '!=', 'LIKE'." ) );
			}

			$where_clauses[] = "{$field} {$operator} %s";
			$query_params[]  = 'LIKE' === $operator ? '%' . $wpdb->esc_like( $value ) . '%' : $value;
		}

		// Construire la requête SQL.
		$where_sql = $where_clauses ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
		$query     = "SELECT * FROM {$this->table_name} {$where_sql}";

		// Exécuter la requête.
		if ( $query_params ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $wpdb->prepare( $query, $query_params ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $query, ARRAY_A );
		}

		// Transformer les résultats en objets Movie.
		return array_map(
			function ( $row ) {
				return new Movie(
				[
					'id'           => (int) $row['id'],
					'title'        => $row['title'],
					'search_title' => $row['search_title'],
					'audio_format' => $row['audio_format'],
					'cover_image'  => $row['cover_image'],
					'status'       => $row['status'],
					'data'         => json_decode( $row['data'], true ),
					'urls'         => json_decode( $row['urls'], true ),
				]
				);
			},
			$results ?? []
			);
	}

	/**
	 * Get a movie by ID.
	 *
	 * @param int $id The movie ID.
	 * @return Movie|null
	 */
	public function get_by_id( int $id ): ?Movie {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ), ARRAY_A );

		if ( $result ) {
			return new Movie(
				[
					'id'           => (int) $result['id'],
					'title'        => $result['title'],
					'search_title' => $result['search_title'],
					'audio_format' => $result['audio_format'],
					'cover_image'  => $result['cover_image'],
					'status'       => $result['status'],
					'data'         => json_decode( $result['data'], true ),
					'urls'         => json_decode( $result['urls'], true ),
				]
				);
		}

		return null;
	}

	/**
	 * Supprimer un film par ID.
	 *
	 * @param int $id The movie ID.
	 */
	public function delete_movie( int $id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->table_name, [ 'id' => $id ], [ '%d' ] );
	}
}
