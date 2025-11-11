<?php
/**
 * CoreAura: Conexión Externa - Enhanced Database Handler
 * 
 * Enhanced version with MySQL strict mode compatibility and robust error handling
 * 
 * @package CoreAura_Conexion_Externa
 * @version 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MCE_DB_Handler {
    
    private $connection = null;
    private $host;
    private $user;
    private $password;
    private $database;
    private $port;
    private $strict_mode_fallback = false;
    
    public function __construct() {
        $this->host = get_option('mce_db_host', '');
        $this->user = get_option('mce_db_user', '');
        $this->password = get_option('mce_db_pass', '');
        $this->database = get_option('mce_db_name', '');
        $this->port = get_option('mce_db_port', '3306');
        
        // Check for strict mode issues
        add_action('init', array($this, 'check_strict_mode_compatibility'));
    }
    
    /**
     * Check for MySQL strict mode compatibility and apply fixes if needed
     */
    public function check_strict_mode_compatibility() {
        // Only run once per request
        static $checked = false;
        if ($checked) return;
        $checked = true;
        
        if (!class_exists('MCE_Strict_Mode_Fix')) {
            // Include the strict mode fix class
            if (file_exists(MCE_PLUGIN_DIR . 'admin/class-mce-strict-mode-fix.php')) {
                require_once MCE_PLUGIN_DIR . 'admin/class-mce-strict-mode-fix.php';
            }
        }
        
        // Add global error handler for MySQL strict mode errors
        if (!has_action('wp_die_handler')) {
            add_action('wp_die_handler', array($this, 'custom_wp_die_handler'));
        }
    }
    
    /**
     * Custom error handler to catch MySQL strict mode errors
     */
    public function custom_wp_die_handler($message) {
        // Check if this is a MySQL strict mode error
        if (strpos($message, 'No index used in query') !== false || 
            strpos($message, 'mysqli_sql_exception') !== false) {
            
            error_log('MCE: Caught MySQL strict mode error: ' . $message);
            
            // Try to auto-fix the issue
            $this->attempt_strict_mode_fix();
            
            // Show a user-friendly message
            return '<div class="notice notice-warning"><p>
                <strong>MySQL Strict Mode Issue Detected</strong><br>
                The system is working to fix database performance issues. Please try again in a moment.
            </p></div>';
        }
        
        return $message;
    }
    
    /**
     * Attempt to fix strict mode issues automatically
     */
    private function attempt_strict_mode_fix() {
        if (class_exists('MCE_Strict_Mode_Fix')) {
            try {
                $fix = new MCE_Strict_Mode_Fix();
                $fixes_applied = $fix->auto_fix_indexes();
                
                if ($fixes_applied > 0) {
                    error_log("MCE: Auto-applied {$fixes_applied} database fixes");
                }
            } catch (Exception $e) {
                error_log('MCE: Auto-fix failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Enhanced connection method with strict mode handling
     */
    private function connect() {
        // If we already have a valid connection, return it
        if ($this->connection !== null && $this->connection instanceof \mysqli) {
            // Test if connection is still alive
            if (@$this->connection->ping()) {
                return $this->connection;
            } else {
                // Connection is dead, close it
                $this->close();
            }
        }
        
        if (empty($this->host) || empty($this->user) || empty($this->database)) {
            $this->connection = false;
            return false;
        }

        // Disable mysqli error reporting to prevent fatal errors
        mysqli_report(MYSQLI_REPORT_OFF);

        try {
            $conn = @new \mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database,
                $this->port
            );

            // Restore error reporting
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            // Validate connection
            if ($conn->connect_errno) {
                $error = $conn->connect_error;
                error_log('MCE DB Connection Error: ' . $error);
                $this->connection = false;
                return false;
            }

            // Set charset and connection options for better compatibility
            $conn->set_charset('utf8mb4');
            
            // Set timeout to prevent hanging
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
            $conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);
            
            // Enable local infile for potential data import/export
            @$conn->query("SET GLOBAL local_infile = 1");

            $this->connection = $conn;
            return $this->connection;
            
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE DB Connection Exception: ' . $e->getMessage());
            $this->connection = false;
            return false;
        }
    }
    
    /**
     * Enhanced query method with strict mode error handling
     */
    public function safe_query($query, $params = array()) {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            // Disable strict mode reporting for this query
            mysqli_report(MYSQLI_REPORT_OFF);
            
            // Prepare statement if params provided
            if (!empty($params)) {
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $types = str_repeat('s', count($params)); // Default to string types
                    $stmt->bind_param($types, ...$params);
                    $result = $stmt->execute();
                    $stmt->close();
                } else {
                    $result = $conn->query($query);
                }
            } else {
                $result = $conn->query($query);
            }
            
            // Restore error reporting
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Check for errors
            if ($conn->error) {
                error_log('MCE Query Error: ' . $conn->error . ' | Query: ' . $query);
                
                // If this is a strict mode error, try to fix it
                if (strpos($conn->error, 'No index used') !== false) {
                    $this->attempt_strict_mode_fix();
                }
                
                return false;
            }
            
            return $result;
            
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE Query Exception: ' . $e->getMessage() . ' | Query: ' . $query);
            return false;
        }
    }
    
    /**
     * Enhanced get_results with strict mode compatibility
     */
    public function get_results_safe($query, $params = array()) {
        $result = $this->safe_query($query, $params);
        
        if (!$result) {
            return false;
        }
        
        if (is_bool($result)) {
            return $result;
        }
        
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $result->close();
        return $data;
    }
    
    /**
     * Enhanced get_var with strict mode compatibility
     */
    public function get_var_safe($query, $params = array()) {
        $result = $this->safe_query($query, $params);
        
        if (!$result || is_bool($result)) {
            return false;
        }
        
        $row = $result->fetch_row();
        $result->close();
        
        return $row ? $row[0] : null;
    }
    
    /**
     * Closes database connection safely
     */
    public function close() {
        if (is_object($this->connection) && method_exists($this->connection, 'close')) {
            try {
                $this->connection->close();
            } catch (Exception $e) {
                // Ignore close errors
            }
        }
        $this->connection = null;
    }
    
    /**
     * Tests database connection
     */
    public function test_connection() {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            $result = $conn->query("SELECT 1");
            if ($result) {
                $result->close();
                return true;
            }
        } catch (Exception $e) {
            error_log('MCE Connection test failed: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Gets list of tables using safe query method
     */
    public function obtener_tablas() {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $query = "SHOW TABLES";
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE obtener_tablas Error: ' . $conn->error);
                return false;
            }
            
            $tablas = array();
            while ($row = $result->fetch_array()) {
                $tablas[] = $row[0];
            }
            
            return $tablas;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE obtener_tablas Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets columns of a table using safe method
     */
    public function obtener_columnas_tabla($tabla) {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $tabla = $conn->real_escape_string($tabla);
            $query = "SHOW COLUMNS FROM `{$tabla}`";
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE obtener_columnas_tabla Error: ' . $conn->error);
                return false;
            }
            
            $columnas = array();
            while ($row = $result->fetch_assoc()) {
                $columnas[] = $row['Field'];
            }
            
            return $columnas;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE obtener_columnas_tabla Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Counts records in a table with safe error handling
     */
    public function contar_registros($tabla, $where = '') {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $tabla = $conn->real_escape_string($tabla);
            $query = "SELECT COUNT(*) as total FROM `{$tabla}`";
            
            if (!empty($where)) {
                $query .= " WHERE {$where}";
            }
            
            error_log('MCE contar_registros Query: ' . $query);
            
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE contar_registros Error: ' . $conn->error);
                return false;
            }
            
            $row = $result->fetch_assoc();
            $result->close();
            return intval($row['total']);
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE contar_registros Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets table data with pagination and safe error handling
     */
    public function obtener_datos($tabla, $columnas = '*', $where = '', $orden = '', $direccion = 'ASC', $limite = 10, $offset = 0) {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $tabla = $conn->real_escape_string($tabla);
            
            // Build columns safely
            if ($columnas === '*') {
                $columnas_query = '*';
            } else {
                $cols = array_map('trim', explode(',', $columnas));
                $cols_escaped = array();
                foreach ($cols as $col) {
                    $cols_escaped[] = '`' . $conn->real_escape_string($col) . '`';
                }
                $columnas_query = implode(', ', $cols_escaped);
            }
            
            $query = "SELECT {$columnas_query} FROM `{$tabla}`";
            
            if (!empty($where)) {
                $query .= " WHERE {$where}";
            }
            
            if (!empty($orden)) {
                $orden = $conn->real_escape_string($orden);
                $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';
                $query .= " ORDER BY `{$orden}` {$direccion}";
            }
            
            $limite = intval($limite);
            $offset = intval($offset);
            $query .= " LIMIT {$limite} OFFSET {$offset}";
            
            error_log('MCE obtener_datos Query: ' . $query);
            
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE obtener_datos Error: ' . $conn->error);
                return false;
            }
            
            $datos = array();
            while ($row = $result->fetch_assoc()) {
                $datos[] = $row;
            }
            
            $result->close();
            return $datos;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE obtener_datos Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets unique values from a column for filters
     */
    public function obtener_valores_unicos($tabla, $columna, $where = '') {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $tabla = $conn->real_escape_string($tabla);
            $columna = $conn->real_escape_string($columna);
            
            $query = "SELECT DISTINCT `{$columna}` FROM `{$tabla}`";
            $where_parts = array();
            
            if (!empty($where)) {
                $where_parts[] = "({$where})";
            }
            
            // Add condition for non-null/non-empty values
            $where_parts[] = "`{$columna}` IS NOT NULL";
            $where_parts[] = "`{$columna}` != ''";
            
            if (!empty($where_parts)) {
                $query .= " WHERE " . implode(' AND ', $where_parts);
            }
            
            $query .= " ORDER BY `{$columna}` ASC";
            
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE obtener_valores_unicos Error: ' . $conn->error);
                return false;
            }
            
            $valores = array();
            while ($row = $result->fetch_assoc()) {
                $valores[] = $row[$columna];
            }
            
            $result->close();
            return $valores;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE obtener_valores_unicos Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Checks if a table exists
     */
    public function tabla_existe($tabla) {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }

        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $tabla = $conn->real_escape_string($tabla);
            $query = "SHOW TABLES LIKE '{$tabla}'";
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                return false;
            }
            
            $exists = $result->num_rows > 0;
            $result->close();
            return $exists;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE tabla_existe Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets detailed column information safely
     */
    public function obtener_info_columnas($tabla) {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }
        
        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $tabla = $conn->real_escape_string($tabla);
            $query = "SHOW COLUMNS FROM `{$tabla}`";
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE obtener_info_columnas Error: ' . $conn->error);
                return false;
            }
            
            $columnas = array();
            while ($row = $result->fetch_assoc()) {
                $columnas[] = array(
                    'field' => $row['Field'],
                    'type' => $row['Type'],
                    'null' => $row['Null'],
                    'key' => $row['Key'],
                    'default' => $row['Default'],
                    'extra' => $row['Extra']
                );
            }
            
            $result->close();
            return $columnas;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE obtener_info_columnas Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executes custom query with safety checks
     */
    public function ejecutar_query($query) {
        $conn = $this->connect();
        if (!$conn) {
            return false;
        }

        try {
            // Security check: only allow SELECT queries
            $query_limpia = trim(strtoupper($query));
            if (strpos($query_limpia, 'SELECT') !== 0) {
                error_log('MCE Security: Only SELECT queries are allowed');
                return false;
            }
            
            mysqli_report(MYSQLI_REPORT_OFF);
            $result = $conn->query($query);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            if (!$result) {
                error_log('MCE ejecutar_query Error: ' . $conn->error);
                return false;
            }
            
            $datos = array();
            while ($row = $result->fetch_assoc()) {
                $datos[] = $row;
            }
            
            $result->close();
            return $datos;
        } catch (\Throwable $e) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            error_log('MCE ejecutar_query Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets paginated table data
     */
    public function get_paginated_table_data($tabla, $limite = 10, $pagina = 1) {
        if (!$this->tabla_existe($tabla)) {
            return new \WP_Error('tabla_no_existe', sprintf('La tabla "%s" no existe.', $tabla));
        }

        $offset = ($pagina - 1) * $limite;
        if ($offset < 0) {
            $offset = 0;
        }

        $total_rows = $this->contar_registros($tabla);
        if ($total_rows === false) {
            return new \WP_Error('error_count', 'Error al contar registros.');
        }

        $datos = $this->obtener_datos($tabla, '*', '', '', 'ASC', $limite, $offset);
        if ($datos === false) {
            return new \WP_Error('error_fetch', 'Error al obtener datos.');
        }

        return array(
            'data' => $datos,
            'total_rows' => $total_rows,
            'page' => $pagina,
            'per_page' => $limite
        );
    }
    
    /**
     * Gets custom styles
     */
    public function obtener_estilos_personalizados() {
        if (!function_exists('get_option')) {
            return array();
        }
        $defaults = array(
            'card_bg_color' => '#ffffff',
            'card_border_color' => '#e1e1e1',
            'card_border_radius' => 4,
            'card_shadow' => 'none',
            'title_color' => '#222222',
            'title_size' => 16,
            'text_color' => '#333333',
            'text_size' => 14,
            'label_color' => '#666666',
            'label_size' => 12,
        );
        $stored = get_option('mce_custom_styles', array());
        if (!is_array($stored)) {
            $stored = array();
        }
        return wp_parse_args($stored, $defaults);
    }
    
    /**
     * Cleanup on destruct
     */
    public function __destruct() {
        $this->close();
    }
    
    /**
     * BACKWARD COMPATIBILITY: Alias for obtener_tablas() method
     * Ensures existing code continues to work with enhanced database handler
     */
    public function get_tables() {
        return $this->obtener_tablas();
    }
    
    /**
     * BACKWARD COMPATIBILITY: Alias for obtener_columnas_tabla() method
     * Ensures existing code continues to work with enhanced database handler
     */
    public function get_table_columns($tabla) {
        return $this->obtener_columnas_tabla($tabla);
    }
    
    /**
     * BACKWARD COMPATIBILITY: Enhanced get_table_content with error handling
     * This method existed in the original implementation
     */
    public function get_table_content($tabla, $limit = 100) {
        // Check if table exists
        if (!$this->tabla_existe($tabla)) {
            error_log('MCE Error: Tabla no existe: ' . $tabla);
            return false;
        }
        
        // Get data from table using enhanced method
        return $this->obtener_datos($tabla, '*', '', '', 'ASC', $limit, 0);
    }
    
    /**
     * Escape string for SQL queries
     * Used by AJAX handlers for safe query building
     */
    public function escape_string($string) {
        $conn = $this->connect();
        if (!$conn) {
            return '';
        }
        
        return $conn->real_escape_string($string);
    }
}
