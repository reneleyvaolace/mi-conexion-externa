<?php
/**
 * Plugin Name:       Mi Conexión a BD Externa
 * Plugin URI:        https://tu-sitio-web.com/
 * Description:       Provee una función global para conectarse a una base de datos externa y un shortcode para mostrar datos.
 * Version:           1.1.0
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

function get_external_db_connection() {
    static $external_db = null;

    if ( $external_db !== null ) {
        return $external_db;
    }

    try {
        if ( ! defined('EXT_DB_USER') || ! defined('EXT_DB_PASSWORD') || ! defined('EXT_DB_NAME') || ! defined('EXT_DB_HOST') ) {
            return null;
        }

        $external_db = new wpdb( 
            EXT_DB_USER, 
            EXT_DB_PASSWORD, 
            EXT_DB_NAME, 
            EXT_DB_HOST 
        );
        
        if ( ! empty( $external_db->error ) ) {
            return null;
        }

    } catch ( Exception $e ) {
        return null;
    }

    return $external_db;
}


/**
 * ===================================================================
 * PASO 2: INTEGRACIÓN CON ELEMENTOR PRO (SECCIÓN CORREGIDA)
 * ===================================================================
 */

function mce_load_elementor_pro_integration() {
    $integration_file = plugin_dir_path( __FILE__ ) . 'includes/class-mce-elementor-integration.php';

    if ( file_exists( $integration_file ) ) {
        require_once $integration_file;
    }
}
add_action( 'elementor/loaded', 'mce_load_elementor_pro_integration' );


/**
 * ===================================================================
 * PASO 3 (NUEVO): SHORTCODE PARA MOSTRAR DATOS EXTERNOS
 * ===================================================================
 */

/**
 * Función que genera el contenido del shortcode [mostrar_datos_externos].
 *
 * @return string El HTML con los datos de la tabla.
 */
function mce_display_external_data_shortcode() {
    
    // 1. Obtener la conexión a la BD externa
    $external_db = get_external_db_connection();

    // Usamos 'ob_start' para capturar todo el HTML que sigue
    // en lugar de imprimirlo (echo). Los shortcodes deben retornar (return)
    // el contenido, no imprimirlo.
    ob_start();

    // 2. Verificar que la conexión fue exitosa
    if ( $external_db ) {

        // -----------------------------------------------------------------
        // IMPORTANTE: Modifica esta consulta
        // -----------------------------------------------------------------
        $nombre_tabla = 'tu_tabla_externa'; // <-- CAMBIA ESTO
        $query = $external_db->prepare( "SELECT * FROM {$nombre_tabla} LIMIT %d", 10 );

        // 3. Ejecutar la consulta
        $resultados = $external_db->get_results( $query );

        // 4. Verificar si obtuvimos resultados
        if ( ! empty( $resultados ) ) {
            
            echo '<p>Mostrando los primeros 10 resultados de la tabla: ' . esc_html( $nombre_tabla ) . '</p>';

            echo '<table class="tabla-externa" border="1" cellpadding="5" cellspacing="0">';
            
            $columnas = array_keys( (array) $resultados[0] );

            echo '<thead><tr>';
            foreach ( $columnas as $columna ) {
                echo '<th>' . esc_html( $columna ) . '</th>';
            }
            echo '</tr></thead>';

            echo '<tbody>';
            foreach ( $resultados as $fila ) {
                echo '<tr>';
                foreach ( $columnas as $columna ) {
                    echo '<td>' . esc_html( $fila->$columna ) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

        } else {
            echo '<p>No se encontraron resultados en la tabla: ' . esc_html( $nombre_tabla ) . '</p>';
        }

    } else {
        echo '<p><strong>Error:</strong> No se pudo establecer la conexión con la base de datos externa.</p>';
    }

    // ob_get_clean() detiene la captura y nos devuelve
    // todo el HTML generado como un string.
    return ob_get_clean();
}

/**
 * Registramos el shortcode en WordPress.
 * Ahora, donde escribamos [mostrar_datos_externos], se ejecutará la función.
 */
add_shortcode( 'mostrar_datos_externos', 'mce_display_external_data_shortcode' );