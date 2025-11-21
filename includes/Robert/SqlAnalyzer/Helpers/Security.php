<?php
/**
 * Security Helper
 *
 * Provides security utilities for input validation and sanitization.
 *
 * @package Robert\SqlAnalyzer\Helpers
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Helpers;

/**
 * Security Class
 *
 * Provides security-related helper methods for validating and sanitizing
 * user input and managing permissions.
 *
 * @since 0.1.0
 */
final class Security {

	/**
	 * Check user capability
	 *
	 * Verifies that the current user has the required capability to access
	 * the SQL Analyzer functionality.
	 *
	 * @since 0.1.0
	 * @return bool True if user has required capability
	 */
	public static function userCanAnalyze(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check nonce validity
	 *
	 * Verifies a WordPress nonce for security verification.
	 *
	 * @since 0.1.0
	 * @param string $nonce The nonce value to verify
	 * @param string $action The action associated with the nonce
	 * @return bool True if nonce is valid
	 */
	public static function verifyNonce( string $nonce, string $action = 'sql_analyzer_nonce' ): bool {
		return wp_verify_nonce( $nonce, $action ) !== false;
	}

	/**
	 * Sanitize SQL query input
	 *
	 * Performs basic sanitization on SQL query input. Note: This does not
	 * prevent SQL injection - use parameterized queries instead.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to sanitize
	 * @return string Sanitized query
	 */
	public static function sanitizeQuery( string $query ): string {
		// Trim whitespace
		$query = trim( $query );

		// Remove potential null bytes
		$query = str_replace( "\0", '', $query );

		// Normalize line endings
		$query = str_replace( array( "\r\n", "\r" ), "\n", $query );

		return $query;
	}

	/**
	 * Validate query syntax
	 *
	 * Performs basic validation on SQL query syntax.
	 *
	 * @since 0.1.0
	 * @param string $query The SQL query to validate
	 * @return array<string, mixed> Array with 'valid' boolean and optional 'errors' array
	 */
	public static function validateQuerySyntax( string $query ): array {
		$errors = array();

		// Check query is not empty
		if ( empty( trim( $query ) ) ) {
			$errors[] = 'Query cannot be empty';
		}

		// Check query length (reasonable limit)
		if ( strlen( $query ) > 50000 ) {
			$errors[] = 'Query exceeds maximum length (50KB)';
		}

		// Check for suspicious patterns
		$suspicious_patterns = array(
			'/xp_/i'          => 'Extended stored procedures not allowed',
			'/sp_/i'          => 'System stored procedures not allowed',
			'/;\s*DROP/i'     => 'DROP statements not allowed',
			'/;\s*DELETE/i'   => 'DELETE statements not allowed',
			'/;\s*TRUNCATE/i' => 'TRUNCATE statements not allowed',
			'/SCRIPT/i'       => 'Script tags not allowed',
			'/EVAL/i'         => 'EVAL not allowed',
			'/EXECUTE/i'      => 'EXECUTE not allowed',
		);

		foreach ( $suspicious_patterns as $pattern => $error ) {
			if ( preg_match( $pattern, $query ) ) {
				$errors[] = $error;
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Escape output for HTML display
	 *
	 * Escapes a string for safe display in HTML context.
	 *
	 * @since 0.1.0
	 * @param string $text The string to escape
	 * @return string Escaped string
	 */
	public static function escapeForDisplay( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape output for JSON response
	 *
	 * Prepares a string for inclusion in JSON response.
	 *
	 * @since 0.1.0
	 * @param mixed $value The value to escape
	 * @return mixed Escaped value
	 */
	public static function escapeForJSON( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( array( self::class, 'escapeForJSON' ), $value );
		}

		if ( is_string( $value ) ) {
			return stripslashes( $value );
		}

		return $value;
	}

	/**
	 * Log security event
	 *
	 * Logs a security-related event to the error log.
	 * Useful for audit trails and debugging.
	 *
	 * @since 0.1.0
	 * @param string               $event The event to log
	 * @param array<string, mixed> $context Additional context for the log
	 * @return void
	 */
	public static function logSecurityEvent( string $event, array $context = array() ): void {
		// Build log message
		$message = sprintf(
			'[SQL Analyzer Security] %s | User: %s',
			$event,
			get_current_user_id()
		);

		// Add context if provided
		if ( ! empty( $context ) ) {
			$message .= ' | ' . wp_json_encode( $context );
		}

		// Log to error log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message );
		}
	}

	/**
	 * Create secure REST API response
	 *
	 * Creates a standardized REST API response with proper headers
	 * and error handling.
	 *
	 * @since 0.1.0
	 * @param bool   $success Whether the request was successful
	 * @param mixed  $data The response data
	 * @param string $message Optional message
	 * @return \WP_REST_Response WordPress REST response object
	 */
	public static function createRestResponse( bool $success, mixed $data = null, string $message = '' ): \WP_REST_Response {
		$response = array(
			'success' => $success,
		);

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		$status = $success ? 200 : 400;

		return new \WP_REST_Response( $response, $status );
	}

	/**
	 * Create error REST API response
	 *
	 * Creates a standardized error REST API response.
	 *
	 * @since 0.1.0
	 * @param string $message The error message
	 * @param int    $status_code The HTTP status code
	 * @return \WP_REST_Response WordPress REST error response
	 */
	public static function createErrorResponse( string $message, int $status_code = 400 ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => $message,
			),
			$status_code
		);
	}

	/**
	 * Check REST API nonce
	 *
	 * Verifies the WordPress REST nonce sent from the frontend.
	 *
	 * @since 0.1.0
	 * @param \WP_REST_Request<array<string, mixed>> $request The REST request object
	 * @return bool True if nonce is valid
	 */
	public static function checkRestNonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce ) {
			return false;
		}

		return wp_verify_nonce( $nonce, 'wp_rest' ) !== false;
	}
}
