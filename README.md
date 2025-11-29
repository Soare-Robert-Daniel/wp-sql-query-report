# SQL Analyzer

WordPress plugin to analyze SQL queries with EXPLAIN plans and export reports for LLM optimization.

## Requirements

-   WordPress 6.7+, PHP 7.4+, MySQL 5.6+ (MySQL 8.0+ for EXPLAIN ANALYZE)

## Install

1. Upload `simple-sql-query-analyzer` folder to `/wp-content/plugins/`
2. Activate in WordPress admin
3. Go to **Tools > SQL Analyzer**

## Development Setup

```bash
npm install && composer install
npm run build
```

## Commands

```bash
npm run build          # Production build
npm run build:watch    # Dev watch mode
npm run lint           # Lint JS/TS

composer lint          # Check PHP standards
composer lint:fix      # Auto-fix PHP
composer phpstan       # Static analysis
```

## Usage

1. Enter a SELECT query
2. Click "Analyze"
3. View EXPLAIN results and table structure
4. Export to LLM via copy/download

## REST API

```
POST /wp-json/simple-sql-query-analyzer/v1/analyze
```

```json
{
	"queries": [{ "id": "1", "label": "My Query", "query": "SELECT ..." }],
	"include_analyze": false
}
```

Requires: WordPress REST nonce + `manage_options` capability

## Troubleshooting

| Problem                      | Solution                    |
| ---------------------------- | --------------------------- |
| "Query contains invalid SQL" | Only SELECT queries allowed |
| EXPLAIN ANALYZE unavailable  | Requires MySQL 8.0.18+      |
| Assets not loading           | Run `npm run build`         |
