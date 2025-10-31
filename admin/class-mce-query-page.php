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
 * *** CLASE RENOMBRADA ***
 * Clase MCE_Query_Page
 *
 * Crea el menú de nivel superior y la página para explorar la BBDD.
 */
class MCE_Query_Page {

	/**
	 * Constructor. Engancha los métodos a los hooks de WordPress.
	 */
	public function __construct() {
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
			array( $this, 'create_query_page_content' ), // *** CAMBIADO ***
			'dashicons-database-view',
			20
		);

		// 2. Añadir la página de "Explorador" como submenú
		add_submenu_page(
			'mce-main-menu',
			__( 'Explorador de BBDD', 'mi-conexion-externa' ), // *** CAMBIADO ***
			__( 'Explorador', 'mi-conexion-externa' ),     // *** CAMBIADO ***
			'manage_options',
			'mce-main-menu',
			array( $this, 'create_query_page_content' ) // *** CAMBIADO ***
		);

		// 3. Añadir nuestra página de "Ajustes"
		remove_submenu_page( 'options-general.php', 'mce-settings' );
		add_submenu_page(
			'mce-main-menu',
			__( 'Ajustes de Conexión', 'mi-conexion-extexrna' ),
			__( 'Ajustes', 'mi-conexion-externa' ),
			'manage_options',
			'mce-settings',
			array( $this, 'redirect_to_settings_page' )
		);
	}

	/**
	 * Callback vacío para el menú de ajustes.
	 */
	public function redirect_to_settings_page() {
		// La clase MCE_Settings_Page se encarga de esto.
	}


	/**
	 * *** MÉTODO MODIFICADO ***
	 * Renderiza el contenido HTML de la página "Explorador".
	 */
	public function create_query_page_content() {
		// 1. Instanciar nuestro manejador de BBDD
		$db_handler = new MCE_DB_Handler();

		// 2. Obtener las TABLAS (en lugar de productos)
		$tables = $db_handler->get_tables();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Explorador de la Base de Datos Externa', 'mi-conexion-externa' ) ); ?></h1>
			
			<?php
			// 3. Manejar Errores
			if ( is_wp_error( $tables ) ) :
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<strong><?php echo esc_html( __( 'Error al Cargar las Tablas:', 'mi-conexion-externa' ) ); ?></strong>
					</p>
					<p>
						<?php
						echo esc_html( $tables->get_error_message() );
						if ( $tables->get_error_data() ) {
							echo '<br><em>' . esc_html( __( 'Detalle:', 'mi-conexion-externa' ) . ' ' . $tables->get_error_data() ) . '</em>';
						}
						?>
					</p>
				</div>
				<?php
			// 4. Manejar Base de Datos Vacía
			elseif ( empty( $tables ) ) :
				?>
				<div class="notice notice-info">
					<p><?php echo esc_html( __( 'No se encontraron tablas en esta base de datos.', 'mi-conexion-externa' ) ); ?></p>
				</div>
				<?php
			// 5. Renderizar la LISTA (Éxito)
			else :
				?>
				<h3><?php echo esc_html( __( 'Tablas Encontradas:', 'mi-conexion-externa' ) ); ?></h3>
				<p><?php echo esc_html( sprintf( __( 'Se encontraron %d tablas en la base de datos.', 'mi-conexion-externa' ), count( $tables ) ) ); ?></p>
				<ul style="list-style: disc; padding-left: 20px;">
					<?php foreach ( $tables as $table ) : ?>
						<li><code><?php echo esc_html( $table ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * La comprobación 'is_admin()' es crucial.
 */
if ( is_admin() ) {
	new MCE_Query_Page();
}