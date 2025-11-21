<?php
/**
 * Admin Assets Handler
 *
 * Enqueues CSS and JavaScript files for the SQL Analyzer admin page.
 *
 * @package Robert\SqlAnalyzer\Admin
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Admin;

/**
 * AdminAssets Class
 *
 * Handles enqueuing of CSS and JavaScript files for the admin interface.
 * Uses proper dependency management and versioning.
 *
 * @since 0.1.0
 */
final class AdminAssets {

	/**
	 * Register asset hooks
	 *
	 * Hooks into WordPress to enqueue admin scripts and styles
	 * on the SQL Analyzer admin page.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * Enqueues CSS and JavaScript files for the admin page.
	 * Only runs on the SQL Analyzer admin page.
	 *
	 * @since 0.1.0
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public static function enqueueAssets( string $hook_suffix ): void {
		// Only enqueue on the SQL Analyzer admin page
		if ( false === strpos( $hook_suffix, 'sql-analyzer' ) ) {
			return;
		}

		// Enqueue admin styles
		self::enqueueStyles();

		// Enqueue admin scripts
		self::enqueueScripts();

		// Localize script with WordPress data
		self::localizeScript();
	}

	/**
	 * Enqueue admin CSS
	 *
	 * Registers and enqueues the admin stylesheet.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function enqueueStyles(): void {
		wp_enqueue_style(
			'sql-analyzer-admin',                               // Handle
			\SQL_ANALYZER_URL . 'assets/admin/css/sql-analyzer-admin.css', // Source
			array(),                                                 // Dependencies
			\SQL_ANALYZER_VERSION,                              // Version
			'all'                                               // Media
		);
	}

	/**
	 * Enqueue admin JavaScript
	 *
	 * Registers and enqueues the admin JavaScript file.
	 * Uses ES6 module syntax (modern JavaScript).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function enqueueScripts(): void {
		wp_enqueue_script(
			'sql-analyzer-admin',                               // Handle
			\SQL_ANALYZER_URL . 'assets/admin/js/sql-analyzer-admin.js', // Source
			array(),                                                 // Dependencies
			\SQL_ANALYZER_VERSION,                              // Version
			array(
				'in_footer' => true,                            // Load in footer
				'strategy'  => 'defer',                         // Defer loading
			)
		);
	}

	/**
	 * Localize script with WordPress data
	 *
	 * Passes PHP data to JavaScript including the REST API endpoint,
	 * nonce for security verification, and other configuration.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function localizeScript(): void {
		$localized_data = array(
			'restRoot'        => rest_url(),
			'restNonce'       => wp_create_nonce( 'wp_rest' ),
			'analyzeEndpoint' => rest_url( 'sql-analyzer/v1/analyze' ),
			'version'         => \SQL_ANALYZER_VERSION,
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
}
