# SQL Analyzer Plugin - Agent Guidelines

## Build & Development Commands

### JavaScript/Frontend (React Dashboard)

-   `npm run build` - Build React dashboard for production
-   `npm run build:watch` - Watch mode for development
-   `npm run dev:hot` - Hot reload development server
-   `npm run lint` - Lint JavaScript/TypeScript with oxlint
-   `npm start` - Run Grunt tasks (i18n, readme generation)

### PHP Backend

-   `composer phpstan` - Run static analysis (level 8)
-   `composer lint` - Check PHP code standards (WPCS)
-   `composer lint:fix` - Auto-fix code style issues
-   Single test: `./vendor/bin/phpunit --filter TestName tests/`

## Architecture & Structure

**Type:** WordPress admin plugin with React frontend
**Root:** `sql-analyzer.php` (main plugin file, ~970 lines)
**Frontend:** `src/dashboard/` (React components built with @wordpress packages)
**Backend:** No separate files (logic in main plugin)
**Database:** Uses WordPress `$wpdb` global, no custom tables
**Build Output:** `build/dashboard.{js,css,asset.php}`

REST endpoint: `/wp-json/sql-analyzer/v1/analyze` (POST, requires `manage_options`)

## Code Style Guidelines

**PHP:**

-   WordPress Coding Standards with PSR-4 namespace `Robert\SqlAnalyzer`
-   Prefix: `sql_analyzer_` for functions/globals
-   Text domain: `sql-analyzer` (internationalization)
-   No destructive SQL (INSERT/UPDATE/DELETE/DROP/ALTER/CREATE blocked)
-   Use `$wpdb->prepare()` for parameterized queries; escaped table identifiers with backticks + `sanitize_key()`

**JavaScript:**

-   @wordpress/eslint-plugin rules
-   Oxlint with plugins: react, react-perf, typescript, unicorn, import, jsdoc
-   Dependencies: @wordpress packages (api-fetch, element, react-i18n, url, icons, etc.)
-   Tailwind CSS v4 with PostCSS

**General:**

-   WordPress minimum: 6.7 (PHP 7.4+)
-   Type hints on PHP functions (return type `: void`, `: array`, etc.)
-   Error handling: Exceptions thrown, caught at endpoint level
-   Security: Nonce verification (`wp_rest`), capability checks (`manage_options`)
