<?php
/**
 * Feed catalog repository class.
 *
 * @package AllI1D
 */

namespace AllI1D\Models\Repositories;

class FeedCatalogRepository {

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
		$this->table_name = $wpdb->prefix . 'all_in_one_download_feed_catalog';
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
	 * Créer la table pour stocker le catalogue indexé des flux/API providers.
	 */
	public function create_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(50) NOT NULL,
            type VARCHAR(20) NOT NULL,
            item_id VARCHAR(255) NOT NULL,
            title VARCHAR(500) NOT NULL,
            quality VARCHAR(20) NULL,
            language VARCHAR(20) NULL,
            score INT NOT NULL DEFAULT 0,
            extra LONGTEXT NULL,
            first_seen_at DATETIME NOT NULL,
            last_seen_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY provider_item (provider, item_id),
            KEY provider_type (provider, type),
            KEY last_seen_at (last_seen_at)
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
	 * Insérer ou mettre à jour une liste d'items dans le catalogue, dédupliqués
	 * par (provider, item_id). Un item déjà connu voit `last_seen_at` (et ses
	 * champs) rafraîchis au lieu d'être dupliqué ; `first_seen_at` n'est posé
	 * qu'à la création.
	 *
	 * @param string                           $provider Le slug du provider (ex. 'tr4ker', 'c411').
	 * @param string                           $type     'movie' | 'tvshow'.
	 * @param array<int, array<string, mixed>> $items Items au format contrat commun (id, title, quality, language, score, extra).
	 * @return int Le nombre d'items traités.
	 */
	public function upsert_items( string $provider, string $type, array $items ): int {
		global $wpdb;
		$now = gmdate( 'Y-m-d H:i:s' );

		foreach ( $items as $item ) {
			$item_id = (string) ( $item['id'] ?? '' );
			if ( '' === $item_id ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE provider = %s AND item_id = %s", $provider, $item_id ) );

			$row = [
				'provider'     => $provider,
				'type'         => $type,
				'item_id'      => $item_id,
				'title'        => (string) ( $item['title'] ?? '' ),
				'quality'      => $item['quality'] ?? null,
				'language'     => $item['language'] ?? null,
				'score'        => (int) ( $item['score'] ?? 0 ),
				'extra'        => wp_json_encode( $item['extra'] ?? [] ),
				'last_seen_at' => $now,
			];

			if ( $existing_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $this->table_name, $row, [ 'id' => $existing_id ] );
			} else {
				$row['first_seen_at'] = $now;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert( $this->table_name, $row );
			}
		}

		return count( $items );
	}

	/**
	 * Rechercher des candidats dans le catalogue par sous-chaîne de titre.
	 *
	 * @param string      $title    Le titre (ou fragment) recherché.
	 * @param string|null $type     Filtrer par type ('movie'|'tvshow'), ou null pour tous.
	 * @param string|null $provider Filtrer par provider, ou null pour tous.
	 * @return array<int, array<string, mixed>> Les items correspondants, au format contrat commun.
	 */
	public function search( string $title, ?string $type = null, ?string $provider = null ): array {
		global $wpdb;

		$where  = [ 'title LIKE %s' ];
		$params = [ '%' . $wpdb->esc_like( $title ) . '%' ];

		if ( null !== $type ) {
			$where[]  = 'type = %s';
			$params[] = $type;
		}
		if ( null !== $provider ) {
			$where[]  = 'provider = %s';
			$params[] = $provider;
		}

		$sql = "SELECT provider, item_id, title, quality, language, score, extra FROM {$this->table_name} WHERE " . implode( ' AND ', $where );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		if ( ! $rows ) {
			return [];
		}

		return array_map(
			static function ( $row ) {
				$extra = json_decode( $row['extra'], true );
				return [
					'provider' => $row['provider'],
					'id'       => $row['item_id'],
					'title'    => $row['title'],
					'quality'  => $row['quality'],
					'language' => $row['language'],
					'score'    => (int) $row['score'],
					'extra'    => is_array( $extra ) ? $extra : [],
				];
			},
			$rows
		);
	}

	/**
	 * Compter le nombre total d'items indexés dans le catalogue.
	 *
	 * @return int Le nombre total d'entrées.
	 */
	public function count_all(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}

	/**
	 * Compter le nombre d'items indexés par type ('movie'|'tvshow').
	 *
	 * @return array<string, int> Le nombre d'entrées, indexé par type.
	 */
	public function count_by_type(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT type, COUNT(*) as cnt FROM {$this->table_name} GROUP BY type", ARRAY_A );

		$counts = [];
		foreach ( (array) $rows as $row ) {
			$counts[ $row['type'] ] = (int) $row['cnt'];
		}
		return $counts;
	}

	/**
	 * Purger les entrées jamais revues depuis plus de `$ttl_seconds`
	 * (probablement retirées du tracker).
	 *
	 * @param int $ttl_seconds Durée en secondes sans être revu au-delà de laquelle une entrée est purgée.
	 * @return int Le nombre de lignes supprimées.
	 */
	public function purge_stale( int $ttl_seconds ): int {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $ttl_seconds );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE last_seen_at <= %s", $cutoff ) );
	}

	/**
	 * Purger toutes les entrées d'un type donné.
	 *
	 * @param string $type 'movie' | 'tvshow'.
	 * @return int Le nombre de lignes supprimées.
	 */
	public function purge_by_type( string $type ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE type = %s", $type ) );
	}

	/**
	 * Purger toutes les entrées d'un provider donné.
	 *
	 * @param string $provider Le slug du provider.
	 * @return int Le nombre de lignes supprimées.
	 */
	public function purge_by_provider( string $provider ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE provider = %s", $provider ) );
	}

	/**
	 * Vider entièrement le catalogue indexé, tous providers et types confondus.
	 *
	 * @return int Le nombre de lignes supprimées.
	 */
	public function truncate_all(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$this->table_name}" );
	}
}
