<?php
/**
 * Lógica de Integración con Elementor Free o Pro (opcional).
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integración segura:
 * - Si Elementor Free está activo, no registra custom query por limitación del plugin.
 * - Si Elementor Pro está activo, se puede añadir integración avanzada (con bucles personalizados).
 */
class MCE_Elementor_Integration {

    /**
     * Constructor.
     * Si Elementor Pro no está activo, solo deja usable el shortcode (fallback universal).
     */
    public function __construct() {
        if ( did_action( 'elementor/loaded' ) ) {
            // Mostrar aviso/log que la integración avanzada requiere Elementor Pro.
            if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
                // Fallback: Solo el shortcode está disponible.
                add_action( 'admin_notices', array( $this, 'integration_notice' ) );
                return;
            }
            // Si Elementor Pro está activo, registrar consulta personalizada (opcional).
            add_action( 'elementor/query/register', array( $this, 'register_custom_query' ) );
        }
    }

    /**
     * Muestra aviso en el admin si solo Elementor Free está activo.
     */
    public function integration_notice() {
        echo '<div class="notice notice-info"><p>'
             . esc_html__( 'CoreAura: La integración avanzada de Elementor está disponible solo con Elementor Pro. Puedes seguir usando el shortcode [mce_mostrar_tabla] en Elementor Free, Gutenberg o cualquier otro constructor.', 'mi-conexion-externa' )
             . '</p></div>';
    }

    /**
     * Registrar la consulta personalizada SOLO si Elementor Pro está activo.
     */
    public function register_custom_query( $query_manager ) {
        $query_manager->register_query( 'mce_productos_query', 'MCE_Elementor_Query' );
    }
}

/**
 * Opcional: Clase controladora de consulta personalizada SOLO si Elementor Pro está disponible.
 */
if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) :
class MCE_Elementor_Query extends \ElementorPro\Modules\QueryControl\Classes\Base_Query {

    /**
     * Devuelve el ID único de la consulta.
     */
    public function get_id() {
        return 'mce_productos_query';
    }

    /**
     * Devuelve el Título de la consulta (visible en Elementor Pro).
     */
    public function get_title() {
        return __( 'Productos Externos (MCE)', 'mi-conexion-externa' );
    }

    /**
     * Método para obtener los datos del loop personalizado.
     */
    public function get_items( array $settings = array() ) {
        $db_handler = new MCE_DB_Handler();
        $productos = $db_handler->get_productos();

        if ( is_wp_error( $productos ) || empty( $productos ) ) {
            return array();
        }
        return $productos;
    }
}
endif;
