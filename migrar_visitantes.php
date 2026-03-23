<?php
/**
 * Script de migración para crear tablas 'visitantes' y 'codigos_qr'
 * 
 * INSTRUCCIONES:
 * 1. Accede a este archivo desde el navegador: http://sgv.digitalcity.cl/sgv/migrar_visitantes.php
 * 2. El script verificará si las tablas existen y las creará si es necesario
 * 3. IMPORTANTE: Elimina este archivo después de ejecutarlo por seguridad
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migración - Crear tablas visitantes y codigos_qr</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: green; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; font-weight: bold; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
    </style>
</head>
<body>
    <div class='container'>
    <h1>🔧 Migración: Crear tablas 'visitantes' y 'codigos_qr'</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    $errores = [];
    $exitos = [];
    
    // Verificar si la tabla visitantes existe
    $stmt = $db->query("SELECT COUNT(*) 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'visitantes'");
    $visitantes_existe = $stmt->fetchColumn() > 0;
    
    if ($visitantes_existe) {
        echo "<div class='info'>✅ La tabla 'visitantes' ya existe</div>";
    } else {
        echo "<div class='info'>📝 La tabla 'visitantes' no existe. Creándola...</div>";
        
        // Crear tabla visitantes
        $sql_visitantes = "CREATE TABLE IF NOT EXISTS `visitantes` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $db->exec($sql_visitantes);
            echo "<div class='success'>✅ Tabla 'visitantes' creada exitosamente</div>";
            $exitos[] = "Tabla visitantes creada";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error al crear tabla 'visitantes': " . htmlspecialchars($e->getMessage()) . "</div>";
            $errores[] = "Error al crear visitantes: " . $e->getMessage();
        }
    }
    
    // Verificar si la tabla codigos_qr existe
    $stmt = $db->query("SELECT COUNT(*) 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'codigos_qr'");
    $codigos_qr_existe = $stmt->fetchColumn() > 0;
    
    if ($codigos_qr_existe) {
        echo "<div class='info'>✅ La tabla 'codigos_qr' ya existe</div>";
    } else {
        echo "<div class='info'>📝 La tabla 'codigos_qr' no existe. Creándola...</div>";
        
        // Crear tabla codigos_qr
        $sql_codigos_qr = "CREATE TABLE IF NOT EXISTS `codigos_qr` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $db->exec($sql_codigos_qr);
            echo "<div class='success'>✅ Tabla 'codigos_qr' creada exitosamente</div>";
            $exitos[] = "Tabla codigos_qr creada";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error al crear tabla 'codigos_qr': " . htmlspecialchars($e->getMessage()) . "</div>";
            $errores[] = "Error al crear codigos_qr: " . $e->getMessage();
        }
    }
    
    // Verificar si el campo visitante_id existe en visitas
    $stmt = $db->query("SELECT COUNT(*) 
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'visitas' 
                        AND COLUMN_NAME = 'visitante_id'");
    $visitante_id_existe = $stmt->fetchColumn() > 0;
    
    if ($visitante_id_existe) {
        echo "<div class='info'>✅ El campo 'visitante_id' ya existe en la tabla 'visitas'</div>";
    } else {
        echo "<div class='info'>📝 El campo 'visitante_id' no existe en 'visitas'. Agregándolo...</div>";
        
        try {
            // Agregar columna visitante_id
            $db->exec("ALTER TABLE `visitas` 
                       ADD COLUMN `visitante_id` int(11) DEFAULT NULL AFTER `trabajador_id`");
            
            // Agregar índice
            $db->exec("ALTER TABLE `visitas` 
                       ADD KEY `fk_visitas_visitante` (`visitante_id`)");
            
            // Agregar foreign key (puede fallar si ya existe, pero está bien)
            try {
                $db->exec("ALTER TABLE `visitas` 
                           ADD CONSTRAINT `fk_visitas_visitante` 
                           FOREIGN KEY (`visitante_id`) REFERENCES `visitantes` (`id`) ON DELETE CASCADE");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
                echo "<div class='warning'>ℹ️ La foreign key ya existe o no se pudo crear (esto está bien)</div>";
            }
            
            echo "<div class='success'>✅ Campo 'visitante_id' agregado a 'visitas' exitosamente</div>";
            $exitos[] = "Campo visitante_id agregado a visitas";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Error al agregar campo 'visitante_id': " . htmlspecialchars($e->getMessage()) . "</div>";
            $errores[] = "Error al agregar visitante_id: " . $e->getMessage();
        }
    }
    
    // Verificar resultado
    echo "<h2>📊 Estado actual</h2>";
    
    $stats = [];
    
    // Estadísticas de visitantes
    try {
        $stmt = $db->query("SELECT 
                            COUNT(*) AS total_visitantes,
                            SUM(CASE WHEN condicion = 'permitida' THEN 1 ELSE 0 END) AS visitantes_permitidos,
                            SUM(CASE WHEN condicion = 'pendiente' THEN 1 ELSE 0 END) AS visitantes_pendientes,
                            SUM(CASE WHEN condicion = 'denegada' THEN 1 ELSE 0 END) AS visitantes_denegados
                           FROM `visitantes`");
        $stats['visitantes'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['visitantes'] = ['error' => $e->getMessage()];
    }
    
    // Estadísticas de códigos QR
    try {
        $stmt = $db->query("SELECT 
                            COUNT(*) AS total_codigos,
                            SUM(CASE WHEN usado = 1 THEN 1 ELSE 0 END) AS codigos_usados,
                            SUM(CASE WHEN usado = 0 THEN 1 ELSE 0 END) AS codigos_disponibles
                           FROM `codigos_qr`");
        $stats['codigos_qr'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['codigos_qr'] = ['error' => $e->getMessage()];
    }
    
    echo "<table>
            <tr><th>Métrica</th><th>Valor</th></tr>";
    
    if (isset($stats['visitantes']['total_visitantes'])) {
        echo "<tr><td><strong>Total de visitantes</strong></td><td>{$stats['visitantes']['total_visitantes']}</td></tr>";
        echo "<tr><td>Visitantes permitidos</td><td>{$stats['visitantes']['visitantes_permitidos']}</td></tr>";
        echo "<tr><td>Visitantes pendientes</td><td>{$stats['visitantes']['visitantes_pendientes']}</td></tr>";
        echo "<tr><td>Visitantes denegados</td><td>{$stats['visitantes']['visitantes_denegados']}</td></tr>";
    }
    
    if (isset($stats['codigos_qr']['total_codigos'])) {
        echo "<tr><td><strong>Total de códigos QR</strong></td><td>{$stats['codigos_qr']['total_codigos']}</td></tr>";
        echo "<tr><td>Códigos usados</td><td>{$stats['codigos_qr']['codigos_usados']}</td></tr>";
        echo "<tr><td>Códigos disponibles</td><td>{$stats['codigos_qr']['codigos_disponibles']}</td></tr>";
    }
    
    echo "</table>";
    
    // Resumen final
    if (empty($errores)) {
        echo "<div class='success'>
                <h3>✅ Migración completada exitosamente</h3>
                <p><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (migrar_visitantes.php) por seguridad después de verificar que todo funciona correctamente.</p>
              </div>";
    } else {
        echo "<div class='error'>
                <h3>⚠️ Migración completada con algunos errores</h3>
                <p>Revisa los errores arriba y corrígelos manualmente si es necesario.</p>
              </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>
            <h3>❌ Error durante la migración</h3>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Código:</strong> " . $e->getCode() . "</p>
          </div>";
}

echo "</div></body></html>";
?>

