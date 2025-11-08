<?php
/**
 * Página de Opciones de Estilo para CoreAura: Conexión Externa
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MCE_Style_Settings_Page {

    private static $initialized = false;

    public function __construct() {
        if ( self::$initialized ) return;
        self::$initialized = true;

        add_action( 'admin_menu', array( $this, 'add_style_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_head', array( $this, 'output_custom_styles' ), 99 );
    }

    public function add_style_settings_page() {
        add_submenu_page(
            'mce-main-menu',
            __( 'Ajustes de Estilo MCE', 'mi-conexion-externa' ),
            __( 'Estilo', 'mi-conexion-externa' ),
            'manage_options',
            'mce-style-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'mce-style-settings' ) !== false ) {
            wp_enqueue_style(
                'mce-admin-global',
                plugins_url( 'admin/css/mce-admin-global.css', dirname( dirname( __FILE__ ) ) . '/mi-conexion-externa.php' ),
                array(),
                '1.1.5'
            );
            
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
            add_action( 'admin_footer', function() {
                ?>
                <script>
                jQuery(function($) {
                    $('.mce-color-picker').wpColorPicker();
                });
                </script>
                <?php
            } );
        }
    }

    public function register_settings() {
        register_setting( 'mce_style_settings', 'mce_style_settings', array( $this, 'sanitize_settings' ) );
        
        add_settings_section(
            'mce_style_section',
            __( 'Personalización Visual de Grids y Tarjetas', 'mi-conexion-externa' ),
            array( $this, 'section_description' ),
            'mce-style-settings'
        );

        $fields = $this->get_style_fields();
        foreach ( $fields as $field => $info ) {
            add_settings_field(
                $field,
                esc_html( $info['label'] ),
                array( $this, 'field_callback' ),
                'mce-style-settings',
                'mce_style_section',
                array(
                    'id'    => $field,
                    'type'  => $info['type'],
                    'desc'  => $info['desc']
                )
            );
        }
    }

    public function get_style_fields() {
        return array(
            'color_titulo' => array(
                'label' => __( 'Color de Título', 'mi-conexion-externa' ),
                'type'  => 'color',
                'desc'  => __( 'Personaliza el color del título en las cards', 'mi-conexion-externa' ),
            ),
            'tamano_titulo' => array(
                'label' => __( 'Tamaño del Título', 'mi-conexion-externa' ),
                'type'  => 'text',
                'desc'  => __( 'Ejemplo: 1.5rem, 24px.', 'mi-conexion-externa' ),
            ),
            'color_etiqueta' => array(
                'label' => __( 'Color de Etiquetas', 'mi-conexion-externa' ),
                'type'  => 'color',
                'desc'  => __( 'Color de las etiquetas de campos (ej. "SKU:", "Precio:")' , 'mi-conexion-externa' ),
            ),
            'color_valor' => array(
                'label' => __( 'Color de Valores', 'mi-conexion-externa' ),
                'type'  => 'color',
                'desc'  => __( 'Color de los valores de campos', 'mi-conexion-externa' ),
            ),
            'color_enlace' => array(
                'label' => __( 'Color de Enlaces (PDF)', 'mi-conexion-externa' ),
                'type'  => 'color',
                'desc'  => __( 'Color de los enlaces a archivos PDF', 'mi-conexion-externa' ),
            ),
            'color_fondo_card' => array(
                'label' => __( 'Color de Fondo de Card', 'mi-conexion-externa' ),
                'type'  => 'color',
                'desc'  => __( 'Color de fondo para cada tarjeta de producto', 'mi-conexion-externa' ),
            ),
            'sombra_card' => array(
                'label' => __( 'Sombras de Card', 'mi-conexion-externa' ),
                'type'  => 'text',
                'desc'  => __( 'CSS box-shadow (ej. 0 4px 12px rgba(0,0,0,0.05))', 'mi-conexion-externa' ),
            ),
        );
    }

    public function field_callback( $args ) {
        $settings = get_option( 'mce_style_settings', array() );
        $value = isset( $settings[ $args['id'] ] ) ? esc_attr( $settings[ $args['id'] ] ) : '';
        switch ( $args['type'] ) {
            case 'color':
                printf(
                    '<input type="text" class="mce-color-picker" name="mce_style_settings[%s]" value="%s" data-default-color="#000000" />',
                    esc_attr( $args['id'] ), $value
                );
                break;
            default:
                printf(
                    '<input type="text" name="mce_style_settings[%s]" value="%s" style="width:300px;" />',
                    esc_attr( $args['id'] ), $value
                );
                break;
        }
        if ( ! empty( $args['desc'] ) ) {
            echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
        }
    }

    public function section_description() {
        echo '<p>' . esc_html__( 'Personaliza colores y tamaños principales de los elementos visuales de las tarjetas y grid en el frontend. Puedes dejar campos vacíos para usar los valores por defecto.', 'mi-conexion-externa' ) . '</p>';
    }

    public function sanitize_settings( $input ) {
        $fields = $this->get_style_fields();
        $new = array();
        foreach ( $fields as $id => $info ) {
            if ( isset( $input[ $id ] ) ) {
                $new[ $id ] = sanitize_text_field( $input[ $id ] );
            }
        }
        return $new;
    }

    public function render_settings_page() {
        ?>
        <div class="wrap mce-admin-page">
            <h1><span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e( 'Ajustes de Estilo de Grids (MCE)', 'mi-conexion-externa' ); ?></h1>
            
            <div class="mce-section">
                <p><?php esc_html_e( 'Configure los estilos visuales globales que se aplicarán a todos los grids y tarjetas mostrados por el plugin en el frontend.', 'mi-conexion-externa' ); ?></p>
                <p class="mce-notice">
                    <?php esc_html_e( 'Los cambios aquí aplicarán automáticamente a todos los shortcodes. También puede personalizar estilos específicos usando atributos en el shortcode.', 'mi-conexion-externa' ); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'mce_style_settings' );
                do_settings_sections( 'mce-style-settings' );
                submit_button( __( 'Guardar Cambios', 'mi-conexion-externa' ) );
                ?>
            </form>

            <div class="mce-section">
                <h3><?php esc_html_e( 'Ejemplo de uso con atributos en shortcode:', 'mi-conexion-externa' ); ?></h3>
                <pre><code>[mce_mostrar_tabla tabla="productos" color_titulo="#1976d2" tamano_titulo="2rem"]</code></pre>
                <p><em><?php esc_html_e( 'Los atributos en el shortcode tienen prioridad sobre los ajustes globales de esta página.', 'mi-conexion-externa' ); ?></em></p>
            </div>
        </div>
        <?php
    }

    public function output_custom_styles() {
        if ( is_admin() ) return;

        $options = get_option( 'mce_style_settings', array() );
        $css = '';
        if ( ! empty( $options['color_titulo'] ) ) {
            $css .= "body .mce-producto-card .mce-card-title, html body .mce-producto-card .mce-card-title, .mce-card-title { color: {$options['color_titulo']} !important; }";
        }
        if ( ! empty( $options['tamano_titulo'] ) ) {
            $css .= "body .mce-producto-card .mce-card-title, .mce-card-title { font-size: {$options['tamano_titulo']} !important; }";
        }
        if ( ! empty( $options['color_etiqueta'] ) ) {
            $css .= "body .mce-card-item strong, .mce-card-item strong { color: {$options['color_etiqueta']} !important; }";
        }
        if ( ! empty( $options['color_valor'] ) ) {
            $css .= "body .mce-card-item span, .mce-card-item span { color: {$options['color_valor']} !important; }";
        }
        if ( ! empty( $options['color_enlace'] ) ) {
            $css .= "body .mce-pdf-link, .mce-pdf-link { color: {$options['color_enlace']} !important; }";
        }
        if ( ! empty( $options['color_fondo_card'] ) ) {
            $css .= "body .mce-producto-card, .mce-producto-card { background: {$options['color_fondo_card']} !important; }";
        }
        if ( ! empty( $options['sombra_card'] ) ) {
            $css .= "body .mce-producto-card, .mce-producto-card { box-shadow: {$options['sombra_card']} !important; }";
        }
        if ( $css ) {
            echo '<style id="mce-custom-styles">' . $css . '</style>';
        }
    }
}

new MCE_Style_Settings_Page();
