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

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'mce-help' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'mce-admin-global',
            plugins_url( 'admin/css/mce-admin-global.css', dirname( dirname( __FILE__ ) ) . '/mi-conexion-externa.php' ),
            array(),
            '1.1.5'
        );
    }

    /**
     * Renderiza el contenido HTML de la página "Ayuda / Guía de Uso".
     */
    public function render_page_content() {
        ?>
        <div class="wrap mce-admin-page">
            <h1><?php echo esc_html( __( 'Guía de Uso - CoreAura Conexión', 'mi-conexion-externa' ) ); ?></h1>
            <p><?php echo esc_html( __( '¡Bienvenido! Este plugin le permite conectarse a una base de datos externa y mostrar sus datos dentro de WordPress.', 'mi-conexion-externa' ) ); ?></p>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( '¿Qué hace este plugin?', 'mi-conexion-externa' ) ); ?></h2>
                <ul>
                    <li><?php echo esc_html( __( 'Conexión a bases de datos externas (MySQL/MariaDB).', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Explorador visual de tablas y registros.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Shortcode universal para mostrar grids/tablas dinámicas en cualquier página/post/constructor de páginas.', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong><?php echo esc_html( __( '🔍 Búsqueda y Filtros AJAX en tiempo real (¡NUEVO v1.2.0!)', 'mi-conexion-externa' ) ); ?></strong></li>
                    <li><?php echo esc_html( __( 'Paginación AJAX, sin recarga completa de la web.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Panel de estilos personalizado en el admin ("Estilo") para colores, tamaños y diseño visual de los cards que muestran la información.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Integración nativa con Gutenberg y Elementor Free.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Paso 1: Configurar la Conexión', 'mi-conexion-externa' ) ); ?></h2>
                <ol>
                    <li><?php echo esc_html( __( 'Vaya a la pestaña "Ajustes".', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Rellene las 5 credenciales de su base de datos (IP/Host, Puerto, Nombre de BBDD, Usuario y Contraseña).', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Haga clic en "Guardar Cambios".', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Use el botón "Probar Conexión a BBDD" para confirmar que todo es correcto. Debe ver un mensaje de "¡Éxito!".', 'mi-conexion-externa' ) ); ?></li>
                </ol>
                <p class="mce-notice mce-notice-warning"><strong><?php echo esc_html( __( '¡No puede continuar hasta que la conexión sea exitosa!', 'mi-conexion-externa' ) ); ?></strong></p>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Paso 2: Mostrar Datos (Shortcode)', 'mi-conexion-externa' ) ); ?></h2>
                <p><?php echo esc_html( __( 'Este es el método principal. Funciona en cualquier página o en el widget "Shortcode" de Elementor.', 'mi-conexion-externa' ) ); ?></p>
                
                <h4><?php echo esc_html( __( 'Uso Básico', 'mi-conexion-externa' ) ); ?></h4>
                <pre><code>[mce_mostrar_tabla tabla="mi_tabla"]</code></pre>

                <h4><?php echo esc_html( __( 'Uso Avanzado (Atributos)', 'mi-conexion-externa' ) ); ?></h4>
                <p><?php echo esc_html( __( 'Puede combinar los siguientes atributos para un control total:', 'mi-conexion-externa' ) ); ?></p>
                <ul>
                    <li><strong>tabla</strong>: <em>(Obligatorio)</em> <?php echo esc_html( __( 'El nombre de la tabla que desea consultar.', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>paginacion</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Número de registros por página.', 'mi-conexion-externa' ) ); ?> (<?php echo esc_html__( 'Defecto: 10', 'mi-conexion-externa' ) ?>)</li>
                    <li><strong>columnas</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Número de columnas de la cuadrícula (1-6).', 'mi-conexion-externa' ) ); ?> (<?php echo esc_html__( 'Defecto: 3', 'mi-conexion-externa' ) ?>)</li>
                    <li><strong>columnas_mostrar</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Lista (separada por comas) de las únicas columnas que desea mostrar.', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>llave_titulo</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'La columna que actuará como título principal (sin etiqueta).', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>ocultar_etiquetas</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Lista (separada por comas) de columnas que no mostrarán su etiqueta (solo el valor).', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>mostrar_buscador</strong>: <em>(Opcional)</em> <?php echo esc_html( __( 'Mostrar u ocultar el sistema de búsqueda y filtros (true/false).', 'mi-conexion-externa' ) ); ?> (<?php echo esc_html__( 'Defecto: true', 'mi-conexion-externa' ) ?>)</li>
                </ul>
                
                <hr>
                <h4><?php echo esc_html( __( 'Atributos de Estilo (Personalización Visual)', 'mi-conexion-externa' ) ); ?></h4>
                <p><?php echo esc_html( __( 'Para garantizar que sus estilos predominen sobre el tema, añada estos atributos. Siempre ganarán.', 'mi-conexion-externa' ) ); ?></p>
                <ul>
                    <li><strong>color_titulo</strong>: <?php echo esc_html( __( 'Un color CSS (ej. "red", "#FF0000").', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>tamano_titulo</strong>: <?php echo esc_html( __( 'Un tamaño de fuente CSS (ej. "20px", "1.5rem").', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>color_etiqueta</strong>: <?php echo esc_html( __( 'Color para las etiquetas de campos (ej. "id:", "nombre:").', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>color_valor</strong>: <?php echo esc_html( __( 'Color para los valores de los campos.', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong>color_enlace</strong>: <?php echo esc_html( __( 'Color para los enlaces PDF.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
                
                <h4><?php echo esc_html( __( 'Ejemplos Completos', 'mi-conexion-externa' ) ); ?></h4>
                <p><?php echo esc_html( __( 'Con búsqueda habilitada:', 'mi-conexion-externa' ) ); ?></p>
                <pre><code>[mce_mostrar_tabla tabla="empleados" paginacion="8" columnas="2" llave_titulo="nombre" color_titulo="#1976d2" columnas_mostrar="nombre,cargo,email"]</code></pre>
                
                <p><?php echo esc_html( __( 'Sin búsqueda (solo visualización):', 'mi-conexion-externa' ) ); ?></p>
                <pre><code>[mce_mostrar_tabla tabla="empleados" paginacion="8" columnas="2" llave_titulo="nombre" mostrar_buscador="false"]</code></pre>
                
                <p class="mce-notice"><?php echo esc_html( __( 'También puede configurar estilos globales en el panel "Estilo".', 'mi-conexion-externa' ) ); ?></p>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Búsqueda y Filtros AJAX (¡NUEVO!)', 'mi-conexion-externa' ) ); ?></h2>
                <p><?php echo esc_html( __( 'La versión 1.2.0 incluye un potente sistema de búsqueda en tiempo real:', 'mi-conexion-externa' ) ); ?></p>
                <ul>
                    <li><?php echo esc_html( __( '🔍 Búsqueda universal: Busca en todos los campos de la tabla simultáneamente.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( '🎛️ Filtros dinámicos: Menús desplegables con valores únicos de cada columna.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( '⚡ Resultados instantáneos: Sin recargar la página, en tiempo real.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( '🎨 Formato consistente: Los resultados se muestran en el mismo diseño de tarjetas atractivo.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( '🧹 Botón limpiar: Restaura rápidamente la vista completa.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
                <p><?php echo esc_html( __( 'El buscador aparece automáticamente en cada shortcode y permite encontrar información específica al instante.', 'mi-conexion-externa' ) ); ?></p>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Paginación AJAX', 'mi-conexion-externa' ) ); ?></h2>
                <ul>
                    <li><?php echo esc_html( __( 'Navegue por los registros al instante, sin recargar la página.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Botones "Anterior" y "Siguiente" activados conforme la cantidad total de registros.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Panel de Estilo Completo', 'mi-conexion-externa' ) ); ?></h2>
                <ul>
                    <li><strong><?php echo esc_html( __( 'Cards y Contenido:', 'mi-conexion-externa' ) ); ?></strong> <?php echo esc_html( __( 'Personaliza color, tamaño, fondo y sombra de cada tarjeta de información.', 'mi-conexion-externa' ) ); ?></li>
                    <li><strong><?php echo esc_html( __( 'Sistema de Búsqueda (v1.2.0):', 'mi-conexion-externa' ) ); ?></strong> <?php echo esc_html( __( 'Personaliza completamente: fondo del buscador, campos de entrada, dropdowns de filtros, botones de búsqueda, estados hover y texto placeholder.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Sección "Estilo" en el admin para cambios globales (sin editar código).', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Los estilos se aplican a cualquier tipo de datos mostrados: productos, empleados, eventos, registros, etc.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Funciona junto con atributos visuales del shortcode para personalizaciones específicas.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Campos opcionales: Deja los que no necesites en blanco para usar valores por defecto.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Recomendaciones técnicas', 'mi-conexion-externa' ) ); ?></h2>
                <ul>
                    <li><?php echo esc_html( __( 'Para máxima velocidad, desactive cache/minificadores en local.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Solo cargue una vez la clase del panel de estilos para evitar menús duplicados.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Los cambios visuales se ven reflejados en todos los grids del frontend.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Plugin preparado para internacionalización y traducción.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Puede mostrar cualquier tipo de información: catálogos, inventarios, listados, directorios, etc.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( 'Cambios recientes y versión actual', 'mi-conexion-externa' ) ); ?></h2>
                <ul>
                    <li><strong><?php echo esc_html( __( 'Versión actual: v1.2.0', 'mi-conexion-externa' ) ); ?></strong></li>
                    <li><strong><?php echo esc_html( __( '🔥 NUEVO: Sistema de búsqueda y filtros AJAX en tiempo real', 'mi-conexion-externa' ) ); ?></strong></li>
                    <li><strong><?php echo esc_html( __( '✨ Búsqueda universal en todos los campos de la base de datos', 'mi-conexion-externa' ) ); ?></strong></li>
                    <li><strong><?php echo esc_html( __( '🎛️ Filtros dinámicos con menús desplegables automáticos', 'mi-conexion-externa' ) ); ?></strong></li>
                    <li><strong><?php echo esc_html( __( '🎨 Resultados de búsqueda en formato de tarjetas consistente', 'mi-conexion-externa' ) ); ?></strong></li>
                    <li><?php echo esc_html( __( 'Corrección de errores MySQL strict mode y compatibilidad total.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Corrección de paginación AJAX y visualización fluida.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Panel de estilo integrado y sin duplicados.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Compatibilidad total con Elementor Free y Gutenberg.', 'mi-conexion-externa' ) ); ?></li>
                    <li><?php echo esc_html( __( 'Estilos modernos y profesionales aplicados a todas las secciones del admin.', 'mi-conexion-externa' ) ); ?></li>
                </ul>
            </div>

            <div class="mce-help-section">
                <h2><?php echo esc_html( __( '¿Necesitas soporte?', 'mi-conexion-externa' ) ); ?></h2>
                <p><?php echo esc_html( __( 'Contacta a CoreAura o revisa la documentación en línea para más ejemplos y opciones avanzadas.', 'mi-conexion-externa' ) ); ?></p>
            </div>
        </div>
        <?php
    }
}
