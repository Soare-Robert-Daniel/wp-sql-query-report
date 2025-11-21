<?php
/**
 * Query Endpoint REST API Handler
 *
 * Handles REST API requests for SQL query analysis.
 *
 * @package Robert\SqlAnalyzer\API
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\API;

use Robert\SqlAnalyzer\Services\{
	DatabaseService,
	QueryAnalyzer,
	SchemaExtractor,
	IndexService,
	FormattedOutput,
};
use Robert\SqlAnalyzer\Helpers\Security;

/**
 * QueryEndpoint Class
 *
 * Registers and handles REST API endpoints for SQL query analysis.
 * Provides security verification, input validation, and response formatting.
 *
 * @since 0.1.0
 */
final class QueryEndpoint {

	/**
	 * REST API namespace
	 */
	private const NAMESPACE = 'sql-analyzer';

	/**
	 * REST API version
	 */
	private const VERSION = 'v1';

	/**
	 * REST API endpoint route
	 */
	private const ROUTE = '/analyze';

	/**
	 * Register REST API endpoint
	 *
	 * Hooks into WordPress to register the REST API endpoint for query analysis.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'registerRoutes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * Registers the analyze endpoint with WordPress REST API.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE . '/' . self::VERSION,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'handleRequest' ),
				'permission_callback' => array( self::class, 'checkPermission' ),
				'args'                => self::getEndpointArgs(),
			)
		);
	}

	/**
	 * Get endpoint argument schema
	 *
	 * Defines the expected arguments for the analyze endpoint.
	 *
	 * @since 0.1.0
	 * @return array<string, array<string, mixed>> Argument schema
	 */
	private static function getEndpointArgs(): array {
		return array(
			'query'           => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => array( Security::class, 'sanitizeQuery' ),
				'description'       => 'The SQL query to analyze',
			),
			'include_analyze' => array(
				'type'        => 'boolean',
				'required'    => false,
				'default'     => false,
				'description' => 'Whether to include ANALYZE results',
			),
		);
	}

	/**
	 * Check endpoint permission
	 *
	 * Verifies that the requesting user has permission to access the endpoint.
	 * Must be an administrator.
	 *
	 * @since 0.1.0
	 * @return bool|\WP_Error True if user has permission, error otherwise
	 */
	public static function checkPermission(): bool|\WP_Error {
		// Check user capability
		if ( ! Security::userCanAnalyze() ) {
			return new \WP_Error(
				'unauthorized',
				__( 'You do not have permission to access this endpoint.', 'sql-analyzer' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handle REST API request
	 *
	 * Processes the analyze request and returns formatted results.
	 *
	 * @since 0.1.0
	 * @param \WP_REST_Request $request The REST request object
	 * @return \WP_REST_Response The REST API response
	 */
	public static function handleRequest( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			// Verify nonce
			if ( ! Security::checkRestNonce( $request ) ) {
				Security::logSecurityEvent( 'Invalid nonce in REST request' );
				return Security::createErrorResponse(
					__( 'Security verification failed. Please refresh the page.', 'sql-analyzer' ),
					403
				);
			}

			// Get and validate query
			$query           = $request->get_param( 'query' );
			$include_analyze = (bool) $request->get_param( 'include_analyze' );

			// Perform additional validation
			$validation = Security::validateQuerySyntax( $query );
			if ( ! $validation['valid'] ) {
				Security::logSecurityEvent( 'Invalid query syntax', array( 'query' => substr( $query, 0, 100 ) ) );
				return Security::createErrorResponse(
					__( 'Query validation failed: ', 'sql-analyzer' ) . implode( ', ', $validation['errors'] ),
					400
				);
			}

			// Validate query is safe for analysis
			if ( ! DatabaseService::validateQuery( $query ) ) {
				Security::logSecurityEvent( 'Unsafe query attempted', array( 'query' => substr( $query, 0, 100 ) ) );
				return Security::createErrorResponse(
					__( 'This query type cannot be analyzed. Only SELECT queries are allowed.', 'sql-analyzer' ),
					400
				);
			}

			// Analyze the query
			$analysis = self::analyzeQuery( $query, $include_analyze );

			// Log successful analysis
			Security::logSecurityEvent(
				'Query analyzed successfully',
				array(
					'tables_count' => count( $analysis['tables'] ),
				)
			);

			// Return success response
			return Security::createRestResponse(
				true,
				$analysis,
				__( 'Query analyzed successfully.', 'sql-analyzer' )
			);
		} catch ( \Exception $e ) {
			// Log error
			Security::logSecurityEvent( 'Query analysis error: ' . $e->getMessage() );

			// Return error response
			return Security::createErrorResponse(
				sprintf( __( 'Analysis error: %s', 'sql-analyzer' ), $e->getMessage() ),
				500
			);
		}
	}

	/**
	 * Perform complete query analysis
	 *
	 * Executes all analysis steps including EXPLAIN, ANALYZE,
	 * schema extraction, and index analysis.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to analyze
	 * @param bool   $include_analyze Whether to include ANALYZE results
	 * @return array<string, mixed> Complete analysis results
	 * @throws \Exception If analysis fails
	 */
	private static function analyzeQuery( string $query, bool $include_analyze ): array {
		// Analyze query and extract tables
		$analysis = QueryAnalyzer::analyze( $query, $include_analyze );

		// Extract table names and filter to WordPress tables
		$tables    = $analysis['tables'];
		$wp_tables = QueryAnalyzer::filterWordPressTables( $tables );

		if ( empty( $wp_tables ) ) {
			throw new \Exception( __( 'No valid WordPress tables found in query.', 'sql-analyzer' ) );
		}

		// Get schema information for all tables
		$schema_info = SchemaExtractor::getMultipleTableStructures( $wp_tables );

		// Get index information for all tables
		$index_info = IndexService::getMultipleTableIndexes( $wp_tables );

		// Get EXPLAIN results
		$explain_results = $analysis['explain'];

		// Get ANALYZE results if requested
		$analyze_results = $analysis['analyze'] ?? array();

		// Format results for display and LLM
		$complete_output = FormattedOutput::createLLMFriendlyOutput(
			$query,
			$explain_results,
			$schema_info,
			$index_info,
			$analyze_results
		);

		// Build response
		return array(
			'query'           => $query,
			'tables'          => $schema_info,
			'indexes'         => $index_info,
			'explain'         => $explain_results,
			'analyze'         => $analyze_results,
			'complete_output' => $complete_output,
		);
	}
}
