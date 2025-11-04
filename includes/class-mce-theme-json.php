<?php
/**
 * Lógica de Integración con Theme.json.
 *
 * @package MiConexionExterna
 */

// Regla 1: Mejor Práctica de Seguridad. Evitar acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase MCE_Theme_Json
 *
 * Inyecta los estilos por defecto del plugin en el theme.json
 * del tema activo. Esto evita la "guerra de CSS".
 */
class MCE_Theme_Json {

	/**
	 * Constructor. Se engancha al hook de theme.json.
	 */
	public function __construct() {
		// Este es el hook moderno para añadir datos al theme.json
		add_filter( 'wp_theme_json_data_theme', array( $this, 'inject_plugin_styles' ) );
	}

	/**
	 * El callback que inyecta nuestros estilos.
	 *
	 * @param WP_Theme_JSON_Data $theme_json El objeto de datos del tema.
	 * @return WP_Theme_JSON_Data El objeto modificado.
	 */
	public function inject_plugin_styles( $theme_json ) {
		// 1. Obtenemos nuestros estilos como un array
		$new_styles_array = $this->get_plugin_styles_as_array();
		
		// 2. *** ¡LÍNEA CORREGIDA! ***
		// Usamos el método 'update_with()' en lugar de 'merge()'
		$theme_json->update_with( $new_styles_array );

		// 3. Devolvemos el objeto modificado
		return $theme_json;
	}

	/**
	 * Define todos nuestros estilos por defecto en la
	 * estructura de array que 'theme.json' entiende.
	 *
	 * @return array
	 */
	private function get_plugin_styles_as_array() {
		
		// Esta es la cadena de CSS que define nuestros estilos por defecto.
		$plugin_css = "
			/* --- Estilos Base CoreAura Conexión (Inyectados) --- */
			.mce-productos-grid {
				display: grid;
				gap: 25px;
				width: 100%;
				margin-top: 20px;
				font-family: var(--wp--preset--font-family--body, sans-serif);
			}
			.mce-producto-card {
				background: var(--wp--preset--color--base, #ffffff);
				border: 1px solid var(--wp--preset--color--contrast-2, #e0e0e0);
				border-radius: 8px;
				padding: 20px;
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
				transition: all 0.3s ease;
				display: flex;
				flex-direction: column;
			}
			.mce-producto-card:hover {
				transform: translateY(-5px);
				box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
			}
			.mce-producto-card .mce-card-title {
				font-family: var(--wp--preset--font-family--heading, sans-serif);
				font-size: var(--wp--preset--font-size--large, 1.25rem);
				color: var(--wp--preset--color--foreground, #111);
				font-weight: 700;
				margin-top: 0;
				margin-bottom: 15px;
			}
			.mce-producto-card .mce-card-meta {
				color: var(--wp--preset--color--contrast-3, #333);
				font-size: var(--wp--preset--font-size--small, 0.9rem);
				margin-top: auto;
			}
			.mce-card-item {
				display: flex;
				justify-content: space-between;
				padding: 8px 0;
				border-bottom: 1px solid var(--wp--preset--color--contrast-2, #f0f0f0);
			}
			.mce-card-item:last-child {
				border-bottom: none;
			}
			.mce-card-item strong {
				color: var(--wp--preset--color--foreground, #111);
				font-weight: 600;
				margin-right: 10px;
				text-transform: capitalize;
			}
			.mce-card-item span {
				text-align: right;
				font-weight: 500;
				color: var(--wp--preset--color--contrast-3, #333);
			}
			.mce-card-item.mce-item-no-label {
				justify-content: flex-start;
				font-size: var(--wp--preset--font-size--medium, 1rem);
				font-weight: 500;
				color: var(--wp--preset--color--foreground, #111);
			}
			.mce-pdf-link {
				display: inline-block;
				background: var(--wp--preset--color--contrast, #f4f4f4);
				padding: 3px 8px;
				border-radius: 4px;
				text-decoration: none;
				font-weight: 500;
				color: var(--wp--preset--color--primary, #0051d2);
				transition: background 0.2s ease;
			}
			.mce-pdf-link:hover {
				background: var(--wp--preset--color--contrast-2, #e0e0e0);
				color: var(--wp--preset--color--foreground, #000);
			}
			.mce-pagination {
				margin-top: 30px;
				text-align: center;
				width: 100%;
			}
			.mce-pagination .page-numbers {
				display: inline-block;
				padding: 8px 14px;
				margin: 0 3px;
				text-decoration: none;
				border: 1px solid var(--wp--preset--color--contrast-2, #ddd);
				border-radius: 4px;
				background: var(--wp--preset--color--base, #fff);
				color: var(--wp--preset--color--primary, #0051d2);
				transition: background 0.2s ease, color 0.2s ease;
			}
			.mce-pagination .page-numbers:hover {
				background: var(--wp--preset--color--contrast, #f4f4f4);
				color: var(--wp--preset--color--primary, #0051d2);
			}
			.mce-pagination .page-numbers.current {
				background: var(--wp--preset--color--primary, #0051d2);
				color: var(--wp--preset--color--base, #fff);
				border-color: var(--wp--preset--color--primary, #0051d2);
				font-weight: 700;
			}
			.mce-pagination .page-numbers.dots {
				border: none;
				background: none;
			}
		";
		
		// Devolvemos el array en la estructura de theme.json
		return [
			'version' => 3,
			'styles'  => [
				'css' => $plugin_css,
			],
		];
	}
}