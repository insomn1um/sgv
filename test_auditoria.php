<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Auditoría</h1>";

try {
    echo "<h2>1. Conectando a la base de datos...</h2>";
    require_once 'config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Conexión exitosa<br>";
    
    echo "<h2>2. Verificando si la tabla auditoria existe...</h2>";
    $stmt = $db->prepare("SHOW TABLES LIKE 'auditoria'");
    $stmt->execute();
    $tabla_existe = $stmt->fetch();
    
    if ($tabla_existe) {
        echo "✅ Tabla auditoria existe<br>";
        
        echo "<h2>3. Verificando estructura de la tabla auditoria...</h2>";
        $sql_describe = "DESCRIBE auditoria";
        echo "SQL Describe: <code>$sql_describe</code><br>";
        try {
            $stmt = $db->prepare($sql_describe);
            $stmt->execute();
            $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Estructura obtenida exitosamente<br>";
        } catch (PDOException $e) {
            echo "❌ Error al obtener estructura: " . $e->getMessage() . "<br>";
            $estructura = [];
        }
        
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($estructura as $campo) {
            echo "<tr>";
            echo "<td>" . ($campo['Field'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Type'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Null'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Key'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Default'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Extra'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>4. Verificando si hay datos en la tabla...</h2>";
        $sql_count = "SELECT COUNT(*) as total FROM auditoria";
        echo "SQL Count: <code>$sql_count</code><br>";
        try {
            $stmt = $db->prepare($sql_count);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "Total de registros: $total<br>";
        } catch (PDOException $e) {
            echo "❌ Error al contar registros: " . $e->getMessage() . "<br>";
            $total = 0;
        }
        
        if ($total > 0) {
            echo "<h2>5. Mostrando algunos registros de ejemplo...</h2>";
            $sql_select = "SELECT * FROM auditoria ORDER BY fecha DESC LIMIT 5";
            echo "SQL Select: <code>$sql_select</code><br>";
            try {
                $stmt = $db->prepare($sql_select);
                $stmt->execute();
                $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "✅ Consulta ejecutada exitosamente<br>";
            } catch (PDOException $e) {
                echo "❌ Error al consultar registros: " . $e->getMessage() . "<br>";
                $registros = [];
            }
            
            // Mostrar campos disponibles en el primer registro
            if (!empty($registros)) {
                echo "<h3>Campos disponibles en la tabla:</h3>";
                echo "<ul>";
                foreach (array_keys($registros[0]) as $campo) {
                    echo "<li><strong>$campo</strong></li>";
                }
                echo "</ul>";
            }
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Usuario ID</th><th>Tipo Acción</th><th>Módulo</th><th>Descripción</th><th>Fecha</th></tr>";
            foreach ($registros as $reg) {
                echo "<tr>";
                echo "<td>" . ($reg['id'] ?? 'N/A') . "</td>";
                echo "<td>" . ($reg['usuario_id'] ?? 'N/A') . "</td>";
                echo "<td>" . ($reg['tipo_accion'] ?? 'N/A') . "</td>";
                echo "<td>" . ($reg['modulo'] ?? 'N/A') . "</td>";
                echo "<td>" . ($reg['descripcion'] ?? 'N/A') . "</td>";
                echo "<td>" . ($reg['fecha'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ Tabla auditoria NO existe<br>";
        
        echo "<h2>3. Creando tabla auditoria...</h2>";
        $sql = "CREATE TABLE IF NOT EXISTS auditoria (
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
        
        try {
            $db->exec($sql);
            echo "✅ Tabla auditoria creada<br>";
        } catch (PDOException $e) {
            echo "❌ Error al crear tabla auditoria: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>6. Verificando tabla usuarios...</h2>";
    
    // Primero verificar la estructura de la tabla usuarios
    echo "<h3>6.1. Estructura de la tabla usuarios:</h3>";
    $sql_describe_usuarios = "DESCRIBE usuarios";
    echo "SQL Describe Usuarios: <code>$sql_describe_usuarios</code><br>";
    try {
        $stmt = $db->prepare($sql_describe_usuarios);
        $stmt->execute();
        $estructura_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Estructura de usuarios obtenida exitosamente<br>";
        
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($estructura_usuarios as $campo) {
            echo "<tr>";
            echo "<td>" . ($campo['Field'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Type'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Null'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Key'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Default'] ?? 'N/A') . "</td>";
            echo "<td>" . ($campo['Extra'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "❌ Error al obtener estructura de usuarios: " . $e->getMessage() . "<br>";
        $estructura_usuarios = [];
    }
    
    $sql_usuarios_count = "SELECT COUNT(*) as total FROM usuarios";
    echo "SQL Usuarios Count: <code>$sql_usuarios_count</code><br>";
    try {
        $stmt = $db->prepare($sql_usuarios_count);
        $stmt->execute();
        $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Total de usuarios: $total_usuarios<br>";
    } catch (PDOException $e) {
        echo "❌ Error al contar usuarios: " . $e->getMessage() . "<br>";
        $total_usuarios = 0;
    }
    
    if ($total_usuarios > 0) {
        $sql_usuarios_select = "SELECT id, username, nombre, apellido, rol FROM usuarios LIMIT 5";
        echo "SQL Usuarios Select: <code>$sql_usuarios_select</code><br>";
        try {
            $stmt = $db->prepare($sql_usuarios_select);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Usuarios obtenidos exitosamente<br>";
        } catch (PDOException $e) {
            echo "❌ Error al obtener usuarios: " . $e->getMessage() . "<br>";
            $usuarios = [];
        }
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Nombre</th><th>Apellido</th><th>Rol</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>" . ($user['id'] ?? 'N/A') . "</td>";
            echo "<td>" . ($user['username'] ?? 'N/A') . "</td>";
            echo "<td>" . ($user['nombre'] ?? 'N/A') . "</td>";
            echo "<td>" . ($user['apellido'] ?? 'N/A') . "</td>";
            echo "<td>" . ($user['rol'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<a href='auditoria.php'>Ir a Auditoría</a> | ";
echo "<a href='dashboard.php'>Ir al Dashboard</a>";
?>
