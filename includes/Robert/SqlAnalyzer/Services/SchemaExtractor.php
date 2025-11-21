<?php
/**
 * Schema Extractor Service
 *
 * Extracts database schema information including table structures,
 * columns, and metadata.
 *
 * @package Robert\SqlAnalyzer\Services
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Services;

/**
 * SchemaExtractor Class
 *
 * Provides methods to extract database schema information from tables
 * including columns, data types, constraints, and metadata.
 *
 * @since 0.1.0
 */
final class SchemaExtractor {

	/**
	 * Get complete table structure
	 *
	 * Retrieves all schema information for a table including columns and metadata.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table to analyze
	 * @return array<string, mixed> Complete table structure information
	 * @throws \Exception If table does not exist
	 */
	public static function getTableStructure( string $table_name ): array {
		// Sanitize table name
		$table_name = sanitize_key( $table_name );

		// Check table exists
		if ( ! DatabaseService::tableExists( $table_name ) ) {
			throw new \Exception(
				sprintf(
					'Table does not exist: %s',
					esc_attr( $table_name )
				)
			);
		}

		return array(
			'name'     => $table_name,
			'columns'  => self::getColumnInfo( $table_name ),
			'metadata' => self::getTableMetadata( $table_name ),
		);
	}

	/**
	 * Get column information for a table
	 *
	 * Retrieves detailed information about all columns in a table
	 * including type, nullability, defaults, and constraints.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<int, array<string, mixed>> Array of column information
	 */
	public static function getColumnInfo( string $table_name ): array {
		$wpdb = DatabaseService::getConnection();

		// Execute query with prepared statement
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION',
				DatabaseService::getDatabaseName(),
				sanitize_key( $table_name )
			),
			ARRAY_A
		);

		if ( null === $columns ) {
			$columns = array();
		}

		// Process and format column information
		return array_map(
			function ( array $column ) {
				return array(
					'name'       => $column['COLUMN_NAME'] ?? '',
					'type'       => self::formatColumnType( $column['COLUMN_TYPE'] ?? '' ),
					'null'       => 'YES' === ( $column['IS_NULLABLE'] ?? 'NO' ) ? true : false,
					'key'        => $column['COLUMN_KEY'] ?? null,
					'default'    => $column['COLUMN_DEFAULT'],
					'extra'      => $column['EXTRA'] ?? null,
					'collation'  => $column['COLLATION_NAME'] ?? null,
					'privileges' => $column['PRIVILEGES'] ?? null,
					'comment'    => $column['COLUMN_COMMENT'] ?? null,
				);
			},
			$columns
		);
	}

	/**
	 * Format column type for display
	 *
	 * Formats the full column type definition into a readable format.
	 *
	 * @since 0.1.0
	 * @param string $column_type The full column type definition
	 * @return string Formatted column type
	 */
	private static function formatColumnType( string $column_type ): string {
		// Clean up type definition
		$column_type = strtoupper( $column_type );

		// Handle MySQL specific types
		if ( strpos( $column_type, 'INT(11)' ) !== false ) {
			return 'INT';
		}

		if ( strpos( $column_type, 'BIGINT(20)' ) !== false ) {
			return 'BIGINT';
		}

		return $column_type;
	}

	/**
	 * Get table metadata
	 *
	 * Retrieves metadata about a table including engine, charset, row count, etc.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<string, mixed> Table metadata
	 */
	public static function getTableMetadata( string $table_name ): array {
		$wpdb = DatabaseService::getConnection();

		// Get table metadata from information schema with prepared statement
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$metadata = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DatabaseService::getDatabaseName(),
				sanitize_key( $table_name )
			),
			ARRAY_A
		);

		if ( null === $metadata ) {
			return array();
		}

		return array(
			'engine'         => $metadata['ENGINE'] ?? null,
			'row_format'     => $metadata['ROW_FORMAT'] ?? null,
			'table_rows'     => (int) ( $metadata['TABLE_ROWS'] ?? 0 ),
			'avg_row_length' => (int) ( $metadata['AVG_ROW_LENGTH'] ?? 0 ),
			'data_length'    => (int) ( $metadata['DATA_LENGTH'] ?? 0 ),
			'index_length'   => (int) ( $metadata['INDEX_LENGTH'] ?? 0 ),
			'data_free'      => (int) ( $metadata['DATA_FREE'] ?? 0 ),
			'auto_increment' => $metadata['AUTO_INCREMENT'],
			'table_charset'  => $metadata['TABLE_COLLATION'] ?? null,
			'create_time'    => $metadata['CREATE_TIME'] ?? null,
			'update_time'    => $metadata['UPDATE_TIME'] ?? null,
			'table_comment'  => $metadata['TABLE_COMMENT'] ?? null,
		);
	}

	/**
	 * Get multiple table structures
	 *
	 * Retrieves structure information for multiple tables at once.
	 *
	 * @since 0.1.0
	 * @param array<int, string> $table_names Array of table names
	 * @return array<int, array<string, mixed>> Array of table structures
	 */
	public static function getMultipleTableStructures( array $table_names ): array {
		return array_map(
			function ( string $table_name ) {
				try {
					return self::getTableStructure( $table_name );
				} catch ( \Exception $e ) {
					// Log error but continue processing other tables
					return array(
						'name'  => $table_name,
						'error' => $e->getMessage(),
					);
				}
			},
			$table_names
		);
	}

	/**
	 * Get table column names
	 *
	 * Returns a simple array of column names for a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<int, string> Array of column names
	 */
	public static function getTableColumnNames( string $table_name ): array {
		$columns = self::getColumnInfo( $table_name );

		return array_map(
			function ( array $column ) {
				return $column['name'];
			},
			$columns
		);
	}

	/**
	 * Get primary key information
	 *
	 * Retrieves information about the primary key of a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @return array<int, string> Array of primary key column names
	 */
	public static function getPrimaryKey( string $table_name ): array {
		$columns = self::getColumnInfo( $table_name );

		$pk_columns = array_filter(
			$columns,
			function ( array $column ) {
				return 'PRI' === $column['key'];
			}
		);

		return array_map(
			function ( array $column ) {
				return $column['name'];
			},
			$pk_columns
		);
	}

	/**
	 * Check if column is nullable
	 *
	 * Checks whether a specific column allows NULL values.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @param string $column_name The name of the column
	 * @return bool True if column is nullable
	 */
	public static function isColumnNullable( string $table_name, string $column_name ): bool {
		$columns = self::getColumnInfo( $table_name );

		foreach ( $columns as $column ) {
			if ( $column['name'] === $column_name ) {
				return $column['null'];
			}
		}

		return false;
	}

	/**
	 * Get columns of specific type
	 *
	 * Returns all columns of a specific data type in a table.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table
	 * @param string $data_type The data type to search for
	 * @return array<int, string> Array of column names with the specified type
	 */
	public static function getColumnsByType( string $table_name, string $data_type ): array {
		$columns   = self::getColumnInfo( $table_name );
		$data_type = strtoupper( $data_type );

		$matching_columns = array_filter(
			$columns,
			function ( array $column ) use ( $data_type ) {
				return strpos( $column['type'], $data_type ) === 0;
			}
		);

		return array_map(
			function ( array $column ) {
				return $column['name'];
			},
			$matching_columns
		);
	}

	/**
	 * Format schema for display
	 *
	 * Formats table structures into human-readable text format.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $tables Array of table structures
	 * @return string Formatted schema information
	 */
	public static function formatForDisplay( array $tables ): string {
		if ( empty( $tables ) ) {
			return 'No tables found.';
		}

		$output  = "Database Schema\n";
		$output .= str_repeat( '=', 80 ) . "\n\n";

		foreach ( $tables as $table ) {
			if ( isset( $table['error'] ) ) {
				$output .= sprintf(
					"Table: %s\nError: %s\n\n",
					$table['name'],
					$table['error']
				);
				continue;
			}

			$output .= sprintf( "Table: %s\n", $table['name'] );
			$output .= str_repeat( '-', 40 ) . "\n";

			// Add metadata
			if ( ! empty( $table['metadata'] ) ) {
				$output .= sprintf( "Engine: %s\n", $table['metadata']['engine'] ?? 'Unknown' );
				$output .= sprintf( "Rows: %d\n\n", $table['metadata']['table_rows'] ?? 0 );
			}

			// Add columns
			$output .= "Columns:\n";
			if ( ! empty( $table['columns'] ) ) {
				foreach ( $table['columns'] as $column ) {
					$constraints = array();
					if ( ! $column['null'] ) {
						$constraints[] = 'NOT NULL';
					}
					if ( 'PRI' === $column['key'] ) {
						$constraints[] = 'PRIMARY KEY';
					}
					if ( 'UNI' === $column['key'] ) {
						$constraints[] = 'UNIQUE';
					}

					$constraint_str = ! empty( $constraints ) ? ', ' . implode( ', ', $constraints ) : '';

					$output .= sprintf(
						"  - %s (%s%s)\n",
						$column['name'],
						$column['type'],
						$constraint_str
					);
				}
			}

			$output .= "\n";
		}

		return $output;
	}
}
