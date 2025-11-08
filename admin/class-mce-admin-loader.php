<?php
/**
 * Cargador Principal del Admin.
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase MCE_Admin_Loader
 */
class MCE_Admin_Loader {

    private $settings_page;
    private $query_page;
    private $help_page;

    /**
     * Constructor.
     */
    public function __construct( $query_page, $settings_page, $help_page ) {
        $this->query_page    = $query_page;
        $this->settings_page = $settings_page;
        $this->help_page     = $help_page;

        add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );

        // INCLUYE e INICIALIZA SOLO UNA VEZ la clase de Estilo
        require_once plugin_dir_path( __FILE__ ) . 'class-mce-style-settings-page.php';
        new MCE_Style_Settings_Page();
    }

    /**
     * Registra todas las páginas del menú del plugin.
     */
    public function register_admin_pages() {
        add_menu_page(
            __( 'CoreAura Conexión', 'mi-conexion-externa' ),
            __( 'CoreAura Conexión', 'mi-conexion-externa' ),
            'manage_options',
            'mce-main-menu',
            array( $this->query_page, 'create_query_page_content' ),
            'dashicons-database-view',
            20
        );

        add_submenu_page(
            'mce-main-menu',
            __( 'Explorador de BBDD', 'mi-conexion-externa' ),
            __( 'Explorador', 'mi-conexion-externa' ),
            'manage_options',
            'mce-main-menu',
            array( $this->query_page, 'create_query_page_content' )
        );

        add_submenu_page(
            'mce-main-menu',
            __( 'Ajustes de Conexión', 'mi-conexion-externa' ),
            __( 'Ajustes', 'mi-conexion-externa' ),
            'manage_options',
            'mce-settings',
            array( $this->settings_page, 'create_settings_page_content' )
        );

        // NO AGREGUES manualmente el submenú de Estilo aquí

        add_submenu_page(
            'mce-main-menu',
            __( 'Ayuda y Guía de Uso', 'mi-conexion-externa' ),
            __( 'Ayuda', 'mi-conexion-externa' ),
            'manage_options',
            'mce-help',
            array( $this->help_page, 'render_page_content' )
        );
    }
}
