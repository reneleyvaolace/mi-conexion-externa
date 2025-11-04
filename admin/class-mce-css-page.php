<?php
/**
 * Lógica para la Página de "Estilos CSS" del Administrador.
 *
 * @package MiConexionExterna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_CSS_Page
 *
 * Registra y muestra la página para CSS personalizado.
 */
class MCE_CSS_Page {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registra el ajuste en la base de datos (wp_options).
	 */
	public function register_settings() {
		register_setting(
			'mce_css_group', 
			'mce_custom_css',
			array( $this, 'sanitize_css' ) // Usar la nueva función de sanitización
		);

		add_settings_section(
			'mce_css_section',
			__( 'Editor de CSS Personalizado', 'mi-conexion-externa' ),
			array( $this, 'print_section_info' ),
			'mce-css-page'
		);

		add_settings_field(
			'mce_custom_css_field',
			__( 'Tu CSS Personalizado', 'mi-conexion-externa' ),
			array( $this, 'render_css_field' ),
			'mce-css-page',
			'mce_css_section'
		);
	}

	/**
	 * *** ¡FUNCIÓN DE SANITIZACIÓN CORREGIDA! ***
	 *
	 * Esta función limpia el CSS de forma segura, eliminando
	 * únicamente etiquetas <script> maliciosas, pero
	 * preservando intacta toda la sintaxis de CSS (., #, {, }, :, !important, etc.).
	 *
	 * @param string $input El CSS crudo del textarea.
	 * @return string El CSS sanitizado.
	 */
	public function sanitize_css( $input ) {
		// Eliminar cualquier etiqueta <script> y su contenido.
		// Esta es una forma segura y no destructiva para el CSS.
		$sanitized_input = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $input );
		return $sanitized_input;
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
	 * Obtiene la plantilla de CSS por defecto.
	 */
	private function get_default_css_template() {
		$default_css = <<<CSS
/* --- PLANTILLA DE ESTILOS ADAPTABLES --- */
/* Para usar, descomenta (borra /* y */) las reglas que quieras cambiar. */

/* --- Título de la Tarjeta (definido en "llave_titulo") --- */
/*
.mce-producto-card .mce-card-title {
	font-family: var(--wp--preset--font-family--heading, sans-serif);
	font-size: var(--wp--preset--font-size--large, 1.25rem);
	color: var(--wp--preset--color--foreground, #111);
	font-weight: 700;
}
*/
CSS;
		return trim( preg_replace( '/^[\t ]*/m', '', $default_css ) );
	}

	/**
	 * Renderiza el campo <textarea> y el botón de reset.
	 */
	public function render_css_field() {
		$saved_css   = get_option( 'mce_custom_css' );
		$default_css = $this->get_default_css_template();
		$css_to_show = ! empty( $saved_css ) ? $saved_css : $default_css;

		printf(
			'<textarea name="mce_custom_css" id="mce_custom_css" class="large-text" rows="25" style="font-family: monospace; width: 100%%; white-space: pre;">%s</textarea>',
			esc_textarea( $css_to_show )
		);

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