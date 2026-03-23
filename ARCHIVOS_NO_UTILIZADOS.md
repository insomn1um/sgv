# 📋 Análisis de Archivos No Utilizados - SGV

## 🔴 Archivos de Prueba/Test (Pueden eliminarse en producción)

### Archivos de Test
- `test_auditoria.php` - Test de funcionalidad de auditoría
- `test_final.php` - Test final del sistema
- `test_system.php` - Test general del sistema
- `test_sidebar_menu.html` - Test HTML del sidebar (no se usa en producción)

### Archivos de Verificación/Debug
- `check_project.php` - Verificación del proyecto
- `check_table_structure.php` - Verificación de estructura de tablas
- `debug_error.php` - Debug de errores
- `verificar_estructura_bd.php` - Verificación de estructura de BD
- `verificar_permisos.php` - Verificación de permisos

## 🟡 Archivos de Desarrollo/Setup (Ya ejecutados)

### Archivos de Setup
- `setup_database.php` - Setup inicial de la base de datos (ya ejecutado)
- `crear_admin_temp.php` - Creación temporal de admin (ya ejecutado)

### Archivos de Migración SQL (Ya ejecutados)
- `migracion_agregar_campos_contrato_seguridad.sql` - Migración ya ejecutada
- `migracion_agregar_empresa_visitante.sql` - Migración ya ejecutada
- `migracion_agregar_patente_visitas.sql` - Migración ya ejecutada
- `migracion_agregar_tipo_vehiculo.sql` - Migración ya ejecutada

### Archivos de Utilidad (Uso ocasional)
- `limpiar_auditoria.php` - Limpieza de registros de auditoría (uso ocasional)
- `insertar_auditoria_ejemplo.php` - Insertar datos de ejemplo (solo para desarrollo)

## 🟠 Archivos Alternativos/No Usados

### Versiones Alternativas
- `nueva_visita_optimizada.php` - Versión optimizada NO utilizada (se usa `nueva_visita.php`)
- `auditoria_simple.php` - Versión simple de auditoría (se usa `auditoria.php`)

### JavaScript No Cargado
- `js/sidebar-overlay.js` - Script creado pero NO se está cargando en ningún archivo
  - **Nota**: El overlay se maneja con código inline en cada página, este archivo no se usa

## 📚 Archivos de Documentación (Opcionales)

### Documentación
- `CAMBIOS_FINALES.md` - Documentación de cambios
- `RESUMEN_PERMISOS.md` - Resumen de permisos
- `INSTRUCCIONES_TIPO_VEHICULO.md` - Instrucciones de tipo vehículo
- `SIDEBAR_MENU_README.md` - Documentación del sidebar
- `database.sql` - Script SQL de la base de datos (útil para referencia)

## ✅ Archivos que SÍ se Utilizan

### Archivos Principales (NO eliminar)
- `index.php` - Página de login
- `dashboard.php` - Dashboard principal
- `visitas.php` - Gestión de visitas
- `nueva_visita.php` - Crear nueva visita
- `ver_visita.php` - Ver visita
- `trabajadores.php` - Gestión de trabajadores
- `nuevo_trabajador.php` - Crear trabajador
- `editar_trabajador.php` - Editar trabajador
- `ver_trabajador.php` - Ver trabajador
- `gestion_empresas.php` - Gestión de empresas
- `empresas.php` - Lista de empresas
- `nueva_empresa.php` - Crear empresa
- `editar_empresa.php` - Editar empresa
- `ver_empresa.php` - Ver empresa
- `usuarios.php` - Gestión de usuarios
- `crear_usuario.php` - Crear usuario
- `editar_usuario.php` - Editar usuario
- `auditoria.php` - Auditoría principal
- `reportes.php` - Reportes
- `ingreso_qr.php` - Ingreso por QR
- `preregistro.php` - Preregistro (referenciado en index.php)
- `cambiar_contrasena.php` - Cambiar contraseña
- `logout.php` - Cerrar sesión

### Archivos de Configuración (NO eliminar)
- `config/database.php` - Configuración de BD
- `includes/functions.php` - Funciones principales
- `includes/audit_functions.php` - Funciones de auditoría
- `includes/sidebar.css` - Estilos del sidebar

### Clases (NO eliminar)
- `classes/Empresa.php`
- `classes/Trabajador.php`
- `classes/Usuario.php`
- `classes/Visita.php`
- `classes/Visitante.php`

### AJAX (NO eliminar)
- `ajax/cambiar_estado_empresa.php`
- `ajax/editar_condicion_empresa.php`
- `ajax/editar_condicion_visitante.php`
- `ajax/obtener_trabajadores.php`
- `ajax/obtener_trabajadores_por_empresa.php`
- `ajax/registrar_salida.php`
- `ajax/verificar_visitante.php`

### JavaScript (NO eliminar)
- `js/sidebar-menu.js` - Script del sidebar (se usa)

## 📊 Resumen

### Total de archivos analizados: ~60

### Archivos que pueden eliminarse: ~20
- 5 archivos de test
- 5 archivos de verificación/debug
- 4 archivos de migración SQL
- 2 archivos de setup
- 2 archivos alternativos
- 1 archivo JavaScript no usado
- 1 archivo de utilidad

### Archivos de documentación: 5
- Pueden mantenerse para referencia o eliminarse si no se necesitan

## ⚠️ Recomendaciones

1. **Antes de eliminar**: Hacer backup del proyecto completo
2. **Archivos de test**: Eliminar en producción, mantener en desarrollo
3. **Archivos de migración**: Mantener como referencia histórica o eliminar
4. **Documentación**: Mantener si es útil para el equipo
5. **sidebar-overlay.js**: Eliminar o integrar en las páginas que lo necesiten

## 🗑️ Comando para eliminar archivos de test (ejemplo)

```bash
# Eliminar archivos de test
rm test_*.php test_*.html

# Eliminar archivos de verificación
rm check_*.php verificar_*.php debug_error.php

# Eliminar archivos de setup
rm setup_database.php crear_admin_temp.php

# Eliminar archivos de migración (opcional)
rm migracion_*.sql

# Eliminar archivos alternativos
rm nueva_visita_optimizada.php auditoria_simple.php

# Eliminar JavaScript no usado
rm js/sidebar-overlay.js
```

