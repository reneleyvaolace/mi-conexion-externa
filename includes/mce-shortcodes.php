<?php

/**
 * Lógica de Shortcodes para Mi Conexión Externa.
 *
 * @package MiConexionExterna
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registrar el shortcode
 */
function mce_register_shortcodes()
{
    remove_shortcode('mostrar_mce_productos');
    add_shortcode('mce_mostrar_tabla', 'mce_render_tabla_shortcode');
}
add_action('init', 'mce_register_shortcodes');

/**
 * Renderizado del shortcode
 */
function mce_render_tabla_shortcode($atts)
{
    // Validar 'tabla'
    if (empty($atts['tabla'])) {
        return '<p style="color:red;">' . esc_html(__('Error del Plugin [MCE]: Falta el atributo "tabla" en el shortcode. Ej: [mce_mostrar_tabla tabla="su_tabla"]', 'mi-conexion-externa')) . '</p>';
    }

    // Parsear atributos y sanitizar
    $a = shortcode_atts(
        array(
            'tabla' => '',
            'columnas' => 3,
            'paginacion' => 10,
            'columnas_mostrar' => '',
            'columnas_filtrar' => '',
            'llave_titulo' => '',
            'ocultar_etiquetas' => '',
            'color_titulo' => '',
            'tamano_titulo' => '',
            'color_etiqueta' => '',
            'color_valor' => '',
            'color_enlace' => '',
            'mostrar_buscador' => 'true',
            'pagina' => 1,
            // NUEVOS PARÁMETROS DE ORDENAMIENTO
            'ordenar_por' => '',
            'orden' => 'ASC'
        ),
        $atts
    );

    $tabla = sanitize_text_field($a['tabla']);
    $columnas = intval($a['columnas']);
    $filas_por_pagina = intval($a['paginacion']);
    $columnas_a_mostrar_str = sanitize_text_field($a['columnas_mostrar']);

    // NUEVOS: Parámetros de ordenamiento
    $ordenar_por = sanitize_text_field($a['ordenar_por']);
    $orden = sanitize_text_field($a['orden']);
    $columnas_a_filtrar_str = sanitize_text_field($a['columnas_filtrar']);
    $llave_titulo = sanitize_text_field($a['llave_titulo']);
    $etiquetas_a_ocultar_str = sanitize_text_field($a['ocultar_etiquetas']);
    $mostrar_buscador = filter_var($a['mostrar_buscador'], FILTER_VALIDATE_BOOLEAN);

    if ($columnas < 1 || $columnas > 6) {
        $columnas = 3;
    }
    if ($filas_por_pagina <= 0) {
        $filas_por_pagina = 10;
    }

    // paginación AJAX + param GET
    $pagina_actual = 1;
    if (isset($a['pagina'])) {
        $pagina_actual = intval($a['pagina']);
    } elseif (isset($_GET['pagina_mce'])) {
        $pagina_actual = intval($_GET['pagina_mce']);
    }
    if ($pagina_actual < 1) {
        $pagina_actual = 1;
    }

    $columnas_a_mostrar = array();
    if (! empty($columnas_a_mostrar_str)) {
        $columnas_a_mostrar = array_map('trim', explode(',', $columnas_a_mostrar_str));
    }
    $columnas_a_filtrar = array();
    if (! empty($columnas_a_filtrar_str)) {
        $columnas_a_filtrar = array_map('trim', explode(',', $columnas_a_filtrar_str));
    }
    $etiquetas_a_ocultar = array();
    if (! empty($etiquetas_a_ocultar_str)) {
        $etiquetas_a_ocultar = array_map('trim', explode(',', $etiquetas_a_ocultar_str));
    }

    $db_handler = new MCE_DB_Handler();

    // DEBUG: Obtener información detallada del error
    $debug_info = array();
    $debug_info[] = '=== MCE DEBUG INFO ===';
    $debug_info[] = 'Tabla: ' . $tabla;
    $debug_info[] = 'Filas por página: ' . $filas_por_pagina;
    $debug_info[] = 'Página actual: ' . $pagina_actual;
    $debug_info[] = 'Ordenar por: ' . ($ordenar_por ?: '(auto-detectado)');
    $debug_info[] = 'Dirección: ' . ($orden ?: 'ASC');

    // Verificar configuración de base de datos
    $host = get_option('mce_db_host', 'NO CONFIGURADO');
    $user = get_option('mce_db_user', 'NO CONFIGURADO');
    $database = get_option('mce_db_name', 'NO CONFIGURADO');
    $port = get_option('mce_db_port', 'NO CONFIGURADO');

    $debug_info[] = 'Host: ' . $host;
    $debug_info[] = 'User: ' . $user;
    $debug_info[] = 'Database: ' . $database;
    $debug_info[] = 'Port: ' . $port;

    // Test directo de conexión
    $debug_info[] = '--- TESTING CONNECTION ---';
    try {
        // Test si la tabla existe
        if ($db_handler->tabla_existe($tabla)) {
            $debug_info[] = '✅ TABLA EXISTE';

            // Test de conteo
            $total = $db_handler->contar_registros($tabla);
            if ($total !== false) {
                $debug_info[] = '✅ REGISTROS CONTADOS: ' . $total;

                // Test de obtener datos
                $datos_test = $db_handler->obtener_datos($tabla, '*', '', '', 'ASC', 5, 0);
                if ($datos_test !== false) {
                    $debug_info[] = '✅ DATOS OBTENIDOS: ' . count($datos_test) . ' registros';
                    $debug_info[] = 'Columnas: ' . implode(', ', array_keys($datos_test[0] ?? []));
                } else {
                    $debug_info[] = '❌ FALLO: No se pudieron obtener datos';
                    $last_error = $db_handler->get_last_error();
                    if ($last_error) {
                        $debug_info[] = 'Error DB: ' . $last_error;
                    }
                }
            } else {
                $debug_info[] = '❌ FALLO: No se pudieron contar registros';
            }
        } else {
            $debug_info[] = '❌ TABLA NO EXISTE';

            // Listar tablas disponibles
            $tablas = $db_handler->obtener_tablas();
            if ($tablas && is_array($tablas)) {
                $debug_info[] = 'Tablas disponibles: ' . implode(', ', array_slice($tablas, 0, 10));
            }
        }
    } catch (Exception $e) {
        $debug_info[] = '❌ EXCEPCIÓN: ' . $e->getMessage();
    }

    // Ahora intentar el método principal con ordenamiento personalizado y columnas específicas
    // APLICAR SISTEMA DE CACHÉ PARA OPTIMIZAR RENDIMIENTO
    $cache_handler = new MCE_Cache_Handler();
    $resultado = $cache_handler->get_paginated_with_cache($db_handler, $tabla, $filas_por_pagina, $pagina_actual, $ordenar_por, $orden, $columnas_a_mostrar_str);

    if (is_wp_error($resultado)) {
        $debug_info[] = '--- ERROR EN METODO PRINCIPAL ---';
        $debug_info[] = 'Código: ' . $resultado->get_error_code();
        $debug_info[] = 'Mensaje: ' . $resultado->get_error_message();

        // Para usuarios con permisos, mostrar debug info
        if (current_user_can('manage_options')) {
            $debug_html = '<div style="background:#f8f9fa; border:1px solid #dee2e6; padding:15px; margin:10px 0; border-radius:5px; font-family:monospace; font-size:12px;">';
            $debug_html .= '<h4 style="margin-top:0; color:#721c24;">🔍 Debug Info - Plugin MCE</h4>';
            $debug_html .= '<pre>' . esc_html(implode("\n", $debug_info)) . '</pre>';
            $debug_html .= '</div>';

            return $debug_html . '<p style="color:red;">' . esc_html(__('Error del Plugin [MCE]:', 'mi-conexion-externa') . ' ' . $resultado->get_error_message()) . '</p>';
        }
        return '';
    }

    $data = $resultado['data'];
    $total_filas = $resultado['total_rows'];

    if (empty($data) && $pagina_actual === 1) {
        return '<p>' . esc_html(sprintf(__('No se encontraron datos en la tabla "%s".', 'mi-conexion-externa'), $tabla)) . '</p>';
    }

    wp_enqueue_style('mce-public-style');
    wp_enqueue_script('mce-public-script');

    // Responsive grid: usa auto-fit para adaptarse automáticamente
    // El parámetro $columnas define el número máximo de columnas en pantallas grandes
    $min_card_width = 280; // Ancho mínimo de cada tarjeta en píxeles
    $max_columns = max(1, min(6, $columnas)); // Limitar entre 1 y 6 columnas
    
    // Crear un grid responsive que respeta el máximo de columnas
    $inline_style = sprintf(
        'grid-template-columns: repeat(auto-fit, minmax(%dpx, 1fr)); max-width: 100%%;',
        $min_card_width
    );
    
    // Si se especifica un número exacto de columnas, agregar una clase CSS personalizada
    $grid_class = 'mce-productos-grid';
    if ($columnas > 0 && $columnas <= 6) {
        $grid_class .= ' mce-grid-max-' . $max_columns;
    }

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
    $instance_id = 'mce_' . md5($tabla . '_' . $filas_por_pagina . '_' . current_time('mysql'));

    // Pass configuration to JavaScript
    $js_config = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mce_ajax_nonce'),
        'instance_id' => $instance_id,
        'mostrar_buscador' => $mostrar_buscador,
        'original_config' => array(
            'tabla' => $tabla,
            'columnas' => $columnas,
            'paginacion' => $filas_por_pagina,
            'columnas_mostrar' => $columnas_a_mostrar_str, // Pasar como string para JS
            'llave_titulo' => $llave_titulo,
            'ocultar_etiquetas' => $etiquetas_a_ocultar_str, // Pasar como string para JS
            'color_titulo' => $a['color_titulo'],
            'tamano_titulo' => $a['tamano_titulo'],
            'color_etiqueta' => $a['color_etiqueta'],
            'color_valor' => $a['color_valor'],
            'color_enlace' => $a['color_enlace'],
            'mostrar_buscador' => $mostrar_buscador,
            'pagina' => $pagina_actual
        ),
        'data' => array(
            'tabla' => $tabla,
            'limite' => $filas_por_pagina,
            'columnas_sql' => implode(',', array_map(function ($col) {
                return "`{$col}`";
            }, $db_handler->obtener_columnas_tabla($tabla))),
            'orden' => 'id',
            'direccion' => 'ASC',
            'where' => '',
            'mostrar_total' => true,
            'texto_resultados' => 'Mostrando %d-%d de %d resultados.'
        )
    );
?>

    <div id="<?php echo esc_attr($instance_id); ?>" class="mce-tabla-wrapper" data-instance="<?php echo esc_attr($instance_id); ?>">
        <script>
            window['mceShortcode_<?php echo esc_js($instance_id); ?>'] = <?php echo wp_json_encode($js_config); ?>;
        </script>

        <!-- Search and Filter Controls (conditional display) -->
        <?php if ($mostrar_buscador) : ?>
            <div class="mce-controles-busqueda">
                <div class="mce-busqueda-universal">
                    <input type="text"
                        class="mce-input-busqueda"
                        placeholder="<?php echo esc_attr__('Buscar en todos los campos...', 'mi-conexion-externa'); ?>"
                        value="">
                    <span class="mce-icono-busqueda">🔍</span>
                </div>

                <?php
                $columnas_para_iterar = ! empty($columnas_a_filtrar) ? $columnas_a_filtrar : $db_handler->obtener_columnas_tabla($tabla);
                if (! empty($columnas_para_iterar)) :
                ?>
                    <div class="mce-filtros-wrapper">
                        <?php foreach ($columnas_para_iterar as $columna) : ?>
                            <div class="mce-filtro-item">
                                <label><?php echo esc_html(ucfirst($columna)); ?></label>
                                <select class="mce-filtro-select" data-columna="<?php echo esc_attr($columna); ?>">
                                    <option value=""><?php echo esc_html__('Todos', 'mi-conexion-externa'); ?></option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mce-botones-busqueda">
                    <button class="mce-btn-buscar"><?php echo esc_html__('Buscar', 'mi-conexion-externa'); ?></button>
                    <button class="mce-btn-limpiar"><?php echo esc_html__('Limpiar', 'mi-conexion-externa'); ?></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Results Info -->
        <div class="mce-info-resultados" style="display: none;"></div>

        <!-- AJAX Content Area -->
        <div class="mce-contenido-ajax">
            <?php
            // Generate the original card-based content for the first load
            echo '<div class="' . esc_attr($grid_class) . '" style="' . esc_attr($inline_style) . '">';
            foreach ($data as $row) :
            ?>
                <?php echo _mce_render_single_card($row, $a); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mce-paginacion-wrapper" style="display: <?php echo (ceil($total_filas / $filas_por_pagina) > 1) ? 'block' : 'none'; ?>">
        <?php
        $total_paginas = ceil($total_filas / $filas_por_pagina);

        if ($total_paginas > 1) {
            $pag_links = paginate_links(
                array(
                    'base'      => str_replace(PHP_INT_MAX, '%#%', esc_url(add_query_arg('pagina_mce', PHP_INT_MAX))),
                    'format'    => '',
                    'current'   => $pagina_actual,
                    'total'     => $total_paginas,
                    'prev_text' => __('« Anterior', 'mi-conexion-externa'),
                    'next_text' => __('Siguiente »', 'mi-conexion-externa'),
                    'type'      => 'array'
                )
            );
            echo '<div class="mce-pagination">';
            foreach ($pag_links as $link) {
                // Add data-page attribute for AJAX
                if (preg_match('/(?:pagina_mce=)([0-9]+)/', $link, $matches)) {
                    $page_num = intval($matches[1]);
                    $link = str_replace('<a', '<a data-page="' . esc_attr($page_num) . '"', $link);
                } elseif (strpos($link, 'current') !== false) {
                    // For the current page, we need to extract the page number differently if format is empty
                    $link = str_replace('<span', '<span data-page="' . esc_attr($pagina_actual) . '"', $link);
                }
                echo $link;
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
function mce_get_page_content_ajax()
{
    error_log('AJAX Pagina solicitada: ' . print_r($_POST['pagina'], true));

    check_ajax_referer('mce_ajax_nonce', 'nonce');

    $atts = array();
    if (isset($_POST['tabla'])) {
        $atts['tabla'] = sanitize_text_field($_POST['tabla']);
    }
    if (isset($_POST['columnas'])) {
        $atts['columnas'] = intval($_POST['columnas']);
    }
    if (isset($_POST['paginacion'])) {
        $atts['paginacion'] = intval($_POST['paginacion']);
    }
    if (isset($_POST['pagina'])) {
        $atts['pagina'] = intval($_POST['pagina']);
    }
    if (isset($_POST['columnas_mostrar'])) {
        $atts['columnas_mostrar'] = sanitize_text_field($_POST['columnas_mostrar']);
    }
    if (isset($_POST['llave_titulo'])) {
        $atts['llave_titulo'] = sanitize_text_field($_POST['llave_titulo']);
    }
    if (isset($_POST['ocultar_etiquetas'])) {
        $atts['ocultar_etiquetas'] = sanitize_text_field($_POST['ocultar_etiquetas']);
    }
    if (isset($_POST['color_titulo'])) {
        $atts['color_titulo'] = sanitize_text_field($_POST['color_titulo']);
    }
    if (isset($_POST['tamano_titulo'])) {
        $atts['tamano_titulo'] = sanitize_text_field($_POST['tamano_titulo']);
    }
    if (isset($_POST['color_etiqueta'])) {
        $atts['color_etiqueta'] = sanitize_text_field($_POST['color_etiqueta']);
    }
    if (isset($_POST['color_valor'])) {
        $atts['color_valor'] = sanitize_text_field($_POST['color_valor']);
    }
    if (isset($_POST['color_enlace'])) {
        $atts['color_enlace'] = sanitize_text_field($_POST['color_enlace']);
    }

    $html = mce_render_tabla_shortcode($atts);

    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_mce_get_page_content', 'mce_get_page_content_ajax');
add_action('wp_ajax_nopriv_mce_get_page_content', 'mce_get_page_content_ajax');

/**
 * AJAX Handler for loading page content (enhanced version)
 */
function mce_cargar_pagina_ajax()
{
    error_log('MCE AJAX: Loading page: ' . print_r($_POST['pagina'], true));

    check_ajax_referer('mce_ajax_nonce', 'nonce');

    // Validate and sanitize input
    $tabla = sanitize_text_field($_POST['tabla'] ?? '');
    if (empty($tabla)) {
        wp_send_json_error(array('message' => 'Tabla no especificada'));
        return;
    }

    $db_handler = new MCE_DB_Handler();
    $pagina = intval($_POST['pagina'] ?? 1);
    $limite = intval($_POST['limite'] ?? 10);
    $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
    $filtros = json_decode(stripslashes($_POST['filtros'] ?? '{}'), true);
    $original_config = json_decode(stripslashes($_POST['original_config'] ?? '{}'), true);

    // Build WHERE clause for search and filters
    $where_conditions = array();

    // Add search condition if provided
    if (! empty($busqueda)) {
        $columnas = $db_handler->obtener_columnas_tabla($tabla);
        if ($columnas) {
            $search_conditions = array();
            foreach ($columnas as $columna) {
                $columna_escaped = '`' . $db_handler->escape_string($columna) . '`';
                $search_conditions[] = "{$columna_escaped} LIKE '%{$db_handler->escape_string($busqueda)}%'";
            }
            if (! empty($search_conditions)) {
                $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
            }
        }
    }

    // Add filter conditions
    if (! empty($filtros) && is_array($filtros)) {
        foreach ($filtros as $columna => $valor) {
            if (! empty($valor)) {
                $columna = sanitize_text_field($columna);
                $valor = sanitize_text_field($valor);
                $where_conditions[] = "`{$columna}` LIKE '%{$valor}%'";
            }
        }
    }

    $where_clause = '';
    if (! empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    $resultado = $db_handler->get_paginated_table_data($tabla, $limite, $pagina);

    if (is_wp_error($resultado)) {
        wp_send_json_error(array('message' => $resultado->get_error_message()));
        return;
    }

    // Generate card-based HTML content (like the search results)
    $html = mce_generate_card_html($resultado['data'], $original_config);

    // Generate pagination
    $paginacion = mce_generate_pagination_html($resultado['total_rows'], $limite, $pagina, $original_config);

    // Generate results info
    $info_resultados = mce_generate_results_info($resultado['total_rows'], $limite, $pagina);

    wp_send_json_success(array(
        'html' => $html,
        'paginacion' => $paginacion,
        'info_resultados' => $info_resultados
    ));
}
add_action('wp_ajax_mce_cargar_pagina', 'mce_cargar_pagina_ajax');
add_action('wp_ajax_nopriv_mce_cargar_pagina', 'mce_cargar_pagina_ajax');

/**
 * AJAX Handler for search and filter functionality
 */
function mce_buscar_filtrar_ajax()
{
    error_log('MCE AJAX: Search/Filter request: ' . print_r($_POST, true));

    check_ajax_referer('mce_ajax_nonce', 'nonce');

    // Validate and sanitize input
    $tabla = sanitize_text_field($_POST['tabla'] ?? '');
    if (empty($tabla)) {
        wp_send_json_error(array('message' => 'Tabla no especificada'));
        return;
    }

    $db_handler = new MCE_DB_Handler();
    $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
    $filtros = json_decode(stripslashes($_POST['filtros'] ?? '{}'), true);
    $limite = intval($_POST['limite'] ?? 10);
    $mostrar_total = filter_var($_POST['mostrar_total'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $original_config = json_decode(stripslashes($_POST['original_config'] ?? '{}'), true);

    // Build WHERE clause for search and filters
    $where_conditions = array();

    // Add search condition if provided
    if (! empty($busqueda)) {
        // Get table columns to search dynamically
        $columnas = $db_handler->obtener_columnas_tabla($tabla);
        if ($columnas) {
            $search_conditions = array();
            foreach ($columnas as $columna) {
                $columna_escaped = '`' . $db_handler->escape_string($columna) . '`';
                $search_conditions[] = "{$columna_escaped} LIKE '%{$db_handler->escape_string($busqueda)}%'";
            }
            if (! empty($search_conditions)) {
                $where_conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
            }
        }
    }

    // Add filter conditions
    if (! empty($filtros) && is_array($filtros)) {
        foreach ($filtros as $columna => $valor) {
            if (! empty($valor)) {
                $columna_escaped = '`' . $db_handler->escape_string($columna) . '`';
                $valor_escaped = $db_handler->escape_string($valor);
                $where_conditions[] = "{$columna_escaped} LIKE '%{$valor_escaped}%'";
            }
        }
    }

    $where_clause = '';
    if (! empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Get filtered data (remove WHERE from the clause since obtener_datos adds it)
    $where_conditions_only = str_replace(array('WHERE ', 'where '), '', $where_clause);
    $datos = $db_handler->obtener_datos($tabla, '*', $where_conditions_only, 'id', 'ASC', $limite, 0);

    if ($datos === false) {
        wp_send_json_error(array('message' => 'Error al obtener datos filtrados'));
        return;
    }

    // Get total count for pagination info
    $total_rows = 0;
    if ($mostrar_total) {
        $total_rows = $db_handler->contar_registros($tabla, $where_conditions_only);
    }

    // Generate card-based HTML content (like the original shortcode)
    $html = mce_generate_card_html($datos, $original_config);

    // Generate pagination (only if showing total)
    $paginacion = '';
    if ($mostrar_total && $total_rows > $limite) {
        $paginacion = mce_generate_pagination_html($total_rows, $limite, 1, $original_config);
    }

    // Generate results info
    $info_resultados = '';
    if ($mostrar_total) {
        $info_resultados = mce_generate_results_info($total_rows, $limite, 1);
    }

    wp_send_json_success(array(
        'html' => $html,
        'paginacion' => $paginacion,
        'info_resultados' => $info_resultados
    ));
}
add_action('wp_ajax_mce_buscar_filtrar', 'mce_buscar_filtrar_ajax');
add_action('wp_ajax_nopriv_mce_buscar_filtrar', 'mce_buscar_filtrar_ajax');

/**
 * AJAX Handler for getting filter options
 */
function mce_obtener_opciones_filtro_ajax()
{
    check_ajax_referer('mce_ajax_nonce', 'nonce');

    // Validate and sanitize input
    $tabla = sanitize_text_field($_POST['tabla'] ?? '');
    $columna = sanitize_text_field($_POST['columna'] ?? '');
    $where = sanitize_text_field($_POST['where'] ?? '');

    if (empty($tabla) || empty($columna)) {
        wp_send_json_error(array('message' => 'Parámetros insuficientes'));
        return;
    }

    $db_handler = new MCE_DB_Handler();
    $opciones = $db_handler->obtener_valores_unicos($tabla, $columna, $where);

    if ($opciones === false) {
        wp_send_json_error(array('message' => 'Error al obtener opciones de filtro'));
        return;
    }

    wp_send_json_success(array('opciones' => $opciones));
}
add_action('wp_ajax_mce_obtener_opciones_filtro', 'mce_obtener_opciones_filtro_ajax');
add_action('wp_ajax_nopriv_mce_obtener_opciones_filtro', 'mce_obtener_opciones_filtro_ajax');

/**
 * Generate table HTML content
 */
function mce_generate_table_html($data, $total_rows, $limite, $pagina)
{
    if (empty($data)) {
        return '<p>No se encontraron datos.</p>';
    }

    ob_start();
?>
    <table class="mce-data-table">
        <thead>
            <tr>
                <?php foreach (array_keys($data[0]) as $header) : ?>
                    <th><?php echo esc_html($header); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row) : ?>
                <tr>
                    <?php foreach ($row as $value) : ?>
                        <td><?php echo esc_html($value); ?></td>
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
function mce_generate_pagination_html($total_rows, $limite, $pagina_actual, $atts = array())
{
    $total_paginas = ceil($total_rows / $limite);

    if ($total_paginas <= 1) {
        return '';
    }

    $pag_links = paginate_links(
        array(
            'base' => str_replace(PHP_INT_MAX, '%#%', esc_url(add_query_arg('pagina_mce', PHP_INT_MAX))),
            'format' => '',
            'current' => $pagina_actual,
            'total' => $total_paginas,
            'prev_text' => __('« Anterior', 'mi-conexion-externa'),
            'next_text' => __('Siguiente »', 'mi-conexion-externa'),
            'type' => 'array'
        )
    );

    if (empty($pag_links)) {
        return '';
    }

    ob_start();
    echo '<div class="mce-pagination">';
    foreach ($pag_links as $link) {
        // Add data-page attribute for AJAX
        if (preg_match('/(?:pagina_mce=)([0-9]+)/', $link, $matches)) {
            $page_num = intval($matches[1]);
            $link = str_replace('<a', '<a data-page="' . esc_attr($page_num) . '"', $link);
        } elseif (strpos($link, 'current') !== false) {
            // For the current page, we need to extract the page number differently if format is empty
            $link = str_replace('<span', '<span data-page="' . esc_attr($pagina_actual) . '"', $link);
        }
        echo $link;
    }
    echo '</div>';
    return ob_get_clean();
}

/**
 * Generate results info HTML
 */
function mce_generate_results_info($total_rows, $limite, $pagina)
{
    $inicio = ($pagina - 1) * $limite + 1;
    $fin = min($pagina * $limite, $total_rows);

    if ($total_rows === 0) {
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
function mce_generate_card_html($data, $atts = array())
{
    if (empty($data)) {
        return '<p>No se encontraron datos.</p>';
    }

    // Extract attributes for consistent rendering
    $columnas = intval($atts['columnas'] ?? 3);
    $columnas_a_mostrar_str = $atts['columnas_mostrar'] ?? '';
    $llave_titulo = $atts['llave_titulo'] ?? '';
    $etiquetas_a_ocultar_str = $atts['ocultar_etiquetas'] ?? '';

    $columnas_a_mostrar = array();
    if (! empty($columnas_a_mostrar_str)) {
        $columnas_a_mostrar = array_map('trim', explode(',', $columnas_a_mostrar_str));
    }
    $etiquetas_a_ocultar = array();
    if (! empty($etiquetas_a_ocultar_str)) {
        $etiquetas_a_ocultar = array_map('trim', explode(',', $etiquetas_a_ocultar_str));
    }

    // Styles from attributes or options
    $options = get_option('mce_style_settings', array());

    $estilo_titulo = '';
    if (!empty($options['color_titulo'])) {
        $estilo_titulo .= 'color:' . esc_attr($options['color_titulo']) . '!important;';
    }
    if (!empty($atts['color_titulo'])) {
        $estilo_titulo .= 'color:' . esc_attr($atts['color_titulo']) . '!important;';
    }
    if (!empty($options['tamano_titulo'])) {
        $estilo_titulo .= 'font-size:' . esc_attr($options['tamano_titulo']) . '!important;';
    }
    if (!empty($atts['tamano_titulo'])) {
        $estilo_titulo .= 'font-size:' . esc_attr($atts['tamano_titulo']) . '!important;';
    }

    $estilo_etiqueta = '';
    if (!empty($options['color_etiqueta'])) {
        $estilo_etiqueta .= 'color:' . esc_attr($options['color_etiqueta']) . '!important;';
    }
    if (!empty($atts['color_etiqueta'])) {
        $estilo_etiqueta .= 'color:' . esc_attr($atts['color_etiqueta']) . '!important;';
    }

    $estilo_valor = '';
    if (!empty($options['color_valor'])) {
        $estilo_valor .= 'color:' . esc_attr($options['color_valor']) . '!important;';
    }
    if (!empty($atts['color_valor'])) {
        $estilo_valor .= 'color:' . esc_attr($atts['color_valor']) . '!important;';
    }

    $estilo_enlace = '';
    if (!empty($options['color_enlace'])) {
        $estilo_enlace .= 'color:' . esc_attr($options['color_enlace']) . '!important;';
    }
    if (!empty($atts['color_enlace'])) {
        $estilo_enlace .= 'color:' . esc_attr($atts['color_enlace']) . '!important;';
    }

    $inline_style = sprintf('grid-template-columns: repeat(%d, 1fr);', $columnas);

    ob_start();
?>
    <div class="<?php echo esc_attr($grid_class); ?>" style="<?php echo esc_attr($inline_style); ?>">
        <?php foreach ($data as $row) : ?>
            <?php echo _mce_render_single_card($row, $atts); ?>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Renders a single product card HTML based on provided data and shortcode attributes.
 *
 * @param array $row  The data for a single row/product.
 * @param array $atts The full array of shortcode attributes.
 * @return string The rendered HTML for a single card.
 */
function _mce_render_single_card($row, $atts)
{
    ob_start();

    // Extract attributes for consistent rendering
    $columnas_a_mostrar_str = $atts['columnas_mostrar'] ?? '';
    $llave_titulo = $atts['llave_titulo'] ?? '';
    $etiquetas_a_ocultar_str = $atts['ocultar_etiquetas'] ?? '';

    $columnas_a_mostrar = array();
    if (! empty($columnas_a_mostrar_str)) {
        $columnas_a_mostrar = array_map('trim', explode(',', $columnas_a_mostrar_str));
    }
    $etiquetas_a_ocultar = array();
    if (! empty($etiquetas_a_ocultar_str)) {
        $etiquetas_a_ocultar = array_map('trim', explode(',', $etiquetas_a_ocultar_str));
    }

    // Styles from attributes or options
    $options = get_option('mce_style_settings', array());

    $estilo_titulo = '';
    if (!empty($options['color_titulo'])) {
        $estilo_titulo .= 'color:' . esc_attr($options['color_titulo']) . '!important;';
    }
    if (!empty($atts['color_titulo'])) {
        $estilo_titulo .= 'color:' . esc_attr($atts['color_titulo']) . '!important;';
    }
    if (!empty($options['tamano_titulo'])) {
        $estilo_titulo .= 'font-size:' . esc_attr($options['tamano_titulo']) . '!important;';
    }
    if (!empty($atts['tamano_titulo'])) {
        $estilo_titulo .= 'font-size:' . esc_attr($atts['tamano_titulo']) . '!important;';
    }

    $estilo_etiqueta = '';
    if (!empty($options['color_etiqueta'])) {
        $estilo_etiqueta .= 'color:' . esc_attr($options['color_etiqueta']) . '!important;';
    }
    if (!empty($atts['color_etiqueta'])) {
        $estilo_etiqueta .= 'color:' . esc_attr($atts['color_etiqueta']) . '!important;';
    }

    $estilo_valor = '';
    if (!empty($options['color_valor'])) {
        $estilo_valor .= 'color:' . esc_attr($options['color_valor']) . '!important;';
    }
    if (!empty($atts['color_valor'])) {
        $estilo_valor .= 'color:' . esc_attr($atts['color_valor']) . '!important;';
    }

    $estilo_enlace = '';
    if (!empty($options['color_enlace'])) {
        $estilo_enlace .= 'color:' . esc_attr($options['color_enlace']) . '!important;';
    }
    if (!empty($atts['color_enlace'])) {
        $estilo_enlace .= 'color:' . esc_attr($atts['color_enlace']) . '!important;';
    }
?>
    <div class="mce-producto-card">
        <?php
        if (! empty($llave_titulo) && isset($row[$llave_titulo])) {
            echo '<h3 class="mce-card-title" style="' . esc_attr($estilo_titulo) . '">' . esc_html($row[$llave_titulo]) . '</h3>';
        }

        echo '<div class="mce-card-meta">';
        foreach ($row as $key => $value) :
            if (! empty($columnas_a_mostrar) && ! in_array($key, $columnas_a_mostrar, true)) {
                continue;
            }
            if ($key === $llave_titulo) {
                continue;
            }

            $mostrar_etiqueta = ! in_array($key, $etiquetas_a_ocultar, true);
            $clase_css_item = $mostrar_etiqueta ? 'mce-card-item' : 'mce-card-item mce-item-no-label';
        ?>
            <div class="<?php echo esc_attr($clase_css_item); ?>">
                <?php if ($mostrar_etiqueta) : ?>
                    <strong style="<?php echo esc_attr($estilo_etiqueta); ?>"><?php echo esc_html($key); ?>:</strong>
                <?php endif; ?>

                <span style="<?php echo esc_attr($estilo_valor); ?>">
                    <?php
                    $clean_value = trim((string) $value);
                    $is_pdf = str_ends_with(strtolower($clean_value), '.pdf');
                    $is_url = filter_var($clean_value, FILTER_VALIDATE_URL);
                    $is_relative_path = str_starts_with($clean_value, '/');

                    if ($is_pdf) {
                        $pdf_url = '';
                        if ($is_url) {
                            $pdf_url = $clean_value;
                        } elseif ($is_relative_path) {
                            $pdf_url = home_url($clean_value);
                        }

                        if ($pdf_url) {
                    ?>
                            <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener noreferrer" class="mce-pdf-link" style="<?php echo esc_attr($estilo_enlace); ?>">
                                <?php echo esc_html(__('Ver PDF', 'mi-conexion-externa')); ?>
                            </a>
                    <?php
                        } else {
                            echo esc_html($value); // Mostrar como texto si no es una URL válida o ruta relativa
                        }
                    } else {
                        echo esc_html($value);
                    }
                    ?>
                </span>
            </div>
        <?php endforeach; ?>
        <?php echo '</div>'; ?>
    </div>
<?php
    return ob_get_clean();
}



