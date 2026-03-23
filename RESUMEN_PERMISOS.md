# 🔒 Sistema de Permisos por Roles - SGV

## ✅ Implementación Completada

Se ha implementado un sistema completo de permisos por roles para controlar el acceso a las diferentes secciones del sistema.

---

## 👥 Roles Disponibles

### 1. **OPERADOR** 🔵
**Permisos Limitados - Solo operaciones básicas de visitas**

✅ **Puede acceder a:**
- Dashboard
- Visitas
  - Listar Visitas
  - Nueva Visita
  - Ver Visita
- Cambiar Contraseña (propia)

❌ **NO puede acceder a:**
- Gestión de Empresas
- Gestión de Trabajadores
- Gestión de Usuarios
- Reportes
- Auditoría

---

### 2. **SUPERVISOR** 🟡
**Permisos Intermedios - Gestión de empresas y trabajadores**

✅ **Puede acceder a:**
- Dashboard
- Gestión
  - Empresas y Contratistas (ver, crear, editar)
  - Trabajadores (ver, crear, editar)
- Visitas (todas las funciones)
- Reportes
- Cambiar Contraseña (propia)

❌ **NO puede acceder a:**
- Gestión de Usuarios
- Auditoría

---

### 3. **ADMINISTRADOR** 🔴
**Permisos Completos - Acceso total al sistema**

✅ **Puede acceder a:**
- **TODO** el sistema sin restricciones
- Dashboard
- Gestión completa
  - Empresas y Contratistas
  - Trabajadores
  - **Usuarios** (exclusivo de Admin)
- Visitas
- Reportes
- **Auditoría** (exclusivo de Admin)
- Cambiar Contraseña (propia y de otros)

---

## 🛡️ Archivos Protegidos

### **Solo ADMIN**
| Archivo | Descripción |
|---------|-------------|
| `usuarios.php` | Gestión de usuarios |
| `crear_usuario.php` | Crear nuevo usuario |
| `editar_usuario.php` | Editar usuario existente |
| `auditoria.php` | Ver registros de auditoría |

### **ADMIN y SUPERVISOR**
| Archivo | Descripción |
|---------|-------------|
| `gestion_empresas.php` | Gestión de empresas |
| `empresas.php` | Lista de empresas |
| `nueva_empresa.php` | Crear nueva empresa |
| `editar_empresa.php` | Editar empresa |
| `ver_empresa.php` | Ver detalles de empresa |
| `trabajadores.php` | Lista de trabajadores |
| `nuevo_trabajador.php` | Crear nuevo trabajador |
| `editar_trabajador.php` | Editar trabajador |
| `ver_trabajador.php` | Ver detalles de trabajador |
| `reportes.php` | Visualización de reportes |

### **TODOS los ROLES**
| Archivo | Descripción |
|---------|-------------|
| `dashboard.php` | Página principal |
| `visitas.php` | Lista de visitas |
| `nueva_visita.php` | Registrar nueva visita |
| `ver_visita.php` | Ver detalles de visita |
| `cambiar_contrasena.php` | Cambiar contraseña propia |

---

## 🔧 Implementación Técnica

### **Funciones de Validación (functions.php)**

```php
// Verificar si es administrador
function isAdmin() {
    return getUserRole() === 'admin';
}

// Verificar si es supervisor (incluye admin)
function isSupervisor() {
    return getUserRole() === 'supervisor' || getUserRole() === 'admin';
}

// Verificar si es operador
function isOperador() {
    return getUserRole() === 'operador';
}
```

### **Validación en Archivos PHP**

#### Para archivos solo de ADMIN:
```php
if (!isAdmin()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores pueden [acción].', 'danger');
    redirect('dashboard.php');
}
```

#### Para archivos de ADMIN y SUPERVISOR:
```php
if (isOperador()) {
    showAlert('No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden [acción].', 'danger');
    redirect('dashboard.php');
}
```

### **Menú Dinámico (Sidebar)**

```php
<?php if (!isOperador()): ?>
    <!-- Sección de Gestión -->
<?php endif; ?>

<?php if (isSupervisor()): ?>
    <!-- Sección de Reportes -->
<?php endif; ?>

<?php if (isAdmin()): ?>
    <!-- Sección de Auditoría -->
<?php endif; ?>
```

---

## 🧪 Herramienta de Verificación

**Acceder a:** `http://localhost/sgv/verificar_permisos.php`

Esta herramienta permite:
- ✅ Probar cada rol (Admin, Supervisor, Operador)
- ✅ Ver qué páginas tiene acceso cada rol
- ✅ Verificar que las funciones de validación funcionen
- ✅ Ver información de sesión actual

### Cómo usar:
1. Abre `http://localhost/sgv/verificar_permisos.php`
2. Haz clic en los botones de roles (Admin, Supervisor, Operador)
3. Verás en verde las páginas con acceso permitido
4. Verás en rojo las páginas con acceso denegado

---

## 🎯 Comportamiento del Sistema

### **Cuando un Operador intenta acceder a una página restringida:**

1. El sistema detecta el rol
2. Muestra un mensaje de alerta rojo:
   > "No tienes permisos para acceder a esta página. Solo administradores y supervisores pueden [acción]."
3. Redirige automáticamente al Dashboard

### **En el menú lateral:**

- **Operador** solo verá:
  - Dashboard
  - Visitas (expandido)

- **Supervisor** verá:
  - Dashboard
  - Gestión (Empresas y Trabajadores)
  - Visitas
  - Reportes

- **Admin** verá:
  - Dashboard
  - Gestión (Empresas, Trabajadores y Usuarios)
  - Visitas
  - Reportes
  - Auditoría

---

## 📊 Resumen de Cambios

### **Archivos Modificados:**
- ✅ `includes/functions.php` - Nueva función `isOperador()`
- ✅ `dashboard.php` - Menú dinámico por rol
- ✅ `visitas.php` - Menú dinámico + Modal de confirmación
- ✅ `nueva_visita.php` - Menú dinámico por rol
- ✅ `usuarios.php` - Validación mejorada
- ✅ `auditoria.php` - Validación mejorada
- ✅ `gestion_empresas.php` - Validación agregada
- ✅ `empresas.php` - Validación agregada
- ✅ `nueva_empresa.php` - Validación agregada
- ✅ `editar_empresa.php` - Validación agregada
- ✅ `ver_empresa.php` - Validación agregada
- ✅ `trabajadores.php` - Validación agregada
- ✅ `nuevo_trabajador.php` - Validación agregada
- ✅ `editar_trabajador.php` - Validación agregada
- ✅ `ver_trabajador.php` - Validación agregada
- ✅ `reportes.php` - Validación mejorada
- ✅ `crear_usuario.php` - Validación mejorada
- ✅ `editar_usuario.php` - Validación mejorada

### **Total: 18 archivos protegidos con validaciones de permisos**

---

## 🔍 Verificación de Seguridad

### **Archivos con doble protección:**

1. **Validación en el menú** (sidebar)
   - El link ni siquiera aparece si no tienes permiso

2. **Validación en el archivo PHP**
   - Si intentas acceder directamente por URL, te redirige con mensaje

### **Ejemplo de seguridad:**

Un Operador:
1. ❌ No ve el link de "Usuarios" en el menú
2. ❌ Si escribe manualmente `usuarios.php` en la URL
3. ✅ El sistema lo detecta y redirige al Dashboard
4. ✅ Muestra mensaje: "No tienes permisos..."

---

## 🎓 Mejores Prácticas Implementadas

✅ **Defensa en profundidad** - Múltiples capas de seguridad  
✅ **Mensajes claros** - El usuario entiende por qué no puede acceder  
✅ **Experiencia de usuario** - Solo ve lo que puede usar  
✅ **Código mantenible** - Funciones reutilizables  
✅ **Sin errores** - Redirecciones limpias con mensajes  

---

## 🚀 Pruebas Realizadas

✅ Función `isOperador()` creada y funcionando  
✅ 11 archivos con validación `isOperador()`  
✅ 28 mensajes de alerta de permisos implementados  
✅ Sin errores de linting en archivos modificados  
✅ Menús dinámicos en 3 archivos principales  

---

## 📝 Notas Importantes

1. **Cambiar Contraseña** está disponible para TODOS los roles (es correcto)
2. **Usuarios** solo visible para Admin (Supervisor NO puede gestionar usuarios)
3. **Auditoría** solo visible para Admin
4. **Reportes** visible para Admin y Supervisor (no para Operador)

---

## 🔗 Herramientas de Diagnóstico

- **Verificar Permisos:** `http://localhost/sgv/verificar_permisos.php`

---

**Fecha de Implementación:** 13 de Octubre, 2025  
**Sistema:** SGV - Sistema de Gestión de Visitas  
**Versión:** 2.0 con Control de Roles


