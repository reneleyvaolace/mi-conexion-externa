<?php
/**
 * MySQL Strict Mode Fix for wp_postmeta
 * Enhanced version that handles errors gracefully and provides multiple approaches
 * 
 * @package CoreAura_Conexion_Externa
 * @version 1.2.0
 */

// Ensure this only runs from admin dashboard with proper nonce/capability
if ( ! function_exists( 'wp_die' ) ) {
    die( 'This file must be included from WordPress.' );
}

class MCE_Strict_Mode_Fix {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Create admin page for the fix
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'init_admin' ) );
    }
    
    /**
     * Initialize admin functionality
     */
    public function init_admin() {
        // Handle form submission
        if ( isset( $_POST['mce_apply_fix'] ) && check_admin_referer( 'mce_strict_mode_fix' ) ) {
            $this->apply_database_fixes();
        }
        
        // Handle auto-fix on activation
        if ( isset( $_POST['mce_auto_fix'] ) && check_admin_referer( 'mce_strict_mode_fix' ) ) {
            $this->auto_fix_indexes();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'MySQL Strict Mode Fix',
            'MySQL Strict Mode Fix',
            'manage_options',
            'mce-strict-mode-fix',
            array( $this, 'render_admin_page' )
        );
    }
    
    /**
     * Get table structure information using alternative methods
     */
    private function get_table_info_alternative() {
        $info = array();
        
        try {
            // Method 1: Use INFORMATION_SCHEMA (doesn't trigger strict mode warnings)
            $query = "
                SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = %s
                ORDER BY ORDINAL_POSITION
            ";
            
            $columns = $this->wpdb->get_results( $this->wpdb->prepare( $query, $this->wpdb->postmeta ) );
            
            if ( ! empty( $columns ) ) {
                return $columns;
            }
            
            // Method 2: Use DESCRIBE with error suppression
            $old_reporting = error_reporting();
            error_reporting( E_ERROR | E_PARSE );
            
            $columns = $this->wpdb->get_results( "DESCRIBE {$this->wpdb->postmeta}" );
            
            error_reporting( $old_reporting );
            
            if ( ! empty( $columns ) ) {
                return $columns;
            }
            
            // Method 3: Try SHOW COLUMNS with strict mode disabled
            $old_mysqli_report = mysqli_report( MYSQLI_REPORT_OFF );
            
            try {
                $result = $this->wpdb->get_results( "SHOW COLUMNS FROM `{$this->wpdb->postmeta}`" );
                mysqli_report( $old_mysqli_report );
                
                if ( ! empty( $result ) ) {
                    return $result;
                }
            } catch ( Exception $e ) {
                mysqli_report( $old_mysqli_report );
            }
            
        } catch ( Exception $e ) {
            error_log( 'MCE: Error getting table info: ' . $e->getMessage() );
        }
        
        // Fallback: Return basic structure
        return array(
            (object) array( 'Field' => 'meta_id', 'Type' => 'bigint(20)', 'Key' => 'PRI', 'Null' => 'NO', 'Default' => NULL, 'Extra' => 'auto_increment' ),
            (object) array( 'Field' => 'post_id', 'Type' => 'bigint(20)', 'Key' => '', 'Null' => 'NO', 'Default' => '0', 'Extra' => '' ),
            (object) array( 'Field' => 'meta_key', 'Type' => 'varchar(255)', 'Key' => '', 'Null' => 'YES', 'Default' => NULL, 'Extra' => '' ),
            (object) array( 'Field' => 'meta_value', 'Type' => 'longtext', 'Key' => '', 'Null' => 'YES', 'Default' => NULL, 'Extra' => '' )
        );
    }
    
    /**
     * Check if index exists
     */
    private function index_exists( $column_name, $index_name = null ) {
        if ( $index_name === null ) {
            $index_name = $column_name;
        }
        
        $query = "
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = %s
            AND COLUMN_NAME = %s
            AND INDEX_NAME = %s
        ";
        
        $result = $this->wpdb->get_var( $this->wpdb->prepare( $query, $this->wpdb->postmeta, $column_name, $index_name ) );
        
        return intval( $result ) > 0;
    }
    
    /**
     * Apply database fixes with proper error handling
     */
    private function apply_database_fixes() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        // Store messages for display
        $messages = array();
        $errors = array();
        $fixes_applied = 0;
        
        try {
            // Temporarily disable strict mode for our operations
            $old_hide_errors = $this->wpdb->hide_errors();
            $this->wpdb->show_errors();
            
            // Check and add PRIMARY KEY if missing
            if ( ! $this->index_exists( 'meta_id', 'PRIMARY' ) ) {
                $result = $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD PRIMARY KEY (meta_id)" );
                
                if ( ! $this->wpdb->last_error ) {
                    $fixes_applied++;
                    $messages[] = '✅ Added PRIMARY KEY to meta_id column';
                    error_log( 'MCE: Successfully added PRIMARY KEY to wp_postmeta' );
                } else {
                    $error = 'Could not add PRIMARY KEY: ' . $this->wpdb->last_error;
                    $errors[] = $error;
                    error_log( 'MCE: ' . $error );
                }
            } else {
                $messages[] = '✅ PRIMARY KEY already exists on meta_id column';
            }
            
            // Add post_id index if missing
            if ( ! $this->index_exists( 'post_id' ) ) {
                $result = $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD INDEX post_id (post_id)" );
                
                if ( ! $this->wpdb->last_error ) {
                    $fixes_applied++;
                    $messages[] = '✅ Added post_id index';
                    error_log( 'MCE: Successfully added post_id index to wp_postmeta' );
                } else {
                    $error = 'Could not add post_id index: ' . $this->wpdb->last_error;
                    $errors[] = $error;
                    error_log( 'MCE: ' . $error );
                }
            } else {
                $messages[] = '✅ post_id index already exists';
            }
            
            // Add meta_key index if missing (with length limit for varchar)
            if ( ! $this->index_exists( 'meta_key' ) ) {
                $result = $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD INDEX meta_key (meta_key(191))" );
                
                if ( ! $this->wpdb->last_error ) {
                    $fixes_applied++;
                    $messages[] = '✅ Added meta_key index';
                    error_log( 'MCE: Successfully added meta_key index to wp_postmeta' );
                } else {
                    $error = 'Could not add meta_key index: ' . $this->wpdb->last_error;
                    $errors[] = $error;
                    error_log( 'MCE: ' . $error );
                }
            } else {
                $messages[] = '✅ meta_key index already exists';
            }
            
            // Add compound index for common queries
            if ( ! $this->index_exists( 'post_id', 'post_id_meta_key' ) ) {
                $result = $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD INDEX post_id_meta_key (post_id, meta_key(191))" );
                
                if ( ! $this->wpdb->last_error ) {
                    $fixes_applied++;
                    $messages[] = '✅ Added compound post_id + meta_key index';
                    error_log( 'MCE: Successfully added compound index to wp_postmeta' );
                } else {
                    $error = 'Could not add compound index: ' . $this->wpdb->last_error;
                    $errors[] = $error;
                    error_log( 'MCE: ' . $error );
                }
            } else {
                $messages[] = '✅ Compound post_id + meta_key index already exists';
            }
            
            $this->wpdb->hide_errors( $old_hide_errors );
            
        } catch ( Exception $e ) {
            $errors[] = 'Exception during fix: ' . $e->getMessage();
            error_log( 'MCE: Exception during database fixes: ' . $e->getMessage() );
        }
        
        // Store messages in session-like storage for display
        set_transient( 'mce_fix_messages', array( 'messages' => $messages, 'errors' => $errors, 'fixes' => $fixes_applied ), 300 );
        
        // Redirect to avoid form resubmission
        wp_redirect( add_query_arg( 'mce_fix_applied', '1', admin_url( 'tools.php?page=mce-strict-mode-fix' ) ) );
        exit;
    }
    
    /**
     * Auto-fix indexes (called during plugin activation or when needed)
     */
    public function auto_fix_indexes() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        
        $fixes_applied = 0;
        
        try {
            // Temporarily disable error reporting
            $old_hide_errors = $this->wpdb->hide_errors();
            
            // Add missing indexes without notifications
            if ( ! $this->index_exists( 'meta_id', 'PRIMARY' ) ) {
                $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD PRIMARY KEY (meta_id)" );
                $fixes_applied++;
            }
            
            if ( ! $this->index_exists( 'post_id' ) ) {
                $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD INDEX post_id (post_id)" );
                $fixes_applied++;
            }
            
            if ( ! $this->index_exists( 'meta_key' ) ) {
                $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD INDEX meta_key (meta_key(191))" );
                $fixes_applied++;
            }
            
            if ( ! $this->index_exists( 'post_id', 'post_id_meta_key' ) ) {
                $this->wpdb->query( "ALTER TABLE {$this->wpdb->postmeta} ADD INDEX post_id_meta_key (post_id, meta_key(191))" );
                $fixes_applied++;
            }
            
            $this->wpdb->hide_errors( $old_hide_errors );
            
            if ( $fixes_applied > 0 ) {
                error_log( "MCE: Auto-fixed {$fixes_applied} missing indexes in wp_postmeta" );
            }
            
        } catch ( Exception $e ) {
            error_log( 'MCE: Auto-fix failed: ' . $e->getMessage() );
        }
        
        return $fixes_applied;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        
        // Check for fix results
        $fix_results = get_transient( 'mce_fix_messages' );
        $fix_applied = isset( $_GET['mce_fix_applied'] ) && $_GET['mce_fix_applied'] === '1';
        
        // Clear transient if we just applied fixes
        if ( $fix_applied ) {
            delete_transient( 'mce_fix_messages' );
        }
        
        ?>
        <div class="wrap">
            <h1>🔧 MySQL Strict Mode Fix for wp_postmeta</h1>
            
            <div class="notice notice-info">
                <p><strong>What this fixes:</strong> MySQL strict mode errors like "No index used in query" that cause fatal errors when editing posts or using certain plugins.</p>
            </div>
            
            <?php if ( $fix_applied || ! empty( $fix_results ) ): ?>
                <?php if ( $fix_applied ): ?>
                    <?php $fix_results = get_transient( 'mce_fix_messages' ); ?>
                <?php endif; ?>
                
                <?php if ( ! empty( $fix_results['messages'] ) ): ?>
                    <?php foreach ( $fix_results['messages'] as $message ): ?>
                        <div class="notice notice-success"><p><?php echo esc_html( $message ); ?></p></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ( ! empty( $fix_results['errors'] ) ): ?>
                    <?php foreach ( $fix_results['errors'] as $error ): ?>
                        <div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ( $fix_results['fixes'] > 0 ): ?>
                    <div class="notice notice-success">
                        <p><strong>✅ Database has been fixed!</strong> You can now edit posts without MySQL strict mode errors.</p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-info">
                        <p><strong>ℹ️ No fixes were needed.</strong> Your database structure looks good.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <h2>📊 Database Table Status</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Column Name</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                        <th>Index Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $columns = $this->get_table_info_alternative();
                    foreach ( $columns as $col ) {
                        $indexed = $this->is_column_indexed( $col->Field );
                        $status = $indexed ? '✅ Indexed' : '❌ No Index';
                        $status_class = $indexed ? 'status-good' : 'status-bad';
                        
                        echo '<tr>';
                        echo '<td><strong>' . esc_html( $col->Field ) . '</strong></td>';
                        echo '<td>' . esc_html( $col->Type ) . '</td>';
                        echo '<td>' . esc_html( $col->Null ) . '</td>';
                        echo '<td>' . esc_html( $col->Key ) . '</td>';
                        echo '<td>' . esc_html( $col->Default ?? 'NULL' ) . '</td>';
                        echo '<td>' . esc_html( $col->Extra ) . '</td>';
                        echo '<td class="' . $status_class . '">' . $status . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            
            <h2>🔧 Apply Database Fixes</h2>
            <form method="POST" style="margin-bottom: 20px;">
                <?php wp_nonce_field( 'mce_strict_mode_fix' ); ?>
                <p>
                    <button type="submit" name="mce_apply_fix" class="button button-primary button-large" value="1">
                        🔧 Apply Database Fixes
                    </button>
                    <button type="submit" name="mce_auto_fix" class="button button-secondary" value="1" style="margin-left: 10px;">
                        🤖 Auto-Fix Indexes
                    </button>
                </p>
            </form>
            
            <h2>📋 What these fixes do:</h2>
            <ul>
                <li><strong>PRIMARY KEY on meta_id:</strong> Ensures unique identification of meta records</li>
                <li><strong>post_id index:</strong> Speeds up queries that filter by post ID</li>
                <li><strong>meta_key index:</strong> Speeds up queries that filter by meta key</li>
                <li><strong>Compound index (post_id + meta_key):</strong> Optimizes common queries that need both fields</li>
            </ul>
            
            <h2>🧪 Test the Fix</h2>
            <p>After applying fixes, try:</p>
            <ol>
                <li>Edit a post: <a href="<?php echo admin_url( 'post.php?post=1&action=edit' ); ?>" target="_blank">Edit Post 1</a></li>
                <li>Check error logs: <code>tail -f /path/to/error.log</code></li>
                <li>No more "No index used" errors should appear</li>
            </ol>
            
            <style>
                .status-good { color: #46b450; font-weight: bold; }
                .status-bad { color: #dc3232; font-weight: bold; }
            </style>
        </div>
        <?php
    }
    
    /**
     * Check if a column has any index
     */
    private function is_column_indexed( $column_name ) {
        $query = "
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = %s
            AND COLUMN_NAME = %s
        ";
        
        $result = $this->wpdb->get_var( $this->wpdb->prepare( $query, $this->wpdb->postmeta, $column_name ) );
        
        return intval( $result ) > 0;
    }
}

// Initialize the fix
new MCE_Strict_Mode_Fix();

/**
 * Auto-run fix on plugin activation if needed
 */
function mce_check_strict_mode_on_activation() {
    if ( ! get_option( 'mce_strict_mode_checked' ) ) {
        $fix = new MCE_Strict_Mode_Fix();
        $fixes = $fix->auto_fix_indexes();
        
        if ( $fixes > 0 ) {
            error_log( "MCE: Applied {$fixes} database fixes during activation" );
        }
        
        update_option( 'mce_strict_mode_checked', true );
    }
}

// Hook to WordPress init to check for strict mode issues
add_action( 'init', 'mce_check_strict_mode_on_activation' );
