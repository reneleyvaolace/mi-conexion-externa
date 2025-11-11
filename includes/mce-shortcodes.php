<?php
/**
 * Lógica de Shortcodes para Mi Conexión Externa.
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrar el shortcode
 */
function mce_register_shortcodes() {
    remove_shortcode( 'mostrar_mce_productos' );
    add_shortcode( 'mce_mostrar_tabla', 'mce_render_tabla_shortcode' );
}
add_action( 'init', 'mce_register_shortcodes' );

/**
 * Renderizado del shortcode
 */
function mce_render_tabla_shortcode( $atts ) {
    // Validar 'tabla'
    if ( empty( $atts['tabla'] ) ) {
        return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]: Falta el atributo "tabla" en el shortcode. Ej: [mce_mostrar_tabla tabla="su_tabla"]', 'mi-conexion-externa' ) ) . '</p>';
    }

    // Parsear atributos y sanitizar
    $a = shortcode_atts(
        array(
            'tabla' => '',
            'columnas' => 3,
            'paginacion' => 10,
            'columnas_mostrar' => '',
            'llave_titulo' => '',
            'ocultar_etiquetas' => '',
            'color_titulo' => '',
            'tamano_titulo' => '',
            'color_etiqueta' => '',
            'color_valor' => '',
            'color_enlace' => '',
            'mostrar_buscador' => 'true',
            'pagina' => 1
        ),
        $atts
    );

    $tabla = sanitize_text_field( $a['tabla'] );
    $columnas = intval( $a['columnas'] );
    $filas_por_pagina = intval( $a['paginacion'] );
    $columnas_a_mostrar_str = sanitize_text_field( $a['columnas_mostrar'] );
    $llave_titulo = sanitize_text_field( $a['llave_titulo'] );
    $etiquetas_a_ocultar_str = sanitize_text_field( $a['ocultar_etiquetas'] );
    $mostrar_buscador = filter_var( $a['mostrar_buscador'], FILTER_VALIDATE_BOOLEAN );

    if ( $columnas < 1 || $columnas > 6 ) { $columnas = 3; }
    if ( $filas_por_pagina <= 0 ) { $filas_por_pagina = 10; }

    // paginación AJAX + param GET
    $pagina_actual = 1;
    if ( isset( $a['pagina'] ) ) {
        $pagina_actual = intval( $a['pagina'] );
    } elseif ( isset( $_GET['pagina_mce'] ) ) {
        $pagina_actual = intval( $_GET['pagina_mce'] );
    }
    if ( $pagina_actual < 1 ) { $pagina_actual = 1; }

    $columnas_a_mostrar = array();
    if ( ! empty( $columnas_a_mostrar_str ) ) {
        $columnas_a_mostrar = array_map( 'trim', explode( ',', $columnas_a_mostrar_str ) );
    }
    $etiquetas_a_ocultar = array();
    if ( ! empty( $etiquetas_a_ocultar_str ) ) {
        $etiquetas_a_ocultar = array_map( 'trim', explode( ',', $etiquetas_a_ocultar_str ) );
    }

    $db_handler = new MCE_DB_Handler();
    $resultado = $db_handler->get_paginated_table_data( $tabla, $filas_por_pagina, $pagina_actual );

    if ( is_wp_error( $resultado ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $resultado->get_error_message() ) . '</p>';
        }
        return '';
    }

    $data = $resultado['data'];
    $total_filas = $resultado['total_rows'];

    if ( empty( $data ) && $pagina_actual === 1 ) {
        return '<p>' . esc_html( sprintf( __( 'No se encontraron datos en la tabla "%s".', 'mi-conexion-externa' ), $tabla ) ) . '</p>';
    }

    wp_enqueue_style( 'mce-public-style' );
    wp_enqueue_script( 'mce-public-script' ); 

    $inline_style = sprintf('grid-template-columns: repeat(%d, 1fr);', $columnas);

    // NUEVO: Lee opciones de estilo del admin
    $options = get_option('mce_style_settings', array());

    $estilo_titulo = '';
    if (!empty($options['color_titulo'])) {
        $estilo_titulo .= 'color:' . esc_attr($options['color_titulo']) . '!important;';
    }
    if (!empty($a['color_titulo'])) {
        $estilo_titulo .= 'color:' . esc_attr($a['color_titulo']) . '!important;';
    }
    if (!empty($options['tamano_titulo'])) {
        $estilo_titulo .= 'font-size:' . esc_attr($options['tamano_titulo']) . '!important;';
    }
    if (!empty($a['tamano_titulo'])) {
        $estilo_titulo .= 'font-size:' . esc_attr($a['tamano_titulo']) . '!important;';
    }

    $estilo_etiqueta = '';
    if (!empty($options['color_etiqueta'])) {
        $estilo_etiqueta .= 'color:' . esc_attr($options['color_etiqueta']) . '!important;';
    }
    if (!empty($a['color_etiqueta'])) {
        $estilo_etiqueta .= 'color:' . esc_attr($a['color_etiqueta']) . '!important;';
    }

    $estilo_valor = '';
    if (!empty($options['color_valor'])) {
        $estilo_valor .= 'color:' . esc_attr($options['color_valor']) . '!important;';
    }
    if (!empty($a['color_valor'])) {
        $estilo_valor .= 'color:' . esc_attr($a['color_valor']) . '!important;';
    }

    $estilo_enlace = '';
    if (!empty($options['color_enlace'])) {
        $estilo_enlace .= 'color:' . esc_attr($options['color_enlace']) . '!important;';
    }
    if (!empty($a['color_enlace'])) {
        $estilo_enlace .= 'color:' . esc_attr($a['color_enlace']) . '!important;';
    }

    ob_start();
    
    // Generate unique instance ID for JavaScript
    $instance_id = 'mce_' . md5( $tabla . '_' . $filas_por_pagina . '_' . current_time('mysql') );
    
    // Pass configuration to JavaScript
    $js_config = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'mce_ajax_nonce' ),
        'instance_id' => $instance_id,
        'mostrar_buscador' => $mostrar_buscador,
        'original_config' => array(
            'columnas' => $columnas,
            'paginacion' => $filas_por_pagina,
            'columnas_mostrar' => $columnas_a_mostrar,
            'llave_titulo' => $llave_titulo,
            'etiquetas_a_ocultar' => $etiquetas_a_ocultar
        ),
        'data' => array(
            'tabla' => $tabla,
            'limite' => $filas_por_pagina,
            'columnas_sql' => implode( ',', array_map( function($col) { return "`{$col}`"; }, $db_handler->obtener_columnas_tabla( $tabla ) ) ),
            'orden' => 'id',
            'direccion' => 'ASC',
            'where' => '',
            'mostrar_total' => true,
            'texto_resultados' => 'Mostrando %d-%d de %d resultados.'
        )
    );
    ?>

    <div class="mce-tabla-wrapper" data-instance="<?php echo esc_attr( $instance_id ); ?>">
        <script>
            window['mceShortcode_<?php echo esc_js( $instance_id ); ?>'] = <?php echo wp_json_encode( $js_config ); ?>;
        </script>

        <!-- Search and Filter Controls (conditional display) -->
        <?php if ( $mostrar_buscador ) : ?>
        <div class="mce-controles-busqueda">
            <div class="mce-busqueda-universal">
                <input type="text"
                       class="mce-input-busqueda"
                       placeholder="<?php echo esc_attr__( 'Buscar en todos los campos...', 'mi-conexion-externa' ); ?>"
                       value="">
                <span class="mce-icono-busqueda">🔍</span>
            </div>
            
            <?php if ( ! empty( $db_handler->obtener_columnas_tabla( $tabla ) ) ) : ?>
                <div class="mce-filtros-wrapper">
                    <?php foreach ( $db_handler->obtener_columnas_tabla( $tabla ) as $columna ) : ?>
                        <div class="mce-filtro-item">
                            <label><?php echo esc_html( ucfirst( $columna ) ); ?></label>
                            <select class="mce-filtro-select" data-columna="<?php echo esc_attr( $columna ); ?>">
                                <option value=""><?php echo esc_html__( 'Todos', 'mi-conexion-externa' ); ?></option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="mce-botones-busqueda">
                <button class="mce-btn-buscar"><?php echo esc_html__( 'Buscar', 'mi-conexion-externa' ); ?></button>
                <button class="mce-btn-limpiar"><?php echo esc_html__( 'Limpiar', 'mi-conexion-externa' ); ?></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Results Info -->
        <div class="mce-info-resultados" style="display: none;"></div>

        <!-- AJAX Content Area -->
        <div class="mce-contenido-ajax">
            <?php
            // Generate the original card-based content for the first load
            echo '<div class="mce-productos-grid" style="' . esc_attr( $inline_style ) . '">';
            foreach ( $data as $row ) :
                ?>
                <div class="mce-producto-card">
                    <?php
                    if ( ! empty( $llave_titulo ) && isset( $row[ $llave_titulo ] ) ) {
                        echo '<h3 class="mce-card-title" style="' . esc_attr($estilo_titulo) . '">' . esc_html( $row[ $llave_titulo ] ) . '</h3>';
                    }

                    echo '<div class="mce-card-meta">';
                    foreach ( $row as $key => $value ) :
                        if ( ! empty( $columnas_a_mostrar ) && ! in_array( $key, $columnas_a_mostrar, true ) ) {
                            continue;
                        }
                        if ( $key === $llave_titulo ) {
                            continue;
                        }

                        $mostrar_etiqueta = ! in_array( $key, $etiquetas_a_ocultar, true );
                        $clase_css_item = $mostrar_etiqueta ? 'mce-card-item' : 'mce-card-item mce-item-no-label';
                        ?>
                        <div class="<?php echo esc_attr( $clase_css_item ); ?>">
                            <?php if ( $mostrar_etiqueta ) : ?>
                                <strong style="<?php echo esc_attr($estilo_etiqueta); ?>"><?php echo esc_html( $key ); ?>:</strong>
                            <?php endif; ?>
                            
                            <span style="<?php echo esc_attr($estilo_valor); ?>">
                                <?php
                                $clean_value = trim( (string) $value );
                                if ( str_starts_with( $clean_value, 'http' ) && str_ends_with( strtolower( $clean_value ), '.pdf' ) ) {
                                    ?>
                                    <a href="<?php echo esc_url( $clean_value ); ?>" target="_blank" rel="noopener noreferrer" class="mce-pdf-link" style="<?php echo esc_attr($estilo_enlace); ?>">
                                        <?php echo esc_html( __( 'Ver PDF', 'mi-conexion-externa' ) ); ?>
                                    </a>
                                    <?php
                                } else {
                                    echo esc_html( $value );
                                }
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php echo '</div>'; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mce-paginacion-wrapper" style="display: <?php echo ( ceil( $total_filas / $filas_por_pagina ) > 1 ) ? 'block' : 'none'; ?>">
            <?php
            $total_paginas = ceil( $total_filas / $filas_por_pagina );

            if ( $total_paginas > 1 ) {
                $pag_links = paginate_links(
                    array(
                        'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( add_query_arg( 'pagina_mce', PHP_INT_MAX ) ) ),
                        'format'    => '',
                        'current'   => $pagina_actual,
                        'total'     => $total_paginas,
                        'prev_text' => __( '« Anterior', 'mi-conexion-externa' ),
                        'next_text' => __( 'Siguiente »', 'mi-conexion-externa' ),
                        'type'      => 'array'
                    )
                );
                echo '<div class="mce-pagination">';
                if ( $pag_links ) {
                    foreach ( $pag_links as $link ) {
                        echo $link;
                    }
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * AJAX Handler
 */
function mce_get_page_content_ajax() {
    error_log('AJAX Pagina solicitada: ' . print_r($_POST['pagina'], true));

    check_ajax_referer( 'mce_ajax_nonce', 'nonce' );

    $atts = array();
    if ( isset( $_POST['tabla'] ) ) { $atts['tabla'] = sanitize_text_field( $_POST['tabla'] ); }
    if ( isset( $_POST['columnas'] ) ) { $atts['columnas'] = intval( $_POST['columnas'] ); }
    if ( isset( $_POST['paginacion'] ) ) { $atts['paginacion'] = intval( $_POST['paginacion'] ); }
    if ( isset( $_POST['pagina'] ) ) { $atts['pagina'] = intval( $_POST['pagina'] ); }
    if ( isset( $_POST['columnas_mostrar'] ) ) { $atts['columnas_mostrar'] = sanitize_text_field( $_POST['columnas_mostrar'] ); }
    if ( isset( $_POST['llave_titulo'] ) ) { $atts['llave_titulo'] = sanitize_text_field( $_POST['llave_titulo'] ); }
    if ( isset( $_POST['ocultar_etiquetas'] ) ) { $atts['ocultar_etiquetas'] = sanitize_text_field( $_POST['ocultar_etiquetas'] ); }
    if ( isset( $_POST['color_titulo'] ) ) { $atts['color_titulo'] = sanitize_text_field( $_POST['color_titulo'] ); }
    if ( isset( $_POST['tamano_titulo'] ) ) { $atts['tamano_titulo'] = sanitize_text_field( $_POST['tamano_titulo'] ); }
    if ( isset( $_POST['color_etiqueta'] ) ) { $atts['color_etiqueta'] = sanitize_text_field( $_POST['color_etiqueta'] ); }
    if ( isset( $_POST['color_valor'] ) ) { $atts['color_valor'] = sanitize_text_field( $_POST['color_valor'] ); }
    if ( isset( $_POST['color_enlace'] ) ) { $atts['color_enlace'] = sanitize_text_field( $_POST['color_enlace'] ); }

    $html = mce_render_tabla_shortcode( $atts );

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_mce_get_page_content', 'mce_get_page_content_ajax' );
add_action( 'wp_ajax_nopriv_mce_get_page_content', 'mce_get_page_content_ajax' );

/**
 * AJAX Handler for loading page content (enhanced version)
 */
function mce_cargar_pagina_ajax() {
    error_log('MCE AJAX: Loading page: ' . print_r($_POST['pagina'], true));
    
    check_ajax_referer( 'mce_ajax_nonce', 'nonce' );
    
    // Validate and sanitize input
    $tabla = sanitize_text_field( $_POST['tabla'] ?? '' );
    if ( empty( $tabla ) ) {
        wp_send_json_error( array( 'message' => 'Tabla no especificada' ) );
        return;
    }
    
    $db_handler = new MCE_DB_Handler();
    $pagina = intval( $_POST['pagina'] ?? 1 );
    $limite = intval( $_POST['limite'] ?? 10 );
    $busqueda = sanitize_text_field( $_POST['busqueda'] ?? '' );
    $filtros = json_decode( stripslashes( $_POST['filtros'] ?? '{}' ), true );
    
    // Build WHERE clause for search and filters
    $where_conditions = array();
    
    // Add search condition if provided
    if ( ! empty( $busqueda ) ) {
        $where_conditions[] = "(campo1 LIKE '%{$busqueda}%' OR campo2 LIKE '%{$busqueda}%')";
    }
    
    // Add filter conditions
    if ( ! empty( $filtros ) && is_array( $filtros ) ) {
        foreach ( $filtros as $columna => $valor ) {
            if ( ! empty( $valor ) ) {
                $columna = sanitize_text_field( $columna );
                $valor = sanitize_text_field( $valor );
                $where_conditions[] = "`{$columna}` LIKE '%{$valor}%'";
            }
        }
    }
    
    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }
    
    $resultado = $db_handler->get_paginated_table_data( $tabla, $limite, $pagina );
    
    if ( is_wp_error( $resultado ) ) {
        wp_send_json_error( array( 'message' => $resultado->get_error_message() ) );
        return;
    }
    
    // Generate card-based HTML content (like the search results)
    $html = mce_generate_card_html( $resultado['data'], $tabla, 3 ); // 3 columns default
    
    // Generate pagination
    $paginacion = mce_generate_pagination_html( $resultado['total_rows'], $limite, $pagina );
    
    // Generate results info
    $info_resultados = mce_generate_results_info( $resultado['total_rows'], $limite, $pagina );
    
    wp_send_json_success( array(
        'html' => $html,
        'paginacion' => $paginacion,
        'info_resultados' => $info_resultados
    ) );
}
add_action( 'wp_ajax_mce_cargar_pagina', 'mce_cargar_pagina_ajax' );
add_action( 'wp_ajax_nopriv_mce_cargar_pagina', 'mce_cargar_pagina_ajax' );

/**
 * AJAX Handler for search and filter functionality
 */
function mce_buscar_filtrar_ajax() {
    error_log('MCE AJAX: Search/Filter request: ' . print_r($_POST, true));
    
    check_ajax_referer( 'mce_ajax_nonce', 'nonce' );
    
    // Validate and sanitize input
    $tabla = sanitize_text_field( $_POST['tabla'] ?? '' );
    if ( empty( $tabla ) ) {
        wp_send_json_error( array( 'message' => 'Tabla no especificada' ) );
        return;
    }
    
    $db_handler = new MCE_DB_Handler();
    $busqueda = sanitize_text_field( $_POST['busqueda'] ?? '' );
    $filtros = json_decode( stripslashes( $_POST['filtros'] ?? '{}' ), true );
    $limite = intval( $_POST['limite'] ?? 10 );
    $mostrar_total = filter_var( $_POST['mostrar_total'] ?? false, FILTER_VALIDATE_BOOLEAN );
    
    // Build WHERE clause for search and filters
    $where_conditions = array();
    
    // Add search condition if provided
    if ( ! empty( $busqueda ) ) {
        // Get table columns to search dynamically
        $columnas = $db_handler->obtener_columnas_tabla( $tabla );
        if ( $columnas ) {
            $search_conditions = array();
            foreach ( $columnas as $columna ) {
                $columna_escaped = '`' . $db_handler->escape_string( $columna ) . '`';
                $search_conditions[] = "{$columna_escaped} LIKE '%{$db_handler->escape_string($busqueda)}%'";
            }
            if ( ! empty( $search_conditions ) ) {
                $where_conditions[] = '(' . implode( ' OR ', $search_conditions ) . ')';
            }
        }
    }
    
    // Add filter conditions
    if ( ! empty( $filtros ) && is_array( $filtros ) ) {
        foreach ( $filtros as $columna => $valor ) {
            if ( ! empty( $valor ) ) {
                $columna_escaped = '`' . $db_handler->escape_string( $columna ) . '`';
                $valor_escaped = $db_handler->escape_string( $valor );
                $where_conditions[] = "{$columna_escaped} LIKE '%{$valor_escaped}%'";
            }
        }
    }
    
    $where_clause = '';
    if ( ! empty( $where_conditions ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
    }
    
    // Get filtered data (remove WHERE from the clause since obtener_datos adds it)
    $where_conditions_only = str_replace( array( 'WHERE ', 'where ' ), '', $where_clause );
    $datos = $db_handler->obtener_datos( $tabla, '*', $where_conditions_only, '', 'ASC', $limite, 0 );
    
    if ( $datos === false ) {
        wp_send_json_error( array( 'message' => 'Error al obtener datos filtrados' ) );
        return;
    }
    
    // Get total count for pagination info
    $total_rows = 0;
    if ( $mostrar_total ) {
        $total_rows = $db_handler->contar_registros( $tabla, $where_conditions_only );
    }
    
    // Generate card-based HTML content (like the original shortcode)
    $html = mce_generate_card_html( $datos, $tabla, 3 ); // 3 columns default
    
    // Generate pagination (only if showing total)
    $paginacion = '';
    if ( $mostrar_total && $total_rows > $limite ) {
        $paginacion = mce_generate_pagination_html( $total_rows, $limite, 1 );
    }
    
    // Generate results info
    $info_resultados = '';
    if ( $mostrar_total ) {
        $info_resultados = mce_generate_results_info( $total_rows, $limite, 1 );
    }
    
    wp_send_json_success( array(
        'html' => $html,
        'paginacion' => $paginacion,
        'info_resultados' => $info_resultados
    ) );
}
add_action( 'wp_ajax_mce_buscar_filtrar', 'mce_buscar_filtrar_ajax' );
add_action( 'wp_ajax_nopriv_mce_buscar_filtrar', 'mce_buscar_filtrar_ajax' );

/**
 * AJAX Handler for getting filter options
 */
function mce_obtener_opciones_filtro_ajax() {
    check_ajax_referer( 'mce_ajax_nonce', 'nonce' );
    
    // Validate and sanitize input
    $tabla = sanitize_text_field( $_POST['tabla'] ?? '' );
    $columna = sanitize_text_field( $_POST['columna'] ?? '' );
    $where = sanitize_text_field( $_POST['where'] ?? '' );
    
    if ( empty( $tabla ) || empty( $columna ) ) {
        wp_send_json_error( array( 'message' => 'Parámetros insuficientes' ) );
        return;
    }
    
    $db_handler = new MCE_DB_Handler();
    $opciones = $db_handler->obtener_valores_unicos( $tabla, $columna, $where );
    
    if ( $opciones === false ) {
        wp_send_json_error( array( 'message' => 'Error al obtener opciones de filtro' ) );
        return;
    }
    
    wp_send_json_success( array( 'opciones' => $opciones ) );
}
add_action( 'wp_ajax_mce_obtener_opciones_filtro', 'mce_obtener_opciones_filtro_ajax' );
add_action( 'wp_ajax_nopriv_mce_obtener_opciones_filtro', 'mce_obtener_opciones_filtro_ajax' );

/**
 * Generate table HTML content
 */
function mce_generate_table_html( $data, $total_rows, $limite, $pagina ) {
    if ( empty( $data ) ) {
        return '<p>No se encontraron datos.</p>';
    }
    
    ob_start();
    ?>
    <table class="mce-data-table">
        <thead>
            <tr>
                <?php foreach ( array_keys( $data[0] ) as $header ) : ?>
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
    return ob_get_clean();
}

/**
 * Generate pagination HTML
 */
function mce_generate_pagination_html( $total_rows, $limite, $pagina_actual ) {
    $total_paginas = ceil( $total_rows / $limite );
    
    if ( $total_paginas <= 1 ) {
        return '';
    }
    
    $pag_links = paginate_links(
        array(
            'base' => str_replace( PHP_INT_MAX, '%#%', esc_url( add_query_arg( 'pagina_mce', PHP_INT_MAX ) ) ),
            'format' => '',
            'current' => $pagina_actual,
            'total' => $total_paginas,
            'prev_text' => __( '« Anterior', 'mi-conexion-externa' ),
            'next_text' => __( 'Siguiente »', 'mi-conexion-externa' ),
            'type' => 'array'
        )
    );
    
    if ( empty( $pag_links ) ) {
        return '';
    }
    
    ob_start();
    echo '<div class="mce-pagination">';
    foreach ( $pag_links as $link ) {
        echo $link;
    }
    echo '</div>';
    return ob_get_clean();
}

/**
 * Generate results info HTML
 */
function mce_generate_results_info( $total_rows, $limite, $pagina ) {
    $inicio = ( $pagina - 1 ) * $limite + 1;
    $fin = min( $pagina * $limite, $total_rows );
    
    if ( $total_rows === 0 ) {
        return '<p class="mce-info-resultados">No se encontraron resultados.</p>';
    }
    
    return sprintf(
        '<p class="mce-info-resultados">Mostrando %d-%d de %d resultados.</p>',
        $inicio,
        $fin,
        $total_rows
    );
}

/**
 * Generate card-based HTML content (like the original shortcode)
 * Used for AJAX search results
 */
function mce_generate_card_html( $data, $tabla, $columnas = 3 ) {
    if ( empty( $data ) ) {
        return '<p>No se encontraron datos.</p>';
    }
    
    $inline_style = sprintf('grid-template-columns: repeat(%d, 1fr);', $columnas);
    $options = get_option('mce_style_settings', array());
    
    ob_start();
    ?>
    <div class="mce-productos-grid" style="<?php echo esc_attr( $inline_style ); ?>">
        <?php foreach ( $data as $row ) : ?>
            <div class="mce-producto-card">
                <?php
                // Use first non-empty field as title if available
                $title = '';
                foreach ( $row as $key => $value ) {
                    if ( ! empty( $value ) && ! is_numeric( $key ) ) {
                        $title = esc_html( $value );
                        break;
                    }
                }
                
                if ( ! empty( $title ) ) {
                    echo '<h3 class="mce-card-title">' . $title . '</h3>';
                }
                
                echo '<div class="mce-card-meta">';
                foreach ( $row as $key => $value ) :
                    if ( empty( $value ) || $value === $title ) {
                        continue;
                    }
                    
                    // Skip numeric keys (avoid showing array indices)
                    if ( is_numeric( $key ) ) {
                        continue;
                    }
                    ?>
                    <div class="mce-card-item">
                        <strong><?php echo esc_html( ucfirst( $key ) ); ?>:</strong>
                        <span>
                            <?php
                            $clean_value = trim( (string) $value );
                            if ( str_starts_with( $clean_value, 'http' ) && str_ends_with( strtolower( $clean_value ), '.pdf' ) ) {
                                ?>
                                <a href="<?php echo esc_url( $clean_value ); ?>" target="_blank" rel="noopener noreferrer" class="mce-pdf-link">
                                    <?php echo esc_html__( 'Ver PDF', 'mi-conexion-externa' ); ?>
                                </a>
                                <?php
                            } else {
                                echo esc_html( $value );
                            }
                            ?>
                        </span>
                    </div>
                    <?php
                endforeach;
                echo '</div>';
                ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
