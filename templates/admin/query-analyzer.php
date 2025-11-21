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

<!-- Main SQL Analyzer Admin Page Container -->
<div class="wrap sql-analyzer-wrap">
	<!-- Page Header -->
	<div class="sql-analyzer-header">
		<!-- Page Title and Icon -->
		<h1 class="sql-analyzer-title">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'SQL Analyzer', 'sql-analyzer' ); ?>
		</h1>
		<!-- Description -->
		<p class="sql-analyzer-description">
			<?php esc_html_e( 'Analyze SQL queries, view execution plans, and export database structures for LLM integration.', 'sql-analyzer' ); ?>
		</p>
	</div>

	<!-- Main Content Wrapper -->
	<div class="sql-analyzer-content">

		<!-- Query Input Section -->
		<section class="sql-analyzer-section sql-analyzer-input-section">
			<!-- Section Header -->
			<div class="sql-analyzer-section-header">
				<h2 class="sql-analyzer-section-title">
					<?php esc_html_e( 'Query Input', 'sql-analyzer' ); ?>
				</h2>
				<p class="sql-analyzer-section-description">
					<?php esc_html_e( 'Paste your SQL SELECT query below to analyze it.', 'sql-analyzer' ); ?>
				</p>
			</div>

			<!-- Query Input Form -->
			<form id="sql-analyzer-form" class="sql-analyzer-form" method="post">
				<!-- Nonce for security verification -->
				<?php wp_nonce_field( 'sql_analyzer_nonce', 'sql_analyzer_nonce_field' ); ?>

				<!-- Query Textarea Container -->
				<div class="sql-analyzer-form-group">
					<!-- Label for textarea -->
					<label for="sql-analyzer-query-input" class="sql-analyzer-label">
						<?php esc_html_e( 'SQL Query:', 'sql-analyzer' ); ?>
					</label>
					<!-- Textarea for SQL query input -->
					<textarea
						id="sql-analyzer-query-input"
						class="sql-analyzer-query-input"
						name="query"
						rows="8"
						placeholder="<?php esc_attr_e( 'SELECT * FROM wp_users WHERE ID = 1', 'sql-analyzer' ); ?>"
						aria-label="<?php esc_attr_e( 'Enter your SQL query', 'sql-analyzer' ); ?>"
						required
					></textarea>
				</div>

				<!-- Query Options Container -->
				<div class="sql-analyzer-form-group sql-analyzer-options">
					<!-- Include ANALYZE checkbox -->
					<label class="sql-analyzer-checkbox-label">
						<input
							type="checkbox"
							id="sql-analyzer-include-analyze"
							name="include_analyze"
							value="1"
							aria-label="<?php esc_attr_e( 'Include ANALYZE in query analysis', 'sql-analyzer' ); ?>"
						/>
						<span><?php esc_html_e( 'Include ANALYZE (shows query execution statistics)', 'sql-analyzer' ); ?></span>
					</label>
				</div>

				<!-- Submit Button Container -->
				<div class="sql-analyzer-form-actions">
					<!-- Submit Button -->
					<button
						type="submit"
						id="sql-analyzer-submit-btn"
						class="button button-primary sql-analyzer-submit-btn"
						aria-label="<?php esc_attr_e( 'Analyze the SQL query', 'sql-analyzer' ); ?>"
					>
						<span class="sql-analyzer-btn-text"><?php esc_html_e( 'Analyze Query', 'sql-analyzer' ); ?></span>
						<span class="sql-analyzer-btn-spinner" aria-hidden="true"></span>
					</button>

					<!-- Clear Button -->
					<button
						type="reset"
						class="button sql-analyzer-clear-btn"
						aria-label="<?php esc_attr_e( 'Clear the query input', 'sql-analyzer' ); ?>"
					>
						<?php esc_html_e( 'Clear', 'sql-analyzer' ); ?>
					</button>
				</div>
			</form>
		</section>

		<!-- Results Section (initially hidden, shown when analysis completes) -->
		<section id="sql-analyzer-results" class="sql-analyzer-section sql-analyzer-results-section sql-analyzer-hidden">

			<!-- Error Alert Container (hidden by default) -->
			<div id="sql-analyzer-error-alert" class="sql-analyzer-alert sql-analyzer-alert-error sql-analyzer-hidden" role="alert">
				<!-- Error Icon -->
				<span class="sql-analyzer-alert-icon dashicons dashicons-warning"></span>
				<!-- Error Message -->
				<div class="sql-analyzer-alert-content">
					<strong><?php esc_html_e( 'Error:', 'sql-analyzer' ); ?></strong>
					<p id="sql-analyzer-error-message"></p>
				</div>
				<!-- Close Button -->
				<button class="sql-analyzer-alert-close" aria-label="<?php esc_attr_e( 'Close error alert', 'sql-analyzer' ); ?>">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>

			<!-- Success Alert Container -->
			<div id="sql-analyzer-success-alert" class="sql-analyzer-alert sql-analyzer-alert-success" role="status">
				<!-- Success Icon -->
				<span class="sql-analyzer-alert-icon dashicons dashicons-yes-alt"></span>
				<!-- Success Message -->
				<span class="sql-analyzer-alert-text"><?php esc_html_e( 'Query analyzed successfully!', 'sql-analyzer' ); ?></span>
				<!-- Close Button -->
				<button class="sql-analyzer-alert-close" aria-label="<?php esc_attr_e( 'Close success alert', 'sql-analyzer' ); ?>">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>

			<!-- SQL Query Analysis Report (Complete) -->
			<div class="sql-analyzer-result-card sql-analyzer-llm-card">
				<!-- Card Header -->
				<div class="sql-analyzer-result-header">
					<h3 class="sql-analyzer-result-title">
						<span class="dashicons dashicons-format-quote"></span>
						<?php esc_html_e( 'SQL Query Analysis Report', 'sql-analyzer' ); ?>
					</h3>
					<!-- Copy Button -->
					<button
						class="sql-analyzer-copy-btn sql-analyzer-copy-all-btn"
						data-copy-target="sql-analyzer-complete-output"
						aria-label="<?php esc_attr_e( 'Copy analysis report to clipboard', 'sql-analyzer' ); ?>"
					>
						<span class="dashicons dashicons-admin-page"></span>
						<?php esc_html_e( 'Copy', 'sql-analyzer' ); ?>
					</button>
				</div>

				<!-- Card Body - Complete Analysis Output -->
				<div class="sql-analyzer-result-body">
					<!-- Container for complete formatted output including execution plan, database structures, and index information (populated by JavaScript) -->
					<pre id="sql-analyzer-complete-output" class="sql-analyzer-code-block sql-analyzer-llm-output"></pre>
				</div>

				<!-- LLM Instructions -->
				<div class="sql-analyzer-llm-instructions">
					<p class="sql-analyzer-llm-note">
						<?php esc_html_e( '💡 Tip: Copy the complete report above and paste it into your LLM chat for comprehensive query analysis and optimization suggestions.', 'sql-analyzer' ); ?>
					</p>
				</div>
			</div>

		</section>

	</div>

</div>

<!-- Feedback notification for clipboard actions (hidden by default) -->
<div id="sql-analyzer-clipboard-feedback" class="sql-analyzer-toast" role="status" aria-live="polite">
	<span id="sql-analyzer-clipboard-message"></span>
</div>
