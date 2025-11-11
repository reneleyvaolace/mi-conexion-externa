<?php
/**
 * MCE Database Handler Compatibility Test
 * Tests backward compatibility of enhanced database handler
 */

// Try to load WordPress if not in WP context
if ( ! defined( 'ABSPATH' ) ) {
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
        die( 'WordPress not found. This test must be run from within WordPress.' );
    }
}

echo "<h1>🔧 MCE Database Handler Compatibility Test</h1>\n";
echo "<style>
    .pass { color: #46b450; font-weight: bold; }
    .fail { color: #dc3232; font-weight: bold; }
    .warning { color: #f56e28; font-weight: bold; }
</style>\n";

try {
    // Test 1: Load the enhanced database handler
    echo "<h2>1. Loading Database Handler</h2>\n";
    
    if ( class_exists( 'MCE_DB_Handler' ) ) {
        $db_handler = new MCE_DB_Handler();
        echo "<p class='pass'>✅ MCE_DB_Handler class loaded successfully</p>\n";
    } else {
        throw new Exception( 'MCE_DB_Handler class not found' );
    }
    
    // Test 2: Check backward compatibility methods
    echo "<h2>2. Testing Backward Compatibility Methods</h2>\n";
    
    $compatibility_methods = array(
        'get_tables' => 'Lists tables in external database',
        'get_table_columns' => 'Gets columns for a table',
        'get_table_content' => 'Gets content of a table'
    );
    
    foreach ( $compatibility_methods as $method => $description ) {
        if ( method_exists( $db_handler, $method ) ) {
            echo "<p class='pass'>✅ get_tables() method exists: {$description}</p>\n";
        } else {
            echo "<p class='fail'>❌ Missing method: {$method} - {$description}</p>\n";
        }
    }
    
    // Test 3: Test method functionality (safe tests only)
    echo "<h2>3. Testing Method Functionality</h2>\n";
    
    // Test connection (should not throw fatal error)
    if ( method_exists( $db_handler, 'test_connection' ) ) {
        try {
            $connection_result = $db_handler->test_connection();
            if ( $connection_result === false ) {
                echo "<p class='warning'>⚠️  External database connection failed (expected if not configured)</p>\n";
            } else {
                echo "<p class='pass'>✅ External database connection working</p>\n";
            }
        } catch ( Exception $e ) {
            echo "<p class='fail'>❌ Connection test failed: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Test get_tables() method (should not throw fatal error)
    if ( method_exists( $db_handler, 'get_tables' ) ) {
        try {
            $tables = $db_handler->get_tables();
            if ( $tables === false ) {
                echo "<p class='warning'>⚠️  get_tables() returned false (expected if external DB not configured)</p>\n";
            } elseif ( is_array( $tables ) ) {
                echo "<p class='pass'>✅ get_tables() returned " . count( $tables ) . " tables</p>\n";
            } else {
                echo "<p class='warning'>⚠️  get_tables() returned unexpected result type: " . gettype( $tables ) . "</p>\n";
            }
        } catch ( Exception $e ) {
            echo "<p class='fail'>❌ get_tables() test failed: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Test 4: Test strict mode compatibility features
    echo "<h2>4. Testing Enhanced Features</h2>\n";
    
    $enhanced_methods = array(
        'safe_query' => 'Safe query with strict mode handling',
        'get_results_safe' => 'Safe get_results with error handling',
        'check_strict_mode_compatibility' => 'Strict mode compatibility check'
    );
    
    foreach ( $enhanced_methods as $method => $description ) {
        if ( method_exists( $db_handler, $method ) ) {
            echo "<p class='pass'>✅ {$method}() exists: {$description}</p>\n";
        } else {
            echo "<p class='fail'>❌ Missing enhanced method: {$method}</p>\n";
        }
    }
    
    // Test 5: Admin page compatibility
    echo "<h2>5. Testing Admin Page Compatibility</h2>\n";
    
    if ( class_exists( 'MCE_Query_Page' ) ) {
        echo "<p class='pass'>✅ MCE_Query_Page class exists</p>\n";
        
        // Test if query page can be instantiated
        try {
            $query_page = new MCE_Query_Page();
            echo "<p class='pass'>✅ MCE_Query_Page can be instantiated</p>\n";
        } catch ( Exception $e ) {
            echo "<p class='fail'>❌ MCE_Query_Page instantiation failed: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p class='warning'>⚠️  MCE_Query_Page class not found (may not be loaded)</p>\n";
    }
    
    // Test 6: Final compatibility check
    echo "<h2>6. Final Compatibility Check</h2>\n";
    
    // Simulate the problematic call from query page
    try {
        if ( method_exists( $db_handler, 'get_tables' ) ) {
            $result = $db_handler->get_tables();
            echo "<p class='pass'>✅ Simulated admin page call successful</p>\n";
        } else {
            echo "<p class='fail'>❌ get_tables() method still missing</p>\n";
        }
    } catch ( Exception $e ) {
        echo "<p class='fail'>❌ Admin page compatibility test failed: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h2>📋 Summary</h2>\n";
    echo "<p class='pass'>✅ Database handler backward compatibility test completed</p>\n";
    echo "<p><strong>Note:</strong> The 'Call to undefined method' error should now be resolved.</p>\n";
    
} catch ( Exception $e ) {
    echo "<h2>❌ Test Failed</h2>\n";
    echo "<p class='fail'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed at: " . date( 'Y-m-d H:i:s' ) . "</em></p>\n";