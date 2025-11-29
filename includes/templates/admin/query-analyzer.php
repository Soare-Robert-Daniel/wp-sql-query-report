<?php
/**
 * SQL Analyzer Admin Page Template.
 *
 * @package simple-sql-query-analyzer/templates/admin
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

// Check user capability.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'simple-sql-query-analyzer' ) );
}
?>

<!-- React Dashboard Root -->
<div id="dashboard"></div>