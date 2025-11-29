<?php
/**
 * SQL Analyzer Plugin
 *
 * A WordPress plugin that allows administrators to analyze SQL queries,
 * view execution plans (EXPLAIN), database structures, and index information.
 * The formatted output can be easily copied and pasted into LLM chat applications.
 *
 * Plugin Name:     Simple SQL Query Analyzer
 * Plugin URI:      https://github.com/soare-robert-daniel/sql-analyzer
 * Description:     Analyze SQL queries with EXPLAIN/ANALYZE, view database structures, and export for LLM integration.
 * Author:          Soare Robert-Daniel
 * Text Domain:     simple-sql-query-analyzer
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Requires WP:     6.8
 * License:         GPLv2 or later
 *
 * @package         simple-sql-query-analyzer
 * @author          Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license         GPL-2.0-or-later
 * @link            https://github.com/soare-robert-daniel/sql-analyzer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

define( 'SIMPLE_SQL_QUERY_ANALYZER_VERSION', '1.0.0' );
define( 'SIMPLE_SQL_QUERY_ANALYZER_FILE', __FILE__ );
define( 'SIMPLE_SQL_QUERY_ANALYZER_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_SQL_QUERY_ANALYZER_URL', plugin_dir_url( __FILE__ ) );

add_action( 'admin_menu', 'simple_sql_query_analyzer_register_menu' );
add_action( 'admin_enqueue_scripts', 'simple_sql_query_analyzer_enqueue_assets' );
add_action( 'rest_api_init', 'simple_sql_query_analyzer_register_rest_endpoint' );

/**
 * Register the admin menu.
 *
 * Creates a submenu under "Tools" for the SQL Analyzer.
 *
 * @return void
 */
function simple_sql_query_analyzer_register_menu(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'tools.php',
		__( 'SQL Analyzer', 'simple-sql-query-analyzer' ),
		__( 'SQL Analyzer', 'simple-sql-query-analyzer' ),
		'manage_options',
		'simple-sql-query-analyzer',
		'simple_sql_query_analyzer_render_page'
	);
}

/**
 * Render the admin page.
 *
 * Displays the SQL Analyzer admin interface.
 *
 * @return void
 */
function simple_sql_query_analyzer_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'simple-sql-query-analyzer' ) );
	}

	$template_path = SIMPLE_SQL_QUERY_ANALYZER_DIR . 'includes/templates/admin/query-analyzer.php';
	if ( file_exists( $template_path ) ) {
		include $template_path;
	}
}

/**
 * Enqueue admin scripts and styles.
 *
 * Enqueues CSS and JavaScript files for the admin page.
 *
 * @param string $hook_suffix The current admin page hook.
 * @return void
 */
function simple_sql_query_analyzer_enqueue_assets( string $hook_suffix ): void {
	if ( false === strpos( $hook_suffix, 'simple-sql-query-analyzer' ) ) {
		return;
	}

	$dashboard_asset = include SIMPLE_SQL_QUERY_ANALYZER_DIR . 'build/dashboard.asset.php';

	wp_enqueue_script(
		'sql-analyzer-dashboard',
		SIMPLE_SQL_QUERY_ANALYZER_URL . 'build/dashboard.js',
		$dashboard_asset['dependencies'],
		$dashboard_asset['version'],
		array( 'in_footer' => true )
	);

	wp_enqueue_style(
		'sql-analyzer-dashboard-style',
		SIMPLE_SQL_QUERY_ANALYZER_URL . 'build/dashboard.css',
		array(),
		$dashboard_asset['version']
	);

	$localized_data = array(
		'restRoot'        => rest_url(),
		'restNonce'       => wp_create_nonce( 'wp_rest' ),
		'analyzeEndpoint' => rest_url( 'sql-analyzer/v1/analyze' ),
		'version'         => SIMPLE_SQL_QUERY_ANALYZER_VERSION,
	);

	wp_localize_script(
		'sql-analyzer-dashboard',
		'sqlAnalyzerData',
		$localized_data
	);
}

/**
 * Register REST API endpoint.
 *
 * Registers the analyze endpoint with WordPress REST API.
 *
 * @return void
 */
function simple_sql_query_analyzer_register_rest_endpoint(): void {
	register_rest_route(
		'sql-analyzer/v1',
		'/analyze',
		array(
			'methods'             => 'POST',
			'callback'            => 'simple_sql_query_analyzer_handle_request',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'queries'         => array(
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array( 'type' => 'string' ),
							'label' => array( 'type' => 'string' ),
							'query' => array( 'type' => 'string' ),
						),
					),
					'required'    => true,
					'description' => 'Array of SQL queries to analyze',
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
 * Handle REST API request.
 *
 * Processes the analyze request and returns formatted results.
 *
 * @param \WP_REST_Request<array<string, mixed>> $request The REST request object.
 * @return \WP_REST_Response The REST API response.
 */
function simple_sql_query_analyzer_handle_request( $request ) {
	try {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Security verification failed. Please refresh the page.', 'simple-sql-query-analyzer' ),
				),
				403
			);
		}

		$queries         = $request->get_param( 'queries' );
		$include_analyze = (bool) $request->get_param( 'include_analyze' );

		if ( empty( $queries ) || ! is_array( $queries ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'At least one query is required', 'simple-sql-query-analyzer' ),
				),
				400
			);
		}

		$results = simple_sql_query_analyzer_analyze_queries( $queries, $include_analyze );

		return new \WP_REST_Response(
			array(
				'success'         => true,
				'message'         => sprintf(
					/* translators: %d = number of queries */
					__( 'Analyzed %d queries successfully.', 'simple-sql-query-analyzer' ),
					count( $results['queries'] )
				),
				'queries'         => $results['queries'],
				'summary'         => $results['summary'],
				'complete_output' => $results['complete_output'],
			),
			200
		);
	} catch ( \Exception $e ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				/* translators: %s = error message from exception */
				'message' => wp_kses_post( sprintf( __( 'Analysis error: %s', 'simple-sql-query-analyzer' ), $e->getMessage() ) ),
			),
			500
		);
	}
}

/**
 * Validate SQL query.
 *
 * Checks that the query is a SELECT statement and doesn't contain destructive operations.
 *
 * @param string $query The SQL query to validate.
 * @return bool True if query is safe for analysis.
 */
function simple_sql_query_analyzer_validate_query( string $query ): bool {
	$query       = trim( $query );
	$query_upper = strtoupper( $query );

	// Remove comments.
	$query_upper = (string) preg_replace( '/--.*$/m', '', $query_upper );
	$query_upper = (string) preg_replace( '|/\*.*?\*/|s', '', $query_upper );

	// Check for destructive operations.
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

	// Check for dangerous functions.
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
 * Extract table names from query.
 *
 * Parses a SQL query to extract all table names referenced.
 *
 * @param string $query The SQL query to parse.
 * @return array<int, string> Array of table names found in query.
 */
function simple_sql_query_analyzer_extract_table_names( string $query ): array {
	$tables = array();

	// Remove SQL comments.
	$query = (string) preg_replace( '/--.*$/m', '', $query );
	$query = (string) preg_replace( '|/\*.*?\*/|s', '', $query );

	// Pattern to match FROM clause.
	$from_pattern = '/FROM\s+([a-zA-Z0-9_`\-\.]+)(?:\s+(?:AS\s+)?([a-zA-Z0-9_`]+))?/i';
	if ( preg_match_all( $from_pattern, $query, $matches ) ) {
		$tables = array_merge( $tables, $matches[1] );
	}

	// Pattern to match JOIN clauses.
	$join_pattern = '/JOIN\s+([a-zA-Z0-9_`\-\.]+)(?:\s+(?:AS\s+)?([a-zA-Z0-9_`]+))?/i';
	if ( preg_match_all( $join_pattern, $query, $matches ) ) {
		$tables = array_merge( $tables, $matches[1] );
	}

	// Clean up table names.
	$tables = array_map(
		function ( $table ) {
			$table = str_replace( '`', '', $table );
			$table = trim( $table );
			return ! empty( $table ) ? $table : null;
		},
		$tables
	);

	// Remove nulls and duplicates.
	$tables = array_filter( $tables );
	$tables = array_unique( $tables );

	return array_values( $tables );
}

/**
 * Analyze multiple SQL queries.
 *
 * Processes an array of queries and returns aggregated results.
 *
 * @param array<int, array<string, string>> $query_inputs Array of query objects with id, label, query.
 * @param bool                              $include_analyze Whether to include ANALYZE results.
 * @return array<string, mixed> Array containing queries, summary, and complete_output.
 * @throws \Exception If analysis fails.
 */
function simple_sql_query_analyzer_analyze_queries( array $query_inputs, bool $include_analyze = false ): array {
	$results       = array();
	$total_cost    = 0;
	$total_time    = 0;
	$slowest_index = null;
	$slowest_time  = 0;
	$has_warnings  = false;

	foreach ( $query_inputs as $index => $input ) {
		// Validate query is safe for analysis.
		if ( ! simple_sql_query_analyzer_validate_query( $input['query'] ) ) {
			$results[] = array(
				'id'      => $input['id'],
				'label'   => $input['label'],
				'query'   => $input['query'],
				'error'   => __( 'Only SELECT queries are allowed', 'simple-sql-query-analyzer' ),
				'tables'  => array(),
				'indexes' => array(),
				'explain' => array(),
				'analyze' => array(),
			);
			continue;
		}

		$start_time = microtime( true );

		try {
			$query_result   = simple_sql_query_analyzer_analyze_query( $input['query'], $include_analyze );
			$execution_time = microtime( true ) - $start_time;

			$query_result['id']             = $input['id'];
			$query_result['label']          = $input['label'];
			$query_result['query']          = $input['query'];
			$query_result['execution_time'] = $execution_time;
			$query_result['error']          = null;

			// Calculate total cost (approximation from first explain line).
			if ( ! empty( $query_result['explain'] ) ) {
				$explain_text = $query_result['explain'][0]['EXPLAIN'] ?? '';
				if ( preg_match( '/cost=([0-9.e+]+)/', $explain_text, $matches ) ) {
					$query_cost  = floatval( $matches[1] );
					$total_cost += $query_cost;
				}
			}

			$total_time += $execution_time;

			if ( $execution_time > $slowest_time ) {
				$slowest_time  = $execution_time;
				$slowest_index = $index;
			}

			if ( ! empty( $query_result['explain'] ) ) {
				$explain_text = strtoupper( $query_result['explain'][0]['EXPLAIN'] ?? '' );
				if ( strpos( $explain_text, 'TABLE SCAN' ) !== false ) {
					$has_warnings = true;
				}
			}

			$results[] = $query_result;
		} catch ( \Exception $e ) {
			$results[] = array(
				'id'      => $input['id'],
				'label'   => $input['label'],
				'query'   => $input['query'],
				'error'   => $e->getMessage(),
				'tables'  => array(),
				'indexes' => array(),
				'explain' => array(),
				'analyze' => array(),
			);
		}
	}

	$complete_output = simple_sql_query_analyzer_format_multi_query_output( $results );

	return array(
		'queries'         => $results,
		'summary'         => array(
			'total_queries'        => count( $results ),
			'total_execution_time' => $total_time,
			'total_cost'           => (int) $total_cost,
			'slowest_query_index'  => $slowest_index,
			'has_warnings'         => $has_warnings,
		),
		'complete_output' => $complete_output,
	);
}

/**
 * Analyze SQL query.
 *
 * Executes EXPLAIN/ANALYZE and gathers database information.
 *
 * @param string $query The SQL query to analyze.
 * @param bool   $include_analyze Whether to include ANALYZE results.
 * @return array<string, mixed> Analysis results.
 * @throws \Exception If analysis fails.
 */
function simple_sql_query_analyzer_analyze_query( string $query, bool $include_analyze = false ): array {
	global $wpdb;

	$tables = simple_sql_query_analyzer_extract_table_names( $query );

	if ( empty( $tables ) ) {
		throw new \Exception( wp_kses_post( __( 'No tables found in query.', 'simple-sql-query-analyzer' ) ) );
	}

	// Execute EXPLAIN with FORMAT=TREE for user-friendly output with cost estimates.
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$explain_results = $wpdb->get_results( 'EXPLAIN FORMAT=TREE ' . $query, ARRAY_A );

	// Execute ANALYZE if requested (MySQL 8.0.18+ provides real-time execution metrics).
	$analyze_results = array();
	if ( $include_analyze ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$analyze_results = $wpdb->get_results( 'EXPLAIN ANALYZE ' . $query, ARRAY_A );
	}

	$table_info = array();
	$index_info = array();

	foreach ( $tables as $table ) {
		$sanitized_table = sanitize_key( $table );

		// Get table structure.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION',
				DB_NAME,
				$sanitized_table
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

		// Get indexes with escaped table name - backticks protect identifier from SQL injection.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$indexes = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifier safely escaped with backticks and sanitize_key
			"SHOW INDEX FROM `$sanitized_table`",
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

	$complete_output = simple_sql_query_analyzer_format_output( $query, $explain_results, $table_info, $index_info, $analyze_results, $include_analyze );

	return array(
		'query'           => $query,
		'tables'          => array_values( $table_info ),
		'indexes'         => $index_info,
		'explain'         => $explain_results ?? array(),
		'analyze'         => $analyze_results ?? array(),
		'complete_output' => $complete_output,
	);
}

/**
 * Format table columns with aligned padding.
 *
 * @param array<int, array<string, mixed>> $columns Array of column data.
 * @return string Formatted columns with aligned padding.
 */
function simple_sql_query_analyzer_format_table_columns( array $columns ): string {
	if ( empty( $columns ) ) {
		return '';
	}

	// Pass 1: Calculate maximum widths.
	$max_name_width = 0;
	$max_type_width = 0;
	$max_null_width = 0;

	foreach ( $columns as $col ) {
		$max_name_width = max( $max_name_width, strlen( $col['name'] ) );
		$max_type_width = max( $max_type_width, strlen( $col['type'] ) );
		$null_text      = $col['null'] ? 'NULL' : 'NOT NULL';
		$max_null_width = max( $max_null_width, strlen( $null_text ) );
	}

	$spacing = 4;
	$output  = '';

	// Pass 2: Format with padding.
	foreach ( $columns as $col ) {
		$null_text = $col['null'] ? 'NULL' : 'NOT NULL';
		$key_text  = $col['key'] ? 'KEY: ' . $col['key'] : '';

		$line = '  ' .
			str_pad( $col['name'], $max_name_width, ' ', STR_PAD_RIGHT ) .
			str_repeat( ' ', $spacing ) .
			str_pad( $col['type'], $max_type_width, ' ', STR_PAD_RIGHT ) .
			str_repeat( ' ', $spacing ) .
			str_pad( $null_text, $max_null_width, ' ', STR_PAD_RIGHT );

		if ( $key_text ) {
			$line .= str_repeat( ' ', $spacing ) . $key_text;
		}

		$output .= $line . "\n";
	}

	return $output;
}

/**
 * Format indexes with aligned padding.
 *
 * @param array<int, array<string, mixed>> $indexes Array of index data.
 * @return string Formatted indexes with aligned padding.
 */
function simple_sql_query_analyzer_format_indexes( array $indexes ): string {
	if ( empty( $indexes ) ) {
		return '';
	}

	// Pass 1: Calculate maximum widths.
	$max_name_width   = 0;
	$max_type_width   = 0;
	$max_column_width = 0;

	foreach ( $indexes as $idx ) {
		$max_name_width   = max( $max_name_width, strlen( $idx['name'] ) );
		$type_text        = '(' . $idx['type'] . ')';
		$max_type_width   = max( $max_type_width, strlen( $type_text ) );
		$max_column_width = max( $max_column_width, strlen( $idx['column'] ) );
	}

	$spacing = 4;
	$output  = '';

	// Pass 2: Format with padding.
	foreach ( $indexes as $idx ) {
		$type_text   = '(' . $idx['type'] . ')';
		$unique_text = $idx['unique'] ? 'UNIQUE' : '';

		$line = '  ' .
			str_pad( $idx['name'], $max_name_width, ' ', STR_PAD_RIGHT ) .
			str_repeat( ' ', $spacing ) .
			str_pad( $type_text, $max_type_width, ' ', STR_PAD_RIGHT ) .
			str_repeat( ' ', $spacing ) .
			str_pad( $idx['column'], $max_column_width, ' ', STR_PAD_RIGHT );

		if ( $unique_text ) {
			$line .= str_repeat( ' ', $spacing ) . $unique_text;
		}

		$output .= $line . "\n";
	}

	return $output;
}

/**
 * Format multi-query output for LLM integration.
 *
 * Creates a comprehensive report for multiple queries.
 *
 * @param array<int, array<string, mixed>> $query_results Array of query result objects.
 * @return string Formatted output for LLM export.
 */
function simple_sql_query_analyzer_format_multi_query_output( array $query_results ): string {
	$output = '';

	// Header.
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "SQL QUERY ANALYSIS REPORT - MULTI-QUERY SESSION\n";
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= 'Generated: ' . current_time( 'mysql' ) . "\n";
	$output .= 'Number of Queries: ' . count( $query_results ) . "\n";

	// Environment Information.
	global $wpdb;
	$db_version = $wpdb->db_version();
	$db_type    = ( strpos( $db_version, 'MariaDB' ) !== false ) ? 'MariaDB' : 'MySQL';

	$output .= 'WordPress Version: ' . get_bloginfo( 'version' ) . "\n";
	$output .= 'PHP Version: ' . phpversion() . "\n";
	$output .= 'Database Type: ' . $db_type . "\n";
	$output .= 'Database Version: ' . $db_version . "\n";
	$output .= "\n";

	// Executive Summary.
	$total_cost    = 0;
	$total_time    = 0;
	$slowest_index = null;
	$slowest_time  = 0;

	foreach ( $query_results as $index => $result ) {
		$execution_time = $result['execution_time'] ?? 0;
		$total_time    += $execution_time;

		if ( ! empty( $result['explain'] ) ) {
			$explain_text = $result['explain'][0]['EXPLAIN'] ?? '';
			if ( preg_match( '/cost=([0-9.e+]+)/', $explain_text, $matches ) ) {
				$total_cost += floatval( $matches[1] );
			}
		}

		if ( $execution_time > $slowest_time ) {
			$slowest_time  = $execution_time;
			$slowest_index = $index;
		}
	}

	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "EXECUTIVE SUMMARY\n";
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= 'Total Execution Time: ' . number_format( $total_time, 3 ) . "s\n";
	$output .= 'Total Estimated Cost: ' . number_format( (int) $total_cost, 0 ) . " (relative units)\n";

	if ( null !== $slowest_index ) {
		$slowest = $query_results[ $slowest_index ];
		$output .= 'Slowest Query: ' . $slowest['label'] . ' (' . number_format( $slowest_time, 3 ) . "s)\n";
	}

	$output .= "\n";

	// Individual query results.
	foreach ( $query_results as $index => $result ) {
		$output .= str_repeat( '=', 80 ) . "\n";
		$output .= 'QUERY ' . ( $index + 1 ) . ': ' . $result['label'] . "\n";
		$output .= str_repeat( '=', 80 ) . "\n";

		if ( ! empty( $result['error'] ) ) {
			$output .= 'ERROR: ' . $result['error'] . "\n\n";
			continue;
		}

		$execution_time = $result['execution_time'] ?? 0;
		$output        .= 'Execution Time: ' . number_format( $execution_time, 3 ) . "s\n\n";

		// Original query.
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "ORIGINAL QUERY:\n";
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= $result['query'] . "\n\n";

		// Query type.
		$query_type = simple_sql_query_analyzer_get_query_type( $result['query'] );
		$output    .= 'Query Type: ' . $query_type . "\n\n";

		// Execution plans.
		if ( ! empty( $result['analyze'] ) ) {
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= "EXECUTION PLAN (ACTUAL - ANALYZE):\n";
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= $result['analyze'][0]['EXPLAIN'] . "\n\n";
		}

		if ( ! empty( $result['explain'] ) ) {
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= "EXECUTION PLAN (ESTIMATED - EXPLAIN):\n";
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= $result['explain'][0]['EXPLAIN'] . "\n\n";
		}

		// Table structures.
		if ( ! empty( $result['tables'] ) ) {
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= "TABLE STRUCTURES:\n";
			$output .= str_repeat( '-', 80 ) . "\n";

			foreach ( $result['tables'] as $table ) {
				$output .= "\nTable: " . $table['name'] . "\n";
				$output .= str_repeat( '-', 40 ) . "\n";
				$output .= simple_sql_query_analyzer_format_table_columns( $table['columns'] );
			}
			$output .= "\n";
		}

		// Indexes.
		if ( ! empty( $result['indexes'] ) ) {
			$output .= str_repeat( '-', 80 ) . "\n";
			$output .= "INDEXES:\n";
			$output .= str_repeat( '-', 80 ) . "\n";

			foreach ( $result['indexes'] as $table_name => $indexes ) {
				$output .= "\nTable: " . $table_name . "\n";
				$output .= simple_sql_query_analyzer_format_indexes( $indexes );
			}
			$output .= "\n";
		}
	}

	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "END OF MULTI-QUERY REPORT\n";
	$output .= str_repeat( '=', 80 ) . "\n";

	return $output;
}

/**
 * Get query type.
 *
 * Determines the type of SQL query.
 *
 * @param string $query The SQL query.
 * @return string The query type (SELECT, INSERT, UPDATE, DELETE, etc.).
 */
function simple_sql_query_analyzer_get_query_type( string $query ): string {
	$upper = strtoupper( trim( $query ) );
	$upper = (string) preg_replace( '/^(\/\*.*?\*\/)*/s', '', $upper );

	if ( preg_match( '/^SELECT\s+/i', $upper ) ) {
		return 'SELECT';
	} elseif ( preg_match( '/^INSERT\s+/i', $upper ) ) {
		return 'INSERT';
	} elseif ( preg_match( '/^UPDATE\s+/i', $upper ) ) {
		return 'UPDATE';
	} elseif ( preg_match( '/^DELETE\s+/i', $upper ) ) {
		return 'DELETE';
	}

	return 'UNKNOWN';
}

/**
 * Format output for LLM integration.
 *
 * Creates a comprehensive, well-formatted analysis report.
 *
 * @param string                                              $query The SQL query.
 * @param array<int, array<string, mixed>>                    $explain EXPLAIN results.
 * @param array<string|int, array<string, mixed>>             $tables Table structure info.
 * @param array<string|int, array<int, array<string, mixed>>> $indexes Index information.
 * @param array<int, array<string, mixed>>                    $analyze ANALYZE results.
 * @param bool                                                $include_analyze Whether ANALYZE was requested.
 * @return string Formatted output.
 */
function simple_sql_query_analyzer_format_output( string $query, array $explain, array $tables, array $indexes, array $analyze = array(), bool $include_analyze = false ): string {
	$output = '';

	// Header.
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "SQL QUERY ANALYSIS REPORT\n";
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= 'Generated: ' . current_time( 'mysql' ) . "\n";
	$output .= 'Include ANALYZE: ' . ( $include_analyze ? 'Yes' : 'No' ) . "\n";
	// Environment Information.
	global $wpdb;
	$db_version = $wpdb->db_version();
	$db_type    = ( strpos( $db_version, 'MariaDB' ) !== false ) ? 'MariaDB' : 'MySQL';

	$output .= 'WordPress Version: ' . get_bloginfo( 'version' ) . "\n";
	$output .= 'PHP Version: ' . phpversion() . "\n";
	$output .= 'Database Type: ' . $db_type . "\n";
	$output .= 'Database Version: ' . $db_version . "\n";
	$output .= "\n";

	// Original Query.
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= "ORIGINAL QUERY:\n";
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= $query . "\n\n";

	// EXPLAIN Output.
	$output .= str_repeat( '-', 80 ) . "\n";
	$output .= "EXECUTION PLAN (EXPLAIN):\n";
	$output .= str_repeat( '-', 80 ) . "\n";

	if ( ! empty( $explain ) ) {
		// Check if this is EXPLAIN FORMAT=TREE output (single column, tree format).
		if ( 1 === count( $explain ) && 1 === count( reset( $explain ) ) ) {
			// Get the tree value directly and output as-is.
			$first_row  = reset( $explain );
			$tree_value = reset( $first_row );
			$output    .= (string) $tree_value . "\n\n";
		} else {
			// Traditional EXPLAIN format with multiple columns/rows.
			foreach ( $explain as $row ) {
				foreach ( $row as $key => $value ) {
					$output .= sprintf( "%-20s: %s\n", $key, $value ?? 'NULL' );
				}
				$output .= "\n";
			}
		}
	} else {
		$output .= "No execution plan data available.\n\n";
	}

	// ANALYZE Output.
	if ( ! empty( $analyze ) ) {
		$output .= str_repeat( '-', 80 ) . "\n";
		$output .= "QUERY EXECUTION ANALYSIS (ANALYZE):\n";
		$output .= str_repeat( '-', 80 ) . "\n";

		// Check if this is tree format output.
		if ( 1 === count( $analyze ) && 1 === count( reset( $analyze ) ) ) {
			// Get the tree value directly and output as-is.
			$first_row  = reset( $analyze );
			$tree_value = reset( $first_row );
			$output    .= (string) $tree_value . "\n\n";
		} else {
			// Traditional ANALYZE format.
			foreach ( $analyze as $row ) {
				foreach ( $row as $key => $value ) {
					$output .= sprintf( "%-20s: %s\n", $key, $value ?? 'NULL' );
				}
				$output .= "\n";
			}
		}
	}

	// Database Structures.
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

	// Indexes.
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

	// Footer.
	$output .= str_repeat( '=', 80 ) . "\n";
	$output .= "END OF REPORT\n";
	$output .= str_repeat( '=', 80 ) . "\n";

	return $output;
}
