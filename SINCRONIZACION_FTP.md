# 📤 Guía de Sincronización FTP con cPanel

Esta guía explica cómo sincronizar tu proyecto local con el servidor cPanel usando FTP.

## 🔧 Configuración Actual

El proyecto está configurado para sincronizar con:
- **Host**: `ftp.digitalcity.cl`
- **Protocolo**: FTP (puerto 21)
- **Usuario**: `digital2`
- **Ruta remota**: `/sgv`

## 📋 Opciones de Sincronización

### Opción 1: Extensión SFTP/FTP para VS Code (Recomendada)

#### Instalación:
1. Abre VS Code
2. Ve a Extensiones (Cmd+Shift+X en Mac, Ctrl+Shift+X en Windows)
3. Busca e instala: **"SFTP"** por Natizyskunk

#### Uso:

**Subir archivo individual:**
- Clic derecho en el archivo → `Upload File`

**Subir carpeta:**
- Clic derecho en la carpeta → `Upload Folder`

**Descargar archivo:**
- Clic derecho en el archivo remoto → `Download File`

**Sincronizar carpeta:**
- Clic derecho en la carpeta → `Sync Local -> Remote` o `Sync Remote -> Local`

**Comandos rápidos:**
- `Cmd+Shift+P` (Mac) o `Ctrl+Shift+P` (Windows)
- Escribe: `SFTP: Upload` o `SFTP: Download`

#### Configuración automática:
El archivo `.vscode/sftp.json` ya está configurado. Si necesitas modificarlo:
- Cambia `uploadOnSave: true` para subir automáticamente al guardar (no recomendado para producción)

### Opción 2: Script de Sincronización con rsync (Mac/Linux)

Si tienes acceso SSH al servidor, puedes usar rsync:

```bash
#!/bin/bash
# sync_ftp.sh

# Configuración
HOST="ftp.digitalcity.cl"
USER="digital2"
REMOTE_PATH="/sgv"
LOCAL_PATH="/Applications/XAMPP/xamppfiles/htdocs/sgv"

# Sincronizar (subir archivos)
rsync -avz --exclude '.git' --exclude '.vscode' --exclude '*.md' \
  "$LOCAL_PATH/" "$USER@$HOST:$REMOTE_PATH/"
```

### Opción 3: Cliente FTP Gráfico

#### FileZilla (Gratis - Mac/Windows/Linux)
1. Descarga: https://filezilla-project.org/
2. Configuración:
   - **Host**: `ftp.digitalcity.cl`
   - **Protocolo**: FTP
   - **Puerto**: 21
   - **Usuario**: `digital2`
   - **Contraseña**: (la que tienes configurada)
3. Conecta y arrastra archivos entre paneles

#### Cyberduck (Gratis - Mac/Windows)
1. Descarga: https://cyberduck.io/
2. Similar configuración a FileZilla

### Opción 4: Sincronización Manual con Terminal

```bash
# Usando lftp (instalar primero: brew install lftp en Mac)
lftp -u digital2,tu_password ftp.digitalcity.cl
cd /sgv
mirror -R --exclude-glob="*.md" --exclude-glob=".git/*" --exclude-glob=".vscode/*" /Applications/XAMPP/xamppfiles/htdocs/sgv /sgv
```

## ⚠️ Archivos que NO se deben subir

Los siguientes archivos están configurados para NO subirse:
- `.vscode/` (configuración del editor)
- `.git/` (control de versiones)
- `*.md` (archivos de documentación)
- `*.log` (archivos de log)
- `node_modules/` (si usas Node.js)
- `.DS_Store` (archivos del sistema Mac)

## 🔒 Seguridad

### ⚠️ IMPORTANTE: Credenciales

El archivo `.vscode/sftp.json` contiene credenciales. **NO lo subas al repositorio**.

Ya está incluido en `.gitignore` para evitar subirlo accidentalmente.

### Cambiar contraseña en sftp.json:

1. Abre `.vscode/sftp.json`
2. Actualiza el campo `password`
3. Guarda el archivo

## 📝 Checklist antes de subir

Antes de sincronizar con producción, verifica:

- [ ] Revisar cambios en archivos PHP
- [ ] Verificar que `config/database.php` tenga las credenciales correctas del servidor
- [ ] No subir archivos de prueba o debug
- [ ] Verificar permisos de archivos (chmod 644 para archivos, 755 para directorios)
- [ ] Hacer backup del servidor antes de cambios importantes

## 🚀 Flujo de trabajo recomendado

1. **Desarrollo local**: Trabaja en tu entorno XAMPP
2. **Pruebas**: Verifica que todo funcione localmente
3. **Sincronización**: Sube solo los archivos modificados
4. **Verificación**: Prueba en el servidor de producción

## 🔄 Sincronización selectiva

Para subir solo archivos específicos:

**En VS Code:**
1. Selecciona los archivos que quieres subir
2. Clic derecho → `Upload File` o `Upload Folder`

**En FileZilla:**
1. Selecciona archivos en el panel local
2. Arrástralos al panel remoto

## 📞 Soporte

Si tienes problemas de conexión:
1. Verifica las credenciales FTP en cPanel
2. Verifica que el puerto 21 no esté bloqueado por firewall
3. Prueba con un cliente FTP gráfico primero
4. Contacta al administrador del hosting si persisten los problemas

## 🔐 Alternativa: SFTP (más seguro)

Si tu hosting soporta SFTP (SSH), puedes cambiar la configuración:

```json
{
    "protocol": "sftp",
    "port": 22
}
```

Esto requiere acceso SSH habilitado en cPanel.



