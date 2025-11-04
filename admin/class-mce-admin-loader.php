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
 *
 * *** ¡LIMPIADO! ***
 * Ya no carga la clase 'mce-css-page'.
 */
class MCE_Admin_Loader {

	private $settings_page;
	private $query_page;
	private $help_page;
	// private $css_page; // Eliminado

	/**
	 * Constructor.
	 */
	public function __construct( $query_page, $settings_page, $help_page ) {
		// 1. Guardar las dependencias
		$this->query_page    = $query_page;
		$this->settings_page = $settings_page;
		$this->help_page     = $help_page;
		// $this->css_page      = $css_page; // Eliminado

		// 2. Engancharse al hook del menú
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
	}

	/**
	 * Registra todas las páginas del menú del plugin.
	 */
	public function register_admin_pages() {
		// 1. Menú Principal
		add_menu_page(
			__( 'Conexión Externa', 'mi-conexion-externa' ),
			__( 'Conexión Externa', 'mi-conexion-externa' ),
			'manage_options',
			'mce-main-menu',
			array( $this->query_page, 'create_query_page_content' ),
			'dashicons-database-view',
			20
		);

		// 2. Submenú "Explorador"
		add_submenu_page(
			'mce-main-menu',
			__( 'Explorador de BBDD', 'mi-conexion-externa' ),
			__( 'Explorador', 'mi-conexion-externa' ),
			'manage_options',
			'mce-main-menu',
			array( $this->query_page, 'create_query_page_content' )
		);

		// 3. Submenú "Ajustes"
		add_submenu_page(
			'mce-main-menu',
			__( 'Ajustes de Conexión', 'mi-conexion-externa' ),
			__( 'Ajustes', 'mi-conexion-externa' ),
			'manage_options',
			'mce-settings',
			array( $this->settings_page, 'create_settings_page_content' )
		);

		// 4. *** ¡SUBMENÚ DE CSS ELIMINADO! ***

		// 5. Submenú "Ayuda"
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