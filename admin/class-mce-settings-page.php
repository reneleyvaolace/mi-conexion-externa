<?php
/**
 * Página de Ajustes para CoreAura: Conexión Externa
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MCE_Settings_Page {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'mce-settings' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'mce-admin-global',
            plugins_url( 'admin/css/mce-admin-global.css', dirname( dirname( __FILE__ ) ) . '/mi-conexion-externa.php' ),
            array(),
            '1.1.5'
        );

        wp_enqueue_script(
            'mce-admin-script',
            plugins_url( 'admin/js/mce-admin-script.js', dirname( dirname( __FILE__ ) ) . '/mi-conexion-externa.php' ),
            array( 'jquery' ),
            '1.1.5',
            true
        );

        wp_localize_script(
            'mce-admin-script',
            'mce_ajax_object',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'test_nonce'   => wp_create_nonce( 'mce_test_nonce' ),
                'testing_text' => __( 'Probando conexión...', 'mi-conexion-externa' ),
                'error_text'   => __( 'Error de comunicación con el servidor.', 'mi-conexion-externa' )
            )
        );
    }

    public function register_settings() {
        register_setting( 'mce_settings', 'mce_db_host', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'mce_settings', 'mce_db_port', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'mce_settings', 'mce_db_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'mce_settings', 'mce_db_user', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'mce_settings', 'mce_db_pass', array( 'sanitize_callback' => 'sanitize_text_field' ) );

        add_settings_section(
            'mce_db_section',
            __( 'Credenciales de Base de Datos Externa', 'mi-conexion-externa' ),
            array( $this, 'section_description' ),
            'mce-settings'
        );

        add_settings_field( 'mce_db_host', __( 'Host / IP', 'mi-conexion-externa' ), array( $this, 'render_text_field' ), 'mce-settings', 'mce_db_section', array( 'id' => 'mce_db_host', 'placeholder' => 'localhost o 192.168.1.100' ) );
        add_settings_field( 'mce_db_port', __( 'Puerto', 'mi-conexion-externa' ), array( $this, 'render_text_field' ), 'mce-settings', 'mce_db_section', array( 'id' => 'mce_db_port', 'placeholder' => '3306' ) );
        add_settings_field( 'mce_db_name', __( 'Nombre de la Base de Datos', 'mi-conexion-externa' ), array( $this, 'render_text_field' ), 'mce-settings', 'mce_db_section', array( 'id' => 'mce_db_name', 'placeholder' => 'mi_base_datos' ) );
        add_settings_field( 'mce_db_user', __( 'Usuario', 'mi-conexion-externa' ), array( $this, 'render_text_field' ), 'mce-settings', 'mce_db_section', array( 'id' => 'mce_db_user', 'placeholder' => 'usuario_db' ) );
        add_settings_field( 'mce_db_pass', __( 'Contraseña', 'mi-conexion-externa' ), array( $this, 'render_password_field' ), 'mce-settings', 'mce_db_section', array( 'id' => 'mce_db_pass' ) );
    }

    public function section_description() {
        echo '<p>' . esc_html__( 'Configure los datos de acceso a su base de datos externa MySQL/MariaDB.', 'mi-conexion-externa' ) . '</p>';
    }

    public function render_text_field( $args ) {
        $id = $args['id'];
        $value = get_option( $id, '' );
        $placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
        printf( '<input type="text" id="%s" name="%s" value="%s" placeholder="%s" class="regular-text" />', esc_attr( $id ), esc_attr( $id ), esc_attr( $value ), esc_attr( $placeholder ) );
    }

    public function render_password_field( $args ) {
        $id = $args['id'];
        $value = get_option( $id, '' );
        printf( '<input type="password" id="%s" name="%s" value="%s" class="regular-text" />', esc_attr( $id ), esc_attr( $id ), esc_attr( $value ) );
    }

    public function create_settings_page_content() {
        ?>
        <div class="wrap mce-admin-page">
            <h1><?php esc_html_e( 'Ajustes de Conexión Externa', 'mi-conexion-externa' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'mce_settings' );
                    do_settings_sections( 'mce-settings' );
                    submit_button( __( 'Guardar cambios', 'mi-conexion-externa' ) );
                ?>
            </form>

            <hr style="margin: 30px 0;">
            
            <div class="mce-section">
                <h2><?php esc_html_e( 'Prueba tu conexión a la base de datos', 'mi-conexion-externa' ); ?></h2>
                <p><?php esc_html_e( 'Haz clic para verificar que las credenciales funcionan correctamente.', 'mi-conexion-externa' ); ?></p>
                <button id="mce-test-connection-btn" class="button button-secondary" type="button">
                    <?php esc_html_e( 'Probar Conexión a BBDD', 'mi-conexion-externa' ); ?>
                </button>
                <span class="spinner" style="float:none; margin: 0 10px;"></span>
                <div id="mce-test-connection-notice" class="notice" style="display:none; margin-top:15px;"></div>
            </div>
        </div>
        <?php
    }
}

function mce_test_connection_ajax() {
    check_ajax_referer( 'mce_test_nonce', 'security' );

    $host = get_option( 'mce_db_host', '' );
    $port = get_option( 'mce_db_port', '3306' );
    $name = get_option( 'mce_db_name', '' );
    $user = get_option( 'mce_db_user', '' );
    $pass = get_option( 'mce_db_pass', '' );

    if ( empty( $host ) || empty( $name ) || empty( $user ) ) {
        wp_send_json_error( array( 'message' => __( 'Por favor, complete todos los campos de conexión primero.', 'mi-conexion-externa' ) ) );
    }

    $mysqli = @new mysqli( $host, $user, $pass, $name, $port );

    if ( $mysqli->connect_errno ) {
        wp_send_json_error( array( 'message' => sprintf( __( 'Error de conexión: %s', 'mi-conexion-externa' ), $mysqli->connect_error ) ) );
    } else {
        $mysqli->close();
        wp_send_json_success( array( 'message' => __( '¡Éxito! Conexión establecida correctamente.', 'mi-conexion-externa' ) ) );
    }
}
add_action( 'wp_ajax_mce_test_connection', 'mce_test_connection_ajax' );
