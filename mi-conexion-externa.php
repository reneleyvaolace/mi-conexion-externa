<?php
/**
 * Plugin Name: CoreAura: Conexión Externa
 * Plugin URI: https://coreaura.com/plugins/conexion-externa
 * Description: Plugin profesional para conectar bases de datos externas MySQL/MariaDB y mostrar información con grids personalizables, paginación AJAX y panel de estilos visual.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: CoreAura
 * Author URI: https://coreaura.com
 * License: Proprietary
 * License URI: https://coreaura.com/licencia
 * Text Domain: mi-conexion-externa
 * Domain Path: /languages
 * 
 * @package MiConexionExterna
 * @author CoreAura
 * @copyright 2025 CoreAura
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente
}

// ===================================
// CONSTANTES DEL PLUGIN
// ===================================

define( 'MCE_VERSION', '1.0.0' );
define( 'MCE_PLUGIN_FILE', __FILE__ );
define( 'MCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MCE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ===================================
// SISTEMA DE ACTUALIZACIONES (GitHub)
// ===================================

if ( file_exists( MCE_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php' ) ) {
    require_once MCE_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
    
    // La declaración use debe ir justo después del require
    $mceUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/TU-USUARIO/coreaura-conexion-externa/', // Cambia TU-USUARIO por tu usuario de GitHub
        __FILE__,
        'mi-conexion-externa'
    );
    
    // Si tu repositorio es PRIVADO, descomenta la siguiente línea y añade tu token personal
    // $mceUpdateChecker->setAuthentication('ghp_tu_token_personal_aqui');
    
    // Habilitar el uso de releases de GitHub (recomendado)
    $mceUpdateChecker->getVcsApi()->enableReleaseAssets();
    
    // Opcional: Añadir filtros de query args (para licencias, site tracking, etc.)
    // $mceUpdateChecker->addQueryArgFilter(function($queryArgs) {
    //     $queryArgs['license_key'] = get_option('mce_license_key', '');
    //     $queryArgs['site_url'] = get_site_url();
    //     return $queryArgs;
    // });
}

// ===================================
// CARGA DE TRADUCCIONES
// ===================================

add_action( 'plugins_loaded', 'mce_load_textdomain' );
function mce_load_textdomain() {
    load_plugin_textdomain( 
        'mi-conexion-externa', 
        false, 
        dirname( MCE_PLUGIN_BASENAME ) . '/languages' 
    );
}

// ===================================
// INCLUDES PRINCIPALES
// ===================================

// Cargar el manejador de base de datos
require_once MCE_PLUGIN_DIR . 'includes/class-mce-db-handler.php';

// Cargar shortcodes
require_once MCE_PLUGIN_DIR . 'includes/mce-shortcodes.php';

// ===================================
// ADMIN: CARGAR SOLO EN BACKEND
// ===================================

if ( is_admin() ) {
    
    // Cargar páginas del admin
    require_once MCE_PLUGIN_DIR . 'admin/class-mce-query-page.php';
    require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';
    require_once MCE_PLUGIN_DIR . 'admin/class-mce-help-page.php';
    require_once MCE_PLUGIN_DIR . 'admin/class-mce-style-settings-page.php';
    require_once MCE_PLUGIN_DIR . 'admin/class-mce-admin-loader.php';
    
    // Inicializar páginas
    $mce_query_page = new MCE_Query_Page();
    $mce_settings_page = new MCE_Settings_Page();
    $mce_help_page = new MCE_Help_Page();
    
    // Inicializar el cargador del admin (que carga el menú principal)
    $mce_admin_loader = new MCE_Admin_Loader( 
        $mce_query_page, 
        $mce_settings_page, 
        $mce_help_page 
    );
    
    // Enlace a ajustes en la lista de plugins
    add_filter( 'plugin_action_links_' . MCE_PLUGIN_BASENAME, 'mce_add_action_links' );
    function mce_add_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=mce-settings' ) . '">' . __( 'Ajustes', 'mi-conexion-externa' ) . '</a>';
        $help_link = '<a href="' . admin_url( 'admin.php?page=mce-help' ) . '">' . __( 'Ayuda', 'mi-conexion-externa' ) . '</a>';
        array_unshift( $links, $settings_link, $help_link );
        return $links;
    }
    
    // Agregar meta links (documentación, soporte)
    add_filter( 'plugin_row_meta', 'mce_add_row_meta', 10, 2 );
    function mce_add_row_meta( $links, $file ) {
        if ( MCE_PLUGIN_BASENAME === $file ) {
            $new_links = array(
                'docs' => '<a href="https://coreaura.com/docs/conexion-externa" target="_blank">' . __( 'Documentación', 'mi-conexion-externa' ) . '</a>',
                'support' => '<a href="https://coreaura.com/soporte" target="_blank">' . __( 'Soporte', 'mi-conexion-externa' ) . '</a>',
            );
            $links = array_merge( $links, $new_links );
        }
        return $links;
    }
}

// ===================================
// FRONTEND: CARGAR ASSETS PÚBLICOS
// ===================================

add_action( 'wp_enqueue_scripts', 'mce_enqueue_public_assets' );
function mce_enqueue_public_assets() {
    
    // CSS público
    wp_register_style( 
        'mce-public-style', 
        MCE_PLUGIN_URL . 'public/css/mce-public-style.css', 
        array(), 
        MCE_VERSION 
    );
    
    // JS público (con dependencias de jQuery)
    wp_register_script( 
        'mce-public-script', 
        MCE_PLUGIN_URL . 'public/js/mce-public-script.js', 
        array( 'jquery' ), 
        MCE_VERSION, 
        true 
    );
    
    // Pasar datos AJAX al script público
    wp_localize_script( 
        'mce-public-script', 
        'mce_ajax_object', 
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mce_ajax_nonce' )
        )
    );
}

// ===================================
// ACTIVACIÓN DEL PLUGIN
// ===================================

register_activation_hook( __FILE__, 'mce_activate_plugin' );
function mce_activate_plugin() {
    
    // Verificar versión mínima de WordPress
    if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
        deactivate_plugins( MCE_PLUGIN_BASENAME );
        wp_die( 
            __( 'Este plugin requiere WordPress 6.0 o superior.', 'mi-conexion-externa' ),
            __( 'Error de Activación', 'mi-conexion-externa' ),
            array( 'back_link' => true )
        );
    }
    
    // Verificar versión mínima de PHP
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        deactivate_plugins( MCE_PLUGIN_BASENAME );
        wp_die( 
            __( 'Este plugin requiere PHP 7.4 o superior.', 'mi-conexion-externa' ),
            __( 'Error de Activación', 'mi-conexion-externa' ),
            array( 'back_link' => true )
        );
    }
    
    // Verificar extensión MySQLi
    if ( ! extension_loaded( 'mysqli' ) ) {
        deactivate_plugins( MCE_PLUGIN_BASENAME );
        wp_die( 
            __( 'Este plugin requiere la extensión MySQLi de PHP.', 'mi-conexion-externa' ),
            __( 'Error de Activación', 'mi-conexion-externa' ),
            array( 'back_link' => true )
        );
    }
    
    // Establecer valores por defecto si no existen
    if ( false === get_option( 'mce_db_port' ) ) {
        add_option( 'mce_db_port', '3306' );
    }
    
    // Guardar versión para futuras migraciones
    update_option( 'mce_plugin_version', MCE_VERSION );
    
    // Flush rewrite rules (por si se añaden CPT en futuro)
    flush_rewrite_rules();
}

// ===================================
// DESACTIVACIÓN DEL PLUGIN
// ===================================

register_deactivation_hook( __FILE__, 'mce_deactivate_plugin' );
function mce_deactivate_plugin() {
    
    // Limpiar transients
    delete_transient( 'mce_tables_cache' );
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// ===================================
// NOTIFICACIÓN DE VERSIÓN (ADMIN)
// ===================================

add_action( 'admin_notices', 'mce_admin_notice_version' );
function mce_admin_notice_version() {
    
    $current_version = get_option( 'mce_plugin_version' );
    
    // Si es una instalación nueva o actualización
    if ( version_compare( $current_version, MCE_VERSION, '<' ) ) {
        
        // Solo mostrar a administradores
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Solo mostrar en páginas del plugin
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'mce' ) === false ) {
            return;
        }
        
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php echo esc_html( sprintf( __( 'CoreAura: Conexión Externa actualizado a la versión %s', 'mi-conexion-externa' ), MCE_VERSION ) ); ?></strong><br>
                <?php echo esc_html( __( 'Revisa la sección de Ayuda para conocer las nuevas funcionalidades.', 'mi-conexion-externa' ) ); ?>
            </p>
        </div>
        <?php
        
        // Actualizar versión guardada
        update_option( 'mce_plugin_version', MCE_VERSION );
    }
}

// ===================================
// DEBUG MODE (OPCIONAL)
// ===================================

// Para activar el modo debug, define esta constante en wp-config.php:
// define('MCE_DEBUG', true);

if ( defined( 'MCE_DEBUG' ) && MCE_DEBUG ) {
    add_action( 'init', function() {
        error_log( '[MCE] Plugin inicializado - Versión: ' . MCE_VERSION );
    });
}
