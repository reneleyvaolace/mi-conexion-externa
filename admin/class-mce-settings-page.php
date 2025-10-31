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
	 * Almacena el 'slug' de nuestra página de ajustes.
	 *
	 * @var string
	 */
	private $settings_page_slug = 'mce-settings';

	/**
	 * Constructor. Engancha los métodos a los hooks de WordPress.
	 */
	public function __construct() {
		// Hook para añadir el menú de administración.
		add_action( 'admin_menu', array( $this, 'add_plugin_settings_page' ) );

		// Hook para registrar nuestros ajustes.
		add_action( 'admin_init', array( $this, 'register_and_build_fields' ) );

		// *** NUEVO: Hook para cargar scripts JS/CSS solo en nuestra página ***
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// *** NUEVO: Hook para el receptor de la prueba de conexión AJAX ***
		add_action( 'wp_ajax_mce_test_connection', array( $this, 'handle_ajax_test_connection' ) );
	}

	/**
	 * *** NUEVO: Carga y localiza scripts solo en nuestra página de ajustes ***
	 *
	 * @param string $hook_suffix El sufijo de la página de admin actual.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Comprueba si estamos en nuestra página de ajustes (Regla 1: Mejor Práctica).
		// 'settings_page_' + $this->settings_page_slug
		if ( 'settings_page_mce-settings' !== $hook_suffix ) {
			return;
		}

		// Registramos nuestro script. Lo crearemos en el próximo paso.
		$script_path = MCE_PLUGIN_DIR . 'admin/js/mce-admin-script.js';
		$script_asset_path = MCE_PLUGIN_DIR . 'admin/js/mce-admin-script.asset.php';
		$version = MCE_VERSION;
		$dependencies = array( 'jquery' ); // WordPress incluye jQuery.

		// Comprobación por si usamos un sistema de 'build' en el futuro.
		if ( file_exists( $script_asset_path ) ) {
			$asset_file = include $script_asset_path;
			$version = $asset_file['version'];
			$dependencies = $asset_file['dependencies'];
		}

		wp_enqueue_script(
			'mce-admin-script',
			MCE_PLUGIN_URL . 'admin/js/mce-admin-script.js',
			$dependencies,
			$version,
			true // Cargar en el footer.
		);

		// *** NUEVO: Pasamos datos de PHP a JS (Nonce y URL de AJAX) (Regla 1) ***
		wp_localize_script(
			'mce-admin-script',
			'mce_ajax_object', // Nombre del objeto JS que crearemos.
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ), // La URL de AJAX de WordPress.
				'test_nonce'  => wp_create_nonce( 'mce_test_nonce' ), // Nonce de seguridad.
				'testing_text' => __( 'Probando conexión...', 'mi-conexion-externa' ),
				'error_text'   => __( 'Error. Revisa la consola.', 'mi-conexion-externa' ),
			)
		);
	}


	/**
	 * Añade la página de ajustes como un submenú de "Ajustes".
	 */
	public function add_plugin_settings_page() {
		add_options_page(
			__( 'Mi Conexión Externa', 'mi-conexion-externa' ),
			__( 'Mi Conexión Externa', 'mi-conexion-externa' ),
			'manage_options',
			$this->settings_page_slug, // Usamos la variable de clase.
			array( $this, 'create_settings_page_content' )
		);
	}

	/**
	 * Renderiza el contenido HTML de la página de ajustes.
	 */
	public function create_settings_page_content() {
		$this->options = get_option( 'mce_db_settings' );
		?>
		<div class="wrap">
			<h2><?php echo esc_html( __( 'Ajustes de Conexión a Base de Datos Externa', 'mi-conexion-externa' ) ); ?></h2>
			
			<div id="mce-test-connection-notice" class="notice" style="display:none; margin-left: 0;"></div>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'mce_db_settings_group' );
				do_settings_sections( 'mce-settings-admin' );
				submit_button();
				?>
			</form>

			<p>
				<button type="button" id="mce-test-connection-btn" class="button button-secondary">
					<?php echo esc_html( __( 'Probar Conexión a BBDD', 'mi-conexion-externa' ) ); ?>
				</button>
				<span class="spinner" style="float: none; vertical-align: middle;"></span>
			</p>

		</div>
		<?php
	}

	/**
	 * Registra las secciones y campos usando la Settings API.
	 */
	public function register_and_build_fields() {
		register_setting(
			'mce_db_settings_group',
			'mce_db_settings',
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'mce_db_credentials_section',
			__( 'Credenciales de la Base de Datos', 'mi-conexion-externa' ),
			array( $this, 'print_section_info' ),
			'mce-settings-admin'
		);

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
	 * (Sin cambios desde la última vez)
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		if ( isset( $input['mce_db_ip'] ) ) {
			$sanitized_input['mce_db_ip'] = sanitize_text_field( $input['mce_db_ip'] );
		}
		if ( isset( $input['mce_db_port'] ) ) {
			$sanitized_input['mce_db_port'] = intval( $input['mce_db_port'] );
		}
		if ( isset( $input['mce_db_name'] ) ) {
			$sanitized_input['mce_db_name'] = sanitize_text_field( $input['mce_db_name'] );
		}
		if ( isset( $input['mce_db_user'] ) ) {
			$sanitized_input['mce_db_user'] = sanitize_text_field( $input['mce_db_user'] );
		}
		if ( isset( $input['mce_db_pass'] ) ) {
			if ( ! empty( $input['mce_db_pass'] ) ) {
				$sanitized_input['mce_db_pass'] = sanitize_text_field( $input['mce_db_pass'] );
			} else {
				$sanitized_input['mce_db_pass'] = '';
			}
		}

		return $sanitized_input;
	}

	/**
	 * (Callbacks de campos, sin cambios desde la última vez)
	 */

	public function print_section_info() {
		echo esc_html( __( 'Introduce las credenciales para la conexión directa a la base de datos.', 'mi-conexion-externa' ) );
	}

	public function db_ip_callback() {
		$value = isset( $this->options['mce_db_ip'] ) ? esc_attr( $this->options['mce_db_ip'] ) : '';
		printf( '<input type="text" id="mce_db_ip" name="mce_db_settings[mce_db_ip]" value="%s" class="regular-text" />', $value );
	}

	public function db_port_callback() {
		$value = isset( $this->options['mce_db_port'] ) ? esc_attr( $this->options['mce_db_port'] ) : '3306';
		printf( '<input type="number" id="mce_db_port" name="mce_db_settings[mce_db_port]" value="%s" class="small-text" />', $value );
		echo ' <p class="description" style="display:inline-block">' . esc_html__( '(Ej. 3306 para MySQL por defecto)', 'mi-conexion-externa' ) . '</p>';
	}

	public function db_name_callback() {
		$value = isset( $this->options['mce_db_name'] ) ? esc_attr( $this->options['mce_db_name'] ) : '';
		printf( '<input type="text" id="mce_db_name" name="mce_db_settings[mce_db_name]" value="%s" class="regular-text" />', $value );
	}

	public function db_user_callback() {
		$value = isset( $this->options['mce_db_user'] ) ? esc_attr( $this->options['mce_db_user'] ) : '';
		printf( '<input type="text" id="mce_db_user" name="mce_db_settings[mce_db_user]" value="%s" class="regular-text" />', $value );
	}

	public function db_pass_callback() {
		$value = isset( $this->options['mce_db_pass'] ) ? esc_attr( $this->options['mce_db_pass'] ) : '';
		printf( '<input type="password" id="mce_db_pass" name="mce_db_settings[mce_db_pass]" value="%s" class="regular-text" />', $value );
	}


	/**
	 * *** NUEVO: El "cerebro" de la prueba de conexión AJAX ***
	 *
	 * Esta función se dispara cuando nuestro JavaScript llama a 'wp_ajax_mce_test_connection'.
	 */
	public function handle_ajax_test_connection() {
		// 1. Seguridad: Verificar el Nonce (Regla 1).
		check_ajax_referer( 'mce_test_nonce', 'security' );

		// 2. Obtener las credenciales guardadas (¡no del $_POST!).
		$opts = get_option( 'mce_db_settings' );

		// 3. Validar que los campos existan y no estén vacíos.
		if ( empty( $opts['mce_db_ip'] ) || empty( $opts['mce_db_port'] ) || empty( $opts['mce_db_name'] ) || empty( $opts['mce_db_user'] ) ) {
			// (Nota: Permitimos una contraseña vacía, algunas BBDD lo permiten).
			wp_send_json_error(
				array(
					'message' => __( 'Error: Faltan credenciales. Por favor, rellena todos los campos (IP, Puerto, Nombre de BBDD, Usuario) y guarda los ajustes primero.', 'mi-conexion-externa' ),
				)
			);
		}

		// 4. Intentar la conexión (Regla 1: Usar @ para suprimir errores).
		// Usamos la extensión mysqli, que es estándar en PHP.
		$host = $opts['mce_db_ip'] . ':' . $opts['mce_db_port'];
		
		// Limpiamos mysqli_report para que no lance excepciones antes de nuestro control.
		mysqli_report( MYSQLI_REPORT_OFF );

		$link = @mysqli_connect(
			$host,
			$opts['mce_db_user'],
			$opts['mce_db_pass'],
			$opts['mce_db_name']
		);

		// 5. Devolver la respuesta JSON.
		if ( ! $link ) {
			// La conexión falló.
			wp_send_json_error(
				array(
					'message' => __( 'FALLO LA CONEXIÓN.', 'mi-conexion-externa' ) . ' ' . mysqli_connect_error(),
				)
			);
		} else {
			// La conexión fue exitosa.
			mysqli_close( $link );
			wp_send_json_success(
				array(
					'message' => __( '¡Éxito! Conexión a la base de datos establecida correctamente.', 'mi-conexion-externa' ),
				)
			);
		}

		// wp_die() es llamado implícitamente por wp_send_json_...
	}
}

/**
 * La comprobación 'is_admin()' es crucial.
 */
if ( is_admin() ) {
	new MCE_Settings_Page();
}