<?php
/**
 * Add an admin notice and simple fix for wp_postmeta table
 * This file should be included in wp-config.php or functions.php to auto-fix on admin page load
 */

// Only run once on plugin/theme activation or when explicitly requested
if (!function_exists('mce_fix_wp_postmeta_strict_mode')) {
    function mce_fix_wp_postmeta_strict_mode() {
        global $wpdb;
        
        // Only run this if we detect the error
        if (!isset($_GET['mce_fix_postmeta'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Temporarily allow errors
        $old_report = error_reporting(E_ALL);
        ini_set('display_errors', 0);
        
        // Check current indexes
        $indexes = $wpdb->get_results("
            SELECT CONSTRAINT_NAME, COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'wp_postmeta'
        ");
        
        $has_primary = false;
        $has_post_id = false;
        $has_meta_key = false;
        
        foreach ($indexes as $index) {
            if ($index->CONSTRAINT_NAME === 'PRIMARY') {
                $has_primary = true;
            }
            if ($index->COLUMN_NAME === 'post_id') {
                $has_post_id = true;
            }
            if ($index->COLUMN_NAME === 'meta_key') {
                $has_meta_key = true;
            }
        }
        
        // Try to add missing indexes
        $errors = [];
        
        if (!$has_primary) {
            $result = $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD PRIMARY KEY (meta_id)");
            if ($wpdb->last_error) {
                $errors[] = "Could not add PRIMARY KEY: {$wpdb->last_error}";
            } else {
                error_log("MCE: Added PRIMARY KEY to wp_postmeta");
            }
        }
        
        if (!$has_post_id) {
            $result = $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX post_id (post_id)");
            if ($wpdb->last_error) {
                $errors[] = "Could not add post_id index: {$wpdb->last_error}";
            } else {
                error_log("MCE: Added post_id index to wp_postmeta");
            }
        }
        
        if (!$has_meta_key) {
            $result = $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX meta_key (meta_key(191))");
            if ($wpdb->last_error) {
                $errors[] = "Could not add meta_key index: {$wpdb->last_error}";
            } else {
                error_log("MCE: Added meta_key index to wp_postmeta");
            }
        }
        
        error_reporting($old_report);
        
        if (empty($errors)) {
            wp_die('✅ wp_postmeta table has been optimized for strict mode! You can now edit posts safely.');
        } else {
            wp_die('⚠️ Attempted fixes (some may have already been applied):<br>' . implode('<br>', $errors));
        }
    }
    
    // Hook this function - it will run when ?mce_fix_postmeta=1 is added to WordPress admin URL
    if (is_admin()) {
        add_action('admin_init', 'mce_fix_wp_postmeta_strict_mode');
    }
}
?>
