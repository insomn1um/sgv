<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificación y Corrección de Estructura de Base de Datos</h1>";

try {
    require_once 'config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Conexión a base de datos establecida<br>";
    
    echo "<h2>1. Verificando tabla usuarios...</h2>";
    
    // Verificar estructura actual
    $stmt = $db->prepare("DESCRIBE usuarios");
    $stmt->execute();
    $estructura_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estructura actual de la tabla usuarios:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($estructura_usuarios as $campo) {
        echo "<tr>";
        echo "<td>{$campo['Field']}</td>";
        echo "<td>{$campo['Type']}</td>";
        echo "<td>{$campo['Null']}</td>";
        echo "<td>{$campo['Key']}</td>";
        echo "<td>{$campo['Default']}</td>";
        echo "<td>{$campo['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si existe la columna 'rol'
    $tiene_rol = false;
    $tiene_perfil = false;
    $tiene_activo = false;
    
    foreach ($estructura_usuarios as $campo) {
        if ($campo['Field'] == 'rol') $tiene_rol = true;
        if ($campo['Field'] == 'perfil') $tiene_perfil = true;
        if ($campo['Field'] == 'activo') $tiene_activo = true;
    }
    
    echo "<h3>Estado de columnas importantes:</h3>";
    echo "Columna 'rol': " . ($tiene_rol ? "✅ Existe" : "❌ No existe") . "<br>";
    echo "Columna 'perfil': " . ($tiene_perfil ? "✅ Existe" : "❌ No existe") . "<br>";
    echo "Columna 'activo': " . ($tiene_activo ? "✅ Existe" : "❌ No existe") . "<br>";
    
    // Corregir estructura si es necesario
    if (!$tiene_rol && $tiene_perfil) {
        echo "<h3>Corrigiendo estructura...</h3>";
        echo "Renombrando columna 'perfil' a 'rol'...<br>";
        
        try {
            $sql = "ALTER TABLE usuarios CHANGE perfil rol ENUM('usuario', 'supervisor', 'administrador') DEFAULT 'usuario'";
            $db->exec($sql);
            echo "✅ Columna 'perfil' renombrada a 'rol' exitosamente<br>";
        } catch (PDOException $e) {
            echo "❌ Error al renombrar columna: " . $e->getMessage() . "<br>";
        }
    }
    
    if (!$tiene_activo) {
        echo "<h3>Agregando columna 'activo'...</h3>";
        echo "Agregando columna 'activo' para compatibilidad...<br>";
        
        try {
            $sql = "ALTER TABLE usuarios ADD COLUMN activo BOOLEAN DEFAULT TRUE AFTER rol";
            $db->exec($sql);
            echo "✅ Columna 'activo' agregada exitosamente<br>";
        } catch (PDOException $e) {
            echo "❌ Error al agregar columna 'activo': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>2. Verificando tabla auditoria...</h2>";
    
    // Verificar si existe la tabla auditoria
    $stmt = $db->prepare("SHOW TABLES LIKE 'auditoria'");
    $stmt->execute();
    $tabla_auditoria_existe = $stmt->fetch();
    
    if ($tabla_auditoria_existe) {
        echo "✅ Tabla auditoria existe<br>";
        
        // Verificar estructura
        $stmt = $db->prepare("DESCRIBE auditoria");
        $stmt->execute();
        $estructura_auditoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Estructura de la tabla auditoria:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($estructura_auditoria as $campo) {
            echo "<tr>";
            echo "<td>{$campo['Field']}</td>";
            echo "<td>{$campo['Type']}</td>";
            echo "<td>{$campo['Null']}</td>";
            echo "<td>{$campo['Key']}</td>";
            echo "<td>{$campo['Default']}</td>";
            echo "<td>{$campo['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ Tabla auditoria no existe<br>";
        echo "<h3>Creando tabla auditoria...</h3>";
        
        try {
            $sql = "CREATE TABLE auditoria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                tipo_accion ENUM('login', 'logout', 'create', 'update', 'delete') NOT NULL,
                modulo VARCHAR(50) NOT NULL,
                descripcion TEXT NOT NULL,
                datos_adicionales JSON,
                ip VARCHAR(45),
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
            )";
            $db->exec($sql);
            echo "✅ Tabla auditoria creada exitosamente<br>";
        } catch (PDOException $e) {
            echo "❌ Error al crear tabla auditoria: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>3. Verificación final...</h2>";
    
    // Verificar usuarios
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de usuarios: $total_usuarios<br>";
    
    // Verificar auditoria
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM auditoria");
    $stmt->execute();
    $total_auditoria = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de registros de auditoría: $total_auditoria<br>";
    
    echo "<h3>✅ Verificación completada</h3>";
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<a href='test_auditoria.php'>Test de Auditoría</a> | ";
echo "<a href='auditoria.php'>Auditoría</a> | ";
echo "<a href='dashboard.php'>Dashboard</a>";
?>
