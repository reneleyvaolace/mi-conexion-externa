# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [2.1.0] - 2025-12-09

### Añadido
- 📱 **Sistema de Diseño Completamente Responsive**: Las tarjetas ahora se adaptan automáticamente a todos los tamaños de pantalla
- 🎯 **Grid Adaptativo con CSS Grid Auto-Fit**: Utiliza tecnología moderna de CSS Grid para distribución inteligente
- 📐 **Clases CSS Personalizadas**: Nuevas clases `.mce-grid-max-1` hasta `.mce-grid-max-6` para control preciso
- 📖 **Documentación Responsive**: Nuevo archivo `RESPONSIVE-DESIGN.md` con guía completa de uso

### Cambiado
- 🔄 **Parámetro `columnas` Redefinido**: Ahora define el número MÁXIMO de columnas en pantallas grandes
- 📱 **Breakpoints Optimizados**: Móviles (<768px) muestran 1 columna, tablets adaptan según espacio
- 🎨 **Grid CSS Mejorado**: Cambio de `repeat(N, 1fr)` a `repeat(auto-fit, minmax(280px, 1fr))`

### Mejorado
- ✨ **Experiencia Móvil**: Tarjetas ahora son completamente legibles en smartphones
- 🖥️ **Experiencia Desktop**: Respeta el número de columnas especificado como máximo
- 📊 **Distribución Inteligente**: Las tarjetas se distribuyen automáticamente según espacio disponible
- 🎯 **Compatibilidad**: Funciona en todos los navegadores modernos sin JavaScript adicional

### Técnico
- Implementación de CSS Grid con `auto-fit` y `minmax()`
- Media queries optimizadas para breakpoints estándar (768px)
- Ancho mínimo de tarjetas establecido en 280px
- Clases CSS dinámicas generadas en PHP
- Retrocompatibilidad total con shortcodes existentes

---

## [2.0.0] - 2025-11-19

### Añadido
- 🛠️ **Herramientas de Debug Avanzadas**: Nueva sección completa en la página de configuración de caché
- 🔍 **Prueba de Conexión**: Botón para verificar la conectividad con la base de datos externa
- 📋 **Listado de Tablas**: Herramienta para ver todas las tablas disponibles en la DB externa
- ⚡ **Ejecutor de Consultas**: Permite ejecutar consultas SELECT personalizadas de forma segura
- 📊 **Resultados en Tiempo Real**: Feedback inmediato de todas las operaciones de debug

### Corregido
- 🧭 **Menú de Caché**: Solucionado problema donde el submenú "Caché" no aparecía en el admin
- 🔧 **Inicialización de Componentes**: Corregida la carga secuencial de clases para asegurar que todos los menús se registren correctamente

### Mejorado
- 🎯 **Experiencia de Usuario**: Mejor navegación y acceso a herramientas de diagnóstico
- 📈 **Mantenibilidad**: Código más organizado y robusto para futuras expansiones

---

## [1.2.0] - 2025-11-11

### Añadido
- 🔥 **Sistema de búsqueda y filtros AJAX en tiempo real** (Funcionalidad completamente nueva)
- 🔍 **Búsqueda universal**: Busca simultáneamente en todos los campos de la base de datos
- 🎛️ **Filtros dinámicos**: Menús desplegables automáticos con valores únicos de cada columna
- ⚡ **Resultados instantáneos**: Sin recargar la página, en tiempo real
- 🎨 **Formato de tarjetas consistente**: Los resultados de búsqueda se muestran en el mismo diseño atractivo
- 🧹 **Botón limpiar**: Restaura rápidamente la vista completa
- 🎛️ **Control de visibilidad del buscador**: Nuevo atributo `mostrar_buscador` para ocultar/mostrar funcionalidad de búsqueda
- 🔄 **Restauración exacta**: El botón limpiar ahora restaura la vista y configuración original exacta
- 🎨 **Panel de Estilo Completamente Mejorado**: Ahora incluye personalización completa del sistema de búsqueda (búsqueda, filtros, botones, estados hover, loading, errores)
- 🛠️ **Sistema de pruebas interactivo** para validar funcionalidad AJAX
- ⚖️ **Licencias y Cumplimiento Legal**: Documentación completa de licenciamiento dual GPL v2+ + Comercial con archivos LICENSE.txt y LICENSE-INFO.md
- 📋 **Documentación actualizada** con nuevas características

### Corregido
- ✅ **MySQL Strict Mode**: Solucionados errores "No index used in query" que causaban fatal errors
- ✅ **Compatibilidad de métodos**: Agregados métodos faltantes `get_tables()` y `escape_string()`
- ✅ **Error PHP Shortcode**: Corregido `current_time()` sin parámetro
- ✅ **Error SQL Double WHERE**: Solucionado problema de sintaxis en consultas de búsqueda
- ✅ **Robustez de la base de datos**: Manejo mejorado de errores y conexiones

### Mejorado
- 🔧 **Manejador de base de datos**: Versión mejorada con compatibilidad MySQL strict mode
- 🛡️ **Manejo de errores**: Sistema comprehensivo de recuperación de errores
- 📊 **Sistema de paginación**: Más robusto y eficiente
- 🎯 **Experiencia de usuario**: Interfaz más fluida y profesional
- 📱 **Responsive design**: Mejor adaptación en dispositivos móviles

### Técnico
- Implementación completa de WordPress AJAX con `wp_ajax_` y `wp_ajax_nopriv_`
- JavaScript infrastructure utilizando jQuery existente
- CSS styling completo para todos los componentes AJAX
- State management para búsqueda/filtros entre requests
- Comprehensive error handling y logging

---

## [1.0.0] - 2025-11-08

### Añadido
- Conexión segura a bases de datos externas MySQL/MariaDB
- Explorador visual de tablas y registros en el admin
- Shortcode universal `[mce_mostrar_tabla]` con múltiples atributos personalizables
- Paginación AJAX sin recarga de página
- Panel de estilos personalizado en el admin
- Soporte para personalización de colores, tamaños y diseño visual
- Integración nativa con Gutenberg y Elementor Free
- Sistema de prueba de conexión con feedback inmediato
- Sanitización y validación completa de datos
- Estilos modernos y profesionales en todas las secciones del admin
- Soporte para enlaces PDF en campos
- Internacionalización completa (preparado para traducciones)
- Documentación completa en la sección de Ayuda
- Diseño responsive en frontend y admin

### Seguridad
- Implementación de nonces en todas las peticiones AJAX
- Sanitización exhaustiva de entradas de usuario
- Verificación de capacidades y permisos
- Protección contra SQL injection
- Validación de tipos de datos

### Rendimiento
- Consultas optimizadas con paginación
- Carga condicional de assets (solo donde se necesitan)
- Delegación de eventos para mejor performance en AJAX
- CSS y JS minimizados para producción

---

## Formato de Versiones

### [X.Y.Z]
- **X (Mayor)**: Cambios incompatibles con versiones anteriores
- **Y (Menor)**: Nueva funcionalidad compatible con versiones anteriores
- **Z (Parche)**: Corrección de bugs compatible con versiones anteriores

### Tipos de cambios
- **Añadido**: para nuevas funcionalidades
- **Cambiado**: para cambios en funcionalidad existente
- **Obsoleto**: para funcionalidades que serán eliminadas
- **Eliminado**: para funcionalidades eliminadas
- **Corregido**: para corrección de bugs
- **Seguridad**: en caso de vulnerabilidades
