<?php
/**
 * Database Service
 *
 * Handles database connections and query execution for the SQL Analyzer.
 *
 * @package Robert\SqlAnalyzer\Services
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Services;

/**
 * DatabaseService Class
 *
 * Provides methods to connect to the WordPress database and execute queries.
 * Uses the global $wpdb object for database operations.
 *
 * @since 0.1.0
 */
final class DatabaseService {

	/**
	 * Get WordPress database connection
	 *
	 * Returns the global WordPress database object.
	 *
	 * @since 0.1.0
	 * @return \wpdb The WordPress database object
	 */
	public static function getConnection(): \wpdb {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Execute EXPLAIN query
	 *
	 * Executes EXPLAIN on a query to get the execution plan.
	 * Does not modify any data.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to analyze
	 * @return array<int, array<string, mixed>> Array of EXPLAIN results
	 * @throws \Exception If query execution fails
	 */
	public static function executeExplain( string $query ): array {
		global $wpdb;

		// Suppress WordPress database query logging for analysis queries
		$old_suppress = $wpdb->suppress_errors();

		// Execute EXPLAIN with FORMAT=TREE for user-friendly output with cost estimates
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( 'EXPLAIN FORMAT=TREE ' . $query, ARRAY_A );

		// Restore error suppression state
		$wpdb->suppress_errors( $old_suppress );

		// Check for database errors
		if ( $wpdb->last_error ) {
			throw new \Exception(
				sprintf(
					'Database error: %s',
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal error logging
					$wpdb->last_error
				)
			);
		}

		// Ensure we have results
		if ( null === $results ) {
			$results = array();
		}

		return $results;
	}

	/**
	 * Execute ANALYZE query
	 *
	 * Uses EXPLAIN ANALYZE (MySQL 8.0.18+) to get real-time execution plan with actual performance metrics.
	 * This actually executes the query and provides real timing information including actual costs
	 * of individual iterators and where MySQL spends time during execution.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to analyze
	 * @return array<int, array<string, mixed>> Array of ANALYZE results
	 * @throws \Exception If query execution fails
	 */
	public static function executeAnalyze( string $query ): array {
		global $wpdb;

		// Suppress WordPress database query logging
		$old_suppress = $wpdb->suppress_errors();

		// Use EXPLAIN ANALYZE for real-time execution plan with actual performance metrics
		// MySQL 8.0.18+ executes the query and provides actual costs and timing data
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( 'EXPLAIN ANALYZE ' . $query, ARRAY_A );

		// Restore error suppression state
		$wpdb->suppress_errors( $old_suppress );

		// Check for database errors
		if ( $wpdb->last_error ) {
			throw new \Exception(
				sprintf(
					'Database error: %s',
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal error logging
					$wpdb->last_error
				)
			);
		}

		// Ensure we have results
		if ( null === $results ) {
			$results = array();
		}

		return $results;
	}

	/**
	 * Get current database name
	 *
	 * Returns the name of the WordPress database.
	 *
	 * @since 0.1.0
	 * @return string The database name
	 */
	public static function getDatabaseName(): string {
		$wpdb = self::getConnection();
		/** @phpstan-ignore-next-line */
		return $wpdb->dbname;
	}

	/**
	 * Get database charset
	 *
	 * Returns the character set of the WordPress database.
	 *
	 * @since 0.1.0
	 * @return string The database charset
	 */
	public static function getDatabaseCharset(): string {
		$wpdb = self::getConnection();
		return $wpdb->charset;
	}

	/**
	 * Check if table exists
	 *
	 * Checks whether a table exists in the database.
	 *
	 * @since 0.1.0
	 * @param string $table_name The name of the table to check
	 * @return bool True if table exists, false otherwise
	 */
	public static function tableExists( string $table_name ): bool {
		$wpdb = self::getConnection();

		// Sanitize table name
		$table_name = sanitize_key( $table_name );

		// Execute query and check results with prepared statement
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $table_name )
			)
		);

		return ! empty( $result );
	}

	/**
	 * Get all WordPress tables
	 *
	 * Returns a list of all WordPress tables in the database.
	 *
	 * @since 0.1.0
	 * @return array<int, string> Array of table names
	 */
	public static function getWordPressTables(): array {
		global $wpdb;

		// Get WordPress table names from $wpdb
		$tables = array();

		// Add core WordPress tables
		$wp_tables = array(
			'posts',
			'postmeta',
			'comments',
			'commentmeta',
			'users',
			'usermeta',
			'options',
			'terms',
			'termmeta',
			'term_taxonomy',
			'term_relationships',
			'links',
			'prefix_capabilities',
			'prefix_role',
		);

		// Replace 'prefix_' with actual WordPress prefix
		foreach ( $wp_tables as $table ) {
			$table     = str_replace( 'prefix_', '', $table );
			$full_name = $wpdb->prefix . $table;

			// Check if table exists
			if ( self::tableExists( $full_name ) ) {
				$tables[] = $full_name;
			}
		}

		return $tables;
	}

	/**
	 * Validate query is safe for analysis
	 *
	 * Checks that the query is a SELECT statement and doesn't
	 * contain destructive operations.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to validate
	 * @return bool True if query is safe for analysis
	 */
	public static function validateQuery( string $query ): bool {
		// Trim whitespace
		$query = trim( $query );

		// Convert to uppercase for comparison
		$query_upper = strtoupper( $query );

		// Remove comments
		$query_upper = preg_replace( '/--.*$/m', '', $query_upper ) ?? $query_upper;
		$query_upper = preg_replace( '|/\*.*?\*/|s', '', $query_upper ) ?? $query_upper;

		// Check for destructive operations (SELECT only is allowed)
		$destructive_patterns = array(
			'/^\s*INSERT\s+/i',
			'/^\s*UPDATE\s+/i',
			'/^\s*DELETE\s+/i',
			'/^\s*DROP\s+/i',
			'/^\s*TRUNCATE\s+/i',
			'/^\s*ALTER\s+/i',
			'/^\s*CREATE\s+/i',
			'/^\s*GRANT\s+/i',
			'/^\s*REVOKE\s+/i',
		);

		foreach ( $destructive_patterns as $pattern ) {
			if ( preg_match( $pattern, $query ) ) {
				return false;
			}
		}

		// Check for dangerous functions
		$dangerous_patterns = array(
			'/EXEC\s*\(/i',
			'/INTO\s+OUTFILE/i',
			'/INTO\s+DUMPFILE/i',
			'/LOAD_FILE\s*\(/i',
			'/UNION\s+SELECT/i',  // Potentially dangerous union queries
		);

		foreach ( $dangerous_patterns as $pattern ) {
			if ( preg_match( $pattern, $query ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check user has required capability
	 *
	 * Verifies that the current user can manage options (admin).
	 * This is a security check to prevent unauthorized database access.
	 *
	 * @since 0.1.0
	 * @return bool True if user has required capability
	 */
	public static function userCanAnalyze(): bool {
		return current_user_can( 'manage_options' );
	}
}
