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
 * 1. REGISTRAR EL SHORTCODE
 */
function mce_register_shortcodes() {
	remove_shortcode( 'mostrar_mce_productos' );
	add_shortcode( 'mce_mostrar_tabla', 'mce_render_tabla_shortcode' );
}
add_action( 'init', 'mce_register_shortcodes' );


/**
 * 2. LA LÓGICA DE RENDERIZADO DEL SHORTCODE
 */
function mce_render_tabla_shortcode( $atts ) {

	// 1. Validar 'tabla'
	if ( empty( $atts['tabla'] ) ) {
		return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]: Falta el atributo "tabla" en el shortcode. Ej: [mce_mostrar_tabla tabla="su_tabla"]', 'mi-conexion-externa' ) ) . '</p>';
	}

	// 2. Parsear atributos
	$a = shortcode_atts(
		array(
			'tabla'             => '',
			'columnas'          => 3,
			'paginacion'        => 10,
			'columnas_mostrar'  => '',
			'llave_titulo'      => '',
			'ocultar_etiquetas' => '',
			'color_titulo'      => '',
			'tamano_titulo'     => '',
			'color_etiqueta'    => '',
			'color_valor'       => '',
			'color_enlace'      => '',
		),
		$atts
	);

	// 3. Sanitizar atributos de datos
	$tabla                   = sanitize_text_field( $a['tabla'] );
	$columnas                = intval( $a['columnas'] );
	$filas_por_pagina        = intval( $a['paginacion'] );
	$columnas_a_mostrar_str    = sanitize_text_field( $a['columnas_mostrar'] );
	$llave_titulo            = sanitize_text_field( $a['llave_titulo'] );
	$etiquetas_a_ocultar_str = sanitize_text_field( $a['ocultar_etiquetas'] );

	if ( $columnas < 1 || $columnas > 6 ) { $columnas = 3; }
	if ( $filas_por_pagina <= 0 ) { $filas_por_pagina = 10; }

	// 4. Obtener página actual
	$pagina_actual = 1;
	if ( isset( $a['pagina'] ) ) {
		$pagina_actual = intval( $a['pagina'] );
	} elseif ( isset( $_GET['pagina_mce'] ) ) {
		$pagina_actual = intval( $_GET['pagina_mce'] );
	}
	if ( $pagina_actual < 1 ) { $pagina_actual = 1; }

	// 5. Convertir strings en arrays
	$columnas_a_mostrar = array();
	if ( ! empty( $columnas_a_mostrar_str ) ) {
		$columnas_a_mostrar = array_map( 'trim', explode( ',', $columnas_a_mostrar_str ) );
	}
	$etiquetas_a_ocultar = array();
	if ( ! empty( $etiquetas_a_ocultar_str ) ) {
		$etiquetas_a_ocultar = array_map( 'trim', explode( ',', $etiquetas_a_ocultar_str ) );
	}

	// 6. Obtener los datos
	$db_handler = new MCE_DB_Handler();
	$resultado = $db_handler->get_paginated_table_data( $tabla, $filas_por_pagina, $pagina_actual );

	// 7. Manejar Errores
	if ( is_wp_error( $resultado ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $resultado->get_error_message() ) . '</p>';
		}
		return '';
	}

	// 8. Separar datos
	$data       = $resultado['data'];
	$total_filas = $resultado['total_rows'];

	// 9. Manejar Tabla Vacía
	if ( empty( $data ) && $pagina_actual === 1 ) {
		return '<p>' . esc_html( sprintf( __( 'No se encontraron datos en la tabla "%s".', 'mi-conexion-externa' ), $tabla ) ) . '</p>';
	}

	// 10. Encolar y Localizar (¡Lógica corregida!)
	wp_enqueue_style( 'mce-public-style' );
	wp_enqueue_script( 'mce-public-script' ); 
	wp_localize_script(
		'mce-public-script',
		'mce_ajax_object',
		array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'mce_ajax_nonce' ), // *** ¡EL NOMBRE ES 'mce_ajax_nonce'! ***
		)
	);
	
	// 11. Estilo en línea para columnas
	$inline_style = sprintf(
		'grid-template-columns: repeat(%d, 1fr);',
		$columnas
	);

	// 12. Construir estilos en línea
	$estilo_titulo = '';
	if ( ! empty( $a['color_titulo'] ) ) { $estilo_titulo .= 'color: ' . esc_attr( $a['color_titulo'] ) . ' !important;'; }
	if ( ! empty( $a['tamano_titulo'] ) ) { $estilo_titulo .= 'font-size: ' . esc_attr( $a['tamano_titulo'] ) . ' !important;'; }
	$estilo_etiqueta = '';
	if ( ! empty( $a['color_etiqueta'] ) ) { $estilo_etiqueta .= 'color: ' . esc_attr( $a['color_etiqueta'] ) . ' !important;'; }
	$estilo_valor = '';
	if ( ! empty( $a['color_valor'] ) ) { $estilo_valor .= 'color: ' . esc_attr( $a['color_valor'] ) . ' !important;'; }
	$estilo_enlace = '';
	if ( ! empty( $a['color_enlace'] ) ) { $estilo_enlace .= 'color: ' . esc_attr( $a['color_enlace'] ) . ' !important;'; }

	// 13. Construir el HTML
	ob_start();
	?>

	<div class="mce-shortcode-wrapper"
		data-tabla="<?php echo esc_attr( $tabla ); ?>"
		data-columnas="<?php echo esc_attr( $columnas ); ?>"
		data-paginacion="<?php echo esc_attr( $filas_por_pagina ); ?>"
		data-columnas_mostrar="<?php echo esc_attr( $columnas_a_mostrar_str ); ?>"
		data-llave_titulo="<?php echo esc_attr( $llave_titulo ); ?>"
		data-ocultar_etiquetas="<?php echo esc_attr( $etiquetas_a_ocultar_str ); ?>"
		data-color_titulo="<?php echo esc_attr( $a['color_titulo'] ); ?>"
		data-tamano_titulo="<?php echo esc_attr( $a['tamano_titulo'] ); ?>"
		data-color_etiqueta="<?php echo esc_attr( $a['color_etiqueta'] ); ?>"
		data-color_valor="<?php echo esc_attr( $a['color_valor'] ); ?>"
		data-color_enlace="<?php echo esc_attr( $a['color_enlace'] ); ?>"
	>

		<div class="mce-productos-grid" style="<?php echo esc_attr( $inline_style ); ?>">
			<?php foreach ( $data as $row ) : ?>
				<div class="mce-producto-card">
					<?php
					if ( ! empty( $llave_titulo ) && isset( $row[ $llave_titulo ] ) ) {
						echo '<h3 class="mce-card-title" style="' . esc_attr( $estilo_titulo ) . '">' . esc_html( $row[ $llave_titulo ] ) . '</h3>';
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
								<strong style="<?php echo esc_attr( $estilo_etiqueta ); ?>"><?php echo esc_html( $key ); ?>:</strong>
							<?php endif; ?>
							
							<span style="<?php echo esc_attr( $estilo_valor ); ?>">
								<?php
								$clean_value = trim( (string) $value );
								if ( str_starts_with( $clean_value, 'http' ) && str_ends_with( strtolower( $clean_value ), '.pdf' ) ) {
									?>
									<a href="<?php echo esc_url( $clean_value ); ?>" target="_blank" rel="noopener noreferrer" class="mce-pdf-link" style="<?php echo esc_attr( $estilo_enlace ); ?>">
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

		<?php
		// 14. DIBUJAR LOS ENLACES DE PAGINACIÓN
		$total_paginas = ceil( $total_filas / $filas_por_pagina );

		if ( $total_paginas > 1 ) {
			echo '<div class="mce-pagination">';
			echo paginate_links(
				array(
					'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( add_query_arg( 'pagina_mce', PHP_INT_MAX ) ) ),
					'format'    => '',
					'current'   => $pagina_actual,
					'total'     => $total_paginas,
					'prev_text' => __( '&laquo; Anterior', 'mi-conexion-externa' ),
					'next_text' => __( 'Siguiente &raquo;', 'mi-conexion-externa' ),
				)
			);
			echo '</div>';
		}

	?>
	</div> <?php
	
	return ob_get_clean();
}


/**
 * 4. EL RECEPTOR DE AJAX
 */
function mce_get_page_content_ajax() {
	// *** ¡LÍNEA CORREGIDA! ***
	// Comprobar el nombre de nonce correcto y la clave 'nonce'.
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