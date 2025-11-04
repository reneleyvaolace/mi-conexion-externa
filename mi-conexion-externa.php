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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constantes
 */
define( 'MCE_VERSION', '1.0.0' );
define( 'MCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MCE_PLUGIN_FILE', __FILE__ );

/**
 * Hooks de Activación/Desactivación
 */
function mce_plugin_activate() {
}
register_activation_hook( MCE_PLUGIN_FILE, 'mce_plugin_activate' );

function mce_plugin_deactivate() {
}
register_deactivation_hook( MCE_PLUGIN_FILE, 'mce_plugin_deactivate' );

/**
 * Carga del núcleo del Plugin.
 *
 * *** ¡LIMPIADO! ***
 */
function mce_load_plugin_core() {

	load_plugin_textdomain(
		'mi-conexion-externa',
		false,
		dirname( plugin_basename( MCE_PLUGIN_FILE ) ) . '/languages/'
	);

	// --- 1. Cargar GLOBALES ---
	require_once MCE_PLUGIN_DIR . 'includes/class-mce-db-handler.php';
	require_once MCE_PLUGIN_DIR . 'includes/mce-shortcodes.php';

	// --- 2. Cargar ADMIN (Lógica corregida) ---
	if ( is_admin() ) {
		
		// Cargar clases del admin
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-query-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-help-page.php';
		// ¡Ya no cargamos class-mce-css-page.php!
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-admin-loader.php';

		// Instanciar clases
		$settings_page = new MCE_Settings_Page();
		$query_page    = new MCE_Query_Page();
		$help_page     = new MCE_Help_Page();
		
		// Iniciar el cargador del menú
		new MCE_Admin_Loader( $query_page, $settings_page, $help_page );
	}
	
	// 3. Cargar ELEMENTOR PRO (Condicional)
	add_action( 'plugins_loaded', 'mce_load_elementor_pro_integration', 11 );
	
	// 4. *** ¡ACCIÓN ELIMINADA! ***
	// Ya no imprimimos CSS personalizado en el footer.
	// add_action( 'wp_footer', 'mce_output_custom_css_in_footer', 999 );

	// 5. Registrar CSS POR DEFECTO (Aún lo necesitamos para el layout)
	add_action( 'wp_enqueue_scripts', 'mce_register_public_styles_global' );
}
add_action( 'plugins_loaded', 'mce_load_plugin_core' );


/**
 * Carga condicional de Elementor Pro
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
 * *** ¡FUNCIÓN ELIMINADA! ***
 * Ya no necesitamos 'mce_output_custom_css_in_footer'.
 */

/**
 * Registra nuestro archivo CSS por defecto.
 */
function mce_register_public_styles_global() {
	wp_register_style(
		'mce-public-style', 
		MCE_PLUGIN_URL . 'public/css/mce-public-style.css',
		array(), 
		MCE_VERSION 
	);
}