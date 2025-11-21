=== SQL Analyzer ===
Contributors: wordpress-org
Donate link: https://example.com/
Tags: database, sql, debugging, query-analysis, ai
Requires at least: 6.7.0
Tested up to: 6.8.3
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate AI-friendly SQL query reports. Convert complex queries into structured data that AI tools can analyze, explain, and optimize.

== Description ==

SQL Analyzer is a WordPress plugin that converts SQL queries into structured, AI-friendly reports. Instead of trying to parse raw SQL, feed these well-formatted reports directly to AI tools (ChatGPT, Claude, Copilot, etc.) for instant analysis, explanations, and optimization suggestions.

= Why You Need SQL Analyzer =

Raw SQL queries are hard for AI models to work with. They're often complex, fragmented, and require context. SQL Analyzer solves this by generating clean, structured reports that include:

* Query breakdown and structure analysis
* Table relationships and data flow
* Performance characteristics and indexing information
* Query complexity metrics
* Formatted output ready for AI consumption

Simply copy the report into ChatGPT or your favorite AI tool and get professional-grade explanations and optimization advice instantly.

= Key Features =

* **Structured Query Reports** – Convert raw SQL into well-formatted, AI-readable reports
* **Metadata Extraction** – Automatic extraction of tables, joins, filters, and operations
* **Performance Metrics** – Include query complexity, estimated impact, and indexing analysis
* **Multiple Export Formats** – Generate reports in JSON, Markdown, or plain text for AI tools
* **Query History** – Store and manage previously analyzed queries
* **Developer-Friendly API** – RESTful endpoints for integration with custom tools
* **Zero Dependencies** – No external AI services or API keys required

= Perfect For =

* WordPress developers who use AI assistants for code review and optimization
* Database teams looking to leverage AI for performance analysis
* Learning SQL best practices with AI mentoring
* Quick query explanations without manual analysis
* Building AI-assisted development workflows

= How It Works =

1. Submit a SQL query to the plugin dashboard or API
2. Plugin analyzes the query and generates a structured report
3. Copy the report and paste it into ChatGPT, Claude, or any AI tool
4. AI tool provides analysis, optimization suggestions, or explanations
5. Implement improvements in your WordPress code

== Installation ==

1. Upload the `sql-analyzer` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **SQL Analyzer** to start generating reports
4. Submit queries via the dashboard or use the REST API endpoints
5. Export reports in your preferred format for AI analysis

No API keys or external services required.

== Frequently Asked Questions ==

= Do I need an API key? =

No! SQL Analyzer works completely independently. It generates structured reports locally without any external dependencies or API calls.

= Which AI tools work best with these reports? =

All AI tools work great with the structured reports: ChatGPT, Claude, GitHub Copilot, Google Gemini, LLaMA, and any other LLM. The reports are format-agnostic and designed for universal AI consumption.

= Can I use this with custom WordPress queries? =

Yes! SQL Analyzer works with any SQL query, including those targeting custom post types, taxonomies, meta tables, and third-party plugin queries.

= What formats can I export reports in? =

Reports can be generated in JSON (for programmatic use), Markdown (for documentation), and plain text (for quick AI prompting).

= Is my data private? =

Completely. All analysis happens locally on your WordPress installation. Queries are never sent anywhere—you control what you share with AI tools.

= What's the performance impact? =

Minimal. SQL Analyzer generates reports on-demand without interfering with site operations. Analysis runs quickly with no database overhead.

= Can I integrate this into my workflow? =

Yes! The REST API allows you to programmatically submit queries and retrieve reports, making it perfect for CI/CD pipelines, automated testing, and development tools.

= Will this replace my database admin? =

No, but it's a powerful assistant. The AI-friendly reports help developers and DBAs understand queries faster and make better optimization decisions.

== Screenshots ==

1. Query submission dashboard showing SQL input and report preview
2. Structured report output with metadata extraction and performance metrics
3. Multiple export formats (JSON, Markdown, Text) ready for AI tools
4. Query history view for managing and re-analyzing previous queries

== Changelog ==

= 0.1.0 =
* Initial release
* SQL query parsing and structural analysis
* Multiple export formats (JSON, Markdown, Text)
* Dashboard interface for query submission
* REST API endpoints for programmatic access
* Query history storage
* Performance metrics calculation

== Upgrade Notice ==

= 0.1.0 =
Initial release of SQL Analyzer. Start generating AI-friendly query reports today!
