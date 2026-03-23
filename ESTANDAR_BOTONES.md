# Estándar de Botones - SGV

## Archivo CSS
Todos los estilos de botones están centralizados en `includes/buttons.css`

## Clases de Botones

### Botones Primarios (btn-primary)
**Uso**: Acciones principales (Guardar, Crear, Registrar, Confirmar)
**Estilo**: Gradiente azul (#3b82f6 a #1d4ed8)
**Ejemplo**:
```html
<button type="submit" class="btn btn-primary">
    <i class="fas fa-save"></i> Guardar
</button>
```

### Botones Secundarios (btn-secondary)
**Uso**: Cancelar, Volver, Acciones secundarias
**Estilo**: Gris (#6c757d)
**Ejemplo**:
```html
<a href="dashboard.php" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Volver
</a>
```

### Botones de Éxito (btn-success)
**Uso**: Confirmar, Aprobar, Activar
**Estilo**: Gradiente verde (#10b981 a #059669)
**Ejemplo**:
```html
<button type="button" class="btn btn-success">
    <i class="fas fa-check"></i> Confirmar
</button>
```

### Botones de Advertencia (btn-warning)
**Uso**: Editar, Modificar, Cambiar estado
**Estilo**: Gradiente amarillo/naranja (#f59e0b a #d97706)
**Ejemplo**:
```html
<a href="editar.php?id=1" class="btn btn-warning">
    <i class="fas fa-edit"></i> Editar
</a>
```

### Botones de Peligro (btn-danger)
**Uso**: Eliminar, Desactivar, Rechazar
**Estilo**: Gradiente rojo (#ef4444 a #dc2626)
**Ejemplo**:
```html
<button type="button" class="btn btn-danger" onclick="eliminar()">
    <i class="fas fa-trash"></i> Eliminar
</button>
```

### Botones de Información (btn-info)
**Uso**: Ver detalles, Información adicional
**Estilo**: Gradiente cian (#06b6d4 a #0891b2)
**Ejemplo**:
```html
<a href="ver.php?id=1" class="btn btn-info">
    <i class="fas fa-eye"></i> Ver Detalles
</a>
```

## Variantes Outline

### btn-outline-primary
**Uso**: Acciones primarias con estilo outline
**Ejemplo**:
```html
<button type="button" class="btn btn-outline-primary">
    <i class="fas fa-plus"></i> Agregar
</button>
```

### btn-outline-secondary
**Uso**: Acciones secundarias con estilo outline
**Ejemplo**:
```html
<a href="lista.php" class="btn btn-outline-secondary">
    <i class="fas fa-list"></i> Listar
</a>
```

### btn-outline-info, btn-outline-warning, btn-outline-danger, btn-outline-success
**Uso**: Variantes outline de los otros colores

## Tamaños

### btn-sm (Pequeño)
**Uso**: Botones en tablas, acciones secundarias
```html
<button class="btn btn-primary btn-sm">
    <i class="fas fa-edit"></i> Editar
</button>
```

### btn-lg (Grande)
**Uso**: Botones principales destacados
```html
<button class="btn btn-primary btn-lg">
    <i class="fas fa-save"></i> Guardar
</button>
```

## Iconos en Botones

### Estándar de Iconos
Todos los botones deben incluir un icono de Font Awesome antes del texto.

**Iconos comunes**:
- `fa-save` - Guardar
- `fa-plus` - Agregar/Nuevo
- `fa-edit` - Editar
- `fa-trash` - Eliminar
- `fa-arrow-left` - Volver
- `fa-times` - Cancelar
- `fa-check` - Confirmar
- `fa-eye` - Ver/Detalles
- `fa-search` - Buscar
- `fa-download` - Descargar
- `fa-print` - Imprimir
- `fa-user-plus` - Nuevo Usuario
- `fa-building` - Empresa
- `fa-users` - Trabajadores
- `fa-chart-bar` - Reportes
- `fa-history` - Auditoría

### Formato
```html
<button class="btn btn-primary">
    <i class="fas fa-[icono]"></i> Texto del Botón
</button>
```

## Estructura HTML

### Botones con Enlaces
```html
<a href="pagina.php" class="btn btn-primary">
    <i class="fas fa-plus"></i> Nuevo
</a>
```

### Botones de Formulario
```html
<button type="submit" class="btn btn-primary">
    <i class="fas fa-save"></i> Guardar
</button>
```

### Botones con JavaScript
```html
<button type="button" class="btn btn-danger" onclick="eliminar()">
    <i class="fas fa-trash"></i> Eliminar
</button>
```

## Espaciado

### Entre Botones
Los botones en grupos deben tener espaciado consistente:
```html
<div class="d-flex gap-2">
    <button class="btn btn-primary">Guardar</button>
    <button class="btn btn-secondary">Cancelar</button>
</div>
```

### En Formularios
```html
<div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
    <a href="lista.php" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar</button>
</div>
```

## Estados

### Deshabilitado
```html
<button class="btn btn-primary" disabled>
    <i class="fas fa-save"></i> Guardar
</button>
```

### Loading
```html
<button class="btn btn-primary" disabled>
    <i class="fas fa-spinner fa-spin"></i> Guardando...
</button>
```

## Responsive

Los botones se adaptan automáticamente en dispositivos móviles:
- Padding reducido en pantallas pequeñas
- Tamaño de fuente ajustado
- Mejor espaciado táctil

## Ejemplos Completos

### Formulario de Creación
```html
<div class="d-flex justify-content-between mt-4">
    <a href="lista.php" class="btn btn-secondary">
        <i class="fas fa-times"></i> Cancelar
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Guardar
    </button>
</div>
```

### Formulario de Edición
```html
<div class="d-flex justify-content-between mt-4">
    <a href="ver.php?id=1" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    <button type="submit" class="btn btn-warning">
        <i class="fas fa-save"></i> Guardar Cambios
    </button>
</div>
```

### Acciones en Tabla
```html
<div class="btn-group">
    <a href="ver.php?id=1" class="btn btn-sm btn-outline-info" title="Ver detalles">
        <i class="fas fa-eye"></i>
    </a>
    <a href="editar.php?id=1" class="btn btn-sm btn-outline-warning" title="Editar">
        <i class="fas fa-edit"></i>
    </a>
    <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminar(1)">
        <i class="fas fa-trash"></i>
    </button>
</div>
```

