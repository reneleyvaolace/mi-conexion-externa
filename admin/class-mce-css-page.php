<?php
/**
 * Lógica para la Página de "Estilos CSS" del Administrador.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_CSS_Page
 *
 * Registra y muestra la página para CSS personalizado.
 */
class MCE_CSS_Page {

	/**
	 * Constructor.
	 * Registra los hooks para los campos.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registra el ajuste en la base de datos (wp_options).
	 */
	public function register_settings() {
		register_setting(
			'mce_css_group', // Nombre del grupo
			'mce_custom_css',  // Nombre de la opción en la BBDD
			array( $this, 'sanitize_css' ) // Función para "limpiar" el CSS
		);

		add_settings_section(
			'mce_css_section', // ID de la sección
			__( 'Editor de CSS Personalizado', 'mi-conexion-externa' ), // Título
			array( $this, 'print_section_info' ), // Callback de descripción
			'mce-css-page' // Slug de la página
		);

		add_settings_field(
			'mce_custom_css_field', // ID del campo
			__( 'Tu CSS Personalizado', 'mi-conexion-externa' ), // Título del campo
			array( $this, 'render_css_field' ), // Callback del campo (textarea)
			'mce-css-page', // Slug de la página
			'mce_css_section' // ID de la sección padre
		);
	}

	/**
	 * Sanitiza el CSS antes de guardarlo.
	 * Elimina etiquetas <script> y HTML peligroso, pero permite CSS.
	 *
	 * @param string $input El CSS crudo del textarea.
	 * @return string El CSS sanitizado.
	 */
	public function sanitize_css( $input ) {
		// wp_strip_all_tags es demasiado agresivo (rompería el CSS).
		// Usamos un equilibrio: removemos etiquetas <script> y <style> explícitamente.
		$input = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $input );
		$input = preg_replace( '#<style(.*?)>(.*?)</style>#is', '', $input );
		// Podemos añadir más reglas, pero para un admin, esto es un equilibrio razonable.
		return $input;
	}

	/**
	 * Muestra la descripción de la sección.
	 */
	public function print_section_info() {
		echo '<p>' . esc_html( __( 'Añade tus propias reglas de CSS aquí para sobreescribir los estilos por defecto del shortcode.', 'mi-conexion-externa' ) ) . '</p>';
		echo '<p>' . esc_html( __( 'Para usar la plantilla por defecto, descomenta (borra /* y */) las reglas que quieras modificar.', 'mi-conexion-externa' ) ) . '</p>';
	}

	/**
	 * Renderiza el campo <textarea> y lo pre-llena con la plantilla.
	 */
	public function render_css_field() {
		// Obtener el valor guardado
		$saved_css = get_option( 'mce_custom_css' );

		// Definir la plantilla por defecto (Heredoc)
		$default_css = '
/* --- PLANTILLA DE ESTILOS MCE --- */
/* Para usar, descomenta (borra /* y */) las reglas que quieras cambiar. */

/* --- Cuadrícula Principal (Grid) --- */
/*
.mce-productos-grid {
	gap: 25px;
}
*/

/* --- Tarjeta Individual --- */
/*
.mce-producto-card {
	background: #ffffff;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
*/

/* --- Título de la Tarjeta (definido en "llave_titulo") --- */
/*
.mce-producto-card .mce-card-title {
	font-size: 1.25rem;
	font-weight: 600;
	color: #111;
	margin-bottom: 15px;
}
*/

/* --- Contenedor de Meta-Datos --- */
/*
.mce-producto-card .mce-card-meta {
	font-size: 0.9rem;
	color: #333;
}
*/

/* --- Cada Fila de Dato (ej. Sku: 123) --- */
/*
.mce-card-item {
	display: flex;
	justify-content: space-between;
	padding: 8px 0;
	border-bottom: 1px solid #f0f0f0;
}
*/

/* --- Etiqueta de la Fila (ej. "Sku:") --- */
/*
.mce-card-item strong {
	color: #555;
	margin-right: 10px;
	text-transform: capitalize;
}
*/

/* --- Valor de la Fila (ej. "123") --- */
/*
.mce-card-item span {
	text-align: right;
	font-weight: 500;
}
*/

/* --- Fila SIN Etiqueta (de "ocultar_etiquetas") --- */
/*
.mce-card-item.mce-item-no-label {
	justify-content: flex-start;
	font-size: 1rem;
	font-weight: 500;
}
*/

/* --- Enlace PDF --- */
/*
.mce-pdf-link {
	background: #f4f4f4;
	color: #0051d2;
}
.mce-pdf-link:hover {
	background: #e0e0e0;
	color: #000;
}
*/
';
		
		// Si hay CSS guardado, lo usamos. Si no, usamos la plantilla.
		$css_to_show = ! empty( $saved_css ) ? $saved_css : $default_css;

		// Imprimir el textarea (Regla 1: esc_textarea)
		printf(
			'<textarea name="mce_custom_css" id="mce_custom_css" class="large-text" rows="25" style="font-family: monospace; width: 100%%;">%s</textarea>',
			esc_textarea( $css_to_show )
		);
	}

	/**
	 * Renderiza la página de admin (el <form>).
	 */
	public function render_page_content() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( __( 'Estilos CSS Personalizados', 'mi-conexion-externa' ) ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'mce_css_group' );
				do_settings_sections( 'mce-css-page' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}