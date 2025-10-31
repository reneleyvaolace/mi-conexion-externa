<?php
/**
 * Manejador de Base de Datos Externa.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
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
		// 1. Si ya estamos conectados, no hacer nada.
		if ( $this->connection ) {
			return true;
		}

		// 2. Validar que tengamos credenciales.
		if ( empty( $this->host ) || empty( $this->port ) || empty( $this->db_name ) || empty( $this->user ) ) {
			$this->connection_error = __( 'Las credenciales de la base de datos no están configuradas.', 'mi-conexion-externa' );
			return false;
		}

		// 3. Suprimir errores nativos para manejarlos nosotros.
		mysqli_report( MYSQLI_REPORT_OFF );

		$this->connection = @new mysqli( $this->host, $this->user, $this->pass, $this->db_name, (int) $this->port );

		// 4. Manejar error de conexión.
		if ( $this->connection->connect_error ) {
			$this->connection_error = $this->connection->connect_error;
			$this->connection = null;
			return false;
		}

		// 5. Establecer charset.
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
	 * (Sin cambios)
	 */
	public function get_tables() {
		// 1. Intentar conectar.
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		// 2. Definir la consulta.
		$sql = 'SHOW TABLES;';

		// 3. Preparar y ejecutar.
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

		// 6. Convertir resultados a un array simple.
		$tables = array();
		while ( $row = $result->fetch_array( MYSQLI_NUM ) ) {
			$tables[] = $row[0];
		}

		// 7. Limpiar.
		$stmt->close();

		// 8. Devolver los datos.
		return $tables;
	}

	/**
	 * *** MÉTODO CORREGIDO (FINAL) ***
	 * Obtiene las primeras 100 filas del contenido de una tabla específica.
	 *
	 * @param string $table_name El nombre de la tabla a consultar.
	 * @return array|WP_Error Un array de filas si tiene éxito, o un WP_Error.
	 */
	public function get_table_content( $table_name ) {
		// 1. Intentar conectar.
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		// 2. *** DEFENSA DE SEGURIDAD (Regla 1) ***
		$available_tables = $this->get_tables();
		if ( is_wp_error( $available_tables ) || ! in_array( $table_name, $available_tables, true ) ) {
			return new WP_Error(
				'invalid_table_name',
				__( 'El nombre de la tabla proporcionado no es válido o no se pudo verificar.', 'mi-conexion-externa' ),
				$table_name
			);
		}

		// 3. *** LÍNEA CORREGIDA ***
		// Se eliminaron las barras invertidas '\' que causaban el error de sintaxis.
		$sql = "SELECT * FROM \`" . $table_name . "\` LIMIT 100;";

		// 4. Preparar y ejecutar.
		$stmt = $this->connection->prepare( $sql );
		if ( $stmt === false ) {
			// Añadimos el error de MySQL para más depuración.
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

		// 5. Convertir resultados a un array asociativo.
		$data = $result->fetch_all( MYSQLI_ASSOC );

		// 6. Limpiar.
		$stmt->close();

		// 7. Devolver los datos.
		return $data;
	}

}