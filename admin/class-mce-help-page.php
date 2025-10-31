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
	 *
	 * *** ¡ACTUALIZADO! ***
	 * Se documentan los nuevos atributos del shortcode.
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
				<p><?php echo esc_html( __( 'Una vez la conexión esté activa, tiene dos formas de mostrar los datos:', 'mi-conexion-externa' ) ); ?></p>
				
				<hr>

				<h3><?php echo esc_html( __( 'Opción A: Shortcode (Para cualquier página o Elementor Gratuito)', 'mi-conexion-externa' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Este método funciona en cualquier página, entrada, o dentro de un widget "Shortcode" de Elementor gratuito.', 'mi-conexion-externa' ) ); ?></p>
				
				<h4><?php echo esc_html( __( 'Uso Básico', 'mi-conexion-externa' ) ); ?></h4>
				<p><?php echo esc_html( __( 'Muestra todas las columnas de una tabla, con un límite de 10 filas y 3 columnas.', 'mi-conexion-externa' ) ); ?></p>
				<pre><code>[mce_mostrar_tabla tabla="mce_productos"]</code></pre>

				<h4><?php echo esc_html( __( 'Uso Avanzado (Atributos)', 'mi-conexion-externa' ) ); ?></h4>
				<p><?php echo esc_html( __( 'Puede combinar los siguientes atributos para un control total:', 'mi-conexion-externa' ) ); ?></p>
				<ul>
					<li><strong>tabla</strong>: <em>(Obligatorio)</em> <?php echo esc_html( __( 'El nombre de la tabla que desea consultar.', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>columnas</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Número de columnas de la cuadrícula (1-6).', 'mi-conexion-externa' ) ); ?> (Defecto: 3)</li>
					<li><strong>limite</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Número de filas a mostrar.', 'mi-conexion-externa' ) ); ?> (Defecto: 10)</li>
					<li><strong>columnas_mostrar</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Lista separada por comas de las únicas columnas que desea mostrar.', 'mi-conexion-externa' ) ); ?> (Defecto: todas)</li>
					<li><strong>llave_titulo</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'La columna que actuará como título principal (sin etiqueta).', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>ocultar_etiquetas</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Lista separada por comas de columnas que no mostrarán su etiqueta (solo el valor).', 'mi-conexion-externa' ) ); ?></li>
				</ul>

				<h4><?php echo esc_html( __( 'Ejemplo Completo', 'mi-conexion-externa' ) ); ?></h4>
				<p><?php echo esc_html( __( 'Este shortcode mostraría 4 columnas, solo las columnas "nombre", "sku" y "documento", usaría "nombre" como el título, y ocultaría la etiqueta de "documento" (mostrando solo el enlace "Ver PDF"):', 'mi-conexion-externa' ) ); ?></p>
				<pre><code>[mce_mostrar_tabla tabla="mce_productos" columnas="4" limite="8" columnas_mostrar="nombre,sku,documento" llave_titulo="nombre" ocultar_etiquetas="documento"]</code></pre>


				<hr>

				<h3><?php echo esc_html( __( 'Opción B: Integración con Elementor Pro (Avanzado)', 'mi-conexion-externa' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Esta opción está "latente" y se activará automáticamente si instala Elementor Pro.', 'mi-conexion-externa' ) ); ?></p>
				<ol>
					<li><?php echo esc_html( __( 'Arrastre el widget "Loop Grid" a su página.', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'En la pestaña "Query" (Consulta), seleccione como "Fuente" (Source) nuestra consulta personalizada:', 'mi-conexion-externa' ) ); ?> <strong>"<?php echo esc_html( __( 'Productos Externos (MCE)', 'mi-conexion-externa' ) ); ?>"</strong>.</li>
					<li><?php echo esc_html( __( '¡Listo! Ahora diseñe su "Plantilla" (template) de loop como lo haría normalmente.', 'mi-conexion-externa' ) ); ?></li>
				</ol>
			</div>
			
			<div class="mce-help-section">
				<h2><?php echo esc_html( __( 'Paso 3: Explorar (Herramienta de Desarrollo)', 'mi-conexion-externa' ) ); ?></h2>
				<p><?php echo esc_html( __( 'La pestaña "Explorador" es una herramienta de depuración para usted. Le permite confirmar que la conexión está activa y ver una lista de todas las tablas en su base de datos externa, así como previsualizar las primeras 100 filas de cualquier tabla.', 'mi-conexion-externa' ) ); ?></p>
			</div>

			<style>
				.mce-help-section { background: #ffffff; border: 1px solid #e0e0e0; padding: 15px 25px; margin-top: 20px; border-radius: 4px; }
				.mce-help-section h2 { margin-top: 0.5em; border-bottom: 1px solid #eee; padding-bottom: 8px; }
				.mce-help-section h3 { margin-top: 1.5em; }
				.mce-help-section h4 { margin-top: 1.2em; }
				.mce-help-section pre { background: #f1f1f1; padding: 15px; border-radius: 4px; border: 1px solid #ddd; }
				.mce-help-section ul { list-style: disc; padding-left: 20px; }
			</style>
		</div>
		<?php
	}
}