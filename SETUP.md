# SQL Analyzer Plugin - Setup & Access Guide

## ✅ Plugin Status

The SQL Analyzer plugin is now **fully functional and ready to use**!

## 📍 How to Access the Plugin

### Step 1: Activate the Plugin
1. Go to WordPress Admin Dashboard
2. Navigate to **Plugins** > **Installed Plugins**
3. Find **"SQL Analyzer"** in the list
4. Click **"Activate"**

### Step 2: Access the SQL Analyzer Page
Once activated, navigate to:
- **Tools** > **SQL Analyzer** (in the WordPress admin menu)

### Step 3: Use the Plugin

The admin page will display:

1. **Query Input Section**
   - A large textarea where you can paste your SQL query
   - Checkbox option to include ANALYZE results
   - "Analyze Query" button to submit
   - "Clear" button to reset the form

2. **Results Section** (appears after analysis)
   - **Execution Plan** - Shows MySQL EXPLAIN output
   - **Database Structures** - Shows tables, columns, types, constraints
   - **Index Information** - Shows all indexes on involved tables
   - **Complete Analysis for LLM** - Full formatted report ready to copy

3. **Copy Buttons**
   - Each section has a "Copy" button to copy that section to clipboard
   - "Copy All" button for the complete analysis
   - Toast notification confirms when copied

## 🎯 Example Queries to Try

### Simple SELECT
```sql
SELECT * FROM wp_users LIMIT 10
```

### SELECT with WHERE
```sql
SELECT ID, user_login, user_email FROM wp_users WHERE ID = 1
```

### SELECT with JOIN
```sql
SELECT p.ID, p.post_title, COUNT(c.comment_ID) as comments
FROM wp_posts p
LEFT JOIN wp_comments c ON p.ID = c.comment_post_ID
WHERE p.post_type = 'post'
GROUP BY p.ID
ORDER BY comments DESC
LIMIT 10
```

### SELECT with Multiple Tables
```sql
SELECT u.ID, u.user_login, pm.meta_value
FROM wp_users u
INNER JOIN wp_usermeta pm ON u.ID = pm.user_id
WHERE pm.meta_key = 'first_name'
```

## 🔐 Security Features

✅ **Admin-Only Access** - Only administrators can access the plugin
✅ **Query Validation** - Only SELECT queries are allowed
✅ **Safe Analysis** - EXPLAIN is non-destructive
✅ **Blocked Operations** - INSERT, UPDATE, DELETE, DROP, etc. are blocked
✅ **CSRF Protection** - Nonce verification on all requests

## 📊 What You'll See

### For Each Query:

1. **Execution Plan (EXPLAIN)**
   - Shows how MySQL will execute your query
   - Displays the index used, number of rows, query type, etc.
   - Useful for understanding query optimization

2. **Database Structures**
   - Shows all tables involved in the query
   - Lists all columns with their data types
   - Shows primary keys and unique constraints
   - Example:
     ```
     Table: wp_users
       Columns:
         - ID (BIGINT UNSIGNED) [PRIMARY KEY]
         - user_login (VARCHAR(60)) [NOT NULL, UNIQUE]
         - user_email (VARCHAR(100)) [NOT NULL]
     ```

3. **Index Information**
   - Lists all indexes on involved tables
   - Shows index types (BTREE, etc.)
   - Helps identify optimization opportunities

4. **Complete Analysis for LLM**
   - Professional formatted report combining everything
   - Ready to copy and paste into Claude, ChatGPT, etc.
   - Provides full context for AI analysis

## 💡 How to Use with LLMs

1. Enter your SQL query in the form
2. Click "Analyze Query"
3. In the "Complete Analysis for LLM" section, click "Copy All"
4. Paste into your LLM chat (Claude, ChatGPT, etc.)
5. Ask the LLM to:
   - Explain what the query does
   - Identify performance issues
   - Suggest optimizations
   - Recommend indexes
   - Rewrite for better performance

## 📝 Example Prompt for LLM

```
Here's my WordPress SQL query analysis. Can you:
1. Explain what this query does
2. Identify any performance issues
3. Suggest optimizations
4. Recommend indexes to add

[Paste complete analysis]
```

## 🐛 Troubleshooting

### Plugin doesn't appear in admin menu
- Make sure you're logged in as an administrator
- Click "Refresh" on the plugins page
- Try deactivating and reactivating the plugin

### "You do not have permission" error
- The plugin is admin-only
- Log in with an administrator account
- Check your user role in Users > Your Profile

### "Query type cannot be analyzed" error
- Only SELECT queries are supported
- No INSERT, UPDATE, DELETE, DROP, etc.
- Try using a simpler SELECT query

### Query returns no results
- Make sure the query references WordPress tables (wp_posts, wp_users, etc.)
- Check that the table names are correct
- Try a simple query first: `SELECT * FROM wp_users LIMIT 1`

### Copied text looks wrong
- The "Copy" button copies formatted text
- This is normal for professional formatting
- Perfect for pasting into LLM chats

## 🎓 Learning Resources

The plugin is useful for:
- Learning how MySQL executes queries
- Understanding query optimization
- Identifying missing indexes
- Getting AI-powered query analysis
- Performance tuning with LLM help

## 📖 File Locations

- **Main Plugin File**: `/wp-content/plugins/sql-analyzer/sql-analyzer.php`
- **Admin Page Template**: `/wp-content/plugins/sql-analyzer/templates/admin/query-analyzer.php`
- **Admin Styles**: `/wp-content/plugins/sql-analyzer/assets/admin/css/sql-analyzer-admin.css`
- **Admin JavaScript**: `/wp-content/plugins/sql-analyzer/assets/admin/js/sql-analyzer-admin.js`

## ✨ Ready to Use!

The plugin is now fully installed and accessible from the WordPress admin menu.

**Access it now at: Tools > SQL Analyzer**

Start by pasting a simple query and exploring the results!
