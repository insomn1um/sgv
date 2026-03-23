# 🚀 Ejecutar Migración - Pasos Rápidos

## ✅ Paso 1: Subir el archivo al servidor

**Método A: Usando VS Code (si tienes la extensión SFTP)**
1. Clic derecho en `migrar_condicion.php`
2. Selecciona `Upload File`
3. Espera a que termine la subida

**Método B: Usando FileZilla**
1. Abre FileZilla
2. Conecta a `ftp.digitalcity.cl` (puerto 21)
3. Arrastra `migrar_condicion.php` desde tu carpeta local a `/sgv` en el servidor

**Método C: Usando el script bash**
```bash
./sync_ftp.sh upload
# Luego selecciona solo migrar_condicion.php
```

---

## ✅ Paso 2: Ejecutar la migración

1. **Abre tu navegador**
2. **Accede a**: `http://sgv.digitalcity.cl/sgv/migrar_condicion.php`
3. **El script ejecutará automáticamente**:
   - ✅ Verificará si la columna existe
   - ✅ La agregará si no existe
   - ✅ Migrará los datos
   - ✅ Mostrará estadísticas

---

## ✅ Paso 3: Verificar resultados

El script mostrará:
- ✅ Estado de la migración
- ✅ Estadísticas de empresas por condición
- ✅ Estructura completa de la tabla

**Verifica que veas:**
- "✅ Columna 'condicion' agregada exitosamente"
- "✅ Datos migrados exitosamente"
- Tabla con estadísticas

---

## ✅ Paso 4: Probar el sistema

1. Accede a `dashboard.php`
2. Verifica que **NO** aparezca el error de columna
3. Verifica que las estadísticas se muestren correctamente

---

## ⚠️ Paso 5: Eliminar el archivo (IMPORTANTE)

**Por seguridad, elimina el archivo después de verificar:**

**Método A: VS Code**
- Clic derecho en `migrar_condicion.php` (en el servidor)
- `Delete File`

**Método B: FileZilla**
- Selecciona `migrar_condicion.php` en el servidor
- Presiona `Delete`

**Método C: Desde el servidor**
- Accede por FTP y elimina el archivo manualmente

---

## 🆘 Si hay errores

Si ves algún error:
1. **Copia el mensaje de error completo**
2. **Verifica las credenciales** en `config/database.php`
3. **Revisa los permisos** de la base de datos
4. **Haz un backup** antes de intentar de nuevo

---

## 📞 URL directa para ejecutar

```
http://sgv.digitalcity.cl/sgv/migrar_condicion.php
```

**¡Listo!** Solo sigue estos pasos y la migración se ejecutará automáticamente.



