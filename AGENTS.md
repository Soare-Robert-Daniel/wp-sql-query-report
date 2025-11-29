# SQL Analyzer - Agent Guide

## Commands

```bash
# Frontend
npm run build          # Production build
npm run build:watch    # Dev watch mode
npm run lint           # Lint JS/TS

# PHP
composer phpstan       # Static analysis (level 8)
composer lint          # Check WPCS
composer lint:fix      # Auto-fix
```

## Structure

-   **Main file:** `simple-sql-query-analyzer.php` (all PHP logic)
-   **Frontend:** `src/dashboard/` (React + Tailwind)
-   **Build output:** `build/dashboard.{js,css,asset.php}`
-   **REST endpoint:** `POST /wp-json/simple-sql-query-analyzer/v1/analyze` (requires `manage_options`)

## Code Rules

**PHP:** WordPress Coding Standards, namespace `Robert\SqlAnalyzer`, prefix `sql_analyzer_`, always use `$wpdb->prepare()`

**JS:** @wordpress packages, Tailwind CSS v4, oxlint for linting

**Security:** No destructive SQL allowed (INSERT/UPDATE/DELETE/DROP/ALTER/CREATE blocked)
