# SQL Analyzer - Quick Start Guide

## Installation

1. **Place plugin in WordPress**
   ```
   /wp-content/plugins/sql-analyzer/
   ```

2. **Install dependencies** (Composer required)
   ```bash
   cd /path/to/sql-analyzer
   composer install
   npm install
   ```

3. **Activate plugin**
   - Go to WordPress admin > Plugins
   - Find "SQL Analyzer"
   - Click "Activate"

4. **Access the plugin**
   - Go to WordPress admin > Tools > SQL Analyzer
   - You should see a query input form

## Basic Usage

### Analyze a Query

1. **Paste your SQL query** into the textarea
   ```sql
   SELECT * FROM wp_users WHERE ID = 1
   ```

2. **Optional: Enable ANALYZE**
   - Check "Include ANALYZE" for execution statistics
   - Warning: This actually runs the query on your database

3. **Click "Analyze Query"**

4. **Review Results**
   - **Execution Plan**: Shows MySQL execution plan (EXPLAIN output)
   - **Database Structures**: Shows table schemas, columns, types
   - **Index Information**: Shows all indexes on involved tables
   - **Complete Analysis for LLM**: Full formatted output for AI assistants

5. **Copy to Clipboard**
   - Click "Copy" button on any section
   - Click "Copy All" to copy complete analysis
   - Paste into your LLM chat application

## Features

### What It Analyzes
- ✅ Query execution plan (EXPLAIN)
- ✅ Query execution statistics (ANALYZE - optional)
- ✅ Table structures (columns, types, constraints)
- ✅ Index information (names, types, columns)
- ✅ Performance insights (warnings about full table scans, etc.)

### What It Shows
- Execution plan details for optimization
- Column names, data types, and constraints
- Primary keys and unique indexes
- Recommendations for missing indexes

### Copy Formats
- **Execution Plan**: Just the EXPLAIN output
- **Database Structures**: Just the schema info
- **Index Information**: Just the indexes
- **Copy All**: Everything combined with professional formatting

## Examples

### Example 1: Simple SELECT
```sql
SELECT * FROM wp_users WHERE user_login = 'admin'
```
Results will show:
- How MySQL executes this query
- wp_users table structure
- Available indexes on wp_users

### Example 2: JOIN Query
```sql
SELECT p.ID, p.post_title, COUNT(c.comment_ID) as comments
FROM wp_posts p
LEFT JOIN wp_comments c ON p.ID = c.comment_post_ID
WHERE p.post_type = 'post'
GROUP BY p.ID
ORDER BY comments DESC
LIMIT 10
```
Results will show:
- Execution plan for the JOIN
- Structures of wp_posts and wp_comments
- Indexes used for the query

### Example 3: Performance Analysis
```sql
SELECT * FROM wp_postmeta WHERE meta_value LIKE '%search-term%'
```
Enable ANALYZE to see:
- Actual query execution statistics
- How many rows were examined
- Performance insights suggesting indexes

## API Usage

### REST API Endpoint

**URL**: `/wp-json/sql-analyzer/v1/analyze`

**Method**: POST

**Headers**:
```
X-WP-Nonce: <wordpress_nonce>
Content-Type: application/json
```

**Request Body**:
```json
{
  "query": "SELECT * FROM wp_users LIMIT 1",
  "include_analyze": false
}
```

**Response**:
```json
{
  "success": true,
  "message": "Query analyzed successfully.",
  "data": {
    "query": "SELECT * FROM wp_users LIMIT 1",
    "tables": [
      {
        "name": "wp_users",
        "columns": [
          {
            "name": "ID",
            "type": "BIGINT",
            "null": false,
            "key": "PRI"
          },
          ...
        ],
        "metadata": {
          "engine": "InnoDB",
          "row_format": "Dynamic",
          "table_rows": 1,
          ...
        }
      }
    ],
    "indexes": {
      "wp_users": [
        {
          "name": "PRIMARY",
          "type": "BTREE",
          "unique": true,
          "columns": [...]
        },
        ...
      ]
    },
    "explain": [
      {
        "id": 1,
        "select_type": "SIMPLE",
        "table": "wp_users",
        "type": "ALL",
        "rows": 1,
        ...
      }
    ],
    "complete_output": "═══════════════════════════════════════════\nSQL QUERY ANALYSIS REPORT\n..."
  }
}
```

## Security

### Allowed Queries
- SELECT queries only
- Complex JOINs
- Subqueries
- Aggregations (COUNT, SUM, etc.)

### Blocked Queries
- ❌ INSERT, UPDATE, DELETE
- ❌ DROP, ALTER, TRUNCATE
- ❌ CREATE, GRANT, REVOKE
- ❌ Dangerous functions (EXEC, LOAD_FILE, INTO OUTFILE)

### Access Control
- Admin-only (requires `manage_options` capability)
- Nonce verification on all requests
- Query validation before execution
- Audit logging of all analyses

## Troubleshooting

### Error: "You do not have permission"
- Only administrators can use this plugin
- Make sure you're logged in as admin
- Check your user role in WordPress

### Error: "Query is not safe for analysis"
- Only SELECT queries are allowed
- No INSERT, UPDATE, DELETE, DROP, etc.
- No UNION-based injections
- Use a simple SELECT query

### Error: "Query validation failed"
- Query exceeds 50KB length
- Contains suspicious patterns
- Missing required clauses
- Shorten your query or simplify it

### Query appears slow
- Check "Execution Plan" for full table scans
- Use "Index Information" to see available indexes
- Add missing indexes suggested by the analyzer
- Enable ANALYZE to see actual row counts

### No results showing
- Make sure query is valid SQL
- Use WordPress table names (wp_users, wp_posts, etc.)
- Check browser console for JavaScript errors
- Verify REST API is working

## Performance Tips

1. **Use LIMIT**
   - Add LIMIT clause for large results
   - ANALYZE will actually run the query

2. **Be specific**
   - Use WHERE clauses to filter
   - Add indexes for WHERE columns

3. **Check EXPLAIN output**
   - Look for "type: ALL" (full table scan)
   - Check "rows" column for row count estimates
   - Use indexes on filtered columns

4. **Monitor indexes**
   - Use "Index Information" to see what's available
   - Create indexes on frequently filtered columns
   - Remove unused indexes for better performance

## For LLM Integration

### Copying to LLM Chats

1. **Click "Copy All"** on the Complete Analysis section
2. **Paste into your LLM chat**
3. **Ask the LLM to**:
   - Optimize the query
   - Suggest indexes
   - Explain the execution plan
   - Improve query performance

### Example LLM Prompt
```
Here's my WordPress SQL query analysis.
Can you:
1. Explain what this query does
2. Identify any performance issues
3. Suggest optimizations
4. Recommend indexes

[Paste complete analysis]
```

### Why This Works
The Complete Analysis includes:
- Original query
- Execution plan details
- Table structures and column types
- All available indexes
- Performance insights
- Formatted for easy reading

LLMs can use this context to provide better optimization suggestions.

## Advanced Features

### Performance Insights
The analyzer automatically warns about:
- Full table scans (type: ALL)
- Large result sets (>10,000 rows)
- Filesort operations
- Temporary table usage

### Table Structure Details
Shows for each table:
- Column names and data types
- Nullability
- Primary keys
- Unique constraints
- Table engine and charset
- Row count estimates

### Index Analysis
Shows:
- Index names and types (BTREE, HASH, etc.)
- Indexed columns and order
- Uniqueness constraints
- Index cardinality
- Primary key configuration

## Support

For issues or questions:
1. Check the IMPLEMENTATION_SUMMARY.md for technical details
2. Review code comments in PHP files
3. Check browser console for JavaScript errors
4. Review WordPress error log

## License

GPL-2.0 or later

---

**Ready to analyze queries?** Go to Tools > SQL Analyzer in WordPress admin!
