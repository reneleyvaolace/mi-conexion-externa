<?php
/**
 * Lógica para la Página de "Productos Externos" del Administrador.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Productos_Page
 *
 * Crea el menú de nivel superior y la página para mostrar los productos
 * de la base de datos externa.
 */
class MCE_Productos_Page {

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
		// 1. Añadir el Menú de Nivel Superior (Regla 1)
		add_menu_page(
			__( 'Conexión Externa', 'mi-conexion-externa' ), // Título de la página
			__( 'Conexión Externa', 'mi-conexion-externa' ), // Título del menú
			'manage_options',                                // Capacidad requerida
			'mce-main-menu',                                 // Slug del menú
			array( $this, 'create_productos_page_content' ), // Función que renderiza la página
			'dashicons-database-view',                       // Icono (de Dashicons)
			20                                               // Posición en el menú
		);

		// 2. Añadir la página de "Productos" como submenú (es la misma que la principal)
		add_submenu_page(
			'mce-main-menu',                                 // Slug del menú padre
			__( 'Ver Productos Externos', 'mi-conexion-externa' ), // Título de la página
			__( 'Ver Productos', 'mi-conexion-externa' ),    // Título del submenú
			'manage_options',                                // Capacidad
			'mce-main-menu',                                 // Slug (el mismo que el padre)
			array( $this, 'create_productos_page_content' )  // Misma función de renderizado
		);

		// 3. Añadir nuestra página de "Ajustes" existente como un submenú
		// Nota: WordPress es lo suficientemente inteligente como para "mover"
		// la página que registramos en 'class-mce-settings-page.php' si
		// la registramos de nuevo aquí bajo un nuevo padre.
		
		// Des-registramos la página de opciones anterior
		remove_submenu_page( 'options-general.php', 'mce-settings' );

		// La registramos de nuevo bajo nuestro menú
		add_submenu_page(
			'mce-main-menu',                               // Slug del menú padre
			__( 'Ajustes de Conexión', 'mi-conexion-externa' ), // Título de la página
			__( 'Ajustes', 'mi-conexion-externa' ),         // Título del submenú
			'manage_options',                              // Capacidad
			'mce-settings',                                // Slug (el mismo que definimos en la otra clase)
			array( $this, 'redirect_to_settings_page' )    // Función de renderizado (solo necesita estar)
		);
	}

	/**
	 * WordPress requiere una función de callback para el submenú de Ajustes.
	 * Dado que la lógica real ya está en 'class-mce-settings-page.php',
	 * esta función puede estar vacía o simplemente no usarse, ya que
	 * la clase MCE_Settings_Page se encargará de renderizar.
	 *
	 * O podemos hacer que MCE_Settings_Page *no* añada su propio menú
	 * y solo se encargue de registrar los campos.
	 * Por ahora, lo más simple es dejar que la otra clase maneje su página.
	 * Este es un truco común: el callback real está en la otra clase.
	 */
	public function redirect_to_settings_page() {
		// La clase MCE_Settings_Page ya se ha enganchado a
		// 'admin_menu' y ha registrado 'mce-settings' con su
		// propia función de callback. No necesitamos hacer nada aquí.
	}


	/**
	 * Renderiza el contenido HTML de la página "Ver Productos".
	 */
	public function create_productos_page_content() {
		// 1. Instanciar nuestro manejador de BBDD
		// (Esto es posible porque 'mi-conexion-externa.php' ya cargó la clase)
		$db_handler = new MCE_DB_Handler();

		// 2. Obtener los productos
		$productos = $db_handler->get_productos();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Productos de la Base de Datos Externa', 'mi-conexion-externa' ) ); ?></h1>
			
			<?php
			// 3. Manejar Errores (Regla 1)
			if ( is_wp_error( $productos ) ) :
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<strong><?php echo esc_html( __( 'Error al Cargar Productos:', 'mi-conexion-externa' ) ); ?></strong>
					</p>
					<p>
						<?php
						// Imprimimos el error de forma segura.
						echo esc_html( $productos->get_error_message() );
						if ( $productos->get_error_data() ) {
							echo '<br><em>' . esc_html( __( 'Detalle:', 'mi-conexion-externa' ) . ' ' . $productos->get_error_data() ) . '</em>';
						}
						?>
					</p>
				</div>
				<?php
			// 4. Manejar Tabla Vacía
			elseif ( empty( $productos ) ) :
				?>
				<div class="notice notice-info">
					<p><?php echo esc_html( __( 'No se encontraron productos en la tabla \'mce_productos\'.', 'mi-conexion-externa' ) ); ?></p>
				</div>
				<?php
			// 5. Renderizar la Tabla (Éxito)
			else :
				?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-primary"><?php echo esc_html( __( 'Nombre', 'mi-conexion-externa' ) ); ?></th>
							<th scope="col" class="manage-column"><?php echo esc_html( __( 'SKU', 'mi-conexion-externa' ) ); ?></th>
							<th scope="col" class="manage-column"><?php echo esc_html( __( 'Precio', 'mi-conexion-externa' ) ); ?></th>
							<th scope="col" class="manage-column"><?php echo esc_html( __( 'Stock', 'mi-conexion-externa' ) ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php foreach ( $productos as $producto ) : ?>
							<tr>
								<td class="title column-title">
									<strong><?php echo esc_html( $producto['nombre'] ); ?></strong>
								</td>
								<td>
									<?php echo esc_html( $producto['sku'] ); ?>
								</td>
								<td>
									$<?php echo esc_html( number_format( $producto['precio'], 2 ) ); ?>
								</td>
								<td>
									<?php echo esc_html( $producto['stock'] ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * La comprobación 'is_admin()' es crucial.
 */
if ( is_admin() ) {
	new MCE_Productos_Page();
}