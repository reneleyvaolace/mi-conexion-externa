<?php
/**
 * MCE MySQL Strict Mode Debug and Test Suite
 * 
 * This file provides comprehensive testing and debugging for MySQL strict mode issues
 * 
 * @package CoreAura_Conexion_Externa
 * @version 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    // If not in WordPress context, try to load WordPress
    $wp_load_paths = array(
        '../../../../wp-load.php',
        '../../../wp-load.php',
        '../../wp-load.php',
        '../wp-load.php',
        'wp-load.php'
    );
    
    $wp_loaded = false;
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once( $path );
            $wp_loaded = true;
            break;
        }
    }
    
    if ( ! $wp_loaded ) {
        die( 'WordPress not found. This file must be run from within WordPress or wp-load.php must be accessible.' );
    }
}

class MCE_Debug_Test_Suite {
    
    private $wpdb;
    private $db_handler;
    private $test_results = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Load our DB handler
        if ( class_exists( 'MCE_DB_Handler' ) ) {
            $this->db_handler = new MCE_DB_Handler();
        }
        
        // Load strict mode fix if available
        if ( class_exists( 'MCE_Strict_Mode_Fix' ) ) {
            $this->strict_mode_fix = new MCE_Strict_Mode_Fix();
        }
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "<h1>🔧 MCE MySQL Strict Mode Test Suite</h1>\n";
        echo "<style>
            .test-pass { color: #46b450; font-weight: bold; }
            .test-fail { color: #dc3232; font-weight: bold; }
            .test-warning { color: #f56e28; font-weight: bold; }
            .test-info { color: #0073aa; }
            pre { background: #f1f1f1; padding: 10px; border-radius: 3px; }
        </style>\n";
        
        $this->test_wordpress_database_connection();
        $this->test_postmeta_table_structure();
        $this->test_postmeta_indexes();
        $this->test_mysql_strict_mode();
        $this->test_wordpress_queries();
        $this->test_mce_db_handler();
        $this->test_strict_mode_fix();
        $this->test_error_handling();
        $this->generate_recommendations();
        
        return $this->test_results;
    }
    
    /**
     * Test WordPress database connection
     */
    private function test_wordpress_database_connection() {
        echo "<h2>📊 WordPress Database Connection Test</h2>\n";
        
        try {
            $result = $this->wpdb->get_var( "SELECT VERSION()" );
            if ( $result ) {
                $this->record_test_result( 'wp_db_connection', 'PASS', "MySQL Version: {$result}" );
                echo "<p class='test-pass'>✅ WordPress database connection successful</p>\n";
                echo "<p class='test-info'>MySQL Version: {$result}</p>\n";
            } else {
                throw new Exception( 'No result from version query' );
            }
        } catch ( Exception $e ) {
            $this->record_test_result( 'wp_db_connection', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ WordPress database connection failed: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Test wp_postmeta table structure
     */
    private function test_postmeta_table_structure() {
        echo "<h2>📋 wp_postmeta Table Structure Test</h2>\n";
        
        try {
            // Test multiple methods to get table structure
            $methods = array(
                'INFORMATION_SCHEMA' => $this->get_table_structure_via_schema(),
                'DESCRIBE' => $this->get_table_structure_via_describe(),
                'SHOW COLUMNS' => $this->get_table_structure_via_show()
            );
            
            $successful_methods = 0;
            foreach ( $methods as $method => $result ) {
                if ( $result !== false ) {
                    $successful_methods++;
                    echo "<p class='test-pass'>✅ {$method} method successful</p>\n";
                    
                    if ( is_array( $result ) ) {
                        echo "<pre>" . print_r( $result, true ) . "</pre>\n";
                    }
                } else {
                    echo "<p class='test-fail'>❌ {$method} method failed</p>\n";
                }
            }
            
            if ( $successful_methods > 0 ) {
                $this->record_test_result( 'postmeta_structure', 'PASS', "{$successful_methods}/3 methods successful" );
            } else {
                throw new Exception( 'All structure query methods failed' );
            }
            
        } catch ( Exception $e ) {
            $this->record_test_result( 'postmeta_structure', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ wp_postmeta structure test failed: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Test wp_postmeta indexes
     */
    private function test_postmeta_indexes() {
        echo "<h2>🔍 wp_postmeta Indexes Test</h2>\n";
        
        try {
            $indexes = $this->get_table_indexes();
            if ( $indexes === false ) {
                throw new Exception( 'Could not retrieve index information' );
            }
            
            $expected_indexes = array(
                'PRIMARY' => 'meta_id',
                'post_id' => 'post_id', 
                'meta_key' => 'meta_key'
            );
            
            $found_indexes = array();
            $missing_indexes = array();
            
            foreach ( $indexes as $index ) {
                $index_name = $index['INDEX_NAME'];
                $column_name = $index['COLUMN_NAME'];
                
                if ( $index_name === 'PRIMARY' && $column_name === 'meta_id' ) {
                    $found_indexes[] = 'PRIMARY (meta_id)';
                } elseif ( $index_name === 'post_id' && $column_name === 'post_id' ) {
                    $found_indexes[] = 'post_id';
                } elseif ( $index_name === 'meta_key' && $column_name === 'meta_key' ) {
                    $found_indexes[] = 'meta_key';
                }
            }
            
            foreach ( $expected_indexes as $expected_name => $expected_column ) {
                $found = false;
                foreach ( $indexes as $index ) {
                    if ( ( $expected_name === 'PRIMARY' && $index['INDEX_NAME'] === 'PRIMARY' && $index['COLUMN_NAME'] === $expected_column ) ||
                         ( $expected_name !== 'PRIMARY' && $index['INDEX_NAME'] === $expected_name && $index['COLUMN_NAME'] === $expected_column ) ) {
                        $found = true;
                        break;
                    }
                }
                
                if ( ! $found ) {
                    $missing_indexes[] = $expected_name;
                }
            }
            
            if ( empty( $missing_indexes ) ) {
                $this->record_test_result( 'postmeta_indexes', 'PASS', 'All expected indexes found' );
                echo "<p class='test-pass'>✅ All required indexes are present</p>\n";
            } else {
                $this->record_test_result( 'postmeta_indexes', 'WARNING', 'Missing indexes: ' . implode( ', ', $missing_indexes ) );
                echo "<p class='test-warning'>⚠️  Missing indexes: " . implode( ', ', $missing_indexes ) . "</p>\n";
                echo "<p><strong>Recommendation:</strong> Run the MySQL Strict Mode Fix to add missing indexes.</p>\n";
            }
            
            echo "<h3>Found Indexes:</h3>\n";
            echo "<pre>" . print_r( $indexes, true ) . "</pre>\n";
            
        } catch ( Exception $e ) {
            $this->record_test_result( 'postmeta_indexes', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ Index test failed: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Test MySQL strict mode
     */
    private function test_mysql_strict_mode() {
        echo "<h2>⚙️ MySQL Strict Mode Test</h2>\n";
        
        try {
            $sql_mode = $this->wpdb->get_var( "SELECT @@sql_mode" );
            $strict_mode_active = false;
            
            $strict_indicators = array( 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY' );
            
            foreach ( $strict_indicators as $indicator ) {
                if ( strpos( $sql_mode, $indicator ) !== false ) {
                    $strict_mode_active = true;
                    break;
                }
            }
            
            if ( $strict_mode_active ) {
                $this->record_test_result( 'mysql_strict_mode', 'WARNING', 'MySQL strict mode is active: ' . $sql_mode );
                echo "<p class='test-warning'>⚠️  MySQL strict mode is active</p>\n";
                echo "<p class='test-info'>SQL Mode: {$sql_mode}</p>\n";
                echo "<p><strong>Impact:</strong> Queries without proper indexes will cause fatal errors.</p>\n";
            } else {
                $this->record_test_result( 'mysql_strict_mode', 'PASS', 'MySQL strict mode is not active' );
                echo "<p class='test-pass'>✅ MySQL strict mode is not active</p>\n";
                echo "<p class='test-info'>SQL Mode: {$sql_mode}</p>\n";
            }
            
        } catch ( Exception $e ) {
            $this->record_test_result( 'mysql_strict_mode', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ Could not check MySQL strict mode: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Test WordPress core queries that trigger strict mode errors
     */
    private function test_wordpress_queries() {
        echo "<h2>🔄 WordPress Core Queries Test</h2>\n";
        
        $queries_to_test = array(
            array( 'name' => 'get_postmeta', 'query' => "SHOW FULL COLUMNS FROM `{$this->wpdb->postmeta}`" ),
            array( 'name' => 'get_charset', 'query' => "SHOW FULL COLUMNS FROM `{$this->wpdb->postmeta}`" ),
            array( 'name' => 'count_postmeta', 'query' => "SELECT COUNT(*) FROM `{$this->wpdb->postmeta}`" )
        );
        
        foreach ( $queries_to_test as $test ) {
            try {
                // Temporarily enable error reporting to catch strict mode errors
                $old_reporting = mysqli_report( MYSQLI_REPORT_OFF );
                
                $result = $this->wpdb->get_results( $test['query'] );
                
                mysqli_report( $old_reporting );
                
                if ( $result !== false ) {
                    $this->record_test_result( 'wp_query_' . $test['name'], 'PASS', 'Query executed successfully' );
                    echo "<p class='test-pass'>✅ {$test['name']} query successful</p>\n";
                } else {
                    throw new Exception( $this->wpdb->last_error );
                }
                
            } catch ( Exception $e ) {
                mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
                $this->record_test_result( 'wp_query_' . $test['name'], 'FAIL', $e->getMessage() );
                echo "<p class='test-fail'>❌ {$test['name']} query failed: {$e->getMessage()}</p>\n";
            }
        }
    }
    
    /**
     * Test MCE DB Handler
     */
    private function test_mce_db_handler() {
        echo "<h2>🔌 MCE DB Handler Test</h2>\n";
        
        if ( ! $this->db_handler ) {
            $this->record_test_result( 'mce_db_handler', 'FAIL', 'MCE DB Handler not available' );
            echo "<p class='test-fail'>❌ MCE DB Handler class not found</p>\n";
            return;
        }
        
        try {
            $connection_test = $this->db_handler->test_connection();
            if ( $connection_test ) {
                $this->record_test_result( 'mce_connection', 'PASS', 'MCE external DB connection successful' );
                echo "<p class='test-pass'>✅ MCE external database connection successful</p>\n";
            } else {
                $this->record_test_result( 'mce_connection', 'WARNING', 'MCE external DB connection failed (this may be expected if not configured)' );
                echo "<p class='test-warning'>⚠️  MCE external database connection failed (may not be configured)</p>\n";
            }
            
            // Test safe query methods
            $safe_query_result = $this->db_handler->safe_query( "SELECT 1 as test" );
            if ( $safe_query_result ) {
                $this->record_test_result( 'mce_safe_query', 'PASS', 'Safe query method working' );
                echo "<p class='test-pass'>✅ MCE safe query method working</p>\n";
            } else {
                $this->record_test_result( 'mce_safe_query', 'FAIL', 'Safe query method failed' );
                echo "<p class='test-fail'>❌ MCE safe query method failed</p>\n";
            }
            
        } catch ( Exception $e ) {
            $this->record_test_result( 'mce_db_handler', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ MCE DB Handler test failed: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Test strict mode fix functionality
     */
    private function test_strict_mode_fix() {
        echo "<h2>🔧 Strict Mode Fix Test</h2>\n";
        
        if ( ! isset( $this->strict_mode_fix ) ) {
            $this->record_test_result( 'strict_mode_fix', 'FAIL', 'Strict mode fix class not available' );
            echo "<p class='test-fail'>❌ MCE Strict Mode Fix class not found</p>\n";
            return;
        }
        
        try {
            // Test auto-fix method
            $fixes_applied = $this->strict_mode_fix->auto_fix_indexes();
            $this->record_test_result( 'auto_fix', 'PASS', "Auto-fix applied {$fixes_applied} indexes" );
            echo "<p class='test-pass'>✅ Auto-fix method executed (applied {$fixes_applied} indexes)</p>\n";
            
        } catch ( Exception $e ) {
            $this->record_test_result( 'strict_mode_fix', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ Strict mode fix test failed: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Test error handling
     */
    private function test_error_handling() {
        echo "<h2>🛡️ Error Handling Test</h2>\n";
        
        try {
            // Test with invalid query
            $result = $this->wpdb->get_results( "SELECT * FROM nonexistent_table_12345" );
            
            if ( $result === false ) {
                $this->record_test_result( 'error_handling', 'PASS', 'Error handling working correctly' );
                echo "<p class='test-pass'>✅ Error handling working correctly</p>\n";
            } else {
                throw new Exception( 'Invalid query did not produce expected error' );
            }
            
        } catch ( Exception $e ) {
            $this->record_test_result( 'error_handling', 'FAIL', $e->getMessage() );
            echo "<p class='test-fail'>❌ Error handling test failed: {$e->getMessage()}</p>\n";
        }
    }
    
    /**
     * Generate recommendations based on test results
     */
    private function generate_recommendations() {
        echo "<h2>📋 Recommendations</h2>\n";
        
        $critical_issues = 0;
        $warnings = 0;
        
        foreach ( $this->test_results as $result ) {
            if ( $result['status'] === 'FAIL' ) {
                $critical_issues++;
            } elseif ( $result['status'] === 'WARNING' ) {
                $warnings++;
            }
        }
        
        if ( $critical_issues === 0 && $warnings === 0 ) {
            echo "<p class='test-pass'>✅ No issues found! Your system is properly configured.</p>\n";
        } else {
            echo "<h3>Issues Found:</h3>\n";
            
            foreach ( $this->test_results as $test_name => $result ) {
                if ( $result['status'] === 'FAIL' ) {
                    echo "<p class='test-fail'>❌ <strong>{$test_name}:</strong> {$result['message']}</p>\n";
                } elseif ( $result['status'] === 'WARNING' ) {
                    echo "<p class='test-warning'>⚠️  <strong>{$test_name}:</strong> {$result['message']}</p>\n";
                }
            }
            
            echo "<h3>Recommended Actions:</h3>\n";
            
            if ( $critical_issues > 0 ) {
                echo "<ol>\n";
                echo "<li><strong>Run MySQL Strict Mode Fix:</strong> Use the admin page to apply database fixes</li>\n";
                echo "<li><strong>Check database permissions:</strong> Ensure WordPress has proper database permissions</li>\n";
                echo "<li><strong>Verify MySQL configuration:</strong> Check MySQL server configuration for strict mode settings</li>\n";
                echo "</ol>\n";
            }
            
            if ( $warnings > 0 ) {
                echo "<h3>Optional Improvements:</h3>\n";
                echo "<ul>\n";
                echo "<li>Consider adding more indexes for better performance</li>\n";
                echo "<li>Monitor error logs for recurring issues</li>\n";
                echo "<li>Run this test suite periodically to catch new issues</li>\n";
                echo "</ul>\n";
            }
        }
    }
    
    /**
     * Get table structure via INFORMATION_SCHEMA
     */
    private function get_table_structure_via_schema() {
        try {
            $query = "
                SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = %s
                ORDER BY ORDINAL_POSITION
            ";
            
            return $this->wpdb->get_results( $this->wpdb->prepare( $query, $this->wpdb->postmeta ) );
        } catch ( Exception $e ) {
            return false;
        }
    }
    
    /**
     * Get table structure via DESCRIBE
     */
    private function get_table_structure_via_describe() {
        try {
            $old_reporting = error_reporting( E_ERROR | E_PARSE );
            $result = $this->wpdb->get_results( "DESCRIBE {$this->wpdb->postmeta}" );
            error_reporting( $old_reporting );
            return $result;
        } catch ( Exception $e ) {
            return false;
        }
    }
    
    /**
     * Get table structure via SHOW COLUMNS
     */
    private function get_table_structure_via_show() {
        try {
            $old_mysqli_report = mysqli_report( MYSQLI_REPORT_OFF );
            $result = $this->wpdb->get_results( "SHOW COLUMNS FROM `{$this->wpdb->postmeta}`" );
            mysqli_report( $old_mysqli_report );
            return $result;
        } catch ( Exception $e ) {
            return false;
        }
    }
    
    /**
     * Get table indexes
     */
    private function get_table_indexes() {
        try {
            $query = "
                SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SEQ_IN_INDEX
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = %s
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ";
            
            return $this->wpdb->get_results( $this->wpdb->prepare( $query, $this->wpdb->postmeta ) );
        } catch ( Exception $e ) {
            return false;
        }
    }
    
    /**
     * Record test result
     */
    private function record_test_result( $test_name, $status, $message ) {
        $this->test_results[ $test_name ] = array(
            'status' => $status,
            'message' => $message,
            'timestamp' => current_time( 'mysql' )
        );
    }
    
    /**
     * Get test results as array
     */
    public function get_test_results() {
        return $this->test_results;
    }
    
    /**
     * Export test results to JSON
     */
    public function export_results_json() {
        header( 'Content-Type: application/json' );
        echo json_encode( $this->test_results, JSON_PRETTY_PRINT );
    }
}

// Auto-run if this file is accessed directly
if ( ! defined( 'DOING_AJAX' ) && ! wp_doing_ajax() ) {
    if ( isset( $_GET['mce_debug_action'] ) ) {
        $test_suite = new MCE_Debug_Test_Suite();
        
        switch ( $_GET['mce_debug_action'] ) {
            case 'run_tests':
                $test_suite->run_all_tests();
                break;
            case 'export_json':
                $test_suite->export_results_json();
                break;
        }
    } else {
        // Default: show test interface
        echo '<!DOCTYPE html><html><head><title>MCE Debug Test Suite</title></head><body>';
        $test_suite = new MCE_Debug_Test_Suite();
        $test_suite->run_all_tests();
        echo '</body></html>';
    }
}
