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
 *
 * *** ¡REFRACTORIZADO! ***
 * Esta clase ahora recibe sus dependencias (las páginas)
 * en el constructor para evitar errores de carga.
 */
class MCE_Admin_Loader {

	private $settings_page;
	private $query_page;
	private $help_page;
	private $css_page;

	/**
	 * Constructor. Carga las dependencias y se engancha.
	 *
	 * @param MCE_Query_Page    $query_page
	 * @param MCE_Settings_Page $settings_page
	 * @param MCE_Help_Page     $help_page
	 * @param MCE_CSS_Page      $css_page
	 */
	public function __construct( $query_page, $settings_page, $help_page, $css_page ) {
		// 1. Guardar las dependencias (ya no las carga)
		$this->query_page    = $query_page;
		$this->settings_page = $settings_page;
		$this->help_page     = $help_page;
		$this->css_page      = $css_page;

		// 2. Engancharse al hook del menú
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
	}

	/**
	 * Registra todas las páginas del menú del plugin.
	 * (El código aquí es el mismo, pero usa las propiedades $this->...)
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
			'mce-main-menu',
			array( $this->query_page, 'create_query_page_content' )
		);

		// 3. Añadir la página de "Ajustes"
		add_submenu_page(
			'mce-main-menu',
			__( 'Ajustes de Conexión', 'mi-conexion-externa' ),
			__( 'Ajustes', 'mi-conexion-externa' ),
			'manage_options',
			'mce-settings',
			array( $this->settings_page, 'create_settings_page_content' )
		);

		// 4. Añadir la nueva página de "Estilos CSS"
		add_submenu_page(
			'mce-main-menu',
			__( 'Estilos CSS', 'mi-conexion-externa' ),
			__( 'Estilos CSS', 'mi-conexion-externa' ),
			'manage_options',
			'mce-css',
			array( $this->css_page, 'render_page_content' )
		);

		// 5. Añadir la página de "Ayuda" (movida al final)
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