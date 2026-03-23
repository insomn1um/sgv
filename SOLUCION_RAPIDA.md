# 🚨 SOLUCIÓN RÁPIDA - Error de columna 'condicion'

## ⚡ Método más rápido: phpMyAdmin (2 minutos)

### Paso 1: Acceder a phpMyAdmin
1. Entra a tu **cPanel** de `digitalcity.cl`
2. Busca **phpMyAdmin** y ábrelo
3. Selecciona la base de datos **`sgv`**

### Paso 2: Ejecutar el SQL
1. Haz clic en la pestaña **"SQL"** (arriba)
2. **Copia TODO** el contenido del archivo `migracion_URGENTE.sql`
3. **Pega** en el cuadro de texto
4. Haz clic en **"Continuar"** o **"Ejecutar"**

### Paso 3: Verificar
Deberías ver un mensaje de éxito. Luego ejecuta la consulta de verificación que está al final del archivo SQL.

### Paso 4: Probar
1. Ve a: `http://sgv.digitalcity.cl/sgv/dashboard.php`
2. **El error debería desaparecer** ✅

---

## 🔄 Método alternativo: Script PHP (si prefieres)

1. **Sube** `migrar_condicion.php` al servidor por FTP
2. **Abre**: `http://sgv.digitalcity.cl/sgv/migrar_condicion.php`
3. El script se ejecutará automáticamente
4. **Elimina** el archivo después por seguridad

---

## 📋 Contenido del SQL (por si no tienes el archivo)

```sql
ALTER TABLE `empresas` 
ADD COLUMN `condicion` enum('aprobada','pendiente','denegada','suspendida') NOT NULL DEFAULT 'pendiente' 
AFTER `email`;

UPDATE `empresas` 
SET `condicion` = CASE 
    WHEN `estado` = 'activa' THEN 'aprobada'
    WHEN `estado` = 'suspendida' THEN 'suspendida'
    WHEN `estado` = 'bloqueada' THEN 'denegada'
    ELSE 'pendiente'
END;

ALTER TABLE `empresas` 
ADD INDEX `idx_condicion` (`condicion`);
```

---

## ✅ Después de ejecutar

El sistema debería funcionar inmediatamente. El error desaparecerá y podrás acceder al dashboard sin problemas.


