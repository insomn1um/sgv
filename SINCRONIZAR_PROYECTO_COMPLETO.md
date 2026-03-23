# 🚀 Sincronizar Proyecto Completo - Cursor a FTP

## ✅ Método 1: Sincronización Completa (RECOMENDADO)

### Paso 1: Verificar Extensión SFTP

1. Abre Cursor
2. Presiona `Cmd+Shift+X` (Mac) para abrir Extensiones
3. Busca: **"SFTP"** (por Natizyskunk)
4. Si NO está instalada, haz clic en **Install**

### Paso 2: Sincronizar Todo el Proyecto

**Opción A: Desde la raíz del proyecto (MÁS RÁPIDO)**

1. En el **Explorador de archivos** de Cursor (panel izquierdo)
2. **Clic derecho** en la carpeta raíz del proyecto (la carpeta `sgv`)
3. Selecciona: **`Sync Local -> Remote`**
4. Espera a que termine (puede tardar varios minutos la primera vez)
5. Verás notificaciones de progreso en la esquina inferior derecha

**Opción B: Desde la Paleta de Comandos**

1. Abre la Paleta de Comandos: `Cmd+Shift+P` (Mac) o `Ctrl+Shift+P` (Windows)
2. Escribe: **`SFTP: Sync Local -> Remote`**
3. Selecciona la opción
4. Espera a que termine la sincronización

**Opción C: Sincronizar desde terminal integrado**

1. Abre la terminal en Cursor: `Ctrl+`` (backtick) o `View` → `Terminal`
2. Ejecuta:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/sgv
   ```

---

## 📋 ¿Qué se Subirá?

### ✅ Archivos que SÍ se suben:
- Todos los archivos PHP
- Todos los archivos CSS y JavaScript
- Todas las imágenes y recursos
- Todas las clases PHP
- Archivos de configuración necesarios

### ❌ Archivos que NO se suben (configurados en `.vscode/sftp.json`):
- `.vscode/` (configuración del editor)
- `.git/` (control de versiones)
- `*.md` (documentación)
- `*.log` (archivos de log)
- `migrar_*.php` (scripts de migración)
- `*.sql` (scripts SQL)

---

## 🔍 Verificar Sincronización

### Método 1: Verificar en el navegador

Accede a: **http://sgv.digitalcity.cl/sgv/dashboard.php**

Si la página carga correctamente, la sincronización fue exitosa.

### Método 2: Listar archivos del servidor

1. `Cmd+Shift+P` → `SFTP: List`
2. Navega a `/sgv` y verifica que tus archivos estén ahí

### Método 3: Ver el log de sincronización

1. `View` → `Output` (o `Cmd+Shift+U`)
2. Selecciona **"SFTP"** en el dropdown
3. Verás los mensajes de subida

---

## ⚡ Método 2: Sincronización Automática (Ya Activa)

Tu proyecto ya tiene configurado el **auto-upload**. Esto significa:

- Cada vez que **guardas** un archivo (`Cmd+S`), se sube automáticamente
- No necesitas hacer nada más

**Para usar esto:**
1. Trabaja normalmente en tus archivos
2. Guarda (`Cmd+S`)
3. Espera la notificación "Uploaded successfully"
4. Listo!

---

## 🎯 Sincronización Selectiva (Solo Archivos Específicos)

### Subir solo un archivo:
1. Abre el archivo en Cursor
2. `Cmd+Shift+P` → `SFTP: Upload Active File`

### Subir solo una carpeta:
1. Clic derecho en la carpeta (ej: `js/`, `includes/`)
2. `Upload Folder`

### Subir solo archivos modificados:
1. `Cmd+Shift+P` → `SFTP: Upload Changed Files`

---

## 🆘 Solución de Problemas

### Problema: No aparece la opción "Sync Local -> Remote"

**Solución:**
1. Verifica que la extensión SFTP esté instalada
2. Reinicia Cursor
3. Verifica que `.vscode/sftp.json` exista y tenga la configuración correcta

### Problema: Error de conexión

**Solución:**
1. Verifica las credenciales en `.vscode/sftp.json`:
   - Host: `ftp.digitalcity.cl`
   - Usuario: `digital2`
   - Puerto: `21`
   - Ruta remota: `/sgv`
2. Prueba conectarte manualmente:
   - `Cmd+Shift+P` → `SFTP: List`
3. Verifica que el puerto 21 no esté bloqueado por firewall

### Problema: La sincronización es muy lenta

**Solución:**
- La primera sincronización puede tardar varios minutos
- Sincronizaciones posteriores son más rápidas (solo sube cambios)
- Si es muy lenta, usa sincronización selectiva (solo archivos modificados)

### Problema: Sube archivos que no quiero

**Solución:**
- Edita `.vscode/sftp.json`
- Agrega los archivos/carpetas a la lista `ignore`

---

## 📊 Estado de la Configuración Actual

Tu configuración está lista:

```json
{
    "host": "ftp.digitalcity.cl",
    "username": "digital2",
    "remotePath": "/sgv",
    "uploadOnSave": true,  ← Auto-subida activada
    "autoUpload": true      ← Detección automática activada
}
```

---

## 🎉 Pasos Rápidos para Sincronizar AHORA

1. ✅ Abre Cursor
2. ✅ Verifica que la extensión SFTP esté instalada (`Cmd+Shift+X`)
3. ✅ En el Explorador, clic derecho en la carpeta `sgv` (raíz del proyecto)
4. ✅ Selecciona: **`Sync Local -> Remote`**
5. ✅ Espera a que termine (verás notificaciones)
6. ✅ Verifica en: **http://sgv.digitalcity.cl/sgv/dashboard.php**

---

## 💡 Consejos

- **Primera sincronización:** Puede tardar 5-10 minutos (muchos archivos)
- **Siguientes sincronizaciones:** Solo sube cambios (rápido)
- **Auto-upload:** Ya está activo, solo guarda archivos y se suben solos
- **Backup:** Considera hacer backup antes de sincronizar cambios importantes

---

## 📞 Comandos Más Usados

| Acción | Comando |
|--------|---------|
| Sincronizar todo | Clic derecho raíz → `Sync Local -> Remote` |
| Subir archivo actual | `Cmd+Shift+P` → `SFTP: Upload Active File` |
| Subir carpeta | Clic derecho carpeta → `Upload Folder` |
| Listar servidor | `Cmd+Shift+P` → `SFTP: List` |
| Ver logs | `View` → `Output` → Seleccionar "SFTP" |

