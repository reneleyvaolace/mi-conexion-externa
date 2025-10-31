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
 * *** ¡REFRACTORIZADO! ***
 * Ahora acepta el atributo 'ocultar_etiquetas'.
 */
function mce_render_tabla_shortcode( $atts ) {

	// 1. Validar el atributo OBLIGATORIO 'tabla'.
	if ( empty( $atts['tabla'] ) ) {
		return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]: Falta el atributo "tabla" en el shortcode. Ej: [mce_mostrar_tabla tabla="su_tabla"]', 'mi-conexion-externa' ) ) . '</p>';
	}

	// 2. Parsear los atributos del shortcode
	// *** ¡NUEVO! Se añade 'ocultar_etiquetas' ***
	$a = shortcode_atts(
		array(
			'tabla'             => '',
			'columnas'          => 3,
			'limite'            => 10,
			'columnas_mostrar'  => '',
			'llave_titulo'      => '',
			'ocultar_etiquetas' => '', // Nuevo: string vacío por defecto
		),
		$atts
	);

	// 3. Sanitizar todos los atributos (Regla 1: Seguridad)
	$tabla                   = sanitize_text_field( $a['tabla'] );
	$columnas                = intval( $a['columnas'] );
	$limite                  = intval( $a['limite'] );
	$columnas_a_mostrar_str    = sanitize_text_field( $a['columnas_mostrar'] );
	$llave_titulo            = sanitize_text_field( $a['llave_titulo'] );
	$etiquetas_a_ocultar_str = sanitize_text_field( $a['ocultar_etiquetas'] ); // Nuevo

	// Forzar valores seguros
	if ( $columnas < 1 || $columnas > 6 ) { $columnas = 3; }
	if ( $limite <= 0 ) { $limite = 10; }

	// 4. Convertir los strings de atributos en arrays limpios
	$columnas_a_mostrar = array();
	if ( ! empty( $columnas_a_mostrar_str ) ) {
		$temp_array = explode( ',', $columnas_a_mostrar_str );
		$columnas_a_mostrar = array_map( 'trim', $temp_array );
	}

	// ¡NUEVO!
	$etiquetas_a_ocultar = array();
	if ( ! empty( $etiquetas_a_ocultar_str ) ) {
		$temp_array = explode( ',', $etiquetas_a_ocultar_str );
		$etiquetas_a_ocultar = array_map( 'trim', $temp_array );
	}

	// 5. Obtener los datos usando nuestro "cerebro".
	$db_handler = new MCE_DB_Handler();
	$data = $db_handler->get_table_content( $tabla, $limite );

	// 6. Manejar Errores
	if ( is_wp_error( $data ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $data->get_error_message() ) . '</p>';
		}
		return '';
	}

	// 7. Manejar Tabla Vacía
	if ( empty( $data ) ) {
		return '<p>' . esc_html( sprintf( __( 'No se encontraron datos en la tabla "%s".', 'mi-conexion-externa' ), $tabla ) ) . '</p>';
	}

	// 8. ¡Éxito! Cargar el CSS.
	wp_enqueue_style( 'mce-public-style' );

	// 9. Crear el estilo en línea para las columnas.
	$inline_style = sprintf(
		'grid-template-columns: repeat(%d, 1fr);',
		$columnas
	);

	// 10. Construir el HTML.
	ob_start();
	?>

	<div class="mce-productos-grid" style="<?php echo esc_attr( $inline_style ); ?>">

		<?php foreach ( $data as $row ) : // Iterar sobre cada fila ?>
			
			<div class="mce-producto-card">
				
				<?php
				// Primero, buscamos y mostramos el TÍTULO.
				if ( ! empty( $llave_titulo ) && isset( $row[ $llave_titulo ] ) ) {
					echo '<h3 class="mce-card-title">' . esc_html( $row[ $llave_titulo ] ) . '</h3>';
				}

				// Segundo, mostramos el resto de los datos.
				echo '<div class="mce-card-meta">';
				
				foreach ( $row as $key => $value ) : // Iterar sobre las columnas (key => value)
					
					// Condición 1: Si especificamos una lista, y esta NO está en la lista, saltar.
					if ( ! empty( $columnas_a_mostrar ) && ! in_array( $key, $columnas_a_mostrar, true ) ) {
						continue;
					}

					// Condición 2: Si esta es la llave del título, ya la mostramos. Saltar.
					if ( $key === $llave_titulo ) {
						continue;
					}

					// *** ¡NUEVA LÓGICA DE ETIQUETA! ***
					$mostrar_etiqueta = ! in_array( $key, $etiquetas_a_ocultar, true );
					$clase_css_item = $mostrar_etiqueta ? 'mce-card-item' : 'mce-card-item mce-item-no-label';
					?>

					<div class="<?php echo esc_attr( $clase_css_item ); ?>">
						
						<?php
						// Condición 3: Mostrar la etiqueta solo si se debe.
						if ( $mostrar_etiqueta ) :
							?>
							<strong><?php echo esc_html( $key ); ?>:</strong>
						<?php endif; ?>

						<span>
							<?php
							// Lógica de detección de PDF
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
					</div> <?php endforeach; // Fin del bucle de columnas (key => value) ?>
				
				<?php echo '</div>'; // Fin de .mce-card-meta ?>

			</div> <?php endforeach; // Fin del bucle de filas (row) ?>

	</div> <?php
	// 11. Devolver el HTML capturado.
	return ob_get_clean();
}