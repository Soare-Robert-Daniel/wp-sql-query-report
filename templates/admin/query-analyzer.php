<?php
/**
 * SQL Analyzer Admin Page Template
 *
 * Displays the SQL Analyzer admin interface with query input,
 * results display, and copy-to-clipboard functionality.
 *
 * @package Robert\SqlAnalyzer
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

// Check user capability
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'sql-analyzer' ) );
}
?>

<!-- React Dashboard Root -->
<div id="dashboard"></div>