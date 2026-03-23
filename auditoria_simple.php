<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Auditoría Simple - Debug</h1>";

// Verificar sesión
if (!isLoggedIn()) {
    echo "❌ No hay sesión activa<br>";
    echo "<a href='index.php'>Ir al Login</a>";
    exit;
}

if (!isAdmin()) {
    echo "❌ No tienes permisos de administrador<br>";
    echo "<a href='dashboard.php'>Ir al Dashboard</a>";
    exit;
}

echo "✅ Sesión válida - Usuario: " . $_SESSION['username'] . "<br>";

try {
    echo "<h2>Conectando a la base de datos...</h2>";
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Conexión exitosa<br>";
    
    echo "<h2>Verificando tabla auditoria...</h2>";
    $stmt = $db->prepare("SHOW TABLES LIKE 'auditoria'");
    $stmt->execute();
    $tabla_existe = $stmt->fetch();
    
    if ($tabla_existe) {
        echo "✅ Tabla auditoria existe<br>";
        
        // Contar registros
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM auditoria");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Total de registros: $total<br>";
        
        if ($total > 0) {
            echo "<h2>Últimos 5 registros:</h2>";
            $stmt = $db->prepare("SELECT * FROM auditoria ORDER BY fecha DESC LIMIT 5");
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Usuario ID</th><th>Tipo Acción</th><th>Módulo</th><th>Descripción</th><th>Fecha</th></tr>";
            foreach ($registros as $reg) {
                echo "<tr>";
                echo "<td>{$reg['id']}</td>";
                echo "<td>{$reg['usuario_id']}</td>";
                echo "<td>{$reg['tipo_accion']}</td>";
                echo "<td>{$reg['modulo']}</td>";
                echo "<td>{$reg['descripcion']}</td>";
                echo "<td>{$reg['fecha']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No hay registros de auditoría</p>";
            echo "<a href='insertar_auditoria_ejemplo.php'>Insertar datos de ejemplo</a><br>";
        }
        
    } else {
        echo "❌ Tabla auditoria NO existe<br>";
        echo "<a href='setup_database.php'>Configurar base de datos</a><br>";
    }
    
    echo "<h2>Verificando tabla usuarios...</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de usuarios: $total_usuarios<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<a href='test_auditoria.php'>Test Completo</a> | ";
echo "<a href='auditoria.php'>Auditoría Completa</a> | ";
echo "<a href='dashboard.php'>Dashboard</a>";
?>
