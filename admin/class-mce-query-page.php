<?php
/**
 * Lógica para la Página de "Consultas" del Administrador.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Query_Page
 *
 * Crea el menú de nivel superior y la página para explorar la BBDD.
 */
class MCE_Query_Page {

	/**
	 * Almacena el objeto de la página de ajustes.
	 *
	 * @var MCE_Settings_Page
	 */
	private $settings_page;

	/**
	 * El Constructor. Acepta la dependencia de la pág de ajustes.
	 *
	 * @param MCE_Settings_Page $settings_page La instancia de la página de ajustes.
	 */
	public function __construct( $settings_page ) {
		$this->settings_page = $settings_page;
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
	}

	/**
	 * Registra las páginas de administración.
	 */
	public function register_admin_pages() {
		// 1. Añadir el Menú de Nivel Superior
		add_menu_page(
			__( 'Conexión Externa', 'mi-conexion-externa' ),
			__( 'Conexión Externa', 'mi-conexion-externa' ),
			'manage_options',
			'mce-main-menu',
			array( $this, 'create_query_page_content' ),
			'dashicons-database-view',
			20
		);

		// 2. Añadir la página de "Explorador" como submenú
		add_submenu_page(
			'mce-main-menu',
			__( 'Explorador de BBDD', 'mi-conexion-externa' ),
			__( 'Explorador', 'mi-conexion-externa' ),
			'manage_options',
			'mce-main-menu',
			array( $this, 'create_query_page_content' )
		);

		// 3. Añadir nuestra página de "Ajustes"
		add_submenu_page(
			'mce-main-menu',
			__( 'Ajustes de Conexión', 'mi-conexion-externa' ),
			__( 'Ajustes', 'mi-conexion-externa' ),
			'manage_options',
			'mce-settings', // El slug debe coincidir.
			array( $this->settings_page, 'create_settings_page_content' ) // Callback correcto.
		);
	}


	/**
	 * *** MÉTODO ACTUALIZADO ***
	 * Renderiza el contenido HTML de la página "Explorador".
	 * Ahora tiene dos vistas: Lista de Tablas o Contenido de Tabla.
	 */
	public function create_query_page_content() {
		// Instanciar nuestro manejador de BBDD
		$db_handler = new MCE_DB_Handler();
		
		// Comprobar si estamos viendo una tabla específica (Regla 1: Sanitización)
		$view_table = isset( $_GET['view_table'] ) ? sanitize_text_field( $_GET['view_table'] ) : null;

		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Explorador de la Base de Datos Externa', 'mi-conexion-externa' ) ); ?></h1>
			
			<?php
			// --- VISTA 1: MOSTRAR CONTENIDO DE LA TABLA ---
			if ( $view_table ) :
				
				echo '<p><a href="' . esc_url( menu_page_url( 'mce-main-menu', false ) ) . '">&larr; ' . esc_html__( 'Volver a la lista de tablas', 'mi-conexion-externa' ) . '</a></p>';
				echo '<h2>' . esc_html( sprintf( __( 'Mostrando contenido de: %s', 'mi-conexion-externa' ), $view_table ) ) . '</h2>';
				echo '<p>' . esc_html__( 'Se muestran las primeras 100 filas.', 'mi-conexion-externa' ) . '</p>';

				$data = $db_handler->get_table_content( $view_table );

				// Manejar Errores (ej. nombre de tabla inválido)
				if ( is_wp_error( $data ) ) :
					$this->render_error( $data, __( 'Error al Cargar el Contenido:', 'mi-conexion-externa' ) );
				
				// Manejar Tabla Vacía
				elseif ( empty( $data ) ) :
					?>
					<div class="notice notice-info"><p><?php echo esc_html( __( 'Esta tabla está vacía.', 'mi-conexion-externa' ) ); ?></p></div>
					<?php
				
				// Renderizar la Tabla de Contenido
				else :
					$column_headers = array_keys( $data[0] );
					?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<?php foreach ( $column_headers as $header ) : ?>
									<th scope="col" class="manage-column"><?php echo esc_html( $header ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $data as $row ) : ?>
								<tr>
									<?php foreach ( $row as $value ) : ?>
										<td><?php echo esc_html( $value ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php
				endif;

			// --- VISTA 2: MOSTRAR LISTA DE TABLAS (POR DEFECTO) ---
			else :

				$tables = $db_handler->get_tables();

				// Manejar Errores (ej. fallo de conexión)
				if ( is_wp_error( $tables ) ) :
					$this->render_error( $tables, __( 'Error al Cargar las Tablas:', 'mi-conexion-externa' ) );
				
				// Manejar Base de Datos Vacía
				elseif ( empty( $tables ) ) :
					?>
					<div class="notice notice-info"><p><?php echo esc_html( __( 'No se encontraron tablas en esta base de datos.', 'mi-conexion-externa' ) ); ?></p></div>
					<?php
				
				// Renderizar la Lista de Tablas
				else :
					?>
					<h3><?php echo esc_html( __( 'Tablas Encontradas:', 'mi-conexion-externa' ) ); ?></h3>
					<p><?php echo esc_html( sprintf( __( 'Se encontraron %d tablas. Haz clic en una para ver su contenido.', 'mi-conexion-externa' ), count( $tables ) ) ); ?></p>
					<ul style="list-style: disc; padding-left: 20px;">
						<?php
						foreach ( $tables as $table ) :
							// Construir la URL para esta tabla (Regla 1: esc_url, esc_attr)
							$table_url = add_query_arg(
								array(
									'page'       => 'mce-main-menu',
									'view_table' => $table,
								),
								admin_url( 'admin.php' )
							);
							?>
							<li>
								<code>
									<a href="<?php echo esc_url( $table_url ); ?>">
										<?php echo esc_html( $table ); ?>
									</a>
								</code>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
				endif;
			endif;
			?>
		</div>
		<?php
	}

	/**
	 * Función auxiliar para renderizar un WP_Error de forma limpia.
	 *
	 * @param WP_Error $error El objeto de error.
	 * @param string   $title El título del error.
	 */
	private function render_error( $error, $title ) {
		?>
		<div class="notice notice-error is-dismissible">
			<p><strong><?php echo esc_html( $title ); ?></strong></p>
			<p>
				<?php
				echo esc_html( $error->get_error_message() );
				if ( $error->get_error_data() ) {
					echo '<br><em>' . esc_html( __( 'Detalle:', 'mi-conexion-externa' ) . ' ' . $error->get_error_data() ) . '</em>';
				}
				?>
			</p>
		</div>
		<?php
	}
}