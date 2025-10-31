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

	// --- Cargamos nuestros archivos principales ---

	// 1. Cargamos el manejador de la BBDD (Global).
	// (Debe cargarse primero, ya que las páginas de admin dependen de él).
	require_once MCE_PLUGIN_DIR . 'includes/class-mce-db-handler.php';

	// 2. Cargamos el archivo de la página de ajustes (Solo Admin).
	require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';
	
	// *** LÍNEA ACTUALIZADA ***
	// 3. Cargamos la nueva página de "Ver Productos" (Solo Admin).
	require_once MCE_PLUGIN_DIR . 'admin/class-mce-productos-page.php';

}
// Usamos 'plugins_loaded' para cargar nuestros archivos principales.
add_action( 'plugins_loaded', 'mce_load_plugin_core' );