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
register_activation_hook( MCE_PLUGIN_FILE, 'mce_plugin_deactivate' );

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
	require_once MCE_PLUGIN_DIR . 'includes/class-mce-db-handler.php';
	require_once MCE_PLUGIN_DIR . 'includes/mce-shortcodes.php';

	// --- 2. Cargamos el núcleo del ADMIN ---
	if ( is_admin() ) {
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-admin-loader.php';
		new MCE_Admin_Loader();
	}
	
	// 3. Cargamos la integración de Elementor Pro (condicional)
	add_action( 'plugins_loaded', 'mce_load_elementor_pro_integration', 11 );
	
	// 4. Enganchamos la función que imprime el CSS personalizado en el <head>
	add_action( 'wp_head', 'mce_output_custom_css', 99 );

	// 5. *** ¡LÍNEA FALTANTE AÑADIDA! ***
	// Registramos los estilos del frontend (nuestro CSS por defecto).
	add_action( 'wp_enqueue_scripts', 'mce_register_public_styles_global' );
}
add_action( 'plugins_loaded', 'mce_load_plugin_core' );


/**
 * Función de carga condicional para la integración de Elementor Pro.
 */
function mce_load_elementor_pro_integration() {
	if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
		return;
	}
	if ( ! class_exists( 'ElementorPro\Modules\QueryControl\Classes\Base_Query' ) ) {
		return;
	}
	require_once MCE_PLUGIN_DIR . 'includes/class-mce-elementor-integration.php';
	new MCE_Elementor_Integration();
}

/**
 * Imprime el CSS guardado en la BBDD dentro del <head> del sitio.
 */
function mce_output_custom_css() {
	$custom_css = get_option( 'mce_custom_css' );

	if ( ! empty( $custom_css ) ) {
		$sanitized_css = wp_strip_all_tags( $custom_css );
		
		echo '' . "\n";
		echo '<style type="text/css" id="mce-custom-styles">' . "\n";
		echo $sanitized_css;
		echo "\n" . '</style>' . "\n";
	}
}

/**
 * *** ¡FUNCIÓN FALTANTE AÑADIDA! ***
 * Registra nuestro archivo CSS por defecto para que
 * el shortcode pueda llamarlo con wp_enqueue_style().
 */
function mce_register_public_styles_global() {
	wp_register_style(
		'mce-public-style', // El "nombre" de nuestro estilo.
		MCE_PLUGIN_URL . 'public/css/mce-public-style.css',
		array(), // Dependencias (ninguna)
		MCE_VERSION // Versión
	);
}