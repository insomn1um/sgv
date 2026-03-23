# 📤 Subir Archivos por FTP - Guía Paso a Paso

## ✅ Opción 1: VS Code SFTP (MÁS FÁCIL - Recomendado)

Ya tienes la extensión configurada en `.vscode/sftp.json`. Solo necesitas:

### Paso 1: Instalar extensión (si no la tienes)
1. Abre VS Code
2. Presiona `Cmd+Shift+X` (Mac) para abrir Extensiones
3. Busca: **"SFTP"** (por Natizyskunk)
4. Haz clic en **Install**

### Paso 2: Subir archivos
**Archivos importantes a subir:**
- `js/sidebar-pin.js` ⭐ NUEVO
- `includes/sidebar.css` (modificado)

**Método rápido:**
1. En VS Code, navega a `js/sidebar-pin.js`
2. **Clic derecho** → `Upload File`
3. Repite con `includes/sidebar.css`
4. Para los archivos PHP, puedes subir carpeta completa o archivo por archivo

**O subir todos los cambios del sidebar:**
1. Selecciona la carpeta `js` (clic derecho)
2. `Upload Folder` (solo subirá los archivos nuevos/modificados)
3. Selecciona la carpeta `includes` → `Upload Folder`

### Paso 3: Verificar
Accede a: `http://sgv.digitalcity.cl/sgv/dashboard.php`
Debes ver el botón de pin junto al título del sidebar.

---

## ✅ Opción 2: FileZilla (Interfaz Gráfica)

### Paso 1: Descargar FileZilla
- Mac: https://filezilla-project.org/download.php?type=client
- O usa: `brew install --cask filezilla` (si tienes Homebrew)

### Paso 2: Conectar
1. Abre FileZilla
2. En la barra superior, ingresa:
   - **Host**: `ftp.digitalcity.cl`
   - **Usuario**: `digital2`
   - **Contraseña**: `Dcity2020...,`
   - **Puerto**: `21`
3. Haz clic en **Conexión Rápida**

### Paso 3: Subir Archivos
1. **Panel izquierdo** (local): Navega a `/Applications/XAMPP/xamppfiles/htdocs/sgv`
2. **Panel derecho** (servidor): Navega a `/sgv`
3. **Arrastra** estos archivos al servidor:
   - `js/sidebar-pin.js` → Arrastra a `js/` en el servidor
   - `includes/sidebar.css` → Arrastra a `includes/` en el servidor
   - Archivos PHP modificados según necesites

---

## ✅ Opción 3: Terminal con FTP nativo (sin instalar nada)

Puedo crear un script que use el comando `ftp` nativo de Mac. ¿Quieres que lo cree?

---

## ✅ Opción 4: Instalar lftp y usar script automático

Si prefieres automatización, instala lftp:

```bash
# Instalar lftp
brew install lftp

# Luego ejecutar
cd /Applications/XAMPP/xamppfiles/htdocs/sgv
./sync_sidebar_changes.sh
```

---

## 🎯 ¿Cuál usar?

- **VS Code SFTP**: Si ya tienes VS Code abierto → **Más rápido**
- **FileZilla**: Si prefieres ver los archivos visualmente → **Más visual**
- **Script automático**: Si quieres automatizar → **Más eficiente**

---

## 📋 Lista de Archivos a Subir

**Prioritarios (para que funcione el sidebar pin):**
1. ✅ `js/sidebar-pin.js` (NUEVO)
2. ✅ `includes/sidebar.css` (MODIFICADO)

**Archivos PHP (puedes subirlos todos o uno por uno):**
- dashboard.php
- visitas.php
- trabajadores.php
- ver_trabajador.php
- nueva_visita.php
- usuarios.php
- (y los demás archivos PHP que tengan sidebar)

---

## 🆘 Ayuda Rápida

Si tienes problemas:

1. **Verifica conexión**: Usa FileZilla primero para confirmar que las credenciales funcionan
2. **Verifica permisos**: Los archivos deben tener permisos 644
3. **Limpia cache**: Después de subir, limpia el cache del navegador (Cmd+Shift+R)

