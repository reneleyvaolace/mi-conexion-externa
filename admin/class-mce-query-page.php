<?php
/**
 * Lógica para la Página de "Consultas" del Administrador.
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MCE_Query_Page {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'mce-main-menu' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'mce-admin-global',
            plugins_url( 'admin/css/mce-admin-global.css', dirname( dirname( __FILE__ ) ) . '/mi-conexion-externa.php' ),
            array(),
            '1.1.5'
        );
    }

    public function create_query_page_content() {
        $db_handler = new MCE_DB_Handler();
        $view_table = isset( $_GET['view_table'] ) ? sanitize_text_field( $_GET['view_table'] ) : null;
        ?>
        <div class="wrap mce-admin-page">
            <h1><?php echo esc_html( __( 'Explorador de la Base de Datos Externa', 'mi-conexion-externa' ) ); ?></h1>
            
            <?php
            if ( $view_table ) :
                
                echo '<p><a href="' . esc_url( menu_page_url( 'mce-main-menu', false ) ) . '">&larr; ' . esc_html__( 'Volver a la lista de tablas', 'mi-conexion-externa' ) . '</a></p>';
                echo '<h2>' . esc_html( sprintf( __( 'Mostrando contenido de: %s', 'mi-conexion-externa' ), $view_table ) ) . '</h2>';
                echo '<p class="mce-notice">' . esc_html__( 'Se muestran las primeras 100 filas.', 'mi-conexion-externa' ) . '</p>';

                $data = $db_handler->get_table_content( $view_table );

                if ( is_wp_error( $data ) ) :
                    $this->render_error( $data, __( 'Error al Cargar el Contenido:', 'mi-conexion-externa' ) );
                
                elseif ( empty( $data ) ) :
                    ?>
                    <div class="mce-notice mce-notice-warning"><p><?php echo esc_html( __( 'Esta tabla está vacía.', 'mi-conexion-externa' ) ); ?></p></div>
                    <?php
                
                else :
                    $column_headers = array_keys( $data[0] );
                    ?>
                    <table class="mce-data-table">
                        <thead>
                            <tr>
                                <?php foreach ( $column_headers as $header ) : ?>
                                    <th><?php echo esc_html( $header ); ?></th>
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

            else :

                $tables = $db_handler->get_tables();

                if ( is_wp_error( $tables ) ) :
                    $this->render_error( $tables, __( 'Error al Cargar las Tablas:', 'mi-conexion-externa' ) );
                
                elseif ( empty( $tables ) ) :
                    ?>
                    <div class="mce-notice mce-notice-warning"><p><?php echo esc_html( __( 'No se encontraron tablas en esta base de datos.', 'mi-conexion-externa' ) ); ?></p></div>
                    <?php
                
                else :
                    ?>
                    <h2><?php echo esc_html( __( 'Tablas Encontradas:', 'mi-conexion-externa' ) ); ?></h2>
                    <p class="mce-notice">
                        <?php echo esc_html( sprintf( __( 'Se encontraron %d tablas. Haz clic en una para ver su contenido.', 'mi-conexion-externa' ), count( $tables ) ) ); ?>
                    </p>
                    <ul class="mce-tables-list">
                        <?php
                        foreach ( $tables as $table ) :
                            $table_url = add_query_arg(
                                array(
                                    'page'       => 'mce-main-menu',
                                    'view_table' => $table,
                                ),
                                admin_url( 'admin.php' )
                            );
                            ?>
                            <li>
                                <a href="<?php echo esc_url( $table_url ); ?>">
                                    <?php echo esc_html( $table ); ?>
                                </a>
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

    private function render_error( $error, $title ) {
        ?>
        <div class="mce-notice mce-notice-error">
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
