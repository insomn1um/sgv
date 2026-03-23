# 🔧 Instrucciones de Migración - Columna 'condicion'

## Problema
La base de datos en producción no tiene la columna `condicion` en la tabla `empresas`, pero el código la requiere. Esto causa el error:
```
Column not found: 1054 Unknown column 'condicion' in 'field list'
```

## Solución
Necesitas agregar la columna `condicion` a la tabla `empresas` en producción.

---

## 📋 Opción 1: Usar el script PHP (Recomendado - Más fácil)

### Pasos:
1. **Sube el archivo `migrar_condicion.php` al servidor** usando FTP
2. **Accede desde el navegador**: `http://sgv.digitalcity.cl/sgv/migrar_condicion.php`
3. **El script automáticamente**:
   - Verificará si la columna existe
   - La agregará si no existe
   - Migrará los datos de `estado` a `condicion`
   - Mostrará un resumen de la migración
4. **Verifica que todo esté correcto**
5. **⚠️ IMPORTANTE: Elimina el archivo `migrar_condicion.php` después** por seguridad

### Ventajas:
- ✅ Interfaz visual
- ✅ Verificación automática
- ✅ Muestra estadísticas después de la migración
- ✅ Manejo de errores

---

## 📋 Opción 2: Usar phpMyAdmin (Alternativa)

### Pasos:
1. **Accede a phpMyAdmin** en tu cPanel
2. **Selecciona la base de datos** `sgv`
3. **Ve a la pestaña "SQL"**
4. **Copia y pega** el contenido de `migracion_agregar_condicion_simple.sql`
5. **Ejecuta el script**
6. **Verifica** que la columna se haya agregado correctamente

### Contenido del script SQL:
```sql
USE `sgv`;

-- Agregar columna 'condicion'
ALTER TABLE `empresas` 
ADD COLUMN `condicion` enum('aprobada','pendiente','denegada','suspendida') NOT NULL DEFAULT 'pendiente' 
AFTER `email`;

-- Migrar datos de 'estado' a 'condicion'
UPDATE `empresas` 
SET `condicion` = CASE 
    WHEN `estado` = 'activa' THEN 'aprobada'
    WHEN `estado` = 'suspendida' THEN 'suspendida'
    WHEN `estado` = 'bloqueada' THEN 'denegada'
    ELSE 'pendiente'
END;

-- Agregar índice
ALTER TABLE `empresas` 
ADD INDEX `idx_condicion` (`condicion`);
```

---

## 📋 Opción 3: Usar línea de comandos MySQL (Avanzado)

Si tienes acceso SSH al servidor:

```bash
mysql -u digital2 -p sgv < migracion_agregar_condicion_simple.sql
```

---

## 🔍 Verificación después de la migración

Después de ejecutar la migración, verifica:

1. **La columna existe**:
   ```sql
   SHOW COLUMNS FROM empresas LIKE 'condicion';
   ```

2. **Los datos se migraron correctamente**:
   ```sql
   SELECT condicion, COUNT(*) as total 
   FROM empresas 
   GROUP BY condicion;
   ```

3. **El dashboard funciona**: Accede a `dashboard.php` y verifica que no haya errores

---

## 📊 Mapeo de datos

La migración mapea los valores de `estado` a `condicion`:

| Estado anterior | Condición nueva |
|----------------|-----------------|
| `activa`       | `aprobada`      |
| `suspendida`   | `suspendida`    |
| `bloqueada`    | `denegada`      |
| (otros)        | `pendiente`     |

---

## ⚠️ Importante

1. **Haz un backup** de la base de datos antes de ejecutar la migración
2. **Ejecuta la migración en un momento de bajo tráfico** si es posible
3. **Elimina los archivos de migración** después de completar el proceso por seguridad
4. **Verifica** que el sistema funcione correctamente después de la migración

---

## 🆘 Si algo sale mal

Si la migración falla o hay problemas:

1. **Restaura el backup** de la base de datos
2. **Revisa los logs de error** en el servidor
3. **Verifica las credenciales** de la base de datos
4. **Contacta al administrador** si persisten los problemas

---

## ✅ Checklist

- [ ] Backup de la base de datos realizado
- [ ] Script de migración subido al servidor
- [ ] Migración ejecutada exitosamente
- [ ] Columna `condicion` verificada
- [ ] Datos migrados correctamente
- [ ] Dashboard funciona sin errores
- [ ] Archivos de migración eliminados del servidor

---

## 📞 Archivos relacionados

- `migrar_condicion.php` - Script PHP para ejecutar desde el navegador
- `migracion_agregar_condicion_simple.sql` - Script SQL simple
- `migracion_agregar_condicion.sql` - Script SQL con verificaciones avanzadas



