# Estándar de Textos - SGV

## 1. Botones de Navegación
- **Volver (genérico)**: `<i class="fas fa-arrow-left"></i> Volver`
- **Volver a página específica**: `<i class="fas fa-arrow-left"></i> Volver a [Página]`
  - Ejemplos: "Volver a Trabajadores", "Volver a Visitas", "Volver a Empresas"

## 2. Botones de Acción

**Nota**: Todos los estilos de botones están definidos en `includes/buttons.css`. Ver `ESTANDAR_BOTONES.md` para detalles completos.

### Crear/Agregar
- **Formularios**: "Nuevo [Entidad]" (ej: "Nuevo Trabajador", "Nuevo Usuario", "Nueva Visita")
- **Botones en listas**: `<i class="fas fa-plus"></i> Nuevo [Entidad]`
- **Títulos de formularios**: "Crear [Entidad]"
- **Clase**: `btn btn-primary`

### Guardar
- **Formularios nuevos**: "Guardar" o "Crear [Entidad]"
- **Formularios de edición**: "Guardar Cambios"
- **Icono**: `<i class="fas fa-save"></i>`
- **Clase**: `btn btn-primary` (nuevo) o `btn btn-warning` (edición)

### Editar
- **Títulos**: "Editar [Entidad]"
- **Botones**: `<i class="fas fa-edit"></i> Editar`
- **Clase**: `btn btn-warning` o `btn btn-outline-warning`

### Eliminar
- **Botones**: `<i class="fas fa-trash"></i> Eliminar`
- **Confirmación**: "¿Está seguro de que desea eliminar [entidad]?"
- **Clase**: `btn btn-danger` o `btn btn-outline-danger`

### Cancelar
- **Botones**: `<i class="fas fa-times"></i> Cancelar`
- **Clase**: `btn btn-secondary` o `btn btn-outline-secondary`

## 3. Títulos y Encabezados

### Páginas principales
- **Gestión**: "Gestión de [Entidad]" (ej: "Gestión de Trabajadores", "Gestión de Usuarios")
- **Listas**: "[Entidad]" (ej: "Visitas", "Empresas")

### Formularios
- **Crear**: "Crear [Entidad]"
- **Editar**: "Editar [Entidad]"
- **Detalles**: "Detalles de [Entidad]" o "Ver [Entidad]"

## 4. Mensajes de Alerta

### Éxito
- "[Acción] exitosamente" (ej: "Usuario creado exitosamente", "Visita registrada exitosamente")

### Error
- "Error al [acción]" (ej: "Error al crear el usuario", "Error al registrar la visita")

### Advertencia
- "Por favor [acción]" (ej: "Por favor complete todos los campos", "Por favor seleccione una opción")

## 5. Labels de Formularios
- **Capitalización**: Primera letra mayúscula
- **Campos requeridos**: Agregar asterisco (*) o texto "(requerido)"
- **Ejemplos**: "Nombre", "Apellido", "Email", "Teléfono"

## 6. Placeholders
- **Formato**: Primera letra mayúscula, resto minúsculas
- **Ejemplos**: "Ingrese el nombre", "Seleccione una opción", "Ingrese el código QR"

## 7. Textos del Sidebar
- **Secciones**: Todo en mayúsculas (ej: "GESTIÓN", "VISITAS")
- **Enlaces**: Capitalización normal (ej: "Dashboard", "Listar Visitas", "Nueva Visita")

## 8. Textos del Navbar
- **Título**: "SGV"
- **Subtítulo**: "Sistema de Gestión de Visitas"

## 9. Mensajes de Confirmación
- **Formato**: "¿Está seguro de que desea [acción]?"
- **Ejemplos**: 
  - "¿Está seguro de que desea eliminar esta empresa?"
  - "¿Confirmar salida del visitante?"

## 10. Estados y Badges
- **Activo**: "Activo" (badge verde)
- **Inactivo**: "Inactivo" (badge gris)
- **Pendiente**: "Pendiente" (badge amarillo)
- **Finalizado**: "Finalizado" (badge azul)

