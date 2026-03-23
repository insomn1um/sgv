# 🚀 Sincronización Automática FTP en Cursor

## ✅ Configuración Completada

He configurado tu `.vscode/sftp.json` para que suba archivos automáticamente al guardar.

**Configuración activada:**
- ✅ `uploadOnSave: true` - Sube automáticamente al guardar archivos
- ✅ `autoUpload: true` - Sube cambios detectados automáticamente

---

## 📋 Pasos para Activar en Cursor

### Paso 1: Instalar Extensión SFTP

1. Abre Cursor
2. Presiona `Cmd+Shift+X` (Mac) o `Ctrl+Shift+X` (Windows) para abrir Extensiones
3. Busca: **"SFTP"** (por Natizyskunk)
4. Haz clic en **Install**

### Paso 2: Verificar Configuración

La configuración ya está lista en `.vscode/sftp.json`. Verifica que:

- ✅ Host: `ftp.digitalcity.cl`
- ✅ Usuario: `digital2`
- ✅ Puerto: `21`
- ✅ Ruta remota: `/sgv`
- ✅ `uploadOnSave: true`

### Paso 3: Probar Sincronización Automática

1. **Edita cualquier archivo** (por ejemplo: `dashboard.php`)
2. **Guarda** el archivo (`Cmd+S` / `Ctrl+S`)
3. **Deberías ver** una notificación en la esquina inferior derecha:
   - "Uploading..." → "Uploaded successfully"

---

## 🎯 Comandos Disponibles en Cursor

Abre la Paleta de Comandos (`Cmd+Shift+P` / `Ctrl+Shift+P`) y escribe:

### Comandos de Subida:
- `SFTP: Upload Active File` - Sube el archivo actualmente abierto
- `SFTP: Upload Folder` - Sube la carpeta seleccionada
- `SFTP: Upload Active Folder` - Sube la carpeta del archivo activo
- `SFTP: Upload Changed Files` - Sube solo los archivos modificados

### Comandos de Descarga:
- `SFTP: Download Active File` - Descarga el archivo del servidor
- `SFTP: Download Folder` - Descarga una carpeta del servidor

### Comandos de Sincronización:
- `SFTP: Sync Local -> Remote` - Sincroniza local → servidor
- `SFTP: Sync Remote -> Local` - Sincroniza servidor → local
- `SFTP: List` - Lista archivos del servidor

---

## ⚙️ Configuración Personalizada

### Si quieres DESACTIVAR el auto-upload:

Edita `.vscode/sftp.json` y cambia:
```json
"uploadOnSave": false,
"watcher": {
    "autoUpload": false
}
```

### Si quieres activar solo para archivos específicos:

Crea un archivo `.vscode/sftp.json` en una subcarpeta con:
```json
{
    "uploadOnSave": true,
    "ignore": []
}
```

---

## 📁 Archivos que NO se Suben Automáticamente

Para evitar subir archivos innecesarios, estos están en la lista de `ignore`:

- `.vscode/`, `.git/` (configuración local)
- `*.md` (documentación)
- `*.log` (archivos de log)
- `migrar_*.php` (scripts de migración)
- `*.sql` (scripts SQL)

**Si necesitas subir un archivo ignorado:**
- Usa el comando: `SFTP: Upload Active File` manualmente

---

## 🔍 Verificar Subida Exitosa

### Método 1: Notificación en Cursor
Cuando guardes un archivo, verás una notificación:
- ✅ "Uploaded successfully" = Subido correctamente
- ❌ "Upload failed" = Error (revisa la consola)

### Método 2: Verificar en el Servidor
1. Abre la Paleta de Comandos
2. Escribe: `SFTP: List`
3. Navega a `/sgv` y verifica que tus archivos estén ahí

### Método 3: Probar en el Navegador
Accede a: `http://sgv.digitalcity.cl/sgv/[tu-archivo]`
Ejemplo: `http://sgv.digitalcity.cl/sgv/dashboard.php`

---

## 🆘 Solución de Problemas

### Problema: No se sube automáticamente

**Solución 1:** Verifica que la extensión SFTP esté instalada
- `Cmd+Shift+P` → `Extensions: Show Installed Extensions`
- Busca "SFTP" y verifica que esté instalada

**Solución 2:** Verifica la configuración
- Abre `.vscode/sftp.json`
- Confirma que `uploadOnSave: true`

**Solución 3:** Revisa la consola de salida
- `View` → `Output`
- Selecciona "SFTP" en el dropdown
- Verás los mensajes de subida

### Problema: Error de conexión

**Solución:**
1. Verifica las credenciales en `.vscode/sftp.json`
2. Prueba conectarte manualmente:
   - `SFTP: List` en la paleta de comandos
3. Verifica que el puerto 21 no esté bloqueado por firewall

### Problema: Sube archivos que no quiero

**Solución:**
- Agrega los archivos/carpetas a la lista `ignore` en `.vscode/sftp.json`

---

## 📝 Ejemplo de Uso

### Escenario: Editar dashboard.php

1. **Abre** `dashboard.php` en Cursor
2. **Haz cambios** (ejemplo: agregar un comentario)
3. **Guarda** (`Cmd+S`)
4. **Espera** la notificación "Uploaded successfully" (2-3 segundos)
5. **Verifica** en: `http://sgv.digitalcity.cl/sgv/dashboard.php`

---

## ⚡ Trucos Rápidos

### Subir solo los cambios del sidebar ahora:

1. Abre la Paleta de Comandos (`Cmd+Shift+P`)
2. Escribe: `SFTP: Upload Changed Files`
3. Selecciona los archivos que quieres subir

### Subir un archivo específico rápidamente:

1. Abre el archivo (ej: `js/sidebar-pin.js`)
2. `Cmd+Shift+P` → `SFTP: Upload Active File`

### Ver el estado de la conexión:

1. `Cmd+Shift+P` → `SFTP: List`
2. Esto mostrará los archivos del servidor y confirmará la conexión

---

## 🎉 ¡Listo!

Ahora cada vez que guardes un archivo en Cursor, se subirá automáticamente a:
**http://sgv.digitalcity.cl/sgv/**

**Prueba ahora:**
1. Edita cualquier archivo
2. Guarda (`Cmd+S`)
3. Revisa la notificación de subida
4. Verifica en el navegador que los cambios estén ahí

---

## 📞 Comandos Más Usados

| Acción | Comando |
|--------|---------|
| Subir archivo actual | `Cmd+Shift+P` → `SFTP: Upload Active File` |
| Subir carpeta | `Cmd+Shift+P` → `SFTP: Upload Folder` |
| Listar servidor | `Cmd+Shift+P` → `SFTP: List` |
| Sincronizar local→remoto | `Cmd+Shift+P` → `SFTP: Sync Local -> Remote` |

