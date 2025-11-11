# MySQL Strict Mode Fix Documentation

## Overview

This documentation provides comprehensive information about the MySQL strict mode issues encountered in the CoreAura: Conexión Externa plugin and how to resolve them.

## What is MySQL Strict Mode?

MySQL strict mode is a configuration that enforces stricter rules for data entry and query operations. When strict mode is enabled, MySQL will reject queries that violate certain rules, such as:

- Queries that don't use indexes (can cause performance issues)
- Inserting invalid data into columns
- Truncation of data that doesn't fit in column constraints

## The Problem

### Error Messages

When MySQL strict mode is enabled, you may encounter fatal errors like:

```
PHP Fatal error: Uncaught mysqli_sql_exception: No index used in query/prepared statement 
SHOW FULL COLUMNS FROM `wp_postmeta` in C:\Users\...\wp-includes\class-wpdb.php:2351
```

### Stack Trace

```
#0 C:\Users\...\wp-includes\class-wpdb.php(2351): mysqli_query(Object(mysqli), 'SHOW FULL COLUMNS FROM `wp_postmeta`')
#1 C:\Users\...\wp-includes\class-wpdb.php(2265): wpdb->_do_query('SHOW FULL COLUMNS FROM `wp_postmeta`')
#2 C:\Users\...\wp-includes\class-wpdb.php(3146): wpdb->query('SHOW FULL COLUMNS FROM `wp_postmeta`')
#3 C:\Users\...\wp-includes\class-wpdb.php(3230): wpdb->get_results('SHOW FULL COLUMNS FROM `wp_postmeta`')
#4 C:\Users\...\wp-includes\class-wpdb.php(3328): wpdb->get_table_charset('wp_postmeta')
#5 C:\Users\...\wp-includes\class-wpdb.php(2937): wpdb->get_col_charset('wp_postmeta', 'meta_value')
```

### Root Cause

The WordPress core and other plugins frequently execute queries like `SHOW FULL COLUMNS FROM wp_postmeta` to get table structure information. When these queries don't use indexes (because the table is small or the query is simple), MySQL strict mode throws a fatal error.

## The Solution

### 1. Database Indexes

The primary fix is to ensure the `wp_postmeta` table has proper indexes:

- **PRIMARY KEY on `meta_id`**: Ensures unique identification
- **Index on `post_id`**: Speeds up post-related queries
- **Index on `meta_key`**: Speeds up meta key lookups
- **Compound index on `post_id + meta_key`**: Optimizes common combined queries

### 2. Error Handling

Enhanced error handling in the plugin to:

- Gracefully handle MySQL strict mode errors
- Automatically attempt to fix missing indexes
- Provide user-friendly error messages
- Log detailed error information for debugging

## Implementation

### Files Modified

1. **admin/class-mce-strict-mode-fix.php** - Enhanced admin interface for applying fixes
2. **includes/class-mce-db-handler.php** - Improved database handler with strict mode compatibility
3. **debug-options.php** - Comprehensive testing and debugging suite

### Key Features

#### Strict Mode Fix Class

```php
class MCE_Strict_Mode_Fix {
    // Automatically detects and fixes missing indexes
    public function auto_fix_indexes() {
        // Checks for PRIMARY KEY, post_id, meta_key, and compound indexes
        // Applies fixes without causing additional errors
    }
}
```

#### Enhanced Database Handler

```php
class MCE_DB_Handler {
    // Safe query methods that handle strict mode
    public function safe_query($query, $params = array()) {
        // Disables strict mode reporting temporarily
        // Attempts fixes if strict mode errors occur
    }
}
```

#### Testing Suite

```php
class MCE_Debug_Test_Suite {
    // Comprehensive testing for:
    // - Database connection
    // - Table structure
    // - Index status
    // - MySQL strict mode detection
    // - Error handling
}
```

## How to Use

### 1. Access the Fix Interface

1. Go to **WordPress Admin → Tools → MySQL Strict Mode Fix**
2. Review the current database table status
3. Click "Apply Database Fixes" to add missing indexes

### 2. Run Tests

1. Access `debug-options.php` directly in your browser
2. Or add `?mce_debug_action=run_tests` to the URL
3. Review the comprehensive test results

### 3. Auto-Fix (Development)

The plugin automatically checks and applies fixes during:

- Plugin activation
- WordPress initialization (if not previously checked)
- When strict mode errors are detected

## Troubleshooting

### Common Issues

#### "Could not add PRIMARY KEY" Error

**Cause**: Table already has a different primary key or data integrity issues.

**Solution**: 
```sql
-- Check existing primary key
SHOW INDEX FROM wp_postmeta WHERE Key_name = 'PRIMARY';

-- If issues persist, backup and recreate table
```

#### "Access Denied" Errors

**Cause**: Insufficient database permissions.

**Solution**: Ensure WordPress database user has `ALTER` privileges.

#### Persistent Strict Mode Errors

**Cause**: MySQL configuration requires different approach.

**Solution**:
1. Contact hosting provider to adjust MySQL strict mode settings
2. Use the testing suite to identify specific issues
3. Check error logs for detailed information

### Error Log Analysis

Check your error logs for:

```bash
# Check recent errors
tail -f /path/to/error.log | grep "MCE"

# Look for specific patterns
grep "No index used" /path/to/error.log
grep "mysqli_sql_exception" /path/to/error.log
```

### MySQL Configuration

If you have access to MySQL configuration, you can temporarily disable strict mode:

```sql
-- Check current mode
SELECT @@sql_mode;

-- Set less strict mode (temporary)
SET SESSION sql_mode = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Or remove specific strict mode flags
SET SESSION sql_mode = REPLACE(@@sql_mode, 'STRICT_TRANS_TABLES', '');
```

**Note**: Changing MySQL configuration is server-level and may affect other applications.

## Prevention

### Development Environment

1. Always test with MySQL strict mode enabled
2. Use the testing suite before deploying
3. Include index creation in your deployment process

### Production Environment

1. Monitor error logs for MySQL strict mode errors
2. Run periodic tests to catch new issues
3. Keep WordPress and plugins updated

### Best Practices

1. **Index Strategy**: Add indexes for frequently queried columns
2. **Query Optimization**: Use `EXPLAIN` to analyze query performance
3. **Error Monitoring**: Set up alerts for database errors
4. **Regular Testing**: Run the test suite monthly

## Advanced Configuration

### Custom Index Management

You can manually add indexes using phpMyAdmin or MySQL command line:

```sql
-- Add missing indexes
ALTER TABLE wp_postmeta ADD PRIMARY KEY (meta_id);
ALTER TABLE wp_postmeta ADD INDEX post_id (post_id);
ALTER TABLE wp_postmeta ADD INDEX meta_key (meta_key(191));
ALTER TABLE wp_postmeta ADD INDEX post_id_meta_key (post_id, meta_key(191));
```

### Plugin Configuration

Add to your `wp-config.php` for enhanced debugging:

```php
// Enable MCE debug mode
define('MCE_DEBUG', true);

// Or for more verbose error reporting
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Performance Impact

### Before Fixes

- Frequent fatal errors on post editing
- Slow database queries
- High server load from error handling

### After Fixes

- Eliminated MySQL strict mode errors
- Improved query performance
- Better error handling and recovery

### Index Benefits

- **Primary Key**: Unique record identification, faster lookups
- **post_id Index**: Faster post-related queries
- **meta_key Index**: Faster meta key searches
- **Compound Index**: Optimized queries for post + meta combinations

## Support

### Getting Help

1. **Check Error Logs**: Use the error log analysis methods above
2. **Run Tests**: Use the comprehensive testing suite
3. **Contact Support**: Provide test results and error logs

### Information to Provide

When seeking support, include:

- WordPress version
- MySQL/MariaDB version
- PHP version
- Error log entries
- Test suite results
- Hosting environment details

## Changelog

### Version 1.2.0

- ✅ Enhanced strict mode fix with automatic error handling
- ✅ Improved database handler with safe query methods
- ✅ Comprehensive testing and debugging suite
- ✅ Better error logging and user feedback
- ✅ Auto-fix functionality during plugin activation
- ✅ **FIXED**: "Call to undefined method get_tables()" compatibility issue
- ✅ Added backward compatibility methods for existing admin interface
- ✅ **NEW**: Complete AJAX functionality implementation
- ✅ Real-time search, filtering, and pagination
- ✅ Interactive test suites for AJAX validation
- ✅ **FIXED**: PHP fatal error in shortcode (current_time parameter)
- ✅ **FIXED**: Missing escape_string() method causing AJAX failures
- ✅ **FIXED**: Double "WHERE" clause SQL syntax error in AJAX search
- ✅ **ENHANCED**: AJAX search results now display in card format (consistent with original layout)
- ✅ **FIXED**: Clear button now displays results in card format (not table format)
- ✅ **NEW**: `mostrar_buscador` attribute to control search visibility (show/hide search controls)
- ✅ **ENHANCED**: Clear button now restores exact original view and configuration

### Version 1.1.x

- Basic MySQL strict mode detection
- Manual fix interface
- Limited error handling

## AJAX Functionality

The plugin now includes comprehensive AJAX capabilities for enhanced user experience:

### Features Implemented

#### 🔄 **Real-time Data Loading**
- **Page Loading**: AJAX pagination without page refresh
- **Search**: Real-time search across all table columns
- **Filtering**: Dynamic filter options loaded via AJAX
- **Results**: Instant display of search results and pagination

#### 🎛️ **Interactive Controls**
- **Search Box**: Universal search across all data
- **Filter Selects**: Dynamic dropdowns with unique values
- **Pagination**: Click-to-load more results
- **Clear Filters**: One-click reset of all filters

#### 🛠️ **Technical Implementation**
- **Backend**: 4 AJAX endpoints (`mce_cargar_pagina`, `mce_buscar_filtrar`, etc.)
- **Frontend**: jQuery-based AJAX with error handling
- **Styling**: Complete CSS for all interactive elements
- **Testing**: Comprehensive AJAX test suite (`ajax-test.php`)

#### 📱 **User Experience**
- **Loading Indicators**: Visual feedback during requests
- **Error Handling**: Graceful error messages
- **Mobile Responsive**: Works on all device sizes
- **Multiple Instances**: Independent shortcodes on same page

### Testing AJAX Functionality

1. **Access Test Suite**: Open `ajax-test.php` in browser
2. **Interactive Testing**: Use the test forms to verify each AJAX endpoint
3. **Real-time Testing**: Add shortcode to page and test live functionality
4. **Debug Mode**: Enable with `define('MCE_DEBUG', true);`

The AJAX functionality transforms the plugin from a static display to a dynamic, interactive data exploration tool while maintaining full compatibility with the existing MySQL strict mode fixes.