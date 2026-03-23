# 🚀 Sincronizar Cambios - Guía Rápida

## 📦 Archivos a Sincronizar (Cambios del Sidebar Pin)

Los siguientes archivos fueron creados o modificados y necesitan subirse:

### Nuevos Archivos:
- `js/sidebar-pin.js` ⭐ NUEVO

### Archivos Modificados:
- `includes/sidebar.css`
- `dashboard.php`
- `visitas.php`
- `trabajadores.php`
- `ver_trabajador.php`
- `nueva_visita.php`
- `usuarios.php`
- `auditoria.php`
- `crear_usuario.php`
- `editar_empresa.php`
- `editar_usuario.php`
- `empresas.php`
- `gestion_empresas.php`
- `ingreso_qr.php`
- `nueva_empresa.php`
- `nuevo_trabajador.php`
- `reportes.php`
- `ver_empresa.php`
- `ver_visita.php`

---

## ✅ Método 1: VS Code SFTP (Más Fácil)

### Paso 1: Verificar Extensión
1. Abre VS Code
2. Ve a Extensiones (Cmd+Shift+X)
3. Busca "SFTP" y verifica que esté instalada

### Paso 2: Subir Archivos

**Opción A: Subir archivo por archivo**
1. Clic derecho en `js/sidebar-pin.js` → `Upload File`
2. Clic derecho en `includes/sidebar.css` → `Upload File`
3. Repite para cada archivo PHP modificado

**Opción B: Subir carpeta completa (más rápido)**
1. Clic derecho en `js/` → `Upload Folder` (solo subirá sidebar-pin.js)
2. Clic derecho en `includes/` → `Upload Folder` (solo subirá sidebar.css actualizado)

**Opción C: Subir todos los cambios a la vez**
1. Abre la Paleta de Comandos: `Cmd+Shift+P` (Mac) o `Ctrl+Shift+P` (Windows)
2. Escribe: `SFTP: Upload Active File`
3. O usa: `SFTP: Upload Folder` para subir carpetas completas

### Paso 3: Verificar
1. Accede a: `http://sgv.digitalcity.cl/sgv/dashboard.php`
2. Verifica que el botón de pin aparezca junto al título del sidebar
3. Prueba hacer clic para alternar entre fijado/flotante

---

## ✅ Método 2: Script Bash (Terminal - Rápido)

### Si tienes `lftp` instalado:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/sgv
./sync_ftp.sh upload
```

### Si NO tienes `lftp`:

```bash
# Instalar lftp primero (Mac)
brew install lftp

# Luego ejecutar
cd /Applications/XAMPP/xamppfiles/htdocs/sgv
./sync_ftp.sh upload
```

---

## ✅ Método 3: FileZilla (Interfaz Gráfica)

1. **Abre FileZilla**
2. **Conecta** usando:
   - Host: `ftp.digitalcity.cl`
   - Usuario: `digital2`
   - Contraseña: `Dcity2020...,`
   - Puerto: `21`
3. **Navega** a `/sgv` en el servidor remoto
4. **Arrastra** los archivos desde tu carpeta local al servidor:
   - `js/sidebar-pin.js` → `/sgv/js/`
   - `includes/sidebar.css` → `/sgv/includes/`
   - Los archivos PHP modificados a `/sgv/`

---

## ✅ Método 4: Sincronización Selectiva (Solo Archivos del Sidebar)

Si solo quieres subir los archivos del sidebar:

### En VS Code:
1. Selecciona estos archivos en el explorador (Cmd+Click para múltiples):
   - `js/sidebar-pin.js`
   - `includes/sidebar.css`
   - `dashboard.php`
   - `visitas.php`
   - `trabajadores.php`
   - `ver_trabajador.php`
   - `nueva_visita.php`
   - `usuarios.php`
   - (y los demás archivos PHP modificados)
2. Clic derecho → `Upload File`

### En Terminal (lftp):
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/sgv

lftp -u digital2,Dcity2020..., ftp.digitalcity.cl <<EOF
cd /sgv
put js/sidebar-pin.js
put includes/sidebar.css
put dashboard.php
put visitas.php
put trabajadores.php
put ver_trabajador.php
put nueva_visita.php
put usuarios.php
put auditoria.php
put crear_usuario.php
put editar_empresa.php
put editar_usuario.php
put empresas.php
put gestion_empresas.php
put ingreso_qr.php
put nueva_empresa.php
put nuevo_trabajador.php
put reportes.php
put ver_empresa.php
put ver_visita.php
quit
EOF
```

---

## 🔍 Verificación Post-Sincronización

Después de subir los archivos, verifica:

1. **Accede al dashboard**: `http://sgv.digitalcity.cl/sgv/dashboard.php`
2. **Busca el botón de pin**: Debe aparecer junto al título "SGV" en el sidebar
3. **Prueba la funcionalidad**:
   - Haz clic en el pin para alternar entre fijado/flotante
   - Verifica que la preferencia se guarde al recargar la página
   - En modo flotante, acercar el mouse al borde izquierdo debe mostrar el sidebar

---

## ⚠️ Notas Importantes

1. **Permisos**: Los archivos deben tener permisos 644 (archivos) y 755 (directorios)
2. **Cache**: Si no ves los cambios, limpia el cache del navegador (Cmd+Shift+R / Ctrl+Shift+R)
3. **Backup**: Antes de subir, considera hacer un backup de los archivos en el servidor
4. **JavaScript**: Si tienes problemas, verifica que el archivo `js/sidebar-pin.js` esté accesible en:
   `http://sgv.digitalcity.cl/sgv/js/sidebar-pin.js`

---

## 🆘 Si algo sale mal

1. **Verifica permisos**: Los archivos deben ser legibles
2. **Verifica rutas**: Asegúrate de que las rutas en los archivos PHP sean correctas
3. **Revisa consola del navegador**: Abre las herramientas de desarrollador (F12) y revisa errores en la consola
4. **Verifica que los archivos se subieron**: Usa FileZilla o cPanel File Manager para confirmar

---

## 📞 Método Recomendado

**Para esta sincronización, recomiendo:**
1. **VS Code SFTP** (Método 1) - Es el más fácil y visual
2. O **FileZilla** (Método 3) - Si prefieres una interfaz gráfica completa

