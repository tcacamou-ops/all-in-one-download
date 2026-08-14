<?php
namespace AllI1D\Tests\Support;

/**
 * Minimal in-memory stand-in for $wpdb, sufficient for MovieRepository.
 * Rows are keyed by an incrementing integer id, mirroring an AUTO_INCREMENT
 * primary key, so update()/get_row() can address a specific row.
 */
class MovieFakeWpdb {
	public string $prefix = 'wp_';

	public int $insert_id = 0;

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
		$this->rows[ $id ] = array_merge( [ 'id' => $id ], $data );
		$this->insert_id   = $id;
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

	public function get_row( $query, $output = ARRAY_A ) {
		if ( preg_match( '/WHERE id = (\d+)/', $query, $m ) ) {
			$id = (int) $m[1];
			return $this->rows[ $id ] ?? null;
		}
		return null;
	}

	public function get_results( $query, $output = ARRAY_A ) {
		return array_values( $this->rows );
	}

	public function query( $query ) {
		if ( str_starts_with( $query, 'UPDATE' ) && preg_match( '/SET general_search_done = (.+)$/', $query, $m ) ) {
			$value = trim( $m[1] );
			if ( "'" === $value[0] ) {
				$value = stripslashes( substr( $value, 1, -1 ) );
			} else {
				$value = (int) $value;
			}
			foreach ( $this->rows as $id => $row ) {
				$this->rows[ $id ]['general_search_done'] = $value;
			}
			return count( $this->rows );
		}
		return 0;
	}

	public function delete( $table, $where ) {
		$id = $where['id'];
		if ( isset( $this->rows[ $id ] ) ) {
			unset( $this->rows[ $id ] );
			return 1;
		}
		return 0;
	}
}
