<?php
/**
 * Query Analyzer Service
 *
 * Analyzes SQL queries and extracts information about tables, execution plans, etc.
 *
 * @package Robert\SqlAnalyzer\Services
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Services;

/**
 * QueryAnalyzer Class
 *
 * Provides methods to analyze SQL queries including extracting table names,
 * executing EXPLAIN/ANALYZE, and parsing results.
 *
 * @since 0.1.0
 */
final class QueryAnalyzer {

	/**
	 * Analyze a complete SQL query
	 *
	 * Performs full analysis of a query including EXPLAIN, ANALYZE,
	 * and database structure extraction.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to analyze
	 * @param bool   $include_analyze Whether to include ANALYZE results
	 * @return array<string, mixed> Complete analysis results
	 * @throws \Exception If query analysis fails
	 */
	public static function analyze( string $query, bool $include_analyze = false ): array {
		// Validate query first
		if ( ! DatabaseService::validateQuery( $query ) ) {
			throw new \Exception( 'Query is not safe for analysis. Only SELECT queries are allowed.' );
		}

		// Extract table names from the query
		$tables = self::extractTableNames( $query );

		if ( empty( $tables ) ) {
			throw new \Exception( 'No tables found in query.' );
		}

		// Execute EXPLAIN to get execution plan (EXPLAIN FORMAT=TREE for human-readable output)
		$explain_result = DatabaseService::executeExplain( $query );

		// Execute ANALYZE if requested (EXPLAIN FORMAT=JSON for detailed statistics)
		// Both sections are provided: EXPLAIN (estimated) and ANALYZE (detailed with statistics)
		$analyze_result = array();
		if ( $include_analyze ) {
			$analyze_result = DatabaseService::executeAnalyze( $query );
		}

		// Build result array with both EXPLAIN and ANALYZE sections
		return array(
			'query'           => $query,
			'tables'          => $tables,
			'explain'         => $explain_result,
			'analyze'         => $analyze_result,
			'include_analyze' => $include_analyze,
		);
	}

	/**
	 * Extract table names from a SQL query
	 *
	 * Parses a SQL query to extract all table names referenced.
	 * Handles FROM, JOIN, and other table references.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to parse
	 * @return array<int, string> Array of table names found in query
	 */
	public static function extractTableNames( string $query ): array {
		$tables = array();

		// Remove SQL comments
		$query = preg_replace( '/--.*$/m', '', $query ) ?? $query;
		$query = preg_replace( '|/\*.*?\*/|s', '', $query ) ?? $query;

		// Pattern to match FROM clause and tables
		$from_pattern = '/FROM\s+([a-zA-Z0-9_`\-\.]+)(?:\s+(?:AS\s+)?([a-zA-Z0-9_`]+))?/i';
		if ( preg_match_all( $from_pattern, $query, $matches ) ) {
			$tables = array_merge( $tables, $matches[1] );
		}

		// Pattern to match JOIN clauses
		$join_pattern = '/JOIN\s+([a-zA-Z0-9_`\-\.]+)(?:\s+(?:AS\s+)?([a-zA-Z0-9_`]+))?/i';
		if ( preg_match_all( $join_pattern, $query, $matches ) ) {
			$tables = array_merge( $tables, $matches[1] );
		}

		// Clean up table names (remove backticks, whitespace, prefixes)
		$tables = array_map(
			function ( $table ) {
				// Remove backticks
				$table = str_replace( '`', '', $table );
				// Trim whitespace
				$table = trim( $table );
				// Skip empty tables
				return ! empty( $table ) ? $table : null;
			},
			$tables
		);

		// Remove nulls and duplicates
		$tables = array_filter( $tables );
		$tables = array_unique( $tables );
		$tables = array_values( $tables );

		return $tables;
	}

	/**
	 * Get query type
	 *
	 * Determines the type of SQL query (SELECT, INSERT, UPDATE, DELETE, etc).
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to analyze
	 * @return string The query type (uppercase)
	 */
	public static function getQueryType( string $query ): string {
		// Remove leading whitespace and comments
		$query = trim( $query );
		$query = preg_replace( '/^\/\*.*?\*\//s', '', $query ) ?? $query;
		$query = trim( $query );

		// Extract first word (the query type)
		if ( preg_match( '/^(\w+)\s/i', $query, $matches ) ) {
			return strtoupper( $matches[1] );
		}

		return 'UNKNOWN';
	}

	/**
	 * Parse EXPLAIN output
	 *
	 * Formats EXPLAIN query results into a human-readable structure.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $explain_result The raw EXPLAIN results
	 * @return string Formatted EXPLAIN output
	 */
	public static function parseExplainOutput( array $explain_result ): string {
		if ( empty( $explain_result ) ) {
			return 'No execution plan available.';
		}

		// For MySQL, EXPLAIN returns array of associative arrays
		$output  = "Execution Plan:\n";
		$output .= str_repeat( '=', 80 ) . "\n\n";

		foreach ( $explain_result as $row ) {
			foreach ( $row as $key => $value ) {
				$output .= sprintf(
					"%-20s: %s\n",
					$key,
					self::formatValue( $value )
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

		return (string) $value;
	}

	/**
	 * Check if table is a WordPress table
	 *
	 * Verifies that a table is part of the WordPress installation.
	 *
	 * @since 0.1.0
	 * @param string $table_name The table name to check
	 * @return bool True if table is a WordPress table
	 */
	public static function isWordPressTable( string $table_name ): bool {
		global $wpdb;

		// Remove backticks if present
		$table_name = str_replace( '`', '', $table_name );

		// Get WordPress tables
		$wp_tables = DatabaseService::getWordPressTables();

		return in_array( $table_name, $wp_tables, true );
	}

	/**
	 * Filter tables from query to only WordPress tables
	 *
	 * Takes a list of tables and returns only those that are part
	 * of the WordPress installation.
	 *
	 * @since 0.1.0
	 * @param array<int, string> $tables Array of table names
	 * @return array<int, string> Filtered array of WordPress tables
	 */
	public static function filterWordPressTables( array $tables ): array {
		return array_filter( $tables, array( self::class, 'isWordPressTable' ) );
	}

	/**
	 * Get query performance insights
	 *
	 * Analyzes EXPLAIN output to provide performance insights.
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $explain_result EXPLAIN query results
	 * @return list<string> Array of insights/warnings
	 */
	public static function getPerformanceInsights( array $explain_result ): array {
		$insights = array();

		foreach ( $explain_result as $row ) {
			// Check for full table scan
			if ( isset( $row['type'] ) && 'ALL' === $row['type'] ) {
				$insights[] = 'Warning: Full table scan detected. Consider adding appropriate indexes.';
			}

			// Check for no WHERE clause
			if ( isset( $row['rows'] ) && (int) $row['rows'] > 10000 ) {
				$insights[] = sprintf(
					'Warning: Query will examine %d rows. This may be slow on large datasets.',
					(int) $row['rows']
				);
			}

			// Check for using filesort
			if ( isset( $row['Extra'] ) && strpos( $row['Extra'], 'filesort' ) !== false ) {
				$insights[] = 'Warning: Query uses filesort. Add an index on the ORDER BY columns.';
			}

			// Check for using temporary table
			if ( isset( $row['Extra'] ) && strpos( $row['Extra'], 'temporary' ) !== false ) {
				$insights[] = 'Warning: Query uses temporary table. Optimize GROUP BY or DISTINCT.';
			}
		}

		return $insights;
	}
}
