<?php
/**
 * Script de migración para agregar columna 'condicion' a la tabla empresas
 * 
 * INSTRUCCIONES:
 * 1. Accede a este archivo desde el navegador: http://tu-dominio.com/sgv/migrar_condicion.php
 * 2. El script verificará si la columna existe y la agregará si es necesario
 * 3. Migrará los datos de 'estado' a 'condicion'
 * 4. IMPORTANTE: Elimina este archivo después de ejecutarlo por seguridad
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
    <title>Migración - Agregar columna condicion</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔧 Migración: Agregar columna 'condicion'</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si la columna 'condicion' ya existe
    $stmt = $db->query("SELECT COUNT(*) 
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'empresas' 
                        AND COLUMN_NAME = 'condicion'");
    $col_exists = $stmt->fetchColumn() > 0;
    
    if ($col_exists) {
        echo "<div class='info'>✅ La columna 'condicion' ya existe en la tabla 'empresas'</div>";
    } else {
        echo "<div class='info'>📝 La columna 'condicion' no existe. Agregándola...</div>";
        
        // Agregar columna 'condicion'
        $db->exec("ALTER TABLE `empresas` 
                   ADD COLUMN `condicion` enum('aprobada','pendiente','denegada','suspendida') NOT NULL DEFAULT 'pendiente' 
                   AFTER `email`");
        
        echo "<div class='success'>✅ Columna 'condicion' agregada exitosamente</div>";
        
        // Migrar datos de 'estado' a 'condicion'
        echo "<div class='info'>🔄 Migrando datos de 'estado' a 'condicion'...</div>";
        
        $db->exec("UPDATE `empresas` 
                   SET `condicion` = CASE 
                       WHEN `estado` = 'activa' THEN 'aprobada'
                       WHEN `estado` = 'suspendida' THEN 'suspendida'
                       WHEN `estado` = 'bloqueada' THEN 'denegada'
                       ELSE 'pendiente'
                   END");
        
        echo "<div class='success'>✅ Datos migrados exitosamente</div>";
        
        // Agregar índice
        try {
            $db->exec("ALTER TABLE `empresas` ADD INDEX `idx_condicion` (`condicion`)");
            echo "<div class='success'>✅ Índice 'idx_condicion' agregado</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='info'>ℹ️ El índice 'idx_condicion' ya existe</div>";
            } else {
                throw $e;
            }
        }
    }
    
    // Verificar resultado
    echo "<h2>📊 Estado actual de la tabla empresas</h2>";
    
    $stmt = $db->query("SELECT 
                        COUNT(*) AS total_empresas,
                        SUM(CASE WHEN condicion = 'aprobada' THEN 1 ELSE 0 END) AS empresas_aprobadas,
                        SUM(CASE WHEN condicion = 'pendiente' THEN 1 ELSE 0 END) AS empresas_pendientes,
                        SUM(CASE WHEN condicion = 'denegada' THEN 1 ELSE 0 END) AS empresas_denegadas,
                        SUM(CASE WHEN condicion = 'suspendida' THEN 1 ELSE 0 END) AS empresas_suspendidas
                       FROM `empresas`");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>
            <tr><th>Métrica</th><th>Valor</th></tr>
            <tr><td>Total de empresas</td><td>{$stats['total_empresas']}</td></tr>
            <tr><td>Empresas aprobadas</td><td>{$stats['empresas_aprobadas']}</td></tr>
            <tr><td>Empresas pendientes</td><td>{$stats['empresas_pendientes']}</td></tr>
            <tr><td>Empresas denegadas</td><td>{$stats['empresas_denegadas']}</td></tr>
            <tr><td>Empresas suspendidas</td><td>{$stats['empresas_suspendidas']}</td></tr>
          </table>";
    
    // Verificar estructura de la tabla
    echo "<h2>🔍 Estructura de la tabla empresas</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM `empresas`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
            <tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>
                <td><strong>{$col['Field']}</strong></td>
                <td>{$col['Type']}</td>
                <td>{$col['Null']}</td>
                <td>{$col['Key']}</td>
                <td>{$col['Default']}</td>
              </tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>
            <h3>✅ Migración completada exitosamente</h3>
            <p><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (migrar_condicion.php) por seguridad después de verificar que todo funciona correctamente.</p>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>
            <h3>❌ Error durante la migración</h3>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Código:</strong> " . $e->getCode() . "</p>
          </div>";
}

echo "</body></html>";
?>

