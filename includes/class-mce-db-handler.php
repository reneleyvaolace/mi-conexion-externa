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

	private $connection = null;
	private $connection_error = null;

	private $host;
	private $port;
	private $db_name;
	private $user;
	private $pass;

	public function __construct() {
		$opts = get_option( 'mce_db_settings' );

		$this->host    = isset( $opts['mce_db_ip'] ) ? $opts['mce_db_ip'] : '';
		$this->port    = isset( $opts['mce_db_port'] ) ? $opts['mce_db_port'] : 3306;
		$this->db_name = isset( $opts['mce_db_name'] ) ? $opts['mce_db_name'] : '';
		$this->user    = isset( $opts['mce_db_user'] ) ? $opts['mce_db_user'] : '';
		$this->pass    = isset( $opts['mce_db_pass'] ) ? $opts['mce_db_pass'] : '';

		// Depuración liviana: confirma qué archivo se carga (temporal).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MCE INIT] Clase MCE_DB_Handler cargada desde: ' . __FILE__ );
		}
	}

	public function __destruct() {
		if ( $this->connection ) {
			$this->connection->close();
		}
	}

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
	 * Obtiene el contenido de una tabla específica (para el Explorador).
	 */
	public function get_table_content( $table_name, $limit = 100 ) {
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		// Validar tabla.
		$available_tables = $this->get_tables();
		if ( is_wp_error( $available_tables ) || ! in_array( $table_name, $available_tables, true ) ) {
			return new WP_Error(
				'invalid_table_name',
				__( 'El nombre de la tabla proporcionado no es válido o no se pudo verificar.', 'mi-conexion-externa' ),
				$table_name
			);
		}

		// Sanear nombre de tabla y límite.
		$table_name = preg_replace( '/[^A-Za-z0-9_]/', '', $table_name );
		$limit      = intval( $limit ) > 0 ? intval( $limit ) : 100;

		// CORRECCIÓN: usar comillas simples para la cadena y backticks sin backslashes.
		$sql = 'SELECT * FROM `' . $table_name . '` LIMIT ' . $limit . ';';

		// Depuración: registrar SQL real antes de preparar (temporal si WP_DEBUG=true)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MCE DEBUG] get_table_content -> SQL: ' . $sql );
		}

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
	 * (Función obsoleta, pero la dejamos por si acaso)
	 */
	public function get_productos() {
		return $this->get_table_content( 'mce_productos', 100 );
	}

	/**
	 * Obtiene datos paginados y total.
	 */
	public function get_paginated_table_data( $table_name, $rows_per_page, $current_page ) {
		if ( ! $this->connect() ) {
			return new WP_Error(
				'db_connection_failed',
				__( 'No se pudo conectar a la base de datos externa.', 'mi-conexion-externa' ),
				$this->connection_error
			);
		}

		// Validar y sanitizar tabla
		$available_tables = $this->get_tables();
		if ( is_wp_error( $available_tables ) || ! in_array( $table_name, $available_tables, true ) ) {
			return new WP_Error(
				'invalid_table_name',
				__( 'El nombre de la tabla proporcionado no es válido.', 'mi-conexion-externa' ),
				$table_name
			);
		}
		$table_name = preg_replace( '/[^A-Za-z0-9_]/', '', $table_name );

		// Sanitizar paginación
		$rows_per_page = intval( $rows_per_page ) > 0 ? intval( $rows_per_page ) : 10;
		$current_page  = intval( $current_page ) > 0 ? intval( $current_page ) : 1;
		$offset        = ( $current_page - 1 ) * $rows_per_page;

		// Consulta COUNT: usar backticks sin backslashes
		$sql_count = 'SELECT COUNT(*) FROM `' . $table_name . '`;';
		$result_count = $this->connection->query( $sql_count );

		if ( $result_count ) {
			$total_rows = (int) $result_count->fetch_row()[0];
			$result_count->free();
		} else {
			return new WP_Error(
				'db_count_failed',
				__( 'Error al contar las filas de la tabla.', 'mi-conexion-externa' ),
				$this->connection->error
			);
		}

		if ( $total_rows === 0 ) {
			return array(
				'data'       => array(),
				'total_rows' => 0,
			);
		}

		// Consulta de datos paginados
		$sql_data = 'SELECT * FROM `' . $table_name . '` LIMIT ' . $rows_per_page . ' OFFSET ' . $offset . ';';

		// Depuración: SQL de paginación
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MCE DEBUG] get_paginated_table_data -> SQL_COUNT: ' . $sql_count );
			error_log( '[MCE DEBUG] get_paginated_table_data -> SQL_DATA: ' . $sql_data );
		}

		$stmt = $this->connection->prepare( $sql_data );
		if ( $stmt === false ) {
			return new WP_Error(
				'db_prepare_failed',
				__( 'Error al preparar la consulta de paginación.', 'mi-conexion-externa' ),
				$this->connection->error
			);
		}

		$stmt->execute();
		$result_data = $stmt->get_result();

		if ( ! $result_data ) {
			return new WP_Error(
				'db_execute_failed',
				__( 'Error al obtener los datos de paginación.', 'mi-conexion-externa' ),
				$stmt->error
			);
		}

		$data = $result_data->fetch_all( MYSQLI_ASSOC );
		$stmt->close();

		return array(
			'data'       => $data,
			'total_rows' => $total_rows,
		);
	}
}
