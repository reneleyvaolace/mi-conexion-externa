<?php
/**
 * Lógica de Shortcodes para Mi Conexión Externa.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. REGISTRAR LOS ESTILOS
 * (Sin cambios)
 */
function mce_register_public_styles() {
	wp_register_style(
		'mce-public-style',
		MCE_PLUGIN_URL . 'public/css/mce-public-style.css',
		array(),
		MCE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'mce_register_public_styles' );


/**
 * 2. REGISTRAR EL SHORTCODE
 * (Sin cambios)
 */
function mce_register_shortcodes() {
	remove_shortcode( 'mostrar_mce_productos' );
	
	add_shortcode(
		'mce_mostrar_tabla',
		'mce_render_tabla_shortcode'
	);
}
add_action( 'init', 'mce_register_shortcodes' );


/**
 * 3. LA LÓGICA DE RENDERIZADO DEL SHORTCODE
 *
 * *** ¡CORREGIDO EL BUG DE PAGINACIÓN! ***
 */
function mce_render_tabla_shortcode( $atts ) {

	// 1. Validar el atributo OBLIGATORIO 'tabla'.
	if ( empty( $atts['tabla'] ) ) {
		return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]: Falta el atributo "tabla" en el shortcode. Ej: [mce_mostrar_tabla tabla="su_tabla"]', 'mi-conexion-externa' ) ) . '</p>';
	}

	// 2. Parsear los atributos del shortcode
	$a = shortcode_atts(
		array(
			'tabla'             => '',
			'columnas'          => 3,
			'paginacion'        => 10,
			'columnas_mostrar'  => '',
			'llave_titulo'      => '',
			'ocultar_etiquetas' => '',
		),
		$atts
	);

	// 3. Sanitizar todos los atributos
	$tabla                   = sanitize_text_field( $a['tabla'] );
	$columnas                = intval( $a['columnas'] );
	$filas_por_pagina        = intval( $a['paginacion'] );
	$columnas_a_mostrar_str    = sanitize_text_field( $a['columnas_mostrar'] );
	$llave_titulo            = sanitize_text_field( $a['llave_titulo'] );
	$etiquetas_a_ocultar_str = sanitize_text_field( $a['ocultar_etiquetas'] );

	// Forzar valores seguros
	if ( $columnas < 1 || $columnas > 6 ) { $columnas = 3; }
	if ( $filas_por_pagina <= 0 ) { $filas_por_pagina = 10; }

	// 4. Obtener la página actual de la URL
	$pagina_actual = 1;
	if ( isset( $_GET['pagina_mce'] ) ) {
		$pagina_actual = intval( $_GET['pagina_mce'] );
		if ( $pagina_actual < 1 ) { $pagina_actual = 1; }
	}

	// 5. Convertir los strings de atributos en arrays limpios
	$columnas_a_mostrar = array();
	if ( ! empty( $columnas_a_mostrar_str ) ) {
		$columnas_a_mostrar = array_map( 'trim', explode( ',', $columnas_a_mostrar_str ) );
	}
	$etiquetas_a_ocultar = array();
	if ( ! empty( $etiquetas_a_ocultar_str ) ) {
		$etiquetas_a_ocultar = array_map( 'trim', explode( ',', $etiquetas_a_ocultar_str ) );
	}

	// 6. Obtener los datos usando nuestro "cerebro"
	$db_handler = new MCE_DB_Handler();
	$resultado = $db_handler->get_paginated_table_data( $tabla, $filas_por_pagina, $pagina_actual );

	// 7. Manejar Errores
	if ( is_wp_error( $resultado ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $resultado->get_error_message() ) . '</p>';
		}
		return '';
	}

	// 8. Separar los datos del resultado
	$data       = $resultado['data'];
	$total_filas = $resultado['total_rows'];

	// 9. Manejar Tabla Vacía
	if ( empty( $data ) ) {
		if ( $pagina_actual === 1 ) {
			return '<p>' . esc_html( sprintf( __( 'No se encontraron datos en la tabla "%s".', 'mi-conexion-externa' ), $tabla ) ) . '</p>';
		} else {
			return '<p>' . esc_html( __( 'No se encontraron datos para esta página.', 'mi-conexion-externa' ) ) . '</p>';
		}
	}

	// 10. ¡Éxito! Cargar el CSS.
	wp_enqueue_style( 'mce-public-style' );

	// 11. Crear el estilo en línea para las columnas.
	$inline_style = sprintf(
		'grid-template-columns: repeat(%d, 1fr);',
		$columnas
	);

	// 12. Construir el HTML.
	ob_start();
	?>

	<div class="mce-productos-grid" style="<?php echo esc_attr( $inline_style ); ?>">
		<?php foreach ( $data as $row ) : // Iterar sobre la "porción" de filas ?>
			
			<div class="mce-producto-card">
				<?php
				// Lógica del Título
				if ( ! empty( $llave_titulo ) && isset( $row[ $llave_titulo ] ) ) {
					echo '<h3 class="mce-card-title">' . esc_html( $row[ $llave_titulo ] ) . '</h3>';
				}

				// Lógica de Meta-Datos
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
							<strong><?php echo esc_html( $key ); ?>:</strong>
						<?php endif; ?>
						<span>
							<?php
							// Lógica de PDF
							$clean_value = trim( (string) $value );
							if ( str_starts_with( $clean_value, 'http' ) && str_ends_with( strtolower( $clean_value ), '.pdf' ) ) {
								?>
								<a href="<?php echo esc_url( $clean_value ); ?>" target="_blank" rel="noopener noreferrer" class="mce-pdf-link">
									<?php echo esc_html( __( 'Ver PDF', 'mi-conexion-externa' ) ); ?>
								</a>
								<?php
							} else {
								echo esc_html( $value );
							}
							?>
						</span>
					</div>
				<?php endforeach; // Fin del bucle de columnas (key => value) ?>
				<?php echo '</div>'; // Fin de .mce-card-meta ?>
			</div> <?php endforeach; // Fin del bucle de filas (row) ?>
	</div> <?php
	// 13. DIBUJAR LOS ENLACES DE PAGINACIÓN
	$total_paginas = ceil( $total_filas / $filas_por_pagina );

	if ( $total_paginas > 1 ) {
		
		echo '<div class="mce-pagination">';
		
		// *** ¡LÓGICA DE PAGINACIÓN CORREGIDA! ***
		// Esta forma es más robusta y maneja los query args correctamente.
		echo paginate_links(
			array(
				// 'base' => add_query_arg( 'pagina_mce', '%#%' ), // Esta es la forma simple y correcta.
				// Forma más robusta:
				'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( add_query_arg( 'pagina_mce', PHP_INT_MAX ) ) ),
				'format'    => '', // Ya no se necesita
				'current'   => $pagina_actual,
				'total'     => $total_paginas,
				'prev_text' => __( '&laquo; Anterior', 'mi-conexion-externa' ),
				'next_text' => __( 'Siguiente &raquo;', 'mi-conexion-externa' ),
			)
		);
		echo '</div>';
	}

	// 14. Devolver el HTML capturado.
	return ob_get_clean();
}