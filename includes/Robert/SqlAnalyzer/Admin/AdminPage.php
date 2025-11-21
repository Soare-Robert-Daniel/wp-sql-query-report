<?php
/**
 * Admin Page Handler
 *
 * Registers and manages the SQL Analyzer admin menu and page.
 *
 * @package Robert\SqlAnalyzer\Admin
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

namespace Robert\SqlAnalyzer\Admin;

/**
 * AdminPage Class
 *
 * Handles registration of the admin menu item and rendering
 * of the SQL Analyzer admin page template.
 *
 * @since 0.1.0
 */
final class AdminPage {

	/**
	 * Admin page slug
	 */
	private const MENU_SLUG = 'sql-analyzer';

	/**
	 * Admin page capability requirement
	 */
	private const REQUIRED_CAPABILITY = 'manage_options';

	/**
	 * Register the admin page
	 *
	 * Adds the menu item and page hook. Called from plugin initialization.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		// Register admin menu
		add_action( 'admin_menu', array( self::class, 'addMenu' ) );

		// Register page content render callback
		add_action( 'admin_page_' . self::MENU_SLUG, array( self::class, 'renderPage' ) );
	}

	/**
	 * Add the admin menu item
	 *
	 * Creates a new submenu under "Tools" for the SQL Analyzer.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function addMenu(): void {
		// Check user capability
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			return;
		}

		// Add submenu page under Tools
		add_submenu_page(
			'tools.php',                                // Parent slug
			__( 'SQL Analyzer', 'sql-analyzer' ),       // Page title
			__( 'SQL Analyzer', 'sql-analyzer' ),       // Menu title
			self::REQUIRED_CAPABILITY,                // Capability required
			self::MENU_SLUG,                          // Menu slug
			array( self::class, 'renderPage' )               // Callback function
		);
	}

	/**
	 * Render the admin page
	 *
	 * Includes the admin page template and handles the page output.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function renderPage(): void {
		// Check user capability
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'sql-analyzer' ),
				esc_html__( 'Unauthorized', 'sql-analyzer' ),
				403
			);
		}

		// Include the admin page template
		/** @phpstan-ignore-next-line */
		$template_path = \SQL_ANALYZER_DIR . 'templates/admin/query-analyzer.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			wp_die(
				esc_html__( 'Admin page template not found.', 'sql-analyzer' ),
				esc_html__( 'Error', 'sql-analyzer' ),
				500
			);
		}
	}

	/**
	 * Get the admin page URL
	 *
	 * Helper method to get the URL to the SQL Analyzer admin page.
	 *
	 * @since 0.1.0
	 * @return string The admin page URL
	 */
	public static function getPageUrl(): string {
		return admin_url( 'tools.php?page=' . self::MENU_SLUG );
	}
}
