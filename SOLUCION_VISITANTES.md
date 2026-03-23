# 🚨 SOLUCIÓN RÁPIDA - Error tabla 'visitantes'

## ⚡ Método más rápido: phpMyAdmin (2 minutos)

### Paso 1: Acceder a phpMyAdmin
1. Entra a tu **cPanel** de `digitalcity.cl`
2. Busca **phpMyAdmin** y ábrelo
3. Selecciona la base de datos **`digital2_sgv`** (o `sgv` según tu configuración)

### Paso 2: Ejecutar el SQL
1. Haz clic en la pestaña **"SQL"** (arriba)
2. **Copia TODO** el contenido del archivo `migracion_crear_visitantes.sql`
3. **Pega** en el cuadro de texto
4. Haz clic en **"Continuar"** o **"Ejecutar"**

### Paso 3: Verificar
Deberías ver mensajes de éxito. Luego ejecuta la consulta de verificación que está al final del archivo SQL.

### Paso 4: Probar
1. Ve a: `http://sgv.digitalcity.cl/sgv/dashboard.php`
2. **El error debería desaparecer** ✅

---

## 🔄 Método alternativo: Script PHP (si prefieres)

1. **Sube** `migrar_visitantes.php` al servidor por FTP
2. **Abre**: `http://sgv.digitalcity.cl/sgv/migrar_visitantes.php`
3. El script se ejecutará automáticamente
4. **Elimina** el archivo después por seguridad

---

## 📋 Contenido del SQL (por si no tienes el archivo)

```sql
-- Crear tabla visitantes
CREATE TABLE IF NOT EXISTS `visitantes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `apellido` varchar(100) NOT NULL,
    `tipo_identificacion` enum('rut','pasaporte','otro') NOT NULL DEFAULT 'rut',
    `numero_identificacion` varchar(20) NOT NULL,
    `numero_contacto` varchar(20) DEFAULT NULL,
    `telefono` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `empresa_representa` varchar(200) DEFAULT NULL,
    `a_quien_visita` varchar(200) DEFAULT NULL,
    `motivo_visita` text DEFAULT NULL,
    `patente_vehiculo` varchar(20) DEFAULT NULL,
    `foto_vehiculo` varchar(255) DEFAULT NULL,
    `condicion` enum('permitida','pendiente','denegada') NOT NULL DEFAULT 'pendiente',
    `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `registrado_por` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_visitantes_usuario` (`registrado_por`),
    KEY `idx_identificacion` (`tipo_identificacion`, `numero_identificacion`),
    KEY `idx_condicion` (`condicion`),
    KEY `idx_fecha_registro` (`fecha_registro`),
    CONSTRAINT `fk_visitantes_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla codigos_qr
CREATE TABLE IF NOT EXISTS `codigos_qr` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `codigo` varchar(100) NOT NULL UNIQUE,
    `visitante_id` int(11) DEFAULT NULL,
    `usado` tinyint(1) NOT NULL DEFAULT 0,
    `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_uso` timestamp NULL DEFAULT NULL,
    `creado_por` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_codigo` (`codigo`),
    KEY `fk_codigos_qr_visitante` (`visitante_id`),
    KEY `fk_codigos_qr_usuario` (`creado_por`),
    KEY `idx_usado` (`usado`),
    CONSTRAINT `fk_codigos_qr_visitante` FOREIGN KEY (`visitante_id`) REFERENCES `visitantes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_codigos_qr_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campo visitante_id a visitas (opcional, para compatibilidad)
ALTER TABLE `visitas` 
ADD COLUMN IF NOT EXISTS `visitante_id` int(11) DEFAULT NULL AFTER `trabajador_id`;
```

---

## ✅ Después de ejecutar

El sistema debería funcionar inmediatamente. El error desaparecerá y podrás acceder a todas las funcionalidades relacionadas con visitantes.

---

## 📝 Nota importante

El sistema tiene dos modelos:
- **Trabajadores**: Visitantes que pertenecen a empresas registradas (usa `trabajador_id` en `visitas`)
- **Visitantes**: Visitantes externos no asociados a empresas (usa `visitante_id` en `visitas`)

Ambos pueden coexistir en el sistema.

