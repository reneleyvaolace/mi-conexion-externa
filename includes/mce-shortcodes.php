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
	add_shortcode(
		'mostrar_mce_productos',
		'mce_render_productos_shortcode'
	);
}
add_action( 'init', 'mce_register_shortcodes' );


/**
 * 3. LA LÓGICA DE RENDERIZADO DEL SHORTCODE
 *
 * *** ¡ACTUALIZADO! ***
 * Ahora acepta el atributo [columnas]
 */
function mce_render_productos_shortcode( $atts ) {

	// *** ¡NUEVO! ***
	// 1. Parsear los atributos del shortcode (Regla 1: WordPress Way)
	// Establecemos un valor por defecto de 3 columnas si no se especifica.
	$a = shortcode_atts(
		array(
			'columnas' => 3,
		),
		$atts
	);

	// 2. Sanitizar el atributo (Regla 1: Seguridad)
	// Nos aseguramos de que sea un número entero.
	$columnas = intval( $a['columnas'] );
	if ( $columnas < 1 || $columnas > 6 ) {
		$columnas = 3; // Forzamos un valor seguro entre 1 y 6.
	}

	// 3. Obtener los datos usando nuestro "cerebro".
	$db_handler = new MCE_DB_Handler();
	$productos = $db_handler->get_productos();

	// 4. Manejar Errores
	if ( is_wp_error( $productos ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $productos->get_error_message() ) . '</p>';
		}
		return '';
	}

	// 5. Manejar Tabla Vacía
	if ( empty( $productos ) ) {
		return '<p>' . esc_html( __( 'No hay productos para mostrar en este momento.', 'mi-conexion-externa' ) . '</p>';
	}

	// 6. ¡Éxito! Cargar el CSS.
	wp_enqueue_style( 'mce-public-style' );

	// 7. *** ¡NUEVO! ***
	// Crear el estilo en línea basado en el atributo.
	$inline_style = sprintf(
		'grid-template-columns: repeat(%d, 1fr);',
		$columnas
	);

	// 8. Construir el HTML.
	ob_start();
	?>

	<div class="mce-productos-grid" style="<?php echo esc_attr( $inline_style ); ?>">

		<?php foreach ( $productos as $producto ) : ?>
			
			<div class="mce-producto-card">
				
				<h3><?php echo esc_html( $producto['nombre'] ); ?></h3>
				
				<div class="mce-sku">
					<strong><?php echo esc_html( __( 'SKU:', 'mi-conexion-externa' ) ); ?></strong>
					<?php echo esc_html( $producto['sku'] ); ?>
				</div>

				<div class="mce-details">
					
					<div class="mce-price">
						$<?php echo esc_html( number_format( $producto['precio'], 2 ) ); ?>
					</div>

					<div class="mce-stock">
						<strong><?php echo esc_html( __( 'Stock:', 'mi-conexion-externa' ) ); ?></strong>
						<?php echo esc_html( $producto['stock'] ); ?>
					</div>

				</div> </div> <?php endforeach; ?>

	</div> <?php
	// 9. Devolver el HTML capturado.
	return ob_get_clean();
}