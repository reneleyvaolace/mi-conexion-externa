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
		$this->options = get_option( 'mce_api_settings' );
		?>
		<div class="wrap">
			<h2><?php echo esc_html( __( 'Ajustes de Conexión Externa', 'mi-conexion-externa' ) ); ?></h2>
			<form method="post" action="options.php">
				<?php
				// Esta función imprime los campos de seguridad (nonces).
				settings_fields( 'mce_settings_group' );

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
		 * WordPress guardará nuestros datos en la tabla wp_options
		 * con el nombre 'mce_api_settings'.
		 */
		register_setting(
			'mce_settings_group',                 // Nombre del grupo (debe coincidir con settings_fields()).
			'mce_api_settings',                   // Nombre de la opción en la base de datos.
			array( $this, 'sanitize_settings' )   // Callback para sanitizar los datos.
		);

		/**
		 * 2. Añadir una Sección de Ajustes.
		 */
		add_settings_section(
			'mce_api_credentials_section',             // ID de la sección.
			__( 'Credenciales de API', 'mi-conexion-externa' ), // Título de la sección.
			array( $this, 'print_section_info' ),      // Callback para la descripción de la sección.
			'mce-settings-admin'                       // Slug de la página (debe coincidir con do_settings_sections()).
		);

		/**
		 * 3. Añadir los Campos de Ajustes.
		 */
		add_settings_field(
			'api_key',                                // ID del campo.
			__( 'API Key', 'mi-conexion-externa' ),   // Título del campo.
			array( $this, 'api_key_field_callback' ), // Callback que renderiza el campo.
			'mce-settings-admin',                     // Slug de la página.
			'mce_api_credentials_section'             // ID de la sección a la que pertenece.
		);

		add_settings_field(
			'api_endpoint_url',
			__( 'URL del Endpoint', 'mi-conexion-externa' ),
			array( $this, 'api_endpoint_url_field_callback' ),
			'mce-settings-admin',
			'mce_api_credentials_section'
		);
	}

	/**
	 * Callback para sanitizar CADA campo antes de guardarlo.
	 *
	 * @param array $input Los datos crudos del formulario.
	 * @return array Los datos sanitizados.
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		if ( isset( $input['api_key'] ) ) {
			$sanitized_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		if ( isset( $input['api_endpoint_url'] ) ) {
			// 'esc_url_raw' es mejor para guardar URLs en la BD.
			$sanitized_input['api_endpoint_url'] = esc_url_raw( $input['api_endpoint_url'] );
		}

		return $sanitized_input;
	}

	/**
	 * Callback para imprimir la descripción de la sección.
	 */
	public function print_section_info() {
		echo esc_html( __( 'Introduce las credenciales para conectar con el servicio externo.', 'mi-conexion-externa' ) );
	}

	/**
	 * Callback para renderizar el campo 'API Key'.
	 */
	public function api_key_field_callback() {
		// Usamos 'esc_attr' para imprimir de forma segura el valor en el atributo 'value'.
		$value = isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : '';
		printf(
			'<input type="password" id="api_key" name="mce_api_settings[api_key]" value="%s" class="regular-text" />',
			$value
		);
	}

	/**
	 * Callback para renderizar el campo 'URL del Endpoint'.
	 */
	public function api_endpoint_url_field_callback() {
		$value = isset( $this->options['api_endpoint_url'] ) ? esc_attr( $this->options['api_endpoint_url'] ) : '';
		printf(
			'<input type="text" id="api_endpoint_url" name="mce_api_settings[api_endpoint_url]" value="%s" class="regular-text" />',
			$value
		);
		echo '<p class="description">' . esc_html__( 'Ej: https://api.misitio.com/v1/', 'mi-conexion-externa' ) . '</p>';
	}
}

/**
 * La comprobación 'is_admin()' es crucial.
 * No queremos instanciar esta clase en el 'frontend' del sitio,
 * solo en el panel de administración.
 */
if ( is_admin() ) {
	new MCE_Settings_Page();
}