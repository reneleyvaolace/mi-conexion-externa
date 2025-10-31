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

		// Hook para cargar scripts JS/CSS solo en nuestra página.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Hook para el receptor de la prueba de conexión AJAX.
		add_action( 'wp_ajax_mce_test_connection', array( $this, 'handle_ajax_test_connection' ) );
	}

	/**
	 * Carga y localiza scripts solo en nuestra página de ajustes.
	 *
	 * @param string $hook_suffix El sufijo de la página de admin actual.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Comprueba si estamos en nuestra página de ajustes.
		if ( 'settings_page_mce-settings' !== $hook_suffix ) {
			return;
		}

		// Registramos nuestro script.
		$script_path = MCE_PLUGIN_DIR . 'admin/js/mce-admin-script.js';
		$script_asset_path = MCE_PLUGIN_DIR . 'admin/js/mce-admin-script.asset.php';
		$version = MCE_VERSION;
		$dependencies = array( 'jquery' ); 

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

		// Pasamos datos de PHP a JS (Nonce y URL de AJAX).
		wp_localize_script(
			'mce-admin-script',
			'mce_ajax_object', // Nombre del objeto JS.
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'test_nonce'  => wp_create_nonce( 'mce_test_nonce' ),
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
			$this->settings_page_slug, 
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
	 * Callbacks de campos (sin cambios).
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
	 * *** FUNCIÓN MODIFICADA ***
	 *
	 * El "cerebro" de la prueba de conexión AJAX.
	 * Ahora con detección de errores comunes y mensajes amigables.
	 */
	public function handle_ajax_test_connection() {
		// 1. Seguridad: Verificar el Nonce (Regla 1).
		check_ajax_referer( 'mce_test_nonce', 'security' );

		// 2. Obtener las credenciales guardadas.
		$opts = get_option( 'mce_db_settings' );

		// 3. Validar que los campos existan.
		if ( empty( $opts['mce_db_ip'] ) || empty( $opts['mce_db_port'] ) || empty( $opts['mce_db_name'] ) || empty( $opts['mce_db_user'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error: Faltan credenciales. Por favor, rellena todos los campos (IP, Puerto, Nombre de BBDD, Usuario) y guarda los ajustes primero.', 'mi-conexion-externa' ),
				)
			);
		}

		// 4. Intentar la conexión.
		$host = $opts['mce_db_ip'] . ':' . $opts['mce_db_port'];
		mysqli_report( MYSQLI_REPORT_OFF ); // Suprimir excepciones.

		$link = @mysqli_connect(
			$host,
			$opts['mce_db_user'],
			$opts['mce_db_pass'],
			$opts['mce_db_name']
		);

		// 5. Devolver la respuesta JSON.
		if ( ! $link ) {
			// *** SECCIÓN DE LÓGICA DE ERRORES MEJORADA ***
			$raw_error_message = mysqli_connect_error();
			$friendly_message = '';
			$solution_message = '';

			// CASO 1: Detectar el error de 'flush-hosts'.
			if ( strpos( $raw_error_message, 'is blocked because of many connection errors' ) !== false ) {
				$friendly_message = __( 'FALLO LA CONEXIÓN (Servidor Bloqueado)', 'mi-conexion-externa' );
				$solution_message = __( '<strong>Posible Solución:</strong> El servidor de la base de datos ha bloqueado tu IP (la de este sitio web) debido a múltiples intentos fallidos. Debes contactar al administrador de esa base de datos y pedirle que ejecute el comando <code>mysqladmin flush-hosts</code> o la consulta SQL <code>FLUSH HOSTS;</code> para desbloquearla.', 'mi-conexion-externa' );
			
			// CASO 2: Detectar 'Acceso Denegado' (usuario/contraseña).
			} elseif ( strpos( $raw_error_message, 'Access denied for user' ) !== false ) {
				$friendly_message = __( 'FALLO LA CONEXIÓN (Acceso Denegado)', 'mi-conexion-externa' );
				$solution_message = __( '<strong>Posible Solución:</strong> El usuario o la contraseña son incorrectos. Verifícalos y vuelve a intentarlo.', 'mi-conexion-externa' );
			
			// CASO 3: Detectar 'Base de Datos Desconocida'.
			} elseif ( strpos( $raw_error_message, 'Unknown database' ) !== false ) {
				$friendly_message = __( 'FALLO LA CONEXIÓN (Base de Datos no encontrada)', 'mi-conexion-externa' );
				$solution_message = __( '<strong>Posible Solución:</strong> El nombre de la base de datos es incorrecto o no existe. Verifica que esté bien escrito.', 'mi-conexion-externa' );

			// CASO 4: Detectar 'No se puede conectar' (Firewall / IP / Puerto).
			} elseif ( strpos( $raw_error_message, 'Can\'t connect to MySQL server' ) !== false || strpos( $raw_error_message, 'Connection timed out' ) !== false ) {
				$friendly_message = __( 'FALLO LA CONEXIÓN (No se puede alcanzar el servidor)', 'mi-conexion-externa' );
				$solution_message = __( '<strong>Posible Solución:</strong> Verifica la IP y el Puerto. Si son correctos, es muy probable que un <strong>Firewall</strong> esté bloqueando la conexión. Asegúrate de que el servidor de la BBDD permite conexiones remotas desde la IP de este sitio web.', 'mi-conexion-externa' );
			
			// CASO 5: Error genérico (mostramos el error original).
			} else {
				$friendly_message = __( 'FALLO LA CONEXIÓN (Error Desconocido)', 'mi-conexion-externa' );
				$solution_message = __( '<strong>Mensaje Original:</strong>', 'mi-conexion-externa' ) . ' ' . esc_html( $raw_error_message );
			}

			// Construir el mensaje final.
			$final_message = $friendly_message . '<br>' . $solution_message;

			wp_send_json_error(
				array(
					'message' => $final_message,
				)
			);
			// *** FIN DE LA SECCIÓN MEJORADA ***

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