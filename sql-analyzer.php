<?php
/**
 * SQL Analyzer Plugin
 *
 * A WordPress plugin that allows administrators to analyze SQL queries,
 * view execution plans (EXPLAIN), database structures, and index information.
 * The formatted output can be easily copied and pasted into LLM chat applications.
 *
 * Plugin Name:     SQL Analyzer
 * Plugin URI:      https://github.com/soare-robert-daniel/sql-analyzer
 * Description:     Analyze SQL queries with EXPLAIN/ANALYZE, view database structures, and export for LLM integration
 * Author:          Soare Robert-Daniel
 * Author URI:      https://soare.dev
 * Text Domain:     sql-analyzer
 * Domain Path:     /languages
 * Version:         0.1.0
 * Requires PHP:    7.4
 * Requires WP:     5.0
 *
 * @package         Robert\SqlAnalyzer
 * @author          Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license         GPL-2.0-or-later
 * @link            https://github.com/soare-robert-daniel/sql-analyzer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

// Define plugin constants
define( 'SQL_ANALYZER_VERSION', '0.1.0' );
define( 'SQL_ANALYZER_FILE', __FILE__ );
define( 'SQL_ANALYZER_DIR', plugin_dir_path( __FILE__ ) );
define( 'SQL_ANALYZER_URL', plugin_dir_url( __FILE__ ) );

// Register admin menu and enqueue scripts
add_action( 'admin_menu', 'sql_analyzer_register_menu' );
add_action( 'admin_enqueue_scripts', 'sql_analyzer_enqueue_assets' );
add_action( 'rest_api_init', 'sql_analyzer_register_rest_endpoint' );

/**
 * Register the admin menu
 *
 * Creates a submenu under "Tools" for the SQL Analyzer.
 *
 * @return void
 */
function sql_analyzer_register_menu(): void {
	// Check user capability
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'tools.php',
		__( 'SQL Analyzer', 'sql-analyzer' ),
		__( 'SQL Analyzer', 'sql-analyzer' ),
		'manage_options',
		'sql-analyzer',
		'sql_analyzer_render_page'
	);
}

/**
 * Render the admin page
 *
 * Displays the SQL Analyzer admin interface.
 *
 * @return void
 */
function sql_analyzer_render_page(): void {
	// Check user capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'sql-analyzer' ) );
	}

	// Include the admin page template
	$template_path = SQL_ANALYZER_DIR . 'templates/admin/query-analyzer.php';
	if ( file_exists( $template_path ) ) {
		include $template_path;
	}
}

/**
 * Enqueue admin scripts and styles
 *
 * Enqueues CSS and JavaScript files for the admin page.
 *
 * @param string $hook_suffix The current admin page hook.
 * @return void
 */
function sql_analyzer_enqueue_assets( string $hook_suffix ): void {
	// Only enqueue on the SQL Analyzer admin page
	if ( false === strpos( $hook_suffix, 'sql-analyzer' ) ) {
		return;
	}

	// Enqueue admin styles
	wp_enqueue_style(
		'sql-analyzer-admin',
		SQL_ANALYZER_URL . 'assets/admin/css/sql-analyzer-admin.css',
		array(),
		SQL_ANALYZER_VERSION,
		'all'
	);

	// Enqueue admin scripts
	wp_enqueue_script(
		'sql-analyzer-admin',
		SQL_ANALYZER_URL . 'assets/admin/js/sql-analyzer-admin.js',
		array(),
		SQL_ANALYZER_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// Localize script with WordPress data
	$localized_data = array(
		'restRoot'        => rest_url(),
		'restNonce'       => wp_create_nonce( 'wp_rest' ),
		'analyzeEndpoint' => rest_url( 'sql-analyzer/v1/analyze' ),
		'version'         => SQL_ANALYZER_VERSION,
		'i18n'            => array(
			'loading'          => __( 'Loading...', 'sql-analyzer' ),
			'analyzing'        => __( 'Analyzing query...', 'sql-analyzer' ),
			'error'            => __( 'Error', 'sql-analyzer' ),
			'success'          => __( 'Success', 'sql-analyzer' ),
			'copied'           => __( 'Copied to clipboard!', 'sql-analyzer' ),
			'copyFailed'       => __( 'Failed to copy to clipboard', 'sql-analyzer' ),
			'invalidQuery'     => __( 'Please enter a valid SQL query', 'sql-analyzer' ),
			'serverError'      => __( 'Server error occurred', 'sql-analyzer' ),
			'noTables'         => __( 'No tables found in query', 'sql-analyzer' ),
			'queryDestructive' => __( 'This query appears to be destructive and cannot be analyzed', 'sql-analyzer' ),
		),
	);

	wp_localize_script(
		'sql-analyzer-admin',
		'sqlAnalyzerData',
		$localized_data
	);
}

/**
 * Register REST API endpoint
 *
 * Registers the analyze endpoint with WordPress REST API.
 *
 * @return void
 */
function sql_analyzer_register_rest_endpoint(): void {
	register_rest_route(
		'sql-analyzer/v1',
		'/analyze',
		array(
			'methods'             => 'POST',
			'callback'            => 'sql_analyzer_handle_request',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'query'           => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => function ( $value ) {
						return trim( (string) $value );
					},
					'description'       => 'The SQL query to analyze',
				),
				'include_analyze' => array(
					'type'        => 'boolean',
					'required'    => false,
					'default'     => false,
					'description' => 'Whether to include ANALYZE results',
				),
			),
		)
	);
}

/**
 * Handle REST API request
 *
 * Processes the analyze request and returns formatted results.
 *
 * @param \WP_REST_Request $request The REST request object.
 * @return \WP_REST_Response The REST API response.
 */
function sql_analyzer_handle_request( \WP_REST_Request $request ): \WP_REST_Response {
	try {
		// Verify nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page.', 'sql-analyzer' ),
				),
				403
			);
		}

		// Get parameters
		$query           = $request->get_param( 'query' );
		$include_analyze = (bool) $request->get_param( 'include_analyze' );

		// Validate query input
		if ( empty( $query ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Query cannot be empty', 'sql-analyzer' ),
				),
				400
			);
		}

		// Validate query is safe for analysis
		if ( ! sql_analyzer_validate_query( $query ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'This query type cannot be analyzed. Only SELECT queries are allowed.', 'sql-analyzer' ),
				),
				400
			);
		}

		// Analyze the query
		$analysis = sql_analyzer_analyze_query( $query, $include_analyze );

		// Return success response
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Query analyzed successfully.', 'sql-analyzer' ),
				'data'    => $analysis,
			),
			200
		);
	} catch ( \Exception $e ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => sprintf( __( 'Analysis error: %s', 'sql-analyzer' ), $e->getMessage() ),
			),
			500
		);
	}
}

/**
 * Validate SQL query
 *
 * Checks that the query is a SELECT statement and doesn't contain destructive operations.
 *
 * @param string $query The SQL query to validate.
 * @return bool True if query is safe for analysis.
 */
function sql_analyzer_validate_query( string $query ): bool {
	$query       = trim( $query );
	$query_upper = strtoupper( $query );

	// Remove comments
	$query_upper = preg_replace( '/--.*$/m', '', $query_upper );
	$query_upper = preg_replace( '|/\*.*?\*/|s', '', $query_upper );

	// Check for destructive operations
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
	);

	foreach ( $dangerous_patterns as $pattern ) {
		if ( preg_match( $pattern, $query ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Extract table names from query
 *
 * Parses a SQL query to extract all table names referenced.
 *
 * @param string $query The SQL query to parse.
 * @return array Array of table names found in query.
 */
function sql_analyzer_extract_table_names( string $query ): array {
	$tables = array();

	// Remove SQL comments
	$query = preg_replace( '/--.*$/m', '', $query );
	$query = preg_replace( '|/\*.*?\*/|s', '', $query );

	// Pattern to match FROM clause
	$from_pattern = '/FROM\s+([a-zA-Z0-9_`\-\.]+)(?:\s+(?:AS\s+)?([a-zA-Z0-9_`]+))?/i';
	if ( preg_match_all( $from_pattern, $query, $matches ) ) {
		$tables = array_merge( $tables, $matches[1] );
	}

	// Pattern to match JOIN clauses
	$join_pattern = '/JOIN\s+([a-zA-Z0-9_`\-\.]+)(?:\s+(?:AS\s+)?([a-zA-Z0-9_`]+))?/i';
	if ( preg_match_all( $join_pattern, $query, $matches ) ) {
		$tables = array_merge( $tables, $matches[1] );
	}

	// Clean up table names
	$tables = array_map(
		function ( $table ) {
			$table = str_replace( '`', '', $table );
			$table = trim( $table );
			return ! empty( $table ) ? $table : null;
		},
		$tables
	);

	// Remove nulls and duplicates
	$tables = array_filter( $tables );
	$tables = array_unique( $tables );

	return array_values( $tables );
}

/**
 * Analyze SQL query
 *
 * Executes EXPLAIN/ANALYZE and gathers database information.
 *
 * @param string $query The SQL query to analyze.
 * @param bool   $include_analyze Whether to include ANALYZE results.
 * @return array Analysis results.
 * @throws \Exception If analysis fails.
 */
function sql_analyzer_analyze_query( string $query, bool $include_analyze = false ): array {
	global $wpdb;

	// Extract table names
	$tables = sql_analyzer_extract_table_names( $query );

	if ( empty( $tables ) ) {
		throw new \Exception( __( 'No tables found in query.', 'sql-analyzer' ) );
	}

	// Execute EXPLAIN
	$explain_query   = 'EXPLAIN ' . $query;
	$explain_results = $wpdb->get_results( $explain_query, ARRAY_A );

	// Execute ANALYZE if requested
	$analyze_results = array();
	if ( $include_analyze ) {
		$analyze_query   = 'ANALYZE ' . $query;
		$analyze_results = $wpdb->get_results( $analyze_query, ARRAY_A );
	}

	// Get table structures and indexes
	$table_info = array();
	$index_info = array();

	foreach ( $tables as $table ) {
		// Get table structure
		$columns = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION',
				DB_NAME,
				$table
			),
			ARRAY_A
		);

		if ( $columns ) {
			$table_info[ $table ] = array(
				'name'    => $table,
				'columns' => array_map(
					function ( $col ) {
						return array(
							'name'    => $col['COLUMN_NAME'],
							'type'    => $col['COLUMN_TYPE'],
							'null'    => 'YES' === $col['IS_NULLABLE'],
							'key'     => $col['COLUMN_KEY'],
							'default' => $col['COLUMN_DEFAULT'],
						);
					},
					$columns
				),
			);
		}

		// Get indexes
		$indexes = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW INDEX FROM %i',
				$table
			),
			ARRAY_A
		);

		if ( $indexes ) {
			$index_info[ $table ] = array_map(
				function ( $idx ) {
					return array(
						'name'   => $idx['Key_name'],
						'type'   => $idx['Index_type'],
						'unique' => ! (bool) $idx['Non_unique'],
						'column' => $idx['Column_name'],
						'seq'    => $idx['Seq_in_index'],
					);
				},
				$indexes
			);
		}
	}

	// Format complete output for LLM
	$complete_output = sql_analyzer_format_output( $query, $explain_results, $table_info, $index_info, $analyze_results );

	return array(
		'query'           => $query,
		'tables'          => array_values( $table_info ),
		'indexes'         => $index_info,
		'explain'         => $explain_results ?: array(),
		'analyze'         => $analyze_results ?: array(),
		'complete_output' => $complete_output,
	);
}

/**
 * Format output for LLM integration
 *
 * Creates a comprehensive, well-formatted analysis report.
 *
 * @param string $query The SQL query.
 * @param array  $explain EXPLAIN results.
 * @param array  $tables Table structure info.
 * @param array  $indexes Index information.
 * @param array  $analyze ANALYZE results.
 * @return string Formatted output.
 */
function sql_analyzer_format_output( string $query, array $explain, array $tables, array $indexes, array $analyze = array() ): string {
	$output = '';

	// Header
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "SQL QUERY ANALYSIS REPORT\n";
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= 'Generated: ' . current_time( 'mysql' ) . "\n\n";

	// Original Query
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= "ORIGINAL QUERY:\n";
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= $query . "\n\n";

	// EXPLAIN Output
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= "EXECUTION PLAN (EXPLAIN):\n";
	$output .= str_repeat( '-', 80 ) . "\n";

	if ( ! empty( $explain ) ) {
		foreach ( $explain as $row ) {
			foreach ( $row as $key => $value ) {
				$output .= sprintf( "%-20s: %s\n", $key, $value ?? 'NULL' );
			}
			$output .= "\n";
		}
	} else {
		$output .= "No execution plan data available.\n\n";
	}

	// ANALYZE Output
	if ( ! empty( $analyze ) ) {
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "QUERY EXECUTION ANALYSIS (ANALYZE):\n";
		$output .= str_repeat( '-', 80 ) . "\n";

		foreach ( $analyze as $row ) {
			foreach ( $row as $key => $value ) {
				$output .= sprintf( "%-20s: %s\n", $key, $value ?? 'NULL' );
			}
			$output .= "\n";
		}
	}

	// Database Structures
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= "DATABASE STRUCTURES:\n";
	$output .= str_repeat( '-', 80 ) . "\n";

	foreach ( $tables as $table ) {
		$output .= sprintf( "Table: %s\n", $table['name'] );

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

	// Indexes
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= "INDEX INFORMATION:\n";
	$output .= str_repeat( '-', 80 ) . "\n";

	foreach ( $indexes as $table_name => $table_indexes ) {
		$output .= sprintf( "Table: %s\n", $table_name );

		if ( ! empty( $table_indexes ) ) {
			foreach ( $table_indexes as $index ) {
				$unique  = $index['unique'] ? 'UNIQUE' : '';
				$output .= sprintf(
					"  - %-30s [%s] %s %s\n",
					$index['name'],
					$index['type'],
					$index['column'],
					$unique
				);
			}
		} else {
			$output .= "  No indexes defined.\n";
		}
		$output .= "\n";
	}

	// Footer
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "END OF REPORT\n";
	$output .= str_repeat( '=', 80 ) . "\n";

	return $output;
}
