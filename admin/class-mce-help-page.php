<?php
/**
 * Lógica para la Página de "Ayuda" del Administrador.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Help_Page
 *
 * No tiene hooks, solo un método público 'render'
 * que es llamado por el cargador principal del admin.
 */
class MCE_Help_Page {

	/**
	 * Renderiza el contenido HTML de la página "Ayuda / Guía de Uso".
	 */
	public function render_page_content() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Guía de Uso - Mi Conexión Externa', 'mi-conexion-externa' ) ); ?></h1>
			<p><?php echo esc_html( __( '¡Bienvenido! Este plugin le permite conectarse a una base de datos externa y mostrar sus datos dentro de WordPress.', 'mi-conexion-externa' ) ); ?></p>

			<div class="mce-help-section">
				<h2><?php echo esc_html( __( 'Paso 1: Configurar la Conexión', 'mi-conexion-externa' ) ); ?></h2>
				<ol>
					<li><?php echo esc_html( __( 'Vaya a la pestaña "Ajustes".', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Rellene los 5 campos con las credenciales de su base de datos (IP/Host, Puerto, Nombre de BBDD, Usuario y Contraseña).', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Haga clic en "Guardar Cambios".', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Use el botón "Probar Conexión a BBDD" para confirmar que todo es correcto. Debe ver un mensaje de "¡Éxito!".', 'mi-conexion-externa' ) ); ?></li>
				</ol>
				<p><strong><?php echo esc_html( __( '¡No puede continuar hasta que la conexión sea exitosa!', 'mi-conexion-externa' ) ); ?></strong></p>
			</div>

			<div class="mce-help-section">
				<h2><?php echo esc_html( __( 'Paso 2: Mostrar Datos (Dos Opciones)', 'mi-conexion-externa' ) ); ?></h2>
				<p><?php echo esc_html( __( 'Una vez la conexión esté activa, tiene dos formas de mostrar los datos de sus productos:', 'mi-conexion-externa' ) ); ?></p>
				
				<hr>

				<h3><?php echo esc_html( __( 'Opción A: Shortcode (Para cualquier página o Elementor Gratuito)', 'mi-conexion-externa' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Este método funciona en cualquier página, entrada, o dentro de un widget "Shortcode" de Elementor gratuito.', 'mi-conexion-externa' ) ); ?></p>
				<ol>
					<li><?php echo esc_html( __( 'Edite una página y añada un bloque/widget de Shortcode.', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Para mostrar la cuadrícula de productos por defecto (3 columnas), use:', 'mi-conexion-externa' ) ); ?>
						<pre><code>[mostrar_mce_productos]</code></pre>
					</li>
					<li><?php echo esc_html( __( '(Opcional) Para especificar el número de columnas (de 1 a 6), use el atributo "columnas":', 'mi-conexion-externa' ) ); ?>
						<pre><code>[mostrar_mce_productos columnas="4"]</code></pre>
					</li>
				</ol>

				<hr>

				<h3><?php echo esc_html( __( 'Opción B: Integración con Elementor Pro (Avanzado)', 'mi-conexion-externa' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Este código ya está incluido. Si usted tiene Elementor Pro instalado y activo, esta opción aparecerá automáticamente.', 'mi-conexion-externa' ) ); ?></p>
				<ol>
					<li><?php echo esc_html( __( 'Edite una página con Elementor.', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Arrastre el widget "Loop Grid" a su página.', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'En el panel de "Loop Grid", vaya a la pestaña "Query" (Consulta).', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'En el campo "Fuente" (Source), seleccione nuestra consulta personalizada:', 'mi-conexion-externa' ) ); ?> <strong>"<?php echo esc_html( __( 'Productos Externos (MCE)', 'mi-conexion-externa' ) ); ?>"</strong>.</li>
					<li><?php echo esc_html( __( '¡Listo! Ahora diseñe su "Plantilla" (template) de loop como lo haría normalmente.', 'mi-conexion-externa' ) ); ?></li>
				</ol>
			</div>
			
			<div class="mce-help-section">
				<h2><?php echo esc_html( __( 'Paso 3: Explorar (Herramienta de Desarrollo)', 'mi-conexion-externa' ) ); ?></h2>
				<p><?php echo esc_html( __( 'La pestaña "Explorador" es una herramienta de depuración para usted. Le permite confirmar que la conexión está activa y ver una lista de todas las tablas en su base de datos externa, así como previsualizar las primeras 100 filas de cualquier tabla.', 'mi-conexion-externa' ) ); ?></p>
			</div>

			<style>
				.mce-help-section {
					background: #ffffff;
					border: 1px solid #e0e0e0;
					padding: 15px 25px;
					margin-top: 20px;
					border-radius: 4px;
				}
				.mce-help-section h2 {
					margin-top: 0.5em;
					border-bottom: 1px solid #eee;
					padding-bottom: 8px;
				}
				.mce-help-section pre {
					background: #f1f1f1;
					padding: 15px;
					border-radius: 4px;
					border: 1px solid #ddd;
				}
			</style>
		</div>
		<?php
	}
}