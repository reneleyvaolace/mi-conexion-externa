=== CoreAura: Conexión Externa ===
Contributors: CoreAura
Tags: base de datos, integración externa, shortcode, elementor, productos, sincronización, ajax
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Un plugin seguro, robusto y modular desarrollado por CoreAura que conecta WordPress con bases de datos externas (MySQL/MariaDB), ideal para mostrar productos y datos externos, compatible con Elementor Pro y shortcodes dinámicos.

== Descripción ==

**CoreAura: Conexión Externa** permite a sitios WordPress acceder, consultar y mostrar información automáticamente desde bases de datos externas.  
Desarrollado profesionalmente por **CoreAura**, este plugin facilita la sincronización, visualización y administración avanzada de productos, reportes, inventarios y más.

- Explorador visual de tablas externas desde el admin
- Shortcode generador de grids responsivos y paginados
- Integración nativa con Elementor Pro (loop widgets, grid)
- Configuración y prueba de conexión vía AJAX seguro
- Seguridad avanzada con nonces, sanitización y roles admin
- Estilización y atributos personalizables para frontend

== Instalación y configuración ==

1. Descarga el ZIP/plugin y súbelo a `/wp-content/plugins/` (o clona desde GitHub).
2. Activa el plugin desde el Panel de Plugins en WordPress.
3. Dirígete a **CoreAura Conexión > Ajustes** y añade las credenciales de tu base de datos externa.
4. Guarda y prueba la conexión usando el botón incluido en ajustes (retorna mensajes claros).
5. Explora las tablas o utiliza el shortcode para mostrar información en cualquier contenido o widget.

== Uso de shortcode ==

- Ejemplo básico:
  `[mce_mostrar_tabla tabla="nombre_tabla"]`
- Ejemplo avanzado (con atributos de estilo y visualización):
  `[mce_mostrar_tabla tabla="productos" paginacion="5" columnas="4" llave_titulo="nombre" color_titulo="#2d9cdb"]`

**Atributos admitidos:**
- `tabla`: Nombre de la tabla externa.
- `paginacion`: Número de filas por página.
- `columnas`: Grid de 1-6 columnas.
- `columnas_mostrar`: Lista separada por comas.
- `llave_titulo`: Columna principal como título.
- `ocultar_etiquetas`: Oculta etiquetas visuales.
- `color_titulo`, `tamano_titulo`, `color_etiqueta`, `color_valor`, `color_enlace`: Estilos CSS rápido.

== Integración con Elementor Pro ==

- El plugin registra una nueva fuente de consulta (loop) lista para los widgets Pro.
- Si Elementor Pro está activo, aparecerán nuevos controles para elegir productos externos.
- Configura, personaliza y muestra cualquier tabla o reporte en plantillas visuales.

== Preguntas frecuencia (FAQ) ==

= ¿Pierdo mis credenciales si desactivo el plugin? =
No. Solo al eliminarlo completamente desde WordPress se borran las credenciales y configuración.

= ¿Qué ocurre si cambio de hosting o migración? =
Solo actualiza el campo de IP/Host en los ajustes. La base de datos externa debe ser accesible desde el nuevo servidor.

= ¿Puedo visualizar otras tablas externas que no sean productos? =
Sí, cualquier tabla que el usuario tenga permisos puede ser consultada y mostrada.

== Contribución y flujo GitHub ==

Este proyecto se administra vía GitHub por el equipo de CoreAura:
- Branch principal: `main`
- Branches de desarrollo/features: `develop`, `feature/mi-funcionalidad`
- Los pull requests deben ser revisados con descripción clara y referencia al issue correspondiente.
- Las releases públicas deben ir acompañadas de actualización de este `readme.txt` y el changelog.

== Changelog ==

= 1.1.5 =
* Integración AJAX y nonces mejorada.
* Estilos públicos listos para override por temas personalizados.
* Nueva documentación y secciones de ayuda mejoradas.

= 1.1.4 =
* Fixes menores internacionales y estilo.

== Licencia y Autores ==

Plugin desarrollado por **CoreAura** — [https://coreaura.com/](https://coreaura.com/)  
Distribuido bajo licencia GPLv2 o posterior.

== Créditos y contacto ==

- Soporte y contacto: soporte@coreaura.com
- Web principal: [https://coreaura.com/](https://coreaura.com/)
- Documentación extendida y tutoriales en el admin y en la web oficial.

== Seguridad y buenas prácticas ==

- Todas las acciones AJAX incluyen verificación de nonce y sanitización.
- Las opciones del plugin solo son accesibles por administradores o roles con `manage_options`.
- El archivo `uninstall.php` elimina configuraciones y credenciales solo al borrar el plugin (no al desactivarlo).

== Traducción e internacionalización ==

- Aceptamos contribuciones de traducción vía archivos `.pot` en `/languages/`.
- El plugin está listo para internacionalización desde el primer uso.

