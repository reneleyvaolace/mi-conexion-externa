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
 *
 * Usamos prefijos MCE_ para evitar colisiones (Regla 3).
 */
define( 'MCE_VERSION', '1.0.0' );
define( 'MCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MCE_PLUGIN_FILE', __FILE__ );

/**
 * Hook de Activación del Plugin.
 *
 * Se dispara una sola vez cuando el plugin es activado.
 * Ideal para configurar opciones por defecto o verificar requisitos.
 */
function mce_plugin_activate() {
	// (Próximos pasos: Aquí podríamos verificar la versión de PHP o WP)
	// (Próximos pasos: Aquí podríamos establecer una opción de 'versión_db' si creamos tablas)
}
register_activation_hook( MCE_PLUGIN_FILE, 'mce_plugin_activate' );

/**
 * Hook de Desactivación del Plugin.
 *
 * Se dispara una sola vez cuando el plugin es desactivado.
 * Ideal para limpiar 'cron jobs' o 'transients' que hayamos creado.
 */
function mce_plugin_deactivate() {
	// (Próximos pasos: Limpiar tareas programadas si las hubiera)
}
register_deactivation_hook( MCE_PLUGIN_FILE, 'mce_plugin_deactivate' );

/**
 * Carga del núcleo del Plugin.
 *
 * Usamos el hook 'plugins_loaded' para asegurarnos de que todas las funciones
 * de WordPress y otros plugins estén disponibles.
 *
 * Aquí es donde cargaremos nuestros archivos de 'includes' y 'admin'.
 */
function mce_load_plugin_core() {

	// Cargar el dominio de texto para traducciones (Regla 3: i18n)
	load_plugin_textdomain(
		'mi-conexion-externa',
		false,
		dirname( plugin_basename( MCE_PLUGIN_FILE ) ) . '/languages/'
	);

	// (Próximos pasos: Aquí incluiremos nuestros archivos)
	// require_once MCE_PLUGIN_DIR . 'includes/mce-functions.php';
	// require_once MCE_PLUGIN_DIR . 'includes/class-mce-api-handler.php';
	// require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';

}
// Usamos 'plugins_loaded' para cargar nuestros archivos principales.
add_action( 'plugins_loaded', 'mce_load_plugin_core' );