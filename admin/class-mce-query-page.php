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
 * No tiene hooks, solo un método público 'render'
 * que es llamado por el cargador principal del admin.
 */
class MCE_Query_Page {

	/**
	 * Renderiza el contenido HTML de la página "Explorador".
	 */
	public function create_query_page_content() {
		// Instanciar nuestro manejador de BBDD
		$db_handler = new MCE_DB_Handler();
		
		// Comprobar si estamos viendo una tabla específica
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

				// Manejar Errores
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

				// Manejar Errores
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
							// Construir la URL para esta tabla
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