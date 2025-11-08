<?php
/**
 * Plugin Name:       CoreAura: Conexión Externa
 * Plugin URI:        https://ejemplo.com/mi-conexion-externa
 * Description:       Conecta WordPress con una base de datos externa para sincronizar contenido.
 * Version:           1.1.5
 * Author:            CoreAura
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
define( 'MCE_VERSION', '1.1.5' );
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

	// --- 2. Cargar ADMIN ---
	if ( is_admin() ) {
		
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-settings-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-query-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-help-page.php';
		require_once MCE_PLUGIN_DIR . 'admin/class-mce-admin-loader.php';

		$settings_page = new MCE_Settings_Page();
		$query_page    = new MCE_Query_Page();
		$help_page     = new MCE_Help_Page();
		
		new MCE_Admin_Loader( $query_page, $settings_page, $help_page );
	}
	
	// 3. Cargar ELEMENTOR PRO (Condicional)
	add_action( 'plugins_loaded', 'mce_load_elementor_pro_integration', 11 );
	
	// 4. Registrar CSS y JS del Frontend
	add_action( 'wp_enqueue_scripts', 'mce_register_public_scripts' );
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
 * Registra nuestro CSS y JS, y localiza el script.
 */
function mce_register_public_scripts() {
	// Registrar el CSS
	wp_register_style(
		'mce-public-style', 
		MCE_PLUGIN_URL . 'public/css/mce-public-style.css',
		array(), 
		MCE_VERSION
	);
	
	// Registrar el JS
	wp_register_script(
		'mce-public-script',
		MCE_PLUGIN_URL . 'public/js/mce-public-script.js',
		array( 'jquery' ),
		MCE_VERSION,
		true
	);
	
	// Adjuntamos el objeto AJAX (Nonce y URL) a nuestro script.
	wp_localize_script(
		'mce-public-script',
		'mce_ajax_object',
		array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'mce_ajax_nonce' ), // *** ¡EL NOMBRE ES 'mce_ajax_nonce'! ***
		)
	);
}