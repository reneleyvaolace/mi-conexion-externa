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
 *
 * *** ¡CAMBIO! ***
 * Reemplazamos el shortcode antiguo por uno genérico.
 */
function mce_register_shortcodes() {
	// Eliminamos el shortcode antiguo (si existiera en caché)
	remove_shortcode( 'mostrar_mce_productos' );
	
	// Añadimos el nuevo shortcode genérico
	add_shortcode(
		'mce_mostrar_tabla', // El shortcode: [mce_mostrar_tabla tabla="..." columnas="..." limite="..."]
		'mce_render_tabla_shortcode' // La función que se ejecutará
	);
}
add_action( 'init', 'mce_register_shortcodes' );


/**
 * 3. LA LÓGICA DE RENDERIZADO DEL SHORTCODE
 *
 * *** ¡REFRACTORIZADO! ***
 * Esta función ahora es genérica y detecta PDFs.
 */
function mce_render_tabla_shortcode( $atts ) {

	// 1. Validar el atributo OBLIGATORIO 'tabla'.
	if ( empty( $atts['tabla'] ) ) {
		return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]: Falta el atributo "tabla" en el shortcode. Ej: [mce_mostrar_tabla tabla="su_tabla"]', 'mi-conexion-externa' ) ) . '</p>';
	}

	// 2. Parsear los atributos del shortcode
	$a = shortcode_atts(
		array(
			'tabla'    => '',
			'columnas' => 3,
			'limite'   => 10,
		),
		$atts
	);

	// 3. Sanitizar todos los atributos (Regla 1: Seguridad)
	$tabla    = sanitize_text_field( $a['tabla'] );
	$columnas = intval( $a['columnas'] );
	$limite   = intval( $a['limite'] );

	// Forzar valores seguros
	if ( $columnas < 1 || $columnas > 6 ) {
		$columnas = 3;
	}
	if ( $limite <= 0 ) {
		$limite = 10;
	}

	// 4. Obtener los datos usando nuestro "cerebro".
	$db_handler = new MCE_DB_Handler();
	// Usamos la función genérica (la que corregiste)
	$data = $db_handler->get_table_content( $tabla, $limite );

	// 5. Manejar Errores (Conexión, tabla inválida, etc.)
	if ( is_wp_error( $data ) ) {
		// Mostrar un error solo a los administradores.
		if ( current_user_can( 'manage_options' ) ) {
			return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $data->get_error_message() ) . '</p>';
		}
		return ''; // No mostrar nada al público.
	}

	// 6. Manejar Tabla Vacía
	if ( empty( $data ) ) {
		return '<p>' . esc_html( sprintf( __( 'No se encontraron datos en la tabla "%s".', 'mi-conexion-externa' ), $tabla ) ) . '</p>';
	}

	// 7. ¡Éxito! Cargar el CSS.
	wp_enqueue_style( 'mce-public-style' );

	// 8. Crear el estilo en línea para las columnas.
	$inline_style = sprintf(
		'grid-template-columns: repeat(%d, 1fr);',
		$columnas
	);

	// 9. Construir el HTML.
	ob_start();
	?>

	<div class="mce-productos-grid" style="<?php echo esc_attr( $inline_style ); ?>">

		<?php foreach ( $data as $row ) : // Iterar sobre cada fila (cada "producto") ?>
			
			<div class="mce-producto-card">
				
				<?php foreach ( $row as $key => $value ) : // Iterar sobre las columnas (ej. 'nombre', 'sku', 'documento_pdf') ?>
					
					<div class="mce-card-item">
						<strong><?php echo esc_html( $key ); ?>:</strong>
						<span>
							<?php
							// *** ¡LÓGICA DE PDF! (Tu Requisito) ***
							
							// 1. Limpiar el valor para comprobaciones
							$clean_value = trim( (string) $value );
							
							// 2. Comprobar si es un enlace PDF
							if ( str_starts_with( $clean_value, 'http' ) && str_ends_with( strtolower( $clean_value ), '.pdf' ) ) {
								
								// 3. Si es PDF, imprimir un enlace seguro (Regla 1: esc_url)
								?>
								<a href="<?php echo esc_url( $clean_value ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( __( 'Ver PDF', 'mi-conexion-externa' ) ); ?>
								</a>
								<?php
								
							} else {
								
								// 4. Si no, solo imprimir el texto seguro (Regla 1: esc_html)
								echo esc_html( $value );
								
							}
							?>
						</span>
					</div> <?php endforeach; // Fin del bucle de columnas (key => value) ?>

			</div> <?php endforeach; // Fin del bucle de filas (row) ?>

	</div> <?php
	// 10. Devolver el HTML capturado.
	return ob_get_clean();
}