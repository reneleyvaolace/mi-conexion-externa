<?php
/**
 * Lógica de Integración con Elementor Pro.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Doble verificación de seguridad.
 * Si este archivo se carga de alguna manera sin que Elementor Pro
 * esté activo, no hacemos nada y salimos temprano.
 */
if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
	return;
}


/**
 * Clase 1: El "Adaptador"
 *
 * Esta clase se encarga de "engancharse" a Elementor y
 * registrar nuestra nueva consulta.
 */
class MCE_Elementor_Integration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Nos enganchamos al hook de Elementor para registrar consultas.
		add_action( 'elementor/query/register', array( $this, 'register_custom_query' ) );
	}

	/**
	 * Callback para registrar nuestra consulta.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $query_manager
	 */
	public function register_custom_query( $query_manager ) {
		// Registramos una nueva "fuente" de consulta
		// 'mce_productos_query' = El ID único de nuestra consulta
		// 'MCE_Elementor_Query' = El nombre de la clase que la controlará
		$query_manager->register_query( 'mce_productos_query', 'MCE_Elementor_Query' );
	}
}


/**
 * Clase 2: El "Controlador de Consulta"
 *
 * Esta clase le dice a Elementor CÓMO obtener los datos
 * para nuestra consulta "mce_productos_query".
 *
 * Extiende la clase base de Elementor Pro.
 */
class MCE_Elementor_Query extends \ElementorPro\Modules\QueryControl\Classes\Base_Query {

	/**
	 * Devuelve el ID único de la consulta.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'mce_productos_query';
	}

	/**
	 * Devuelve el Título de la consulta (lo que el usuario ve).
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Productos Externos (MCE)', 'mi-conexion-externa' );
	}

	/**
	 * Este es el método más importante.
	 * Elementor lo llama para obtener los datos del bucle.
	 *
	 * @param array $settings
	 * @return array Los datos (nuestro array de productos)
	 */
	public function get_items( array $settings = array() ) {
		
		// 1. Llamar a nuestro "cerebro".
		$db_handler = new MCE_DB_Handler();
		$productos = $db_handler->get_productos();

		// 2. Si falló la BBDD o está vacía, devolver un array vacío.
		if ( is_wp_error( $productos ) || empty( $productos ) ) {
			return array();
		}

		// 3. ¡Éxito! Devolvemos el array de productos.
		// Elementor ahora iterará sobre este array.
		return $productos;
	}
}