<?php
/**
 * Index Service
 *
 * Handles retrieval and analysis of database indexes.
 *
 * @package Robert\SqlAnalyzer\Services
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Services;

/**
 * IndexService Class
 *
 * Provides methods to retrieve and analyze indexes on database tables
 * including index statistics and performance information.
 *
 * @since 0.1.0
 */
final class IndexService {

	/**
	 * Get all indexes for a table
	 *
	 * Retrieves information about all indexes defined on a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<int, array<string, mixed>> Array of index information
	 */
	public static function getTableIndexes( string $table_name ): array {
		$wpdb = DatabaseService::getConnection();

		// Sanitize table name - backticks protect identifier from SQL injection
		$table_name = '`' . sanitize_key( $table_name ) . '`';

		// Execute query with escaped table identifier
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$indexes = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifier safely escaped with backticks and sanitize_key
			"SHOW INDEX FROM $table_name WHERE Key_name IS NOT NULL",
			ARRAY_A
		);

		if ( null === $indexes ) {
			$indexes = array();
		}

		// Process and organize indexes
		return self::processIndexes( $indexes );
	}

	/**
	 * Process raw index data
	 *
	 * Organizes raw SHOW INDEX results into a structured format.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $raw_indexes Raw SHOW INDEX results
	 * @return array<int, array<string, mixed>> Processed index information
	 */
	private static function processIndexes( array $raw_indexes ): array {
		$processed = array();

		foreach ( $raw_indexes as $index ) {
			$key_name = $index['Key_name'] ?? 'UNKNOWN';

			// Find or create index entry
			$index_entry = null;
			foreach ( $processed as &$entry ) {
				if ( $entry['name'] === $key_name ) {
					$index_entry = &$entry;
					break;
				}
			}

			if ( null === $index_entry ) {
				$processed[] = array(
					'name'         => $key_name,
					'type'         => $index['Index_type'] ?? 'BTREE',
					'unique'       => 0 === (int) ( $index['Non_unique'] ?? 0 ),
					'columns'      => array(),
					'seq_in_index' => (int) ( $index['Seq_in_index'] ?? 1 ),
				);
				$index_entry = &$processed[ count( $processed ) - 1 ];
			}

			// Add column to index
			if ( isset( $index['Column_name'] ) ) {
				$index_entry['columns'][] = array(
					'name'        => $index['Column_name'],
					'position'    => (int) ( $index['Seq_in_index'] ?? 1 ),
					'cardinality' => (int) ( $index['Cardinality'] ?? 0 ),
					'direction'   => $index['Collation'] ?? 'A',
				);
			}
		}

		// Sort by sequence position
		usort(
			$processed,
			function ( array $a, array $b ) {
				return $a['seq_in_index'] <=> $b['seq_in_index'];
			}
		);

		return $processed;
	}

	/**
	 * Get index details for a specific index
	 *
	 * Retrieves detailed information about a specific index on a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @param string $index_name The name of the index
	 * @return array<string, mixed> Index details or empty array if not found
	 */
	public static function getIndexDetails( string $table_name, string $index_name ): array {
		$indexes = self::getTableIndexes( $table_name );

		foreach ( $indexes as $index ) {
			if ( $index['name'] === $index_name ) {
				return $index;
			}
		}

		return array();
	}

	/**
	 * Get primary key for a table
	 *
	 * Retrieves the primary key index information.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<string, mixed> Primary key information or empty array
	 */
	public static function getPrimaryKey( string $table_name ): array {
		return self::getIndexDetails( $table_name, 'PRIMARY' );
	}

	/**
	 * Get unique indexes for a table
	 *
	 * Returns all unique indexes on a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<int, array<string, mixed>> Array of unique indexes
	 */
	public static function getUniqueIndexes( string $table_name ): array {
		$indexes = self::getTableIndexes( $table_name );

		return array_filter(
			$indexes,
			function ( array $index ) {
				return $index['unique'];
			}
		);
	}

	/**
	 * Check if index exists on table
	 *
	 * Verifies whether a specific index exists on a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @param string $index_name The name of the index
	 * @return bool True if index exists
	 */
	public static function indexExists( string $table_name, string $index_name ): bool {
		$indexes = self::getTableIndexes( $table_name );

		foreach ( $indexes as $index ) {
			if ( $index['name'] === $index_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get index statistics for optimization analysis
	 *
	 * Provides analysis of index usage and performance.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<string, mixed> Index statistics
	 */
	public static function getIndexStats( string $table_name ): array {
		$indexes = self::getTableIndexes( $table_name );

		$stats = array(
			'total_indexes'      => count( $indexes ),
			'unique_indexes'     => 0,
			'primary_key_exists' => false,
			'indexes'            => array(),
		);

		foreach ( $indexes as $index ) {
			if ( 'PRIMARY' === $index['name'] ) {
				$stats['primary_key_exists'] = true;
			}

			if ( $index['unique'] ) {
				++$stats['unique_indexes'];
			}

			$stats['indexes'][] = array(
				'name'         => $index['name'],
				'type'         => $index['type'],
				'unique'       => $index['unique'],
				'columns'      => array_map(
					function ( array $col ) {
						return $col['name'];
					},
					$index['columns']
				),
				'column_count' => count( $index['columns'] ),
			);
		}

		return $stats;
	}

	/**
	 * Find columns that might benefit from indexing
	 *
	 * Analyzes a query's WHERE clause to suggest potential indexes.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to analyze
	 * @param string $table_name The table to check
	 * @return array<int, string> Array of suggested column names
	 */
	public static function suggestIndexes( string $query, string $table_name ): array {
		// Extract WHERE clause columns
		$where_columns = array();

		// Simple pattern to match WHERE clause
		if ( preg_match( '/WHERE\s+(.+?)(?:GROUP BY|ORDER BY|LIMIT|$)/i', $query, $matches ) ) {
			$where_clause = $matches[1];

			// Find column names in WHERE clause
			if ( preg_match_all( '/(\w+)\s*[=><]/i', $where_clause, $matches ) ) {
				$where_columns = $matches[1];
			}
		}

		if ( empty( $where_columns ) ) {
			return array();
		}

		// Get existing indexes
		$indexes         = self::getTableIndexes( $table_name );
		$indexed_columns = array();

		foreach ( $indexes as $index ) {
			foreach ( $index['columns'] as $column ) {
				$indexed_columns[] = $column['name'];
			}
		}

		// Find columns that would benefit from indexing
		$suggestions = array();
		foreach ( $where_columns as $column ) {
			if ( ! in_array( $column, $indexed_columns, true ) ) {
				$suggestions[] = $column;
			}
		}

		return array_unique( $suggestions );
	}

	/**
	 * Format indexes for display
	 *
	 * Formats index information into human-readable text format.
	 *
	 * @since 0.1.0
	 * @param array<string, array<int, array<string, mixed>>> $indexes Index data organized by table
	 * @return string Formatted index information
	 */
	public static function formatForDisplay( array $indexes ): string {
		if ( empty( $indexes ) ) {
			return 'No indexes found.';
		}

		$output  = "Database Indexes\n";
		$output .= str_repeat( '=', 80 ) . "\n\n";

		foreach ( $indexes as $table_name => $table_indexes ) {
			$output .= sprintf( "Table: %s\n", $table_name );
			$output .= str_repeat( '-', 40 ) . "\n";

			if ( empty( $table_indexes ) ) {
				$output .= "No indexes defined.\n\n";
				continue;
			}

			foreach ( $table_indexes as $index ) {
				$unique  = $index['unique'] ? 'UNIQUE' : '';
				$columns = implode(
					', ',
					array_map(
						function ( array $col ) {
							return $col['name'];
						},
						$index['columns']
					)
				);

				$output .= sprintf(
					"Index: %s [%s] (%s)\n",
					$index['name'],
					$index['type'],
					trim( "$unique $columns" )
				);
			}

			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Get indexes for multiple tables
	 *
	 * Retrieves index information for a list of tables.
	 *
	 * @since 0.1.0
	 * @param array<int, string> $table_names Array of table names
	 * @return array<string, array<int, array<string, mixed>>> Indexes organized by table
	 */
	public static function getMultipleTableIndexes( array $table_names ): array {
		$all_indexes = array();

		foreach ( $table_names as $table_name ) {
			try {
				$all_indexes[ $table_name ] = self::getTableIndexes( $table_name );
			} catch ( \Exception $e ) {
				// Log error but continue
				$all_indexes[ $table_name ] = array();
			}
		}

		return $all_indexes;
	}
}
