<?php
/**
 * Lógica para la Página de "Ayuda" del Administrador.
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Help_Page
 */
class MCE_Help_Page {

	/**
	 * Renderiza el contenido HTML de la página "Ayuda / Guía de Uso".
	 *
	 * *** ¡ACTUALIZADO! ***
	 * Se documenta el método de estilos por atributos.
	 */
	public function render_page_content() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Guía de Uso - CoreAura Conexión', 'mi-conexion-externa' ) ); ?></h1>
			<p><?php echo esc_html( __( '¡Bienvenido! Este plugin le permite conectarse a una base de datos externa y mostrar sus datos dentro de WordPress.', 'mi-conexion-externa' ) ); ?></p>

			<div class="mce-help-section">
				<h2><?php echo esc_html( __( 'Paso 1: Configurar la Conexión', 'mi-conexion-externa' ) ); ?></h2>
				<ol>
					<li><?php echo esc_html( __( 'Vaya a la pestaña "Ajustes".', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Rellene las 5 credenciales de su base de datos (IP/Host, Puerto, Nombre de BBDD, Usuario y Contraseña).', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Haga clic en "Guardar Cambios".', 'mi-conexion-externa' ) ); ?></li>
					<li><?php echo esc_html( __( 'Use el botón "Probar Conexión a BBDD" para confirmar que todo es correcto. Debe ver un mensaje de "¡Éxito!".', 'mi-conexion-externa' ) ); ?></li>
				</ol>
				<p><strong><?php echo esc_html( __( '¡No puede continuar hasta que la conexión sea exitosa!', 'mi-conexion-externa' ) ); ?></strong></p>
			</div>

			<div class="mce-help-section">
				<h2><?php echo esc_html( __( 'Paso 2: Mostrar Datos (Opción A - Shortcode)', 'mi-conexion-externa' ) ); ?></h2>
				<p><?php echo esc_html( __( 'Este es el método principal. Funciona en cualquier página o en el widget "Shortcode" de Elementor.', 'mi-conexion-externa' ) ); ?></p>
				
				<h4><?php echo esc_html( __( 'Uso Básico', 'mi-conexion-externa' ) ); ?></h4>
				<pre><code>[mce_mostrar_tabla tabla="mce_productos"]</code></pre>

				<h4><?php echo esc_html( __( 'Uso Avanzado (Atributos)', 'mi-conexion-externa' ) ); ?></h4>
				<p><?php echo esc_html( __( 'Puede combinar los siguientes atributos para un control total:', 'mi-conexion-externa' ) ); ?></p>
				<ul>
					<li><strong>tabla</strong>: <em>(Obligatorio)</em> <?php echo esc_html( __( 'El nombre de la tabla que desea consultar.', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>paginacion</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Número de filas por página.', 'mi-conexion-externa' ) ); ?> (Defecto: 10)</li>
					<li><strong>columnas</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Número de columnas de la cuadrícula (1-6).', 'mi-conexion-externa' ) ); ?> (Defecto: 3)</li>
					<li><strong>columnas_mostrar</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Lista (separada por comas) de las únicas columnas que desea mostrar.', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>llave_titulo</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'La columna que actuará como título principal (sin etiqueta).', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>ocultar_etiquetas</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Lista (separada por comas) de columnas que no mostrarán su etiqueta (solo el valor).', 'mi-conexion-externa' ) ); ?></li>
				</ul>
				
				<hr>
				<h4><?php echo esc_html( __( '¡NUEVO! Cómo Estilizar (Atributos de Estilo)', 'mi-conexion-externa' ) ); ?></h4>
				<p><?php echo esc_html( __( 'Para garantizar que sus estilos predominen sobre el tema, añada estos atributos. Siempre ganarán.', 'mi-conexion-externa' ) ); ?></p>
				<ul>
					<li><strong>color_titulo</strong>: <?php echo esc_html( __( 'Un color CSS (ej. "red", "#FF0000").', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>tamano_titulo</strong>: <?php echo esc_html( __( 'Un tamaño de fuente CSS (ej. "20px", "1.5rem").', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>color_etiqueta</strong>: <?php echo esc_html( __( 'Color para las etiquetas (ej. "sku:", "precio:").', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>color_valor</strong>: <?php echo esc_html( __( 'Color para los valores (ej. "LP15-001").', 'mi-conexion-externa' ) ); ?></li>
					<li><strong>color_enlace</strong>: <?php echo esc_html( __( 'Color para los enlaces PDF.', 'mi-conexion-externa' ) ); ?></li>
				</ul>
				
				<h4><?php echo esc_html( __( 'Ejemplo Completo', 'mi-conexion-externa' ) ); ?></h4>
				<pre><code>[mce_mostrar_tabla tabla="mce_productos" paginacion="4" columnas="4" llave_titulo="nombre" color_titulo="red" columnas_mostrar="nombre,sku"]</code></pre>
				
				<hr>

				<h3><?php echo esc_html( __( 'Opción B: Integración con Elementor Pro', 'mi-conexion-externa' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Esta opción está "latente" y se activará automáticamente si instala Elementor Pro.', 'mi-conexion-externa' ) ); ?></p>
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