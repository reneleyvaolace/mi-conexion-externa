<?php
/**
 * Lógica para la Página de Ajustes del Administrador.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Settings_Page
 *
 * Encapsula la creación de la página de ajustes del plugin
 * bajo el menú "Ajustes" de WordPress.
 */
class MCE_Settings_Page {

	/**
	 * Almacena los valores de nuestras opciones.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor. Engancha los métodos a los hooks de WordPress.
	 */
	public function __construct() {
		// Hook para añadir el menú de administración.
		add_action( 'admin_menu', array( $this, 'add_plugin_settings_page' ) );

		// Hook para registrar nuestros ajustes.
		add_action( 'admin_init', array( $this, 'register_and_build_fields' ) );
	}

	/**
	 * Añade la página de ajustes como un submenú de "Ajustes".
	 */
	public function add_plugin_settings_page() {
		add_options_page(
			__( 'Mi Conexión Externa', 'mi-conexion-externa' ), // Título de la página
			__( 'Mi Conexión Externa', 'mi-conexion-externa' ), // Título del menú
			'manage_options',                                   // Capacidad requerida
			'mce-settings',                                     // Slug del menú
			array( $this, 'create_settings_page_content' )      // Función que renderiza la página
		);
	}

	/**
	 * Renderiza el contenido HTML de la página de ajustes.
	 */
	public function create_settings_page_content() {
		// Obtenemos nuestras opciones guardadas para pasarlas a los campos.
		// *** CAMBIO: Usaremos 'mce_db_settings' ***
		$this->options = get_option( 'mce_db_settings' );
		?>
		<div class="wrap">
			<h2><?php echo esc_html( __( 'Ajustes de Conexión a Base de Datos Externa', 'mi-conexion-externa' ) ); ?></h2>
			<form method="post" action="options.php">
				<?php
				// *** CAMBIO: Usaremos 'mce_db_settings_group' ***
				settings_fields( 'mce_db_settings_group' );

				// Imprime las secciones y campos registrados.
				do_settings_sections( 'mce-settings-admin' );

				// Imprime el botón de guardar.
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registra las secciones y campos usando la Settings API.
	 */
	public function register_and_build_fields() {
		/**
		 * 1. Registrar el ajuste (el grupo de opciones).
		 *
		 * *** CAMBIO: Nuevo nombre de opción 'mce_db_settings' ***
		 */
		register_setting(
			'mce_db_settings_group',              // Nombre del grupo (debe coincidir con settings_fields()).
			'mce_db_settings',                    // Nombre de la opción en la base de datos.
			array( $this, 'sanitize_settings' )   // Callback para sanitizar los datos.
		);

		/**
		 * 2. Añadir una Sección de Ajustes.
		 *
		 * *** CAMBIO: Título actualizado ***
		 */
		add_settings_section(
			'mce_db_credentials_section',             // ID de la sección.
			__( 'Credenciales de la Base de Datos', 'mi-conexion-externa' ), // Título de la sección.
			array( $this, 'print_section_info' ),      // Callback para la descripción de la sección.
			'mce-settings-admin'                       // Slug de la página (debe coincidir con do_settings_sections()).
		);

		/**
		 * 3. Añadir los Campos de Ajustes (NUEVOS CAMPOS).
		 */
		add_settings_field(
			'mce_db_ip',
			__( 'IP o Host', 'mi-conexion-externa' ),
			array( $this, 'db_ip_callback' ),
			'mce-settings-admin',
			'mce_db_credentials_section'
		);

		add_settings_field(
			'mce_db_port',
			__( 'Puerto', 'mi-conexion-externa' ),
			array( $this, 'db_port_callback' ),
			'mce-settings-admin',
			'mce_db_credentials_section'
		);

		add_settings_field(
			'mce_db_name',
			__( 'Nombre de la Base de Datos', 'mi-conexion-externa' ),
			array( $this, 'db_name_callback' ),
			'mce-settings-admin',
			'mce_db_credentials_section'
		);

		add_settings_field(
			'mce_db_user',
			__( 'Usuario', 'mi-conexion-externa' ),
			array( $this, 'db_user_callback' ),
			'mce-settings-admin',
			'mce_db_credentials_section'
		);

		add_settings_field(
			'mce_db_pass',
			__( 'Contraseña', 'mi-conexion-externa' ),
			array( $this, 'db_pass_callback' ),
			'mce-settings-admin',
			'mce_db_credentials_section'
		);
	}

	/**
	 * Callback para sanitizar CADA campo antes de guardarlo.
	 *
	 * *** CAMBIO: Actualizado para los 5 campos nuevos ***
	 *
	 * @param array $input Los datos crudos del formulario.
	 * @return array Los datos sanitizados.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		if ( isset( $input['mce_db_ip'] ) ) {
			$sanitized_input['mce_db_ip'] = sanitize_text_field( $input['mce_db_ip'] );
		}
		if ( isset( $input['mce_db_port'] ) ) {
			// Aseguramos que el puerto sea un entero.
			$sanitized_input['mce_db_port'] = intval( $input['mce_db_port'] );
		}
		if ( isset( $input['mce_db_name'] ) ) {
			$sanitized_input['mce_db_name'] = sanitize_text_field( $input['mce_db_name'] );
		}
		if ( isset( $input['mce_db_user'] ) ) {
			$sanitized_input['mce_db_user'] = sanitize_text_field( $input['mce_db_user'] );
		}
		if ( isset( $input['mce_db_pass'] ) ) {
			// Permite que se guarde una contraseña vacía si se borra,
			// pero sanitiza si hay contenido.
			if ( ! empty( $input['mce_db_pass'] ) ) {
				$sanitized_input['mce_db_pass'] = sanitize_text_field( $input['mce_db_pass'] );
			} else {
				$sanitized_input['mce_db_pass'] = '';
			}
		}

		return $sanitized_input;
	}

	/**
	 * Callback para imprimir la descripción de la sección.
	 */
	public function print_section_info() {
		echo esc_html( __( 'Introduce las credenciales para la conexión directa a la base de datos.', 'mi-conexion-externa' ) );
	}

	/**
	 * === Callbacks de Campos Nuevos ===
	 */

	public function db_ip_callback() {
		$value = isset( $this->options['mce_db_ip'] ) ? esc_attr( $this->options['mce_db_ip'] ) : '';
		printf(
			'<input type="text" id="mce_db_ip" name="mce_db_settings[mce_db_ip]" value="%s" class="regular-text" />',
			$value
		);
	}

	public function db_port_callback() {
		$value = isset( $this->options['mce_db_port'] ) ? esc_attr( $this->options['mce_db_port'] ) : '3306';
		printf(
			'<input type="number" id="mce_db_port" name="mce_db_settings[mce_db_port]" value="%s" class="small-text" />',
			$value
		);
		echo ' <p class="description" style="display:inline-block">' . esc_html__( '(Ej. 3306 para MySQL por defecto)', 'mi-conexion-externa' ) . '</p>';
	}

	public function db_name_callback() {
		$value = isset( $this->options['mce_db_name'] ) ? esc_attr( $this->options['mce_db_name'] ) : '';
		printf(
			'<input type="text" id="mce_db_name" name="mce_db_settings[mce_db_name]" value="%s" class="regular-text" />',
			$value
		);
	}

	public function db_user_callback() {
		$value = isset( $this->options['mce_db_user'] ) ? esc_attr( $this->options['mce_db_user'] ) : '';
		printf(
			'<input type="text" id="mce_db_user" name="mce_db_settings[mce_db_user]" value="%s" class="regular-text" />',
			$value
		);
	}

	public function db_pass_callback() {
		// Nota: Usamos 'password' como tipo para ocultar la entrada.
		$value = isset( $this->options['mce_db_pass'] ) ? esc_attr( $this->options['mce_db_pass'] ) : '';
		printf(
			'<input type="password" id="mce_db_pass" name="mce_db_settings[mce_db_pass]" value="%s" class="regular-text" />',
			$value
		);
	}
}

/**
 * La comprobación 'is_admin()' es crucial.
 */
if ( is_admin() ) {
	new MCE_Settings_Page();
}