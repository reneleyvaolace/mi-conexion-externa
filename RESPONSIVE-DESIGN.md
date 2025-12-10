# Sistema Responsive del Plugin Mi Conexión Externa

## Resumen

El plugin ahora cuenta con un sistema de diseño **completamente responsive** que adapta automáticamente el número de columnas según el tamaño de la pantalla del dispositivo.

## ¿Cómo Funciona?

### Antes (Sistema Fijo)
```
[mce_mostrar_tabla tabla="gaceta_parlamentaria" columnas="3"]
```
- Mostraba **siempre 3 columnas**, sin importar el tamaño de pantalla
- En móviles, las tarjetas se veían muy pequeñas y difíciles de leer

### Ahora (Sistema Responsive)
```
[mce_mostrar_tabla tabla="gaceta_parlamentaria" columnas="3"]
```
- En **pantallas grandes** (desktop): Muestra hasta 3 columnas
- En **tablets** (768px - 1024px): Se adapta automáticamente (2-3 columnas)
- En **móviles** (< 768px): Muestra **1 columna** para mejor legibilidad

## Parámetro `columnas`

El parámetro `columnas` ahora define el **número MÁXIMO de columnas** en pantallas grandes:

| Valor | Pantalla Grande (>768px) | Tablet | Móvil (<768px) |
|-------|--------------------------|--------|----------------|
| `columnas="1"` | 1 columna | 1 columna | 1 columna |
| `columnas="2"` | Hasta 2 columnas | 1-2 columnas | 1 columna |
| `columnas="3"` | Hasta 3 columnas | 2-3 columnas | 1 columna |
| `columnas="4"` | Hasta 4 columnas | 2-4 columnas | 1 columna |
| `columnas="5"` | Hasta 5 columnas | 3-5 columnas | 1 columna |
| `columnas="6"` | Hasta 6 columnas | 3-6 columnas | 1 columna |

## Ejemplos de Uso

### Ejemplo 1: Gaceta Parlamentaria (3 columnas)
```php
[mce_mostrar_tabla 
    tabla="gaceta_parlamentaria" 
    llave_titulo="concepto" 
    columnas="3" 
    paginacion="30" 
    columnas_mostrar="concepto,fecha,arch1" 
    columnas_filtrar="concepto,fecha,arch1"]
```

**Resultado:**
- **Desktop**: 3 tarjetas por fila
- **Tablet**: 2-3 tarjetas por fila (según espacio disponible)
- **Móvil**: 1 tarjeta por fila (pantalla completa)

### Ejemplo 2: Lista Compacta (2 columnas)
```php
[mce_mostrar_tabla 
    tabla="empleados" 
    columnas="2" 
    paginacion="10"]
```

**Resultado:**
- **Desktop**: 2 tarjetas por fila
- **Tablet**: 1-2 tarjetas por fila
- **Móvil**: 1 tarjeta por fila

### Ejemplo 3: Galería Amplia (4 columnas)
```php
[mce_mostrar_tabla 
    tabla="productos" 
    columnas="4" 
    paginacion="20"]
```

**Resultado:**
- **Desktop**: 4 tarjetas por fila
- **Tablet**: 2-4 tarjetas por fila
- **Móvil**: 1 tarjeta por fila

## Características Técnicas

### Ancho Mínimo de Tarjetas
- Cada tarjeta tiene un ancho mínimo de **280px**
- Esto asegura que el contenido sea legible en todos los dispositivos
- Las tarjetas se expanden para llenar el espacio disponible

### Breakpoints Responsive

| Breakpoint | Tamaño de Pantalla | Comportamiento |
|------------|-------------------|----------------|
| Móvil | < 768px | 1 columna forzada |
| Tablet | 768px - 1024px | Adaptativo (respeta máximo) |
| Desktop | > 1024px | Máximo de columnas especificado |

### CSS Grid Auto-Fit
El sistema utiliza CSS Grid con `auto-fit`:
```css
grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
```

Esto significa:
- **auto-fit**: Ajusta automáticamente el número de columnas
- **minmax(280px, 1fr)**: Mínimo 280px, máximo el espacio disponible
- **1fr**: Distribución equitativa del espacio

## Ventajas del Sistema Responsive

✅ **Mejor experiencia de usuario** en todos los dispositivos
✅ **No requiere cambios** en shortcodes existentes
✅ **Automático**: Se adapta sin configuración adicional
✅ **Optimizado para móviles**: Texto legible y fácil navegación
✅ **Flexible**: Respeta el parámetro `columnas` como máximo
✅ **Rendimiento**: No requiere JavaScript adicional

## Compatibilidad

- ✅ WordPress 5.0+
- ✅ Todos los navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Dispositivos móviles (iOS, Android)
- ✅ Tablets
- ✅ Pantallas grandes y ultra-wide

## Notas Importantes

1. **El parámetro `columnas` sigue siendo opcional**
   - Por defecto: `columnas="3"`
   
2. **Los shortcodes existentes siguen funcionando**
   - No es necesario modificar nada
   - El comportamiento responsive se aplica automáticamente

3. **Personalización adicional**
   - Los estilos se pueden personalizar en `mce-public-style.css`
   - Las clases CSS son: `.mce-grid-max-1` hasta `.mce-grid-max-6`

## Soporte

Para más información o soporte, consulta:
- Documentación del plugin
- Archivo: `includes/mce-shortcodes.php` (línea 192-210)
- Estilos: `public/css/mce-public-style.css` (línea 183-247)
