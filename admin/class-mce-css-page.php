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
	 *
	 * @param string $input El CSS crudo del textarea.
	 * @return string El CSS sanitizado.
	 */
	public function sanitize_css( $input ) {
		return wp_strip_all_tags( $input );
	}

	/**
	 * Muestra la descripción de la sección.
	 */
	public function print_section_info() {
		echo '<p>' . esc_html( __( 'Añade tus propias reglas de CSS aquí para sobreescribir los estilos por defecto del shortcode.', 'mi-conexion-externa' ) ) . '</p>';
		echo '<p>' . esc_html( __( 'Para usar la plantilla, descomenta (borra /* y */) las reglas que quieras modificar.', 'mi-conexion-externa' ) ) . '</p>';
		echo '<p>' . esc_html( __( 'Usa el botón "Restablecer" para volver a la plantilla por defecto (¡debes guardar los cambios después!).', 'mi-conexion-externa' ) ) . '</p>';
	}

	/**
	 * Obtiene la plantilla de CSS por defecto (nuestro CSS "inteligente").
	 *
	 * @return string
	 */
	private function get_default_css_template() {
		// Usamos un Heredoc para definir el string de la plantilla
		$default_css = <<<CSS
/* --- PLANTILLA DE ESTILOS ADAPTABLES --- */
/* Para usar, descomenta (borra /* y */) las reglas que quieras cambiar. */

/* --- Cuadrícula Principal (Grid) --- */
/*
.mce-productos-grid {
	gap: 25px;
	font-family: var(--wp--preset--font-family--body, sans-serif);
}
*/

/* --- Tarjeta Individual --- */
/*
.mce-producto-card {
	background: var(--wp--preset--color--base, #ffffff);
	border: 1px solid var(--wp--preset--color--contrast-2, #e0e0e0);
	border-radius: 8px;
}
*/

/* --- Título de la Tarjeta (definido en "llave_titulo") --- */
/*
.mce-producto-card .mce-card-title {
	font-family: var(--wp--preset--font-family--heading, sans-serif);
	font-size: var(--wp--preset--font-size--large, 1.25rem);
	color: var(--wp--preset--color--foreground, #111);
	font-weight: 700;
}
*/

/* --- Contenedor de Meta-Datos --- */
/*
.mce-producto-card .mce-card-meta {
	color: var(--wp--preset--color--contrast-3, #333);
	font-size: var(--wp--preset--font-size--small, 0.9rem);
}
*/

/* --- Cada Fila de Dato (ej. Sku: 123) --- */
/*
.mce-card-item {
	justify-content: space-between;
	border-bottom: 1px solid var(--wp--preset--color--contrast-2, #f0f0f0);
}
*/

/* --- Etiqueta de la Fila (ej. "Sku:") --- */
/*
.mce-card-item strong {
	color: var(--wp--preset--color--foreground, #111);
	font-weight: 600;
}
*/

/* --- Valor de la Fila (ej. "123") --- */
/*
.mce-card-item span {
	text-align: right;
	font-weight: 500;
	color: var(--wp--preset--color--contrast-3, #333);
}
*/

/* --- Fila SIN Etiqueta (de "ocultar_etiquetas") --- */
/*
.mce-card-item.mce-item-no-label {
	font-size: var(--wp--preset--font-size--medium, 1rem);
	font-weight: 500;
	color: var(--wp--preset--color--foreground, #111);
}
*/

/* --- Enlace PDF --- */
/*
.mce-pdf-link {
	background: var(--wp--preset--color--contrast, #f4f4f4);
	color: var(--wp--preset--color--primary, #0051d2);
}
.mce-pdf-link:hover {
	background: var(--wp--preset--color--contrast-2, #e0e0e0);
}
*/
CSS;
		// Quitar la indentación inicial del Heredoc
		return trim( preg_replace( '/^[\t ]*/m', '', $default_css ) );
	}

	/**
	 * Renderiza el campo <textarea> y lo pre-llena con la plantilla.
	 */
	public function render_css_field() {
		$saved_css   = get_option( 'mce_custom_css' );
		$default_css = $this->get_default_css_template();
		$css_to_show = ! empty( $saved_css ) ? $saved_css : $default_css;

		// Imprimir el textarea principal
		printf(
			'<textarea name="mce_custom_css" id="mce_custom_css" class="large-text" rows="25" style="font-family: monospace; width: 100%%; white-space: pre;">%s</textarea>',
			esc_textarea( $css_to_show )
		);

		// Imprimir la plantilla oculta (para el botón de Restablecer)
		printf(
			'<textarea id="mce-default-css-template" style="display:none;">%s</textarea>',
			esc_textarea( $default_css )
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
			
			<button type="button" id="mce-reset-css" class="button button-secondary">
				<?php echo esc_html( __( 'Restablecer a la Plantilla por Defecto', 'mi-conexion-externa' ) ); ?>
			</button>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#mce-reset-css').on('click', function(e) {
						e.preventDefault();
						
						if ( ! confirm('<?php echo esc_js( __( '¿Estás seguro de que quieres borrar tus estilos personalizados y volver a la plantilla por defecto? Perderás tus cambios no guardados.', 'mi-conexion-externa' ) ); ?>') ) {
							return;
						}

						var defaultTemplate = $('#mce-default-css-template').val();
						$('#mce_custom_css').val(defaultTemplate);
						
						alert('<?php echo esc_js( __( 'Estilos restablecidos. Haz clic en "Guardar Cambios" para confirmar.', 'mi-conexion-externa' ) ); ?>');
					});
				});
			</script>
		</div>
		<?php
	}
}