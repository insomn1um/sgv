# Sistema de Submenús del Sidebar - SGV

## Descripción
Sistema centralizado para manejar los submenús colapsables del sidebar en toda la aplicación SGV.

## Archivos Involucrados

### JavaScript Centralizado
- **`js/sidebar-menu.js`** - Script principal que maneja toda la funcionalidad de submenús

### Archivos PHP Actualizados
Los siguientes archivos ahora incluyen el script centralizado:
- `visitas.php`
- `nueva_visita.php`
- `dashboard.php`
- `gestion_empresas.php`
- `trabajadores.php`
- `usuarios.php`
- `reportes.php`
- `auditoria.php`
- `crear_usuario.php`
- `editar_usuario.php`
- `ver_empresa.php`
- `nueva_empresa.php`
- `editar_empresa.php`

## Funcionalidades

### 1. Rotación de Chevrones
- Los chevrones (flechas) rotan 180° cuando se expande un submenú
- Rotación suave con transición CSS de 0.3s
- Estado inicial correcto según submenús abiertos

### 2. Manejo de Estado
- Previene el comportamiento por defecto de los enlaces de collapse
- Mantiene el estado visual correcto de los submenús
- Inicializa automáticamente el estado de chevrones según submenús abiertos

### 3. Enlaces Activos
- Maneja la clase `active` en los enlaces de submenú
- Remueve `active` de otros enlaces al hacer clic
- Mantiene la navegación visual consistente

## Estructura HTML Requerida

```html
<!-- Sección de submenú -->
<div class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#submenuId">
        <i class="fas fa-icon"></i> Título
        <i class="fas fa-chevron-down ms-auto"></i>
    </a>
    <div class="collapse show" id="submenuId">
        <a class="nav-link submenu" href="pagina.php">
            <i class="fas fa-icon"></i> Enlace
        </a>
    </div>
</div>
```

## CSS Requerido

```css
.sidebar .nav-link.submenu {
    padding-left: 2rem;
    font-size: 0.9rem;
}

.sidebar .nav-link.submenu.active {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar .fa-chevron-down {
    transition: transform 0.3s ease;
}
```

## Implementación

### 1. Incluir el Script
```html
<script src="js/sidebar-menu.js"></script>
```

### 2. Estructura Consistente
- Todos los archivos deben usar la misma estructura HTML
- Mismos IDs para los submenús (`gestionSubmenu`, `visitasSubmenu`)
- Mismas clases CSS

### 3. Estado Activo
- Marcar el enlace correcto con `class="nav-link submenu active"`
- Marcar la sección padre con `class="nav-link active"` si corresponde

## Beneficios

✅ **Consistencia** - Mismo comportamiento en toda la aplicación
✅ **Mantenibilidad** - Un solo archivo para actualizar
✅ **Rendimiento** - Script optimizado y reutilizable
✅ **UX** - Experiencia de usuario fluida y predecible
✅ **Accesibilidad** - Manejo correcto de estados y transiciones

## Troubleshooting

### Problema: Chevrones no rotan
- Verificar que el archivo `js/sidebar-menu.js` está incluido
- Verificar que Bootstrap 5 está cargado
- Verificar que los elementos tienen las clases correctas

### Problema: Submenús no se expanden
- Verificar que los IDs de los submenús son únicos
- Verificar que `data-bs-target` apunta al ID correcto
- Verificar que Bootstrap JavaScript está cargado

### Problema: Estado activo incorrecto
- Verificar que las clases `active` están en los elementos correctos
- Verificar que la estructura HTML es consistente
