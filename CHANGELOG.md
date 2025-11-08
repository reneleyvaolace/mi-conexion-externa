# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

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
