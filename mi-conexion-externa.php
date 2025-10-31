<?php
/**
 * Plugin Name:       Mi Conexión Externa
 * Plugin URI:        https://ejemplo.com/mi-conexion-externa
 * Description:       Conecta WordPress con una base de datos externa para sincronizar contenido.
 * Version:           1.0.0
 * Author:            Tu Nombre Aquí
 * Author URI:        https://ejemplo.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mi-conexion-externa
 * Domain Path:       /languages
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Salir si se accede directamente.
}

/**
 * Definición de Constantes del Plugin
 */
define( 'MCE_VERSION', '1.0.0' );
define( 'MCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MCE_PLUGIN_FILE', __FILE__ );

/**
 * Hook de Activación del Plugin.
 */
function mce_plugin_activate() {
}
register_activation_hook( MCE_PLUGIN_FILE, 'mce_plugin_activate' );

/**
 * Hook de Desactivación del Plugin.
 */
function mce_plugin_deactivate() {
}
register_deactivation_hook( MCE_PLUGIN_FILE, 'mce_plugin_deactivate' );

/**
 * Carga del núcleo del Plugin.
 */
function mce_load_plugin_core() {

	// Cargar el dominio de texto para traducciones
	load_plugin_textdomain(
		'mi-conexion-externa',
		false,
		dirname( plugin_basename( MCE_PLUGIN_FILE ) ) . '/languages/'
	);

	// --- 1. Cargamos los archivos de clases GLOBALES ---
	// El "cerebro" (Manejador de BBDD)
	require_once MCE_PLUGIN_DIR . 'includes/class-mce-db-handler.php';
	
	// El Shortcode (Plan A)
	require_once MCE_PLUGIN_DIR . 'includes/mce-shortcodes.php';

	// --- 2. Cargamos los archivos de ADMIN ---
	if ( is_admin() ) {
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-query-page.php';
		
		// Instanciamos
		$mce_settings = new MCE_Settings_Page();
		$mce_query_page = new MCE_Query_Page( $mce_settings );
	}
	
	// *** ¡NUEVA LÓGICA! ***
	// 3. Cargamos el "Plan B" (Integración Pro) solo si Elementor Pro está activo.
	// Usamos el hook 'plugins_loaded' con prioridad 11 para asegurarnos
	// de que Elementor Pro ya se haya cargado (que se carga en prioridad 10).
	add_action( 'plugins_loaded', 'mce_load_elementor_pro_integration', 11 );
}
// Usamos 'plugins_loaded' para cargar nuestros archivos principales.
add_action( 'plugins_loaded', 'mce_load_plugin_core' );


/**
 * Función de carga condicional para la integración de Elementor Pro.
 */
function mce_load_elementor_pro_integration() {
	// (Regla 1: Mejor Práctica de Carga Condicional)
	// Esta constante 'ELEMENTOR_PRO_VERSION' solo existe si Elementor Pro
	// está instalado y activo.
	if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
		require_once MCE_PLUGIN_DIR . 'includes/class-mce-elementor-integration.php';
		// Instanciamos la clase de integración para que se enganche.
		new MCE_Elementor_Integration();
	}
}