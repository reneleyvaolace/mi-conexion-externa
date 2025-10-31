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
 *
 * Registramos nuestro CSS público. No lo "encolamos" (enqueue) aún.
 * Solo lo dejamos "listo" para que el shortcode lo llame si lo necesita.
 */
function mce_register_public_styles() {
	wp_register_style(
		'mce-public-style', // El "nombre" de nuestro estilo.
		MCE_PLUGIN_URL . 'public/css/mce-public-style.css', // Ruta al archivo
		array(), // Dependencias (ninguna)
		MCE_VERSION // Versión
	);
}
add_action( 'wp_enqueue_scripts', 'mce_register_public_styles' );


/**
 * 2. REGISTRAR EL SHORTCODE
 *
 * Esta función le dice a WordPress sobre nuestro shortcode.
 */
function mce_register_shortcodes() {
	add_shortcode(
		'mostrar_mce_productos', // El shortcode que escribirás: [mostrar_mce_productos]
		'mce_render_productos_shortcode' // La función que se ejecutará
	);
}
add_action( 'init', 'mce_register_shortcodes' );


/**
 * 3. LA LÓGICA DE RENDERIZADO DEL SHORTCODE
 *
 * Esto es lo que se ejecuta cuando WordPress encuentra [mostrar_mce_productos].
 */
function mce_render_productos_shortcode( $atts ) {

	// 1. Obtener los datos usando nuestro "cerebro".
	$db_handler = new MCE_DB_Handler();
	$productos = $db_handler->get_productos();

	// 2. Manejar Errores (Conexión, etc.)
	if ( is_wp_error( $productos ) ) {
		// Mostrar un error solo a los administradores.
		if ( current_user_can( 'manage_options' ) ) {
			return '<p style="color:red;">' . esc_html( __( 'Error del Plugin [MCE]:', 'mi-conexion-externa' ) . ' ' . $productos->get_error_message() ) . '</p>';
		}
		return ''; // No mostrar nada al público.
	}

	// 3. Manejar Tabla Vacía
	if ( empty( $productos ) ) {
		return '<p>' . esc_html__( 'No hay productos para mostrar en este momento.', 'mi-conexion-externa' ) . '</p>';
	}

	// 4. *** ¡ÉXITO! Hay productos. ***
	// Ahora que sabemos que vamos a mostrar algo, le decimos a WordPress
	// que cargue nuestro archivo CSS en esta página.
	wp_enqueue_style( 'mce-public-style' );

	// 5. Construir el HTML.
	// Usamos un "output buffer" (Regla 1) para capturar todo el
	// HTML y devolverlo como una sola cadena de texto.
	ob_start();
	?>

	<div class="mce-productos-grid">

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
	// 6. Devolver el HTML capturado.
	return ob_get_clean();
}