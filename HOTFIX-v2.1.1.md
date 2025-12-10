# HOTFIX v2.1.1 - Error en Filtros AJAX

## 🐛 Problema Detectado

**Error:** "Error al obtener datos filtrados"

**Síntoma:** Al intentar filtrar información usando los filtros desplegables, el sistema mostraba un mensaje de error y no filtraba los datos.

**Causa Raíz:** El código asumía que todas las tablas tenían una columna llamada `id` para ordenar los resultados. Cuando una tabla no tenía esta columna (como `gaceta_parlamentaria` que usa otras columnas), la consulta SQL fallaba.

## ✅ Solución Implementada

### Cambios Realizados

**Archivo:** `includes/mce-shortcodes.php`
**Función:** `mce_buscar_filtrar_ajax()`

**ANTES (v2.1.0):**
```php
$datos = $db_handler->obtener_datos($tabla, '*', $where_conditions_only, 'id', 'ASC', $limite, 0);

if ($datos === false) {
    wp_send_json_error(array('message' => 'Error al obtener datos filtrados'));
    return;
}
```

**AHORA (v2.1.1):**
```php
// Get the first column name for ordering (or leave empty if none available)
$columnas_tabla = $db_handler->obtener_columnas_tabla($tabla);
$orden_columna = !empty($columnas_tabla) ? $columnas_tabla[0] : '';

$datos = $db_handler->obtener_datos($tabla, '*', $where_conditions_only, $orden_columna, 'ASC', $limite, 0);

if ($datos === false) {
    $last_error = $db_handler->get_last_error();
    error_log('MCE buscar_filtrar_ajax Error: ' . $last_error);
    wp_send_json_error(array('message' => 'Error al obtener datos filtrados: ' . $last_error));
    return;
}
```

### Mejoras Implementadas

1. **Ordenamiento Dinámico:**
   - Ya no asume que existe una columna `id`
   - Obtiene dinámicamente la primera columna disponible de la tabla
   - Si no hay columnas, usa string vacío (sin ordenamiento)

2. **Mensajes de Error Mejorados:**
   - Ahora muestra el error específico de la base de datos
   - Facilita el diagnóstico de problemas
   - Logging mejorado en el error_log de PHP

3. **Mayor Compatibilidad:**
   - Funciona con cualquier estructura de tabla
   - No requiere columnas específicas
   - Más robusto y flexible

## 📊 Impacto

### Tablas Afectadas
- ✅ `gaceta_parlamentaria` - Ahora funciona correctamente
- ✅ Cualquier tabla sin columna `id`
- ✅ Todas las tablas con estructuras personalizadas

### Funcionalidad Restaurada
- ✅ Filtros desplegables funcionan correctamente
- ✅ Búsqueda universal sigue funcionando
- ✅ Paginación no afectada
- ✅ Ordenamiento adaptativo

## 🔧 Detalles Técnicos

### Flujo de Corrección

1. **Detección de Columnas:**
   ```php
   $columnas_tabla = $db_handler->obtener_columnas_tabla($tabla);
   ```
   - Obtiene todas las columnas disponibles de la tabla

2. **Selección de Columna de Ordenamiento:**
   ```php
   $orden_columna = !empty($columnas_tabla) ? $columnas_tabla[0] : '';
   ```
   - Usa la primera columna si existe
   - String vacío si no hay columnas (caso extremo)

3. **Consulta con Ordenamiento Dinámico:**
   ```php
   $datos = $db_handler->obtener_datos($tabla, '*', $where_conditions_only, $orden_columna, 'ASC', $limite, 0);
   ```
   - Pasa la columna dinámica en lugar de 'id' hardcodeado

4. **Manejo de Errores Mejorado:**
   ```php
   $last_error = $db_handler->get_last_error();
   error_log('MCE buscar_filtrar_ajax Error: ' . $last_error);
   wp_send_json_error(array('message' => 'Error al obtener datos filtrados: ' . $last_error));
   ```
   - Captura el error específico
   - Lo registra en el log
   - Lo devuelve al frontend para mejor diagnóstico

## 🚀 Despliegue

### Versión
- **v2.1.1** - Hotfix

### Archivos Modificados
1. `mi-conexion-externa.php` - Versión actualizada
2. `includes/mce-shortcodes.php` - Corrección del error
3. `CHANGELOG.md` - Documentación del cambio

### GitHub
- ✅ Commit: `5cdc20d`
- ✅ Tag: `v2.1.1`
- ✅ Push completado
- ✅ Release disponible

## 📝 Notas para el Usuario

### ¿Necesito Hacer Algo?
**No.** La actualización es automática y no requiere ninguna acción.

### ¿Afecta Mis Shortcodes?
**No.** Todos los shortcodes existentes siguen funcionando exactamente igual.

### ¿Qué Mejora Veo?
- Los filtros ahora funcionan correctamente en todas las tablas
- Mensajes de error más claros si algo falla
- Mayor estabilidad general del sistema

## 🔍 Verificación

### Cómo Probar
1. Ve a una página con el shortcode `[mce_mostrar_tabla]`
2. Selecciona un valor en cualquier filtro desplegable
3. Haz clic en "Buscar"
4. Verifica que los resultados se filtren correctamente

### Resultado Esperado
- ✅ Los datos se filtran según el criterio seleccionado
- ✅ No aparece mensaje de error
- ✅ Los resultados se muestran en formato de tarjetas
- ✅ La paginación funciona correctamente

## 📚 Referencias

- **Issue:** Error en filtros AJAX
- **Versión Anterior:** v2.1.0
- **Versión Actual:** v2.1.1
- **Tipo:** Hotfix (Patch)
- **Prioridad:** Alta
- **Estado:** ✅ Resuelto

---

**Fecha:** 9 de diciembre de 2025
**Autor:** CoreAura Development Team
**Tipo de Actualización:** Hotfix (Patch Release)
