/**
 * SQL Analyzer Admin JavaScript
 *
 * Modern ES6+ JavaScript for the SQL Analyzer admin interface.
 * Handles form submission, AJAX requests, and result display.
 *
 * @package Robert\SqlAnalyzer
 * @author  Soare Robert-Daniel <soare.robert.daniel@protonmail.com>
 * @license GPL-2.0-or-later
 * @since   0.1.0
 */

/**
 * SQL Analyzer Main Class
 *
 * Manages all interactions for the SQL Analyzer admin page.
 * Uses modern JavaScript with async/await and fetch API.
 *
 * @class
 */
class SQLAnalyzer {
    /**
     * Constructor
     *
     * Initializes the SQL Analyzer instance and sets up event listeners.
     *
     * @constructor
     */
    constructor() {
        // Store references to DOM elements
        this.form = document.getElementById('sql-analyzer-form');
        this.queryInput = document.getElementById('sql-analyzer-query-input');
        this.analyzeBtn = document.getElementById('sql-analyzer-submit-btn');
        this.resultsSection = document.getElementById('sql-analyzer-results');
        this.errorAlert = document.getElementById('sql-analyzer-error-alert');
        this.errorMessage = document.getElementById('sql-analyzer-error-message');
        this.successAlert = document.getElementById('sql-analyzer-success-alert');
        this.clipboardFeedback = document.getElementById('sql-analyzer-clipboard-feedback');
        this.clipboardMessage = document.getElementById('sql-analyzer-clipboard-message');

        // Initialize event listeners
        this.initializeEventListeners();
    }

    /**
     * Initialize all event listeners
     *
     * Sets up form submission, copy buttons, and alert close buttons.
     *
     * @private
     * @return {void}
     */
    initializeEventListeners() {
        // Form submission handler
        if (this.form) {
            this.form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.handleFormSubmit();
            });
        }

        // Copy buttons click handler
        document.addEventListener('click', (event) => {
            const copyBtn = event.target.closest('.sql-analyzer-copy-btn');
            if (copyBtn) {
                this.handleCopyClick(copyBtn);
            }

            // Alert close buttons
            const closeBtn = event.target.closest('.sql-analyzer-alert-close');
            if (closeBtn) {
                closeBtn.closest('.sql-analyzer-alert').classList.add('sql-analyzer-hidden');
            }
        });
    }

    /**
     * Handle form submission
     *
     * Validates input, shows loading state, sends AJAX request,
     * and handles the response.
     *
     * @private
     * @async
     * @return {Promise<void>}
     */
    async handleFormSubmit() {
        // Get query from textarea
        const query = this.queryInput.value.trim();

        // Validate query input
        if (!query) {
            this.showError(sqlAnalyzerData.i18n.invalidQuery);
            return;
        }

        // Show loading state
        this.setLoadingState(true);
        this.hideResults();
        this.hideError();

        try {
            // Send query to server for analysis
            const response = await this.analyzeQuery(query);

            // Handle response
            if (response.success) {
                this.displayResults(response.data);
                this.showSuccess();
            } else {
                this.showError(response.message || sqlAnalyzerData.i18n.serverError);
            }
        } catch (error) {
            // Display error message
            console.error('Analysis error:', error);
            this.showError(error.message || sqlAnalyzerData.i18n.serverError);
        } finally {
            // Reset loading state
            this.setLoadingState(false);
        }
    }

    /**
     * Analyze query via REST API
     *
     * Sends the SQL query to the REST API endpoint for analysis.
     *
     * @private
     * @async
     * @param {string} query - The SQL query to analyze
     * @return {Promise<Object>} The API response data
     */
    async analyzeQuery(query) {
        // Get the include_analyze checkbox value
        const includeAnalyze = document.getElementById('sql-analyzer-include-analyze').checked;

        // Prepare request payload
        const payload = {
            query: query,
            include_analyze: includeAnalyze,
        };

        // Send fetch request to REST API
        const response = await fetch(sqlAnalyzerData.analyzeEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': sqlAnalyzerData.restNonce,
            },
            body: JSON.stringify(payload),
        });

        // Check for HTTP errors
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP Error: ${response.status}`);
        }

        // Parse and return JSON response
        return response.json();
    }

    /**
     * Display analysis results
     *
     * Displays the complete SQL Query Analysis Report containing
     * execution plan, database structures, and index information.
     *
     * @private
     * @param {Object} data - The analysis results data
     * @return {void}
     */
    displayResults(data) {
        // Display complete formatted output (contains all information needed)
        if (data.complete_output) {
            const completeOutput = document.getElementById('sql-analyzer-complete-output');
            if (completeOutput) {
                // Use textContent for security (prevents XSS) and preserves formatting
                completeOutput.textContent = data.complete_output;
            }
        }

        // Show the results section
        this.showResults();
    }


    /**
     * Handle copy button click
     *
     * Copies the content of the specified element to clipboard
     * and shows feedback to the user.
     *
     * @private
     * @async
     * @param {HTMLElement} button - The copy button element
     * @return {Promise<void>}
     */
    async handleCopyClick(button) {
        // Get the target element ID from data attribute
        const targetId = button.getAttribute('data-copy-target');
        const targetElement = document.getElementById(targetId);

        if (!targetElement) {
            console.warn(`Target element not found: ${targetId}`);
            return;
        }

        try {
            // Get text content to copy
            const textToCopy = targetElement.textContent || targetElement.innerText;

            // Copy to clipboard using modern API
            await navigator.clipboard.writeText(textToCopy);

            // Show success feedback
            this.showCopyFeedback(sqlAnalyzerData.i18n.copied, button);
        } catch (error) {
            // Fallback for older browsers or if clipboard API fails
            console.error('Clipboard error:', error);
            this.showCopyFeedback(sqlAnalyzerData.i18n.copyFailed, button);
        }
    }

    /**
     * Show copy feedback
     *
     * Displays visual feedback when content is copied to clipboard.
     *
     * @private
     * @param {string} message - The message to display
     * @param {HTMLElement} button - The copy button element
     * @return {void}
     */
    showCopyFeedback(message, button) {
        // Add success class to button
        button.classList.add('is-copied');
        button.disabled = true;

        // Update feedback message
        this.clipboardMessage.textContent = message;
        this.clipboardFeedback.classList.add('is-visible');

        // Remove feedback after 2 seconds
        setTimeout(() => {
            button.classList.remove('is-copied');
            button.disabled = false;
            this.clipboardFeedback.classList.remove('is-visible');
        }, 2000);
    }

    /**
     * Set loading state
     *
     * Updates the submit button to show loading animation
     * and disables it during processing.
     *
     * @private
     * @param {boolean} isLoading - Whether loading is in progress
     * @return {void}
     */
    setLoadingState(isLoading) {
        if (isLoading) {
            this.analyzeBtn.classList.add('is-loading');
            this.analyzeBtn.disabled = true;
        } else {
            this.analyzeBtn.classList.remove('is-loading');
            this.analyzeBtn.disabled = false;
        }
    }

    /**
     * Show results section
     *
     * Displays the results section with fade animation.
     *
     * @private
     * @return {void}
     */
    showResults() {
        this.resultsSection.classList.remove('sql-analyzer-hidden');

        // Scroll to results with smooth behavior
        this.resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /**
     * Hide results section
     *
     * Hides the results section.
     *
     * @private
     * @return {void}
     */
    hideResults() {
        this.resultsSection.classList.add('sql-analyzer-hidden');
    }

    /**
     * Show error alert
     *
     * Displays an error message to the user.
     *
     * @private
     * @param {string} message - The error message to display
     * @return {void}
     */
    showError(message) {
        this.errorMessage.textContent = message;
        this.errorAlert.classList.remove('sql-analyzer-hidden');
    }

    /**
     * Hide error alert
     *
     * Hides the error alert message.
     *
     * @private
     * @return {void}
     */
    hideError() {
        this.errorAlert.classList.add('sql-analyzer-hidden');
    }

    /**
     * Show success alert
     *
     * Displays a success message to the user.
     *
     * @private
     * @return {void}
     */
    showSuccess() {
        this.successAlert.classList.remove('sql-analyzer-hidden');

        // Auto-hide success alert after 4 seconds
        setTimeout(() => {
            this.successAlert.classList.add('sql-analyzer-hidden');
        }, 4000);
    }

    /**
     * Escape HTML special characters
     *
     * Prevents XSS attacks by escaping HTML characters.
     *
     * @private
     * @param {string} text - The text to escape
     * @return {string} Escaped text safe for HTML display
     */
    escapeHtml(text) {
        if (!text) return '';

        // Create a temporary div to use browser's HTML escaping
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/**
 * Initialize SQL Analyzer when DOM is ready
 *
 * Creates a new SQLAnalyzer instance after the DOM has fully loaded.
 * Uses DOMContentLoaded event for modern browsers.
 *
 * @return {void}
 */
document.addEventListener('DOMContentLoaded', () => {
    // Create new instance of SQLAnalyzer
    window.sqlAnalyzerInstance = new SQLAnalyzer();

    // Log initialization to console in development
    if (window.location.search.includes('debug')) {
        console.log('SQL Analyzer initialized', sqlAnalyzerData);
    }
});
