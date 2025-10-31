<?php
/**
 * Cargador Principal del Admin.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Admin_Loader
 *
 * Instancia las otras clases de Admin y registra el menú.
 */
class MCE_Admin_Loader {

	/**
	 * Instancia de la página de Ajustes.
	 *
	 * @var MCE_Settings_Page
	 */
	private $settings_page;

	/**
	 * Instancia de la página de Explorador.
	 *
	 * @var MCE_Query_Page
	 */
	private $query_page;

	/**
	 * Instancia de la página de Ayuda.
	 *
	 * @var MCE_Help_Page
	 */
	private $help_page;

	/**
	 * Constructor. Carga las dependencias y se engancha.
	 */
	public function __construct() {
		// 1. Cargar los archivos de clases
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-query-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-help-page.php';

		// 2. Instanciar las clases
		$this->settings_page = new MCE_Settings_Page();
		$this->query_page    = new MCE_Query_Page();
		$this->help_page     = new MCE_Help_Page();

		// 3. Engancharse al hook del menú
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
	}

	/**
	 * Registra todas las páginas del menú del plugin.
	 */
	public function register_admin_pages() {
		// 1. Añadir el Menú de Nivel Superior
		add_menu_page(
			__( 'Conexión Externa', 'mi-conexion-externa' ),
			__( 'Conexión Externa', 'mi-conexion-externa' ),
			'manage_options',
			'mce-main-menu',
			array( $this->query_page, 'create_query_page_content' ), // Página por defecto: Explorador
			'dashicons-database-view',
			20
		);

		// 2. Añadir la página de "Explorador"
		add_submenu_page(
			'mce-main-menu',
			__( 'Explorador de BBDD', 'mi-conexion-externa' ),
			__( 'Explorador', 'mi-conexion-externa' ),
			'manage_options',
			'mce-main-menu', // Slug (el mismo que el padre para que sea la pág. por defecto)
			array( $this->query_page, 'create_query_page_content' )
		);

		// 3. Añadir la página de "Ajustes"
		add_submenu_page(
			'mce-main-menu',
			__( 'Ajustes de Conexión', 'mi-conexion-externa' ),
			__( 'Ajustes', 'mi-conexion-externa' ),
			'manage_options',
			'mce-settings', // Slug único para esta página
			array( $this->settings_page, 'create_settings_page_content' )
		);

		// 4. Añadir la nueva página de "Ayuda"
		add_submenu_page(
			'mce-main-menu',
			__( 'Ayuda y Guía de Uso', 'mi-conexion-externa' ),
			__( 'Ayuda', 'mi-conexion-externa' ),
			'manage_options',
			'mce-help', // Slug único
			array( $this->help_page, 'render_page_content' )
		);
	}
}