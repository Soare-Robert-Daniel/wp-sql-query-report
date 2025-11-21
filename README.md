# SQL Analyzer

A WordPress plugin that analyzes SQL queries, explains their execution plans, and exports comprehensive reports for LLM-based optimization suggestions.

## Features

-   **SQL Query Analysis** – Execute EXPLAIN and EXPLAIN ANALYZE on SELECT queries
-   **Multi-Query Support** – Analyze multiple queries in a single session
-   **Database Introspection** – View table structures, column details, and indexes
-   **LLM Integration** – Export formatted reports for LLM chat applications (copy or download)
-   **Real-time Execution Metrics** – Get EXPLAIN ANALYZE results with actual execution data (MySQL 8.0.18+)
-   **React Dashboard** – Modern, responsive interface built with React and Tailwind CSS
-   **Security Focused** – WordPress nonce verification, user capability checks, SQL injection prevention

## Requirements

### Production

-   **WordPress:** 6.7+ (tested up to 6.8.3)
-   **PHP:** 7.4+
-   **Database:** MySQL 5.6+ or MariaDB (MySQL 8.0+ recommended for EXPLAIN ANALYZE support)

### Development

-   Node.js (for npm)
-   Composer (for PHP dependencies)
-   Git

## Installation

### For End Users

1. Download the plugin from GitHub or WordPress.org
2. Upload the `sql-analyzer` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin **Plugins** menu
4. Navigate to **Tools > SQL Analyzer** to get started

### For Development

1. Clone the repository:

    ```bash
    git clone <repository-url> sql-analyzer
    cd sql-analyzer
    ```

2. Install dependencies:

    ```bash
    npm install
    composer install
    ```

3. Build the React dashboard:

    ```bash
    npm run build
    ```

4. Upload to your local WordPress installation (same as end-user installation above)

5. Activate the plugin and navigate to **Tools > SQL Analyzer**

## Development

### Build Commands

-   **`npm run build`** – Build for production (minified, optimized)
-   **`npm run build:watch`** – Watch for changes and rebuild automatically
-   **`npm run dev:hot`** – Development server with hot reload
-   **`npm start`** – Run default tasks (i18n and readme generation)

### Code Quality

#### JavaScript/TypeScript

```bash
npm run lint
```

#### PHP

```bash
# Check code standards
composer lint

# Auto-fix code style issues
composer lint:fix

# Static analysis (PHPStan level 8)
composer phpstan
```

### Internationalization

Generate translation files (.pot):

```bash
npm run i18n
```

### Testing

```bash
# Run all PHPUnit tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit --filter TestName tests/
```

## Usage

### Accessing the Plugin

1. Go to **Tools > SQL Analyzer** in the WordPress admin
2. Requires administrator role (`manage_options` capability)

### Basic Workflow

1. **Enter SQL Query** – Paste a SELECT query in the query input field
2. **Add Label** (optional) – Give your query a descriptive name
3. **Analyze** – Click the "Analyze" button
4. **View Results** – See the execution plan, table structure, and indexes
5. **Export for LLM** – Switch to the "LLM Export" tab and copy or download the report

### Multiple Queries

You can analyze multiple queries in one session:

1. Click "Add Query" to add more input fields
2. Fill in each query with an optional label
3. Click "Analyze" to run all queries at once
4. Each query appears in the report with its own analysis

## REST API

### Endpoint

**URL:** `/wp-json/sql-analyzer/v1/analyze`
**Method:** POST
**Authentication:** WordPress REST nonce + `manage_options` capability

### Request Example

```json
{
	"queries": [
		{
			"id": "query-1",
			"label": "Get Recent Posts",
			"query": "SELECT * FROM wp_posts WHERE post_status = 'publish' ORDER BY post_date DESC LIMIT 10"
		}
	],
	"include_analyze": false
}
```

### Response Example

```json
{
  "success": true,
  "message": "Analyzed 1 queries successfully.",
  "queries": [
    {
      "id": "query-1",
      "label": "Get Recent Posts",
      "query": "SELECT * FROM wp_posts WHERE post_status = 'publish' ORDER BY post_date DESC LIMIT 10",
      "execution_time": 0.025,
      "explain": "...",
      "explain_tree": "...",
      "tables": [...],
      "warnings": []
    }
  ],
  "summary": {
    "total_queries": 1,
    "total_execution_time": 0.025,
    "total_cost": 1500,
    "slowest_query_index": 0,
    "has_warnings": false
  },
  "complete_output": "Formatted text report for LLM..."
}
```

## Project Structure

```
sql-analyzer/
├── sql-analyzer.php                    # Main plugin file (PHP logic)
├── templates/
│   └── admin/query-analyzer.php       # Admin page template
├── src/
│   └── dashboard/
│       ├── index.tsx                  # React app entry point
│       ├── index.css                  # Tailwind CSS imports
│       ├── types/
│       │   └── index.ts               # TypeScript definitions
│       └── components/
│           ├── Alert.tsx              # Alert notifications
│           ├── AnalysisReport.tsx     # Main report component
│           ├── CopyButton.tsx         # Copy-to-clipboard button
│           ├── DownloadButton.tsx     # Download report button
│           ├── QueryCard.tsx          # Individual query result card
│           ├── QueryForm.tsx          # Multi-query input form
│           └── ...other components
├── build/                             # Compiled assets (generated)
├── tests/                             # PHPUnit tests
├── package.json                       # NPM configuration
├── composer.json                      # Composer configuration
├── webpack.config.js                  # Webpack build config
├── postcss.config.js                  # PostCSS/Tailwind config
├── .phpcs.xml.dist                    # PHP CodeSniffer rules
├── phpstan.neon                       # PHPStan configuration
└── .oxlintrc.json                     # Oxlint configuration
```

## Contributing

We welcome contributions! Please follow these guidelines:

### Code Style

**PHP:**

-   Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
-   Prefix all functions with `sql_analyzer_`
-   Use type hints on all functions
-   Run `composer lint:fix` before committing
-   Pass PHPStan level 8 checks: `composer phpstan`

**JavaScript/TypeScript:**

-   Follow [@wordpress/eslint-plugin](https://github.com/WordPress/gutenberg/tree/trunk/packages/eslint-plugin) rules
-   Run `npm run lint` before committing
-   Use functional React components with hooks
-   Use WordPress packages when available (@wordpress/element, @wordpress/i18n, etc.)

**CSS:**

-   Use Tailwind CSS utility classes
-   Avoid custom CSS unless absolutely necessary
-   Maintain responsive design

### Git Workflow

1. Create a new branch for your feature or fix:

    ```bash
    git checkout -b feature/your-feature-name
    ```

2. Make your changes and commit with clear messages:

    ```bash
    git commit -m "Add feature description"
    ```

3. Run linters and tests before pushing:

    ```bash
    npm run lint
    composer lint:fix
    composer phpstan
    ./vendor/bin/phpunit
    ```

4. Push your branch and create a Pull Request with a clear description

### Pull Request Checklist

-   [ ] Code follows project style guidelines
-   [ ] All linters pass (`npm run lint`, `composer lint:fix`)
-   [ ] Static analysis passes (`composer phpstan`)
-   [ ] Tests pass (`./vendor/bin/phpunit`)
-   [ ] Changes are properly documented (PHPDoc for PHP, JSDoc for complex functions)
-   [ ] PR description explains the change and why it's needed

## Troubleshooting

### "Query contains invalid SQL" error

-   Only SELECT queries are supported
-   Destructive queries (INSERT, UPDATE, DELETE, DROP) are blocked for safety
-   Ensure your query syntax is valid

### "Database type not supported" error

-   The plugin supports MySQL 5.6+ and MariaDB
-   Ensure your WordPress database is properly configured

### EXPLAIN ANALYZE not available

-   EXPLAIN ANALYZE requires MySQL 8.0.18 or later
-   Check the "Include EXPLAIN ANALYZE" option availability on your server

### Assets not loading (build folder missing)

-   Run `npm run build` to compile the React dashboard
-   Ensure the `build/` directory exists and contains compiled files

## License

MIT

## Support

For issues, questions, or suggestions:

-   Open an issue on GitHub
-   Check existing documentation in AGENTS.md

## Changelog

See [CHANGELOG.md](CHANGELOG.md) (if applicable) or Git commit history for changes.
