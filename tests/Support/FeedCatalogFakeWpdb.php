<?php
namespace AllI1D\Tests\Support;

/**
 * Minimal in-memory stand-in for $wpdb, sufficient for FeedCatalogRepository.
 * Rows are keyed by an incrementing integer id, mirroring an AUTO_INCREMENT
 * primary key, so update()/get_var() can address a specific row.
 */
class FeedCatalogFakeWpdb {
	public string $prefix = 'wp_';

	/** @var array<int, array<string, mixed>> */
	public array $rows = [];

	private int $next_id = 1;

	public function esc_like( $text ) {
		return addcslashes( (string) $text, '_%\\' );
	}

	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		foreach ( $args as $arg ) {
			$replacement = ( is_int( $arg ) || is_float( $arg ) ) ? (string) $arg : "'" . addslashes( (string) $arg ) . "'";
			$pos_s       = strpos( $query, '%s' );
			$pos_d       = strpos( $query, '%d' );
			if ( false === $pos_s && false === $pos_d ) {
				break;
			}
			if ( false === $pos_d || ( false !== $pos_s && $pos_s < $pos_d ) ) {
				$query = substr_replace( $query, $replacement, $pos_s, 2 );
			} else {
				$query = substr_replace( $query, $replacement, $pos_d, 2 );
			}
		}
		return $query;
	}

	public function insert( $table, $data ) {
		$id                = $this->next_id++;
		$this->rows[ $id ] = $data;
		return 1;
	}

	public function update( $table, $data, $where ) {
		$id = $where['id'];
		if ( isset( $this->rows[ $id ] ) ) {
			$this->rows[ $id ] = array_merge( $this->rows[ $id ], $data );
			return 1;
		}
		return 0;
	}

	public function get_var( $query ) {
		if ( preg_match( "/WHERE provider = '([^']*)' AND item_id = '([^']*)'/", $query, $m ) ) {
			foreach ( $this->rows as $id => $row ) {
				if ( $row['provider'] === $m[1] && $row['item_id'] === $m[2] ) {
					return $id;
				}
			}
		}
		if ( str_starts_with( $query, 'SELECT COUNT(*)' ) ) {
			return count( $this->rows );
		}
		return null;
	}

	public function get_results( $query, $output = ARRAY_A ) {
		if ( str_contains( $query, 'GROUP BY type' ) ) {
			$counts = [];
			foreach ( $this->rows as $row ) {
				$counts[ $row['type'] ] = ( $counts[ $row['type'] ] ?? 0 ) + 1;
			}
			return array_map(
				static fn( $type, $cnt ) => [
					'type' => $type,
					'cnt'  => $cnt,
				],
				array_keys( $counts ),
				array_values( $counts )
			);
		}

		$needle   = '';
		$type     = null;
		$provider = null;

		if ( preg_match( "/title LIKE '%(.*?)%'/", $query, $m ) ) {
			$needle = stripslashes( $m[1] );
		}
		if ( preg_match( "/type = '([^']*)'/", $query, $m ) ) {
			$type = $m[1];
		}
		if ( preg_match( "/provider = '([^']*)'/", $query, $m ) ) {
			$provider = $m[1];
		}

		return array_values(
			array_filter(
				$this->rows,
				static function ( $row ) use ( $needle, $type, $provider ) {
					if ( '' !== $needle && false === stripos( $row['title'], $needle ) ) {
						return false;
					}
					if ( null !== $type && $row['type'] !== $type ) {
						return false;
					}
					if ( null !== $provider && $row['provider'] !== $provider ) {
						return false;
					}
					return true;
				}
			)
		);
	}

	public function query( $query ) {
		if ( str_starts_with( $query, 'DELETE FROM' ) ) {
			$before = count( $this->rows );
			if ( preg_match( "/WHERE last_seen_at <= '([^']*)'/", $query, $m ) ) {
				$this->rows = array_filter( $this->rows, fn( $row ) => $row['last_seen_at'] > $m[1] );
			} elseif ( preg_match( "/WHERE type = '([^']*)'/", $query, $m ) ) {
				$this->rows = array_filter( $this->rows, fn( $row ) => $row['type'] !== $m[1] );
			} elseif ( preg_match( "/WHERE provider = '([^']*)'/", $query, $m ) ) {
				$this->rows = array_filter( $this->rows, fn( $row ) => $row['provider'] !== $m[1] );
			} else {
				$this->rows = [];
			}
			return $before - count( $this->rows );
		}
		return 0;
	}
}
