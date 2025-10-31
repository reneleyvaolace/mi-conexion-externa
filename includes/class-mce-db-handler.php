<?php
/**
 * Manejador de Base de Datos Externa.
 *
 * @package MiConexionExterna
 */

// Seguridad básica: evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_DB_Handler
 *
 * Encapsula toda la lógica de conexión y consulta
 * con la base de datos externa.
 */
class MCE_DB_Handler {

	/**
	 * Almacena la instancia de la conexión mysqli.
	 *
	 * @var mysqli|null
	 */
	private $connection = null;

	/**
	 * Almacena el último error de conexión.
	 *
	 * @var string|null
	 */
	private $connection_error = null;

	// --- Propiedades de las credenciales ---
	private $host;
	private $port;
	private $db_name;
	private $user;
	private $pass;

	/**
	 * Constructor. Carga las credenciales guardadas.
	 */
	public function __construct() {
		$opts = get_option( 'mce_db_settings' );

		$this->host    = isset( $opts['mce_db_ip'] ) ? $opts['mce_db_ip'] : '';
		$this->port    = isset( $opts['mce_db_port'] ) ? $opts['mce_db_port'] : 3306;
		$this->db_name = isset( $opts['mce_db_name'] ) ? $opts['mce_db_name'] : '';
		$this->user    = isset( $opts['mce_db_user'] ) ? $opts['mce_db_user'] : '';
		$this->pass    = isset( $opts['mce_db_pass'] ) ? $opts['mce_db_pass'] : '';
	}

	/**
	 * Destructor. Cierra la conexión automáticamente al final.
	 */
	public function __destruct() {
		if ( $this->connection ) {
			$this->connection->close();
		}
	}

	/**
	 * Establece la conexión con la base de datos.
	 *
	 * @return bool True si la conexión es exitosa, False si falla.
	 */
	private function connect() {
		if ( $this->connection ) {
			return true;
		}

		if ( empty( $this->host ) || empty( $this->port ) || empty( $this->db_name ) || empty( $this->user ) ) {
			$this->connection_error = __( 'Las credenciales de la base de datos no están configuradas.', 'mi-conexion-externa' );
			return false;
		}

		mysqli_report( MYSQLI_REPORT_OFF );

		$this->connection = @new mysqli( $this->host, $this->user, $this->pass, $this->db_name, (int) $this->port );

		if ( $this->connection->connect_error ) {
			$this->connection_error = $this->connection->connect_error;
			$this->connection = null;
			return false;
		}

		if ( ! $this->connection->set_charset( 'utf8mb4' ) ) {
			$this->connection_error = __( 'Error al establecer el charset utf8mb4.', 'mi-conexion-externa' );
			$this->connection->close();
			$this->connection = null;
			return false;
		}

		return true;
	}

	/**
	 * Obtiene una lista de todas las tablas en la base de datos conectada.
	 * (Para el Explorador)
	 */
	public function get_tables() {
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		$sql = 'SHOW TABLES;';
		$stmt = $this->connection->prepare( $sql );
		if ( $stmt === false ) {
			return new WP_Error(
				'db_prepare_failed',
				__( 'Error al preparar la consulta SQL.', 'mi-conexion-externa' ),
				$this->connection->error
			);
		}
		$stmt->execute();
		$result = $stmt->get_result();

		if ( ! $result ) {
			return new WP_Error(
				'db_execute_failed',
				__( 'Error al ejecutar la consulta SQL.', 'mi-conexion-externa' ),
				$stmt->error
			);
		}

		$tables = array();
		while ( $row = $result->fetch_array( MYSQLI_NUM ) ) {
			$tables[] = $row[0];
		}

		$stmt->close();
		return $tables;
	}

	/**
	 * Obtiene las primeras 100 filas del contenido de una tabla específica.
	 * (Para el Explorador)
	 *
	 * @param string $table_name El nombre de la tabla a consultar.
	 * @return array|WP_Error Un array de filas si tiene éxito, o un WP_Error.
	 */
	public function get_table_content( $table_name ) {
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		// 1. Validar que la tabla existe en la BD.
		$available_tables = $this->get_tables();
		if ( is_wp_error( $available_tables ) || ! in_array( $table_name, $available_tables, true ) ) {
			return new WP_Error(
				'invalid_table_name',
				__( 'El nombre de la tabla proporcionado no es válido o no se pudo verificar.', 'mi-conexion-externa' ),
				$table_name
			);
		}

		// 2. Protección adicional: eliminar caracteres no válidos.
		$table_name = preg_replace( '/[^A-Za-z0-9_]/', '', $table_name );

		// 3. Construir la consulta SQL de forma segura (sin backslashes).
		$sql = "SELECT * FROM \`" . $table_name . "\` LIMIT 100;";

		$stmt = $this->connection->prepare( $sql );
		if ( $stmt === false ) {
			$mysql_error = $this->connection->error;
			return new WP_Error(
				'db_prepare_failed',
				__( 'Error al preparar la consulta SQL.', 'mi-conexion-externa' ),
				$mysql_error
			);
		}

		$stmt->execute();
		$result = $stmt->get_result();

		if ( ! $result ) {
			return new WP_Error(
				'db_execute_failed',
				__( 'Error al ejecutar la consulta SQL.', 'mi-conexion-externa' ),
				$stmt->error
			);
		}

		$data = $result->fetch_all( MYSQLI_ASSOC );
		$stmt->close();

		return $data;
	}

	/**
	 * *** FUNCIÓN AÑADIDA DE VUELTA ***
	 * Obtiene todos los productos de la tabla 'mce_productos'.
	 * (Para el Shortcode)
	 *
	 * @return array|WP_Error Un array de productos si tiene éxito,
	 * o un WP_Error si falla.
	 */
	public function get_productos() {
		// 1. Intentar conectar.
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		// 2. Definir la consulta.
		$sql = 'SELECT id, nombre, sku, precio, stock FROM mce_productos ORDER BY nombre ASC';

		// 3. Preparar la consulta (Regla 1: Prepared Statements).
		$stmt = $this->connection->prepare( $sql );
		if ( $stmt === false ) {
			return new WP_Error(
				'db_prepare_failed',
				__( 'Error al preparar la consulta SQL.', 'mi-conexion-externa' ),
				$this->connection->error
			);
		}

		// 4. Ejecutar la consulta.
		$stmt->execute();

		// 5. Obtener los resultados.
		$result = $stmt->get_result();

		if ( ! $result ) {
			return new WP_Error(
				'db_execute_failed',
				__( 'Error al ejecutar la consulta SQL.', 'mi-conexion-externa' ),
				$stmt->error
			);
		}

		// 6. Convertir resultados a un array asociativo.
		$productos = $result->fetch_all( MYSQLI_ASSOC );

		// 7. Limpiar.
		$stmt->close();

		// 8. Devolver los datos.
		return $productos;
	}
}