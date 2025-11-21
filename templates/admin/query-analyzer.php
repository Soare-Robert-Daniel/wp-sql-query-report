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

<!--
	LEGACY PHP TEMPLATE - ARCHIVED FOR REFERENCE
	This template has been migrated to React. The old HTML structure is preserved below for reference
	and can be restored by uncommenting the legacy assets in sql-analyzer.php if needed.

	COMMENTED OUT LEGACY HTML:

	<?php
	/*
	<div class="wrap sql-analyzer-wrap">
		<div class="sql-analyzer-header">
			<h1 class="sql-analyzer-title">
				<span class="dashicons dashicons-database"></span>
				SQL Analyzer
			</h1>
			<p class="sql-analyzer-description">
				Analyze SQL queries, view execution plans, and export database structures for LLM integration.
			</p>
		</div>

		<div class="sql-analyzer-content">
			<section class="sql-analyzer-section sql-analyzer-input-section">
				<div class="sql-analyzer-section-header">
					<h2 class="sql-analyzer-section-title">Query Input</h2>
					<p class="sql-analyzer-section-description">Paste your SQL SELECT query below to analyze it.</p>
				</div>

				<form id="sql-analyzer-form" class="sql-analyzer-form" method="post">
					<div class="sql-analyzer-form-group">
						<label for="sql-analyzer-query-input" class="sql-analyzer-label">SQL Query:</label>
						<textarea
							id="sql-analyzer-query-input"
							class="sql-analyzer-query-input"
							name="query"
							rows="8"
							placeholder="SELECT * FROM wp_users WHERE ID = 1"
							required
						></textarea>
					</div>

					<div class="sql-analyzer-form-group sql-analyzer-options">
						<label class="sql-analyzer-checkbox-label">
							<input type="checkbox" id="sql-analyzer-include-analyze" name="include_analyze" value="1" />
							<span>Include ANALYZE (shows query execution statistics)</span>
						</label>
					</div>

					<div class="sql-analyzer-form-actions">
						<button type="submit" id="sql-analyzer-submit-btn" class="button button-primary sql-analyzer-submit-btn">
							<span class="sql-analyzer-btn-text">Analyze Query</span>
							<span class="sql-analyzer-btn-spinner" aria-hidden="true"></span>
						</button>
						<button type="reset" class="button sql-analyzer-clear-btn">Clear</button>
					</div>
				</form>
			</section>

			<section id="sql-analyzer-results" class="sql-analyzer-section sql-analyzer-results-section sql-analyzer-hidden">
				<div id="sql-analyzer-error-alert" class="sql-analyzer-alert sql-analyzer-alert-error sql-analyzer-hidden" role="alert">
					<span class="sql-analyzer-alert-icon dashicons dashicons-warning"></span>
					<div class="sql-analyzer-alert-content">
						<strong>Error:</strong>
						<p id="sql-analyzer-error-message"></p>
					</div>
					<button class="sql-analyzer-alert-close" aria-label="Close error alert">
						<span class="dashicons dashicons-no"></span>
					</button>
				</div>

				<div id="sql-analyzer-success-alert" class="sql-analyzer-alert sql-analyzer-alert-success" role="status">
					<span class="sql-analyzer-alert-icon dashicons dashicons-yes-alt"></span>
					<span class="sql-analyzer-alert-text">Query analyzed successfully!</span>
					<button class="sql-analyzer-alert-close" aria-label="Close success alert">
						<span class="dashicons dashicons-no"></span>
					</button>
				</div>

				<div class="sql-analyzer-result-card sql-analyzer-llm-card">
					<div class="sql-analyzer-result-header">
						<h3 class="sql-analyzer-result-title">
							<span class="dashicons dashicons-format-quote"></span>
							SQL Query Analysis Report
						</h3>
						<button class="sql-analyzer-copy-btn sql-analyzer-copy-all-btn" data-copy-target="sql-analyzer-complete-output">
							<span class="dashicons dashicons-admin-page"></span>
							Copy
						</button>
					</div>
					<div class="sql-analyzer-result-body">
						<pre id="sql-analyzer-complete-output" class="sql-analyzer-code-block sql-analyzer-llm-output"></pre>
					</div>
					<div class="sql-analyzer-llm-instructions">
						<p class="sql-analyzer-llm-note">
							💡 Tip: Copy the complete report above and paste it into your LLM chat for comprehensive query analysis and optimization suggestions.
						</p>
					</div>
				</div>
			</section>
		</div>
	</div>

	<div id="sql-analyzer-clipboard-feedback" class="sql-analyzer-toast" role="status" aria-live="polite">
		<span id="sql-analyzer-clipboard-message"></span>
	</div>
	*/
	?>
-->
