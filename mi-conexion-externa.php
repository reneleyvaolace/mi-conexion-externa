<?php
/**
 * Plugin Name:       Mi Conexión a BD Externa
 * Plugin URI:        https://tu-sitio-web.com/
 * Description:       Provee una función global para conectarse a una base de datos externa y se integra con Elementor.
 * Version:           1.0.1
 * Author:            Tu Nombre (René Leyva)
 * Author URI:        https://tu-sitio-web.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mi-conexion-externa
 */

// Evitar que el archivo sea accedido directamente
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ===================================================================
 * PASO 1: FUNCIÓN DE CONEXIÓN A LA BASE DE DATOS EXTERNA
 * ===================================================================
 */

/**
 * Función para establecer y retornar la conexión a la BD externa.
 *
 * Utilizamos una variable estática (static) para que la conexión
 * se cree solo una vez por cada carga de página, no cada vez
 * que llamamos a la función.
 *
 * @return wpdb|null La instancia de conexión a la base de datos externa o null si falla.
 */
function get_external_db_connection() {
    
    // La variable estática $external_db persiste durante la ejecución
    static $external_db = null;

    // Si la conexión ya existe, simplemente la retornamos.
    if ( $external_db !== null ) {
        return $external_db;
    }

    // Si no existe, creamos la nueva instancia de wpdb
    // Usamos las constantes que definimos en wp-config.php
    try {
        // Asegurarnos de que las constantes existen antes de usarlas
        if ( ! defined('EXT_DB_USER') || ! defined('EXT_DB_PASSWORD') || ! defined('EXT_DB_NAME') || ! defined('EXT_DB_HOST') ) {
            // error_log('Error de Plugin: Faltan las constantes de BD externa en wp-config.php');
            return null;
        }

        $external_db = new wpdb( 
            EXT_DB_USER, 
            EXT_DB_PASSWORD, 
            EXT_DB_NAME, 
            EXT_DB_HOST 
        );
        
        // Manejar errores de conexión iniciales
        if ( ! empty( $external_db->error ) ) {
            // error_log('Error al conectar a la BD externa: ' . $external_db->error->get_error_message());
            return null; // Retornamos null para indicar fallo
        }

    } catch ( Exception $e ) {
        // Capturar cualquier excepción durante la instanciación
        // error_log('Excepción al conectar a BD externa: ' . $e->getMessage());
        return null;
    }

    return $external_db;
}


/**
 * ===================================================================
 * PASO 2: INTEGRACIÓN CON ELEMENTOR PRO (SECCIÓN CORREGIDA)
 * ===================================================================
 */

/**
 * Carga los archivos de integración de Elementor Pro.
 * Esta función es la que se mencionaba en tu error (línea 81).
 */
function mce_load_elementor_pro_integration() {
    
    // Este es el archivo que estaba causando el error fatal
    // porque se cargaba demasiado pronto.
    $integration_file = plugin_dir_path( __FILE__ ) . 'includes/class-mce-elementor-integration.php';

    if ( file_exists( $integration_file ) ) {
        require_once $integration_file;
    }
}

/**
 * Enganchamos nuestra función de carga al hook correcto.
 *
 * ------------------------------------------------------------------
 * ESTA ES LA CORRECCIÓN:
 * ------------------------------------------------------------------
 *
 * ANTES (EL ERROR):
 * add_action( 'plugins_loaded', 'mce_load_elementor_pro_integration' );
 *
 * AHORA (LA SOLUCIÓN):
 * Usamos 'elementor/loaded' que se dispara DESPUÉS de que Elementor
 * y Elementor Pro han cargado todas sus clases base.
 */
add_action( 'elementor/loaded', 'mce_load_elementor_pro_integration' );