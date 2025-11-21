<?php
/**
 * Formatted Output Service
 *
 * Formats analysis results for display and LLM integration.
 *
 * @package Robert\SqlAnalyzer\Services
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Services;

/**
 * FormattedOutput Class
 *
 * Provides methods to format analysis results into various output formats
 * suitable for display and integration with LLM applications.
 *
 * @since 0.1.0
 */
final class FormattedOutput {

	/**
	 * Format complete analysis output for LLM
	 *
	 * Creates a comprehensive, well-formatted analysis report suitable
	 * for pasting into LLM chat applications.
	 *
	 * @since 0.1.0
	 * @param string                                          $query The original SQL query
	 * @param array<int, array<string, mixed>>                $explain EXPLAIN results
	 * @param array<int, array<string, mixed>>                $tables Table structures
	 * @param array<string, array<int, array<string, mixed>>> $indexes Index information
	 * @param array<int, array<string, mixed>>                $analyze ANALYZE results (optional)
	 * @return string Formatted output for LLM
	 */
	public static function createLLMFriendlyOutput(
		string $query,
		array $explain,
		array $tables,
		array $indexes,
		array $analyze = array()
	): string {
		$output = '';

		// Header
		$output .= str_repeat( '=', 80 ) . "\n";
		$output .= "SQL QUERY ANALYSIS REPORT\n";
		$output .= str_repeat( '=', 80 ) . "\n";
		$output .= 'Generated: ' . current_time( 'mysql' ) . "\n";
		$output .= "\n";

		// Original Query Section
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "ORIGINAL QUERY:\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= $query . "\n";
		$output .= "\n";

		// Query Type
		$query_type = QueryAnalyzer::getQueryType( $query );
		$output    .= sprintf( "Query Type: %s\n", $query_type );
		$output    .= "\n";

		// EXPLAIN Output Section
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "EXECUTION PLAN (EXPLAIN):\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= self::formatExplainOutput( $explain );
		$output .= "\n";

		// ANALYZE Output Section (if available)
		if ( ! empty( $analyze ) ) {
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= "QUERY EXECUTION ANALYSIS (ANALYZE):\n";
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= self::formatAnalyzeOutput( $analyze );
			$output .= "\n";
		}

		// Performance Insights
		$insights = QueryAnalyzer::getPerformanceInsights( $explain );
		if ( ! empty( $insights ) ) {
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= "PERFORMANCE INSIGHTS:\n";
			$output .= str_repeat( '-', 80 ) . "\n";
			foreach ( $insights as $insight ) {
				$output .= '• ' . $insight . "\n";
			}
			$output .= "\n";
		}

		// Database Structures Section
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "DATABASE STRUCTURES:\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= self::formatSchemaOutput( $tables );
		$output .= "\n";

		// Index Information Section
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "INDEX INFORMATION:\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= self::formatIndexOutput( $indexes );
		$output .= "\n";

		// Footer
		$output .= str_repeat( '=', 80 ) . "\n";
		$output .= "END OF REPORT\n";
		$output .= str_repeat( '=', 80 ) . "\n";

		return $output;
	}

	/**
	 * Format EXPLAIN output
	 *
	 * Formats EXPLAIN query results into readable text.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $explain_data EXPLAIN results
	 * @return string Formatted EXPLAIN output
	 */
	private static function formatExplainOutput( array $explain_data ): string {
		if ( empty( $explain_data ) ) {
			return "No execution plan data available.\n";
		}

		$output = '';

		foreach ( $explain_data as $index => $row ) {
			$output .= sprintf( "Row %d:\n", $index + 1 );

			foreach ( $row as $key => $value ) {
				$formatted_value = self::formatValue( $value );
				$output         .= sprintf( "  %-20s: %s\n", $key, $formatted_value );
			}

			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Format ANALYZE output
	 *
	 * Formats ANALYZE query results into readable text.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $analyze_data ANALYZE results
	 * @return string Formatted ANALYZE output
	 */
	private static function formatAnalyzeOutput( array $analyze_data ): string {
		if ( empty( $analyze_data ) ) {
			return "No analyze data available.\n";
		}

		$output = '';

		foreach ( $analyze_data as $index => $row ) {
			$output .= sprintf( "Row %d:\n", $index + 1 );

			foreach ( $row as $key => $value ) {
				$formatted_value = self::formatValue( $value );
				$output         .= sprintf( "  %-20s: %s\n", $key, $formatted_value );
			}

			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Format schema/table structure output
	 *
	 * Formats database table structure information.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $tables Table structure data
	 * @return string Formatted schema output
	 */
	private static function formatSchemaOutput( array $tables ): string {
		if ( empty( $tables ) ) {
			return "No table structure information available.\n";
		}

		$output = '';

		foreach ( $tables as $table ) {
			if ( isset( $table['error'] ) ) {
				$output .= sprintf( "Table: %s - ERROR: %s\n", $table['name'], $table['error'] );
				continue;
			}

			$output .= sprintf( "Table: %s\n", $table['name'] );

			// Table metadata
			if ( ! empty( $table['metadata'] ) ) {
				$output .= sprintf( "  Engine: %s\n", $table['metadata']['engine'] ?? 'Unknown' );
				$output .= sprintf( "  Rows: %d\n", $table['metadata']['table_rows'] ?? 0 );
				$output .= sprintf( "  Charset: %s\n", $table['metadata']['table_charset'] ?? 'Unknown' );
			}

			// Columns
			if ( ! empty( $table['columns'] ) ) {
				$output .= "  Columns:\n";

				foreach ( $table['columns'] as $column ) {
					$constraints = array();

					if ( ! $column['null'] ) {
						$constraints[] = 'NOT NULL';
					}

					if ( 'PRI' === $column['key'] ) {
						$constraints[] = 'PRIMARY KEY';
					} elseif ( 'UNI' === $column['key'] ) {
						$constraints[] = 'UNIQUE';
					} elseif ( 'MUL' === $column['key'] ) {
						$constraints[] = 'INDEXED';
					}

					$constraint_str = ! empty( $constraints ) ? ' [' . implode( ', ', $constraints ) . ']' : '';

					$output .= sprintf(
						"    - %-30s %-20s%s\n",
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

	/**
	 * Format index output
	 *
	 * Formats database index information.
	 *
	 * @since 0.1.0
	 * @param array<string, array<int, array<string, mixed>>> $indexes Index data by table
	 * @return string Formatted index output
	 */
	private static function formatIndexOutput( array $indexes ): string {
		if ( empty( $indexes ) ) {
			return "No index information available.\n";
		}

		$output = '';

		foreach ( $indexes as $table_name => $table_indexes ) {
			$output .= sprintf( "Table: %s\n", $table_name );

			if ( empty( $table_indexes ) ) {
				$output .= "  No indexes defined.\n";
				$output .= "\n";
				continue;
			}

			foreach ( $table_indexes as $index ) {
				$unique_flag = $index['unique'] ? '(UNIQUE)' : '';
				$columns     = implode(
					', ',
					array_map(
						function ( array $col ) {
							return $col['name'];
						},
						$index['columns']
					)
				);

				$output .= sprintf(
					"  - %-30s %s [%s]\n",
					$index['name'],
					$unique_flag,
					$columns
				);
			}

			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Format a single value for display
	 *
	 * Converts various data types to readable string format.
	 *
	 * @since 0.1.0
	 * @param mixed $value The value to format
	 * @return string Formatted value
	 */
	private static function formatValue( mixed $value ): string {
		if ( null === $value ) {
			return 'NULL';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		if ( is_array( $value ) ) {
			return json_encode( $value );
		}

		return (string) $value;
	}

	/**
	 * Format for JSON response
	 *
	 * Creates a JSON-serializable array of formatted outputs.
	 *
	 * @since 0.1.0
	 * @param string                                          $query The original SQL query
	 * @param array<int, array<string, mixed>>                $explain EXPLAIN results
	 * @param array<int, array<string, mixed>>                $tables Table structures
	 * @param array<string, array<int, array<string, mixed>>> $indexes Index information
	 * @param array<int, array<string, mixed>>                $analyze ANALYZE results (optional)
	 * @return array<string, mixed> JSON-serializable output
	 */
	public static function formatForJSON(
		string $query,
		array $explain,
		array $tables,
		array $indexes,
		array $analyze = array()
	): array {
		return array(
			'query'           => $query,
			'explain'         => $explain,
			'analyze'         => $analyze,
			'tables'          => $tables,
			'indexes'         => $indexes,
			'complete_output' => self::createLLMFriendlyOutput( $query, $explain, $tables, $indexes, $analyze ),
		);
	}

	/**
	 * Get insights summary
	 *
	 * Provides a summary of insights from the analysis.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $explain EXPLAIN results
	 * @return array<string, string> Array of insight messages
	 */
	public static function getInsightsSummary( array $explain ): array {
		return QueryAnalyzer::getPerformanceInsights( $explain );
	}
}
